<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\SalidaStock;
use App\Models\Producto;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SalidaStockController extends Controller
{
    use HasRolePermissionChecks;

    /**
     * Mostrar lista de salidas de stock
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = SalidaStock::with(['producto.categoria', 'producto.unidadMedida']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo_salida', 'like', "%{$search}%")
                  ->orWhere('motivo', 'like', "%{$search}%")
                  ->orWhereHas('producto', function($q) use ($search) {
                      $q->where('nombre', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('producto')) {
            $query->where('codigo_producto', $request->input('producto'));
        }

        $salidas = $query->orderBy('fecha', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($salida) {
                return [
                    'codigo_salida' => $salida->codigo_salida,
                    'producto' => $salida->producto->nombre,
                    'codigo_producto' => $salida->codigo_producto,
                    'cantidad' => $salida->cantidad,
                    'precio_unitario' => $salida->producto->precio_unitario,
                    'monto_total' => $salida->cantidad * $salida->producto->precio_unitario,
                    'motivo' => $salida->motivo,
                    'usuario' => $salida->usuario,
                    'fecha' => $salida->fecha->format('d/m/Y H:i'),
                    'fecha_raw' => $salida->fecha,
                    'created_at' => $salida->created_at,
                ];
            });

        $productos = Producto::with(['categoria', 'unidadMedida'])
            ->orderBy('nombre')
            ->get()
            ->map(function ($prod) {
                return [
                    'codigo' => $prod->codigo_producto,
                    'nombre' => $prod->nombre,
                    'stock_actual' => $prod->stock,
                    'unidad_medida' => $prod->unidadMedida?->nombre,
                ];
            });

        return Inertia::render('Productos/SalidasStock/Index', [
            'salidas' => $salidas,
            'productos' => $productos,
            'filters' => $request->only(['search', 'producto']),
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_SALIDAS_STOCK->value),
                'edit' => $user->hasPermission(PermissionEnum::EDIT_SALIDAS_STOCK->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_SALIDAS_STOCK->value),
            ],
        ]);
    }

    /**
     * Guardar nueva salida de stock
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_producto' => 'required|exists:productos,codigo_producto',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:150',
            'fecha' => 'required|date',
        ], [
            'codigo_producto.required' => 'El producto es obligatorio.',
            'codigo_producto.exists' => 'El producto seleccionado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'motivo.required' => 'El motivo es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $validated['usuario'] = $request->user()->name;

            // Verificar stock disponible
            $producto = Producto::where('codigo_producto', $validated['codigo_producto'])->first();
            if ($producto->stock < $validated['cantidad']) {
                return redirect()->back()
                    ->with('error', "Stock insuficiente. Stock actual: {$producto->stock}")
                    ->withErrors(['cantidad' => 'Stock insuficiente']);
            }

            SalidaStock::create($validated);

            // Actualizar stock del producto
            $producto->decrement('stock', $validated['cantidad']);

            DB::commit();

            return redirect()->route('productos.salidas-stock.index')
                ->with('success', 'Salida de stock registrada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al registrar la salida de stock: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar salida de stock
     */
    public function update(Request $request, $codigo_salida)
    {
        $salidaStock = SalidaStock::findOrFail($codigo_salida);

        $validated = $request->validate([
            'codigo_producto' => 'required|exists:productos,codigo_producto',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:150',
            'fecha' => 'required|date',
        ], [
            'codigo_producto.required' => 'El producto es obligatorio.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'motivo.required' => 'El motivo es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $oldCantidad = $salidaStock->cantidad;
            $oldProducto = $salidaStock->codigo_producto;

            // Revertir el stock anterior
            $productoAnterior = Producto::where('codigo_producto', $oldProducto)->first();
            $productoAnterior->increment('stock', $oldCantidad);

            // Verificar stock disponible para la nueva cantidad
            $productoNuevo = Producto::where('codigo_producto', $validated['codigo_producto'])->first();
            if ($productoNuevo->stock < $validated['cantidad']) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', "Stock insuficiente. Stock actual: {$productoNuevo->stock}");
            }

            // Actualizar salida
            $salidaStock->update($validated);

            // Aplicar nuevo stock
            $productoNuevo->decrement('stock', $validated['cantidad']);

            DB::commit();

            return redirect()->route('productos.salidas-stock.index')
                ->with('success', 'Salida de stock actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al actualizar la salida de stock: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar salida de stock
     */
    public function destroy($codigo_salida)
    {
        $salidaStock = SalidaStock::findOrFail($codigo_salida);

        DB::beginTransaction();
        try {
            // Revertir el stock
            $producto = Producto::where('codigo_producto', $salidaStock->codigo_producto)->first();
            $producto->increment('stock', $salidaStock->cantidad);

            $salidaStock->delete();

            DB::commit();

            return redirect()->route('productos.salidas-stock.index')
                ->with('success', 'Salida de stock eliminada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al eliminar la salida de stock: ' . $e->getMessage());
        }
    }
}
