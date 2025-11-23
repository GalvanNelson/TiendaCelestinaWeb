<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Models\UnidadMedida;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UnidadMedidaController extends Controller
{
    use HasRolePermissionChecks;

    /**
     * Mostrar lista de unidades de medida
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = UnidadMedida::withCount('productos');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo_unidad', 'like', "%{$search}%");
                  //->orWhere('abreviatura', 'like', "%{$search}%");
            });
        }

        $unidades = $query->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Productos/Unidades/Index', [
            'unidades' => $unidades,
            'filters' => $request->only(['search']),
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_UNIDADES->value),
                'edit' => $user->hasPermission(PermissionEnum::EDIT_UNIDADES->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_UNIDADES->value),
            ],
        ]);
    }

    /**
     * Guardar nueva unidad de medida
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:unidad_medidas,nombre',
            //'abreviatura' => 'required|string|max:10|unique:unidad_medidas,abreviatura',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe una unidad de medida con este nombre.',
            //'abreviatura.required' => 'La abreviatura es obligatoria.',
            //'abreviatura.unique' => 'Ya existe una unidad de medida con esta abreviatura.',
            //'abreviatura.max' => 'La abreviatura no puede tener más de 10 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ]);

        UnidadMedida::create($validated);

        return redirect()->route('productos.unidades.index')
            ->with('success', 'Unidad de medida creada exitosamente.');
    }

    /**
     * Actualizar unidad de medida
     */
    public function update(Request $request, UnidadMedida $unidade)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:unidad_medidas,nombre,' . $unidade->codigo_unidad . ',codigo_unidad',
            //'abreviatura' => 'required|string|max:10|unique:unidad_medidas,abreviatura,' . $unidade->codigo_unidad . ',codigo_unidad',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe una unidad de medida con este nombre.',
            //'abreviatura.required' => 'La abreviatura es obligatoria.',
            //'abreviatura.unique' => 'Ya existe una unidad de medida con esta abreviatura.',
            //'abreviatura.max' => 'La abreviatura no puede tener más de 10 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ]);

        $unidade->update($validated);

        return redirect()->route('productos.unidades.index')
            ->with('success', 'Unidad de medida actualizada exitosamente.');
    }

    /**
     * Eliminar unidad de medida
     */
    public function destroy(UnidadMedida $unidade)
    {
        try {
            $unidade->delete();
            return redirect()->route('productos.unidades.index')
                ->with('success', 'Unidad de medida eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('productos.unidades.index')
                ->with('error', 'No se puede eliminar la unidad de medida porque tiene productos asociados.');
        }
    }
}
