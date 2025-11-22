<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener estadísticas
        $stats = $this->getStats();

        // Obtener ventas recientes (últimas 5)
        $ventasRecientes = $this->getVentasRecientes();

        // Obtener productos más vendidos (top 5)
        $productosMasVendidos = $this->getProductosMasVendidos();

        // Obtener actividad reciente
        $actividadReciente = $this->getActividadReciente();

        // Obtener alertas
        $alertas = $this->getAlertas();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'ventasRecientes' => $ventasRecientes,
            'productosMasVendidos' => $productosMasVendidos,
            'actividadReciente' => $actividadReciente,
            'alertas' => $alertas,
        ]);
    }

    /**
     * Obtener estadísticas generales
     */
    private function getStats()
    {
        // Total de clientes
        $totalClientes = Cliente::count();
        $clientesMesAnterior = Cliente::whereMonth('created_at', now()->subMonth()->month)->count();
        $clientesTrend = $clientesMesAnterior > 0
            ? round((($totalClientes - $clientesMesAnterior) / $clientesMesAnterior) * 100, 1)
            : 0;

        // Ventas del mes (ejemplo con modelo Venta - ajusta según tu estructura)
        // $ventasMes = Venta::whereMonth('created_at', now()->month)->sum('total');
        // $ventasMesAnterior = Venta::whereMonth('created_at', now()->subMonth()->month)->sum('total');

        // Datos de ejemplo (reemplaza con tus consultas reales)
        $ventasMes = 45230.50;
        $ventasMesAnterior = 38450.00;
        $ventasTrend = $ventasMesAnterior > 0
            ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
            : 0;

        // Total de productos (ejemplo)
        // $totalProductos = Producto::count();
        $totalProductos = 156;
        $productosTrend = 12; // Productos en stock bajo

        // Pedidos pendientes (ejemplo)
        // $pedidosPendientes = Pedido::where('estado', 'pendiente')->count();
        $pedidosPendientes = 8;

        return [
            'clientes' => $totalClientes,
            'clientes_trend' => $clientesTrend,
            'ventas' => $ventasMes,
            'ventas_trend' => $ventasTrend,
            'productos' => $totalProductos,
            'productos_trend' => $productosTrend,
            'pedidos_pendientes' => $pedidosPendientes,
        ];
    }

    /**
     * Obtener ventas recientes
     */
    private function getVentasRecientes()
    {
        // Ejemplo con datos ficticios - reemplaza con tu lógica real
        // return Venta::with('cliente')
        //     ->latest()
        //     ->take(5)
        //     ->get()
        //     ->map(function ($venta) {
        //         return [
        //             'id' => $venta->id,
        //             'cliente' => $venta->cliente->nombre_completo,
        //             'fecha' => $venta->created_at->diffForHumans(),
        //             'total' => $venta->total,
        //         ];
        //     });

        return [
            [
                'id' => 1,
                'cliente' => 'Juan Pérez',
                'fecha' => 'Hace 2 horas',
                'total' => 1250.00,
            ],
            [
                'id' => 2,
                'cliente' => 'María García',
                'fecha' => 'Hace 5 horas',
                'total' => 850.50,
            ],
            [
                'id' => 3,
                'cliente' => 'Carlos López',
                'fecha' => 'Ayer',
                'total' => 2340.00,
            ],
            [
                'id' => 4,
                'cliente' => 'Ana Martínez',
                'fecha' => 'Hace 2 días',
                'total' => 567.25,
            ],
            [
                'id' => 5,
                'cliente' => 'Luis Rodríguez',
                'fecha' => 'Hace 3 días',
                'total' => 1890.75,
            ],
        ];
    }

    /**
     * Obtener productos más vendidos
     */
    private function getProductosMasVendidos()
    {
        // Ejemplo con datos ficticios - reemplaza con tu lógica real
        return [
            [
                'id' => 1,
                'nombre' => 'Laptop HP Pavilion',
                'categoria' => 'Electrónica',
                'ventas' => 45,
                'ingresos' => 18500.00,
            ],
            [
                'id' => 2,
                'nombre' => 'Mouse Inalámbrico Logitech',
                'categoria' => 'Accesorios',
                'ventas' => 89,
                'ingresos' => 4450.00,
            ],
            [
                'id' => 3,
                'nombre' => 'Teclado Mecánico RGB',
                'categoria' => 'Accesorios',
                'ventas' => 34,
                'ingresos' => 6800.00,
            ],
            [
                'id' => 4,
                'nombre' => 'Monitor LG 27 pulgadas',
                'categoria' => 'Electrónica',
                'ventas' => 23,
                'ingresos' => 11500.00,
            ],
            [
                'id' => 5,
                'nombre' => 'Webcam Logitech HD',
                'categoria' => 'Accesorios',
                'ventas' => 56,
                'ingresos' => 3920.00,
            ],
        ];
    }

    /**
     * Obtener actividad reciente del sistema
     */
    private function getActividadReciente()
    {
        // Ejemplo con datos ficticios - implementa tu lógica con un modelo de logs
        return [
            [
                'id' => 1,
                'tipo' => 'venta',
                'descripcion' => 'Nueva venta registrada por Bs 1,250.00',
                'tiempo' => 'Hace 30 minutos',
            ],
            [
                'id' => 2,
                'tipo' => 'cliente',
                'descripcion' => 'Nuevo cliente registrado: Ana Martínez',
                'tiempo' => 'Hace 1 hora',
            ],
            [
                'id' => 3,
                'tipo' => 'producto',
                'descripcion' => 'Stock actualizado: Laptop HP Pavilion (10 unidades)',
                'tiempo' => 'Hace 2 horas',
            ],
            [
                'id' => 4,
                'tipo' => 'venta',
                'descripcion' => 'Venta completada por Bs 850.50',
                'tiempo' => 'Hace 3 horas',
            ],
            [
                'id' => 5,
                'tipo' => 'sistema',
                'descripcion' => 'Backup automático completado exitosamente',
                'tiempo' => 'Hace 4 horas',
            ],
        ];
    }

    /**
     * Obtener alertas del sistema
     */
    private function getAlertas()
    {
        $alertas = [];

        // Verificar productos con stock bajo (ejemplo)
        // $productosStockBajo = Producto::where('stock', '<', 10)->count();
        $productosStockBajo = 5;

        if ($productosStockBajo > 0) {
            $alertas[] = [
                'id' => 1,
                'tipo' => 'warning',
                'titulo' => 'Stock Bajo',
                'mensaje' => "{$productosStockBajo} productos tienen stock bajo. Considera reabastecer.",
            ];
        }

        // Verificar pedidos pendientes (ejemplo)
        $pedidosPendientes = 8;

        if ($pedidosPendientes > 5) {
            $alertas[] = [
                'id' => 2,
                'tipo' => 'info',
                'titulo' => 'Pedidos Pendientes',
                'mensaje' => "Tienes {$pedidosPendientes} pedidos pendientes de procesar.",
            ];
        }

        // Verificar clientes sin compras recientes (ejemplo)
        $clientesInactivos = 12;

        if ($clientesInactivos > 10) {
            $alertas[] = [
                'id' => 3,
                'tipo' => 'info',
                'titulo' => 'Clientes Inactivos',
                'mensaje' => "{$clientesInactivos} clientes no han realizado compras en el último mes.",
            ];
        }

        // Agregar alerta de éxito si todo está bien
        if (empty($alertas)) {
            $alertas[] = [
                'id' => 99,
                'tipo' => 'success',
                'titulo' => 'Sistema Operando Correctamente',
                'mensaje' => 'No hay alertas ni problemas detectados.',
            ];
        }

        return $alertas;
    }
}
