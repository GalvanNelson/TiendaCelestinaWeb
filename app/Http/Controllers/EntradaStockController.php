<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\EntradaStock;
use App\Models\Producto;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EntradaStockController extends Controller
{
    use HasRolePermissionChecks;

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = EntradaStock::with(['producto.categoria', 'producto.unidadMedida']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo_entrada', 'like', "%{$search}%")
                  ->orWhere('motivo', 'like', "%{$search}%")
                  ->orWhereHas('producto', function($q) use ($search) {
                      $q->where('nombre', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('producto')) {
            $query->where('codigo_producto', $request->input('producto'));
        }

        $entradas = $query->orderBy('fecha', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($entrada) {
                return [
                    'codigo_entrada' => $entrada->codigo_entrada,
                    'producto' => $entrada->producto->nombre,
                    'codigo_producto' => $entrada->codigo_producto,
                    'cantidad' => $entrada->cantidad,
                    'precio_unitario' => $entrada->producto->precio_unitario,
                    'monto_total' => $entrada->cantidad * $entrada->producto->precio_unitario,
                    'motivo' => $entrada->motivo,
                    'usuario' => $entrada->usuario,
                    'fecha' => $entrada->fecha->format('d/m/Y H:i'),
                    'fecha_raw' => $entrada->fecha,
                    'created_at' => $entrada->created_at,
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

        return Inertia::render('Productos/EntradasStock/Index', [
            'entradas' => $entradas,
            'productos' => $productos,
            'filters' => $request->only(['search', 'producto']),
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_ENTRADAS_STOCK->value),
                'edit' => $user->hasPermission(PermissionEnum::EDIT_ENTRADAS_STOCK->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_ENTRADAS_STOCK->value),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_producto' => 'required|exists:productos,codigo_producto',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:150', // Cambiado a nullable según tu migración
            'fecha' => 'required|date',
        ], [
            'codigo_producto.required' => 'El producto es obligatorio.',
            'codigo_producto.exists' => 'El producto seleccionado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'motivo.max' => 'El motivo no puede tener más de 150 caracteres.',
            'fecha.required' => 'La fecha es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $validated['usuario'] = $request->user()->name;

            EntradaStock::create($validated);

            // Actualizar stock del producto
            $producto = Producto::where('codigo_producto', $validated['codigo_producto'])->first();
            $producto->increment('stock', $validated['cantidad']);

            DB::commit();

            return redirect()->route('productos.entradas-stock.index')
                ->with('success', 'Entrada de stock registrada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al registrar la entrada de stock: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $codigo_entrada)
    {
        $entradasStock = EntradaStock::findOrFail($codigo_entrada);

        $validated = $request->validate([
            'codigo_producto' => 'required|exists:productos,codigo_producto',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:150',
            'fecha' => 'required|date',
        ], [
            'codigo_producto.required' => 'El producto es obligatorio.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'motivo.max' => 'El motivo no puede tener más de 150 caracteres.',
            'fecha.required' => 'La fecha es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $oldCantidad = $entradasStock->cantidad;
            $oldProducto = $entradasStock->codigo_producto;

            // Revertir el stock anterior
            $productoAnterior = Producto::where('codigo_producto', $oldProducto)->first();
            $productoAnterior->decrement('stock', $oldCantidad);

            // Actualizar entrada
            $entradasStock->update($validated);

            // Aplicar nuevo stock
            $productoNuevo = Producto::where('codigo_producto', $validated['codigo_producto'])->first();
            $productoNuevo->increment('stock', $validated['cantidad']);

            DB::commit();

            return redirect()->route('productos.entradas-stock.index')
                ->with('success', 'Entrada de stock actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al actualizar la entrada de stock: ' . $e->getMessage());
        }
    }

    public function destroy($codigo_entrada)
    {
        $entradasStock = EntradaStock::findOrFail($codigo_entrada);

        DB::beginTransaction();
        try {
            // Revertir el stock
            $producto = Producto::where('codigo_producto', $entradasStock->codigo_producto)->first();
            $producto->decrement('stock', $entradasStock->cantidad);

            $entradasStock->delete();

            DB::commit();

            return redirect()->route('productos.entradas-stock.index')
                ->with('success', 'Entrada de stock eliminada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al eliminar la entrada de stock: ' . $e->getMessage());
        }
    }
}
