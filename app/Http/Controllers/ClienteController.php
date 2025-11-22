<?php

namespace App\Http\Controllers;

use App\Enum\PermissionEnum;
use App\Enum\RoleEnum;
use App\Models\Role;
use App\Models\User;
use App\Traits\HasRolePermissionChecks;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ClienteController extends Controller
{
    use HasRolePermissionChecks;

    public function index(Request $request): InertiaResponse
    {

        $user = $request->user();

        $clienteRole = Role::where('name', RoleEnum::CLIENTE->value)->first();
        $clientes = User::whereHas('roles', function ($query) use ($clienteRole) {
            $query->where('role_id', $clienteRole->id);
        })
        ->orderBy('id','desc')
        ->paginate(10)
        ->through(function ($cliente){
            return [
                'id' => $cliente->id,
                'nombre' => $cliente->name,
                'apellido' => $cliente->apellido,
                'nombre_completo' => $cliente->full_name,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'domicilio' => $cliente->domicilio,
                'created_at' => $cliente->created_at,
                'updated_at' => $cliente->updated_at,
            ];
        });

        return Inertia::render('Clientes/Index', [
            'clientes' => $clientes,
            'can' => [
                'create' => $user->hasPermission(PermissionEnum::CREATE_CLIENTS->value),
                'edit' => $user->hasPermission(PermissionEnum::EDIT_CLIENTS->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_CLIENTS->value),
            ],
        ]);

    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Clientes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'telefono' => 'nullable|string|max:255',
            'domicilio' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Crear el usuario
        $cliente = User::create([
            'name' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'telefono' => $validated['telefono'] ?? null,
            'domicilio' => $validated['domicilio'] ?? null,
            'email_verified_at' => now(), // Auto-verificar email
        ]);

        // Asignar rol de Cliente
        $clienteRole = Role::where('name', RoleEnum::CLIENTE->value)->first();
        $cliente->roles()->attach($clienteRole->id);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $cliente): InertiaResponse
    {
        $user = $request->user();

        // Verificar que el usuario sea un cliente
        if (!$cliente->hasRole(RoleEnum::CLIENTE->value)) {
            abort(404);
        }

        return Inertia::render('clientes/Show', [
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->name,
                'apellido' => $cliente->apellido,
                'nombre_completo' => $cliente->full_name,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'domicilio' => $cliente->domicilio,
                'email_verified_at' => $cliente->email_verified_at,
                'created_at' => $cliente->created_at,
                'updated_at' => $cliente->updated_at,
            ],
            'can' => [
                'edit' => $user->hasPermission(PermissionEnum::EDIT_CLIENTS->value),
                'delete' => $user->hasPermission(PermissionEnum::DELETE_CLIENTS->value),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $cliente): InertiaResponse
    {
        $user = $request->user();

        // Verificar que el usuario sea un cliente
        if (!$cliente->hasRole(RoleEnum::CLIENTE->value)) {
            abort(404);
        }

        return Inertia::render('clientes/Edit', [
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->name,
                'apellido' => $cliente->apellido,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'domicilio' => $cliente->domicilio,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $cliente)
    {
        // Verificar que el usuario sea un cliente
        if (!$cliente->hasRole(RoleEnum::CLIENTE->value)) {
            abort(404);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $cliente->id,
            'password' => 'nullable|string|min:8|confirmed',
            'telefono' => 'nullable|string|max:255',
            'domicilio' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'Este email ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Actualizar los datos del cliente
        $cliente->update([
            'name' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'domicilio' => $validated['domicilio'] ?? null,
        ]);

        // Actualizar la contraseña solo si se proporcionó
        if (!empty($validated['password'])) {
            $cliente->update([
                'password' => bcrypt($validated['password']),
            ]);
        }

        return redirect()->route('clientes.show', $cliente->id)
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $cliente)
    {
        // Verificar que el usuario sea un cliente
        if (!$cliente->hasRole(RoleEnum::CLIENTE->value)) {
            abort(404);
        }

        // Eliminar el cliente
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }


}
