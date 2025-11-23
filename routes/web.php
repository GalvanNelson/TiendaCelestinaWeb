<?php

use App\Enum\PermissionEnum;
use App\Http\Controllers\CategoriaController;
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
    // ========================================
    // CLIENTES
    // ========================================
    Route::get('clientes', [ClienteController::class, 'index'])
        ->name('clientes.index')
        ->middleware('permission:' . PermissionEnum::VIEW_CLIENTS->value);

    Route::post('clientes', [ClienteController::class, 'store'])
        ->name('clientes.store')
        ->middleware('permission:' . PermissionEnum::CREATE_CLIENTS->value);

    Route::put('clientes/{cliente}', [ClienteController::class, 'update'])
        ->name('clientes.update')
        ->middleware('permission:' . PermissionEnum::EDIT_CLIENTS->value);

    Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])
        ->name('clientes.destroy')
        ->middleware('permission:' . PermissionEnum::DELETE_CLIENTS->value);


    // ========================================
    // CATEGORÍAS
    // ========================================
    Route::get('productos/categorias', [CategoriaController::class, 'index'])
        ->middleware('permission:' . PermissionEnum::VIEW_CATEGORIAS->value)
        ->name('categorias.index');
});
