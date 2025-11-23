<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\Categoria;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CategoriaController extends Controller
{
    use HasRolePermissionChecks;

    /**
     * Mostrar lista de categorías
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = Categoria::withCount('productos');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo_categoria', 'like', "%{$search}%");
            });
        }

        $categorias = $query->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Productos/Categorias/Index', [
            'categorias' => $categorias,
            'filters' => $request->only(['search']),
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_CATEGORIAS->value),
                'edit' => $user->hasPermission(PermissionEnum::EDIT_CATEGORIAS->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_CATEGORIAS->value),
            ],
        ]);
    }

    /**
     * Guardar nueva categoría
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ]);

        Categoria::create($validated);

        return redirect()->route('productos.categorias.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre,' . $categoria->codigo_categoria . ',codigo_categoria',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ]);

        $categoria->update($validated);

        return redirect()->route('productos.categorias.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Eliminar categoría
     */
    public function destroy(Categoria $categoria)
    {
        try {
            $categoria->delete();
            return redirect()->route('productos.categorias.index')
                ->with('success', 'Categoría eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('productos.categorias.index')
                ->with('error', 'No se puede eliminar la categoría porque tiene productos asociados.');
        }
    }
}
