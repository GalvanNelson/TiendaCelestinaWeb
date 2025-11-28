<?php

use App\Enum\PermissionEnum;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CuentaPorCobrarController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\EntradaStockController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\SalidaStockController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UnidadMedidaController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;

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
    // PRODUCTOS
    // ========================================
    Route::prefix('productos')->group(function () {
        // Productos
        Route::get('/', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/', [ProductoController::class, 'store'])->name('productos.store');
        Route::put('/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::post('/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');

        // Categorías
        Route::get('categorias', [CategoriaController::class, 'index'])->name('productos.categorias.index');
        Route::post('categorias', [CategoriaController::class, 'store'])->name('productos.categorias.store');
        Route::put('categorias/{categoria}', [CategoriaController::class, 'update'])->name('productos.categorias.update');
        Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('productos.categorias.destroy');

        // Unidades de Medida
        Route::get('unidades', [UnidadMedidaController::class, 'index'])->name('productos.unidades.index');
        Route::post('unidades', [UnidadMedidaController::class, 'store'])->name('productos.unidades.store');
        Route::put('unidades/{unidade}', [UnidadMedidaController::class, 'update'])->name('productos.unidades.update');
        Route::delete('unidades/{unidade}', [UnidadMedidaController::class, 'destroy'])->name('productos.unidades.destroy');

        // Entradas de Stock
        Route::get('entradas-stock', [EntradaStockController::class, 'index'])->name('productos.entradas-stock.index');
        Route::post('entradas-stock', [EntradaStockController::class, 'store'])->name('productos.entradas-stock.store');
        Route::put('entradas-stock/{codigo_entrada}', [EntradaStockController::class, 'update'])->name('productos.entradas-stock.update');
        Route::delete('entradas-stock/{codigo_entrada}', [EntradaStockController::class, 'destroy'])->name('productos.entradas-stock.destroy');

         // Salidas de Stock
        Route::get('salidas-stock', [SalidaStockController::class, 'index'])->name('productos.salidas-stock.index');
        Route::post('salidas-stock', [SalidaStockController::class, 'store'])->name('productos.salidas-stock.store');
        Route::put('salidas-stock/{codigo_salida}', [SalidaStockController::class, 'update'])->name('productos.salidas-stock.update');
        Route::delete('salidas-stock/{codigo_salida}', [SalidaStockController::class, 'destroy'])->name('productos.salidas-stock.destroy');
    });

    // ========================================
    // VENTAS
    // ========================================
    Route::prefix('ventas')->group(function () {
        // CRUD de Ventas
        Route::get('/', [VentaController::class, 'index'])
            ->name('ventas.index')
            ->middleware('permission:' . PermissionEnum::VIEW_SALES->value);

        Route::get('/{venta}', [VentaController::class, 'show'])
            ->name('ventas.show')
            ->middleware('permission:' . PermissionEnum::VIEW_SALES->value);

        Route::post('/', [VentaController::class, 'store'])
            ->name('ventas.store')
            ->middleware('permission:' . PermissionEnum::CREATE_SALES->value);

        Route::put('/{venta}', [VentaController::class, 'update'])
            ->name('ventas.update')
            ->middleware('permission:' . PermissionEnum::EDIT_SALES->value);

        Route::delete('/{venta}', [VentaController::class, 'destroy'])
            ->name('ventas.destroy')
            ->middleware('permission:' . PermissionEnum::DELETE_SALES->value);
    });
    // ========================================
    // PAGOS
    // ========================================
    Route::get('/pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::post('/pagos', [PagoController::class, 'store'])->name('pagos.store');
    Route::delete('/pagos/{pago}', [PagoController::class, 'destroy'])->name('pagos.destroy');
    Route::post('/pagos/pago-qr/{venta}', [PagoController::class, 'generarPagoQR'])->name('pagos.pago-qr');

    // ========================================
    // CUENTAS POR COBRAR
    // ========================================
    Route::get('cuentas-por-cobrar', [CuentaPorCobrarController::class, 'index'])->name('cuentas-cobrar.index');
    Route::get('cuentas-por-cobrar/{cuentaPorCobrar}', [CuentaPorCobrarController::class, 'show'])->name('cuentas-cobrar.show');
    Route::post('cuentas-por-cobrar/{cuentaPorCobrar}/cuotas', [CuentaPorCobrarController::class, 'generarCuotas'])->name('cuentas-cobrar.generar-cuotas');

    // ========================================
    // CUOTAS
    // ========================================
    Route::put('cuotas/{cuota}/pagar', [CuotaController::class, 'marcarPagada'])->name('cuotas.pagar');
});
