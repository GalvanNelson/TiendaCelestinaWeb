<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\Producto;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProductoController extends Controller
{
    use HasRolePermissionChecks;

    /**
     * Mostrar lista de productos
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = Producto::with(['categoria', 'unidadMedida']);

        // Filtro de búsqueda por nombre o código
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                    ->orWhere('codigo_producto', 'like', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria_codigo', $request->input('categoria'));
        }

        $productos = $query->orderBy('codigo_producto', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($producto) {
                return [
                    'codigo_producto' => $producto->codigo_producto,
                    'nombre' => $producto->nombre,
                    'descripcion' => $producto->descripcion ?? '',
                    'imagen' => $producto->imagen ? Storage::url($producto->imagen) : null,
                    'precio_unitario' => $producto->precio_unitario,
                    'stock' => $producto->stock,
                    'categoria' => $producto->categoria?->nombre,
                    'categoria_codigo' => $producto->categoria_codigo,
                    'unidad_medida' => $producto->unidadMedida?->nombre,
                    'unidad_codigo' => $producto->unidad_codigo,
                    'created_at' => $producto->created_at,
                    'updated_at' => $producto->updated_at,
                ];
            });

        // Obtener todas las categorías para el filtro
        $categorias = \App\Models\Categoria::orderBy('nombre')->get()->map(function ($cat) {
            return [
                'codigo' => $cat->codigo_categoria,
                'nombre' => $cat->nombre,
            ];
        });

        // Obtener todas las unidades de medida
        $unidades = \App\Models\UnidadMedida::orderBy('nombre')->get()->map(function ($unidad) {
            return [
                'codigo' => $unidad->codigo_unidad,
                'nombre' => $unidad->nombre,
            ];
        });

        return Inertia::render('Productos/Index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'unidades' => $unidades,
            'filters' => $request->only(['search', 'categoria']),
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_PRODUCTS->value),
                'edit' => $user->hasPermission(PermissionEnum::EDIT_PRODUCTS->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_PRODUCTS->value),
            ],
        ]);
    }

    /**
     * Guardar nuevo producto
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio_unitario' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_codigo' => 'required|exists:categorias,codigo_categoria',
            'unidad_codigo' => 'required|exists:unidad_medidas,codigo_unidad',
            'imagen' => 'nullable|image|max:2048', // max 2MB
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_unitario.required' => 'El precio es obligatorio.',
            'precio_unitario.min' => 'El precio debe ser mayor o igual a 0.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.min' => 'El stock debe ser mayor o igual a 0.',
            'categoria_codigo.required' => 'La categoría es obligatoria.',
            'categoria_codigo.exists' => 'La categoría seleccionada no existe.',
            'unidad_codigo.required' => 'La unidad de medida es obligatoria.',
            'unidad_codigo.exists' => 'La unidad de medida seleccionada no existe.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.max' => 'La imagen no debe ser mayor a 2MB.',
        ]);

        // Procesar imagen si existe
        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        Producto::create($validated);

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio_unitario' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_codigo' => 'required|exists:categorias,codigo_categoria',
            'unidad_codigo' => 'required|exists:unidad_medidas,codigo_unidad',
            'imagen' => 'nullable|image|max:2048',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_unitario.required' => 'El precio es obligatorio.',
            'precio_unitario.min' => 'El precio debe ser mayor o igual a 0.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.min' => 'El stock debe ser mayor o igual a 0.',
            'categoria_codigo.required' => 'La categoría es obligatoria.',
            'unidad_codigo.required' => 'La unidad de medida es obligatoria.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.max' => 'La imagen no debe ser mayor a 2MB.',
        ]);

        // Procesar nueva imagen si existe
        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($validated);

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Eliminar producto
     */
    public function destroy(Producto $producto)
    {
        try {
            // Eliminar imagen si existe
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $producto->delete();

            return redirect()->route('productos.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('productos.index')
                ->with('error', 'No se puede eliminar el producto porque tiene movimientos de stock asociados.');
        }
    }
}
