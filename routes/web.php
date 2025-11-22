<?php

use App\Enum\PermissionEnum;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProfileController; // Add this
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::resource('dashboard/user', UserController::class);

    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    Route::get('/settings', function() {
        return inertia('Settings/Index');
    })->name('settings.index');

    // Add these profile routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/user/profile', [ProfileController::class, 'edit'])->name('profile.show');
    Route::put('/user/profile-information', [ProfileController::class, 'update'])->name('user-profile-information.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('clientes', [ClienteController::class, 'index'])->middleware('permission:' . PermissionEnum::VIEW_CLIENTS->value)->name('clientes.index');
    Route::get('clientes/create', [ClienteController::class, 'create'])
        ->middleware('permission:' . PermissionEnum::CREATE_CLIENTS->value)
        ->name('clientes.create');
    Route::post('clientes', [ClienteController::class, 'store'])
        ->middleware('permission:' . PermissionEnum::CREATE_CLIENTS->value)
        ->name('clientes.store');

    // Editar clientes (DEBE IR ANTES de {cliente})
    Route::get('clientes/{cliente}/edit', [ClienteController::class, 'edit'])
        ->middleware('permission:' . PermissionEnum::EDIT_CLIENTS->value)
        ->name('clientes.edit');

    // Ver detalle de cliente (DEBE IR DESPUÉS de rutas específicas)
    Route::get('clientes/{cliente}', [ClienteController::class, 'show'])
        ->middleware('permission:' . PermissionEnum::VIEW_CLIENTS->value)
        ->name('clientes.show');

    Route::put('clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:' . PermissionEnum::EDIT_CLIENTS->value)
        ->name('clientes.update');

    Route::patch('clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:' . PermissionEnum::EDIT_CLIENTS->value);

    // Eliminar clientes
    Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])
        ->middleware('permission:' . PermissionEnum::DELETE_CLIENTS->value)
        ->name('clientes.destroy');
});
