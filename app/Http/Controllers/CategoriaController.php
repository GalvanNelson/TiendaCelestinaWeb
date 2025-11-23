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
     * Display a listing of the resource.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        // Obtener query base
        $query = Categoria::query();

        // Aplicar filtro de búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo_categoria', 'like', "%{$search}%");
            });
        }

        // Obtener categorías paginadas
        $categorias = $query->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('productos/categorias/Index', [
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Categoria $categoria)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categoria $categoria)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categoria $categoria)
    {
        //
    }
}
