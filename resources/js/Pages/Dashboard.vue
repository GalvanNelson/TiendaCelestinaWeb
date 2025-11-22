<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800">Dashboard</h2>
                    <p class="text-sm text-gray-600 mt-1">Bienvenido, {{ $page.props.auth.user?.name }}</p>
                </div>
                <div class="text-sm text-gray-500">
                    {{ currentDate }}
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tarjetas de Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Clientes -->
                <StatCard
                    title="Clientes"
                    :value="stats.clientes"
                    icon="Users"
                    color="blue"
                    :trend="stats.clientes_trend"
                    trend-label="vs mes anterior"
                />

                <!-- Ventas -->
                <StatCard
                    title="Ventas del Mes"
                    :value="`Bs ${formatNumber(stats.ventas)}`"
                    icon="ShoppingCart"
                    color="green"
                    :trend="stats.ventas_trend"
                    trend-label="vs mes anterior"
                />

                <!-- Productos -->
                <StatCard
                    title="Productos"
                    :value="stats.productos"
                    icon="Package"
                    color="purple"
                    :trend="stats.productos_trend"
                    trend-label="en stock"
                />

                <!-- Pedidos Pendientes -->
                <StatCard
                    title="Pedidos Pendientes"
                    :value="stats.pedidos_pendientes"
                    icon="Clock"
                    color="orange"
                    badge="Urgente"
                />
            </div>

            <!-- Gráficos y Tablas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Ventas Recientes -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Ventas Recientes</h3>
                            <Link
                                v-if="can('ver ventas')"
                                :href="route('ventas.index')"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                            >
                                Ver todas →
                            </Link>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="ventasRecientes.length > 0" class="space-y-4">
                            <div
                                v-for="venta in ventasRecientes"
                                :key="venta.id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
                            >
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <DollarSign :size="20" class="text-green-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ venta.cliente }}</p>
                                        <p class="text-xs text-gray-500">{{ venta.fecha }}</p>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Bs {{ formatNumber(venta.total) }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <ShoppingCart class="mx-auto text-gray-400" :size="48" />
                            <p class="mt-2 text-sm text-gray-500">No hay ventas recientes</p>
                        </div>
                    </div>
                </div>

                <!-- Productos Más Vendidos -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Productos Más Vendidos</h3>
                            <Link
                                v-if="can('ver productos')"
                                :href="route('productos.index')"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                            >
                                Ver todos →
                            </Link>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="productosMasVendidos.length > 0" class="space-y-4">
                            <div
                                v-for="(producto, index) in productosMasVendidos"
                                :key="producto.id"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center space-x-3 flex-1">
                                    <span class="text-lg font-bold text-gray-400">#{{ index + 1 }}</span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ producto.nombre }}</p>
                                        <p class="text-xs text-gray-500">{{ producto.categoria }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ producto.ventas }} vendidos</p>
                                    <p class="text-xs text-gray-500">Bs {{ formatNumber(producto.ingresos) }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <Package class="mx-auto text-gray-400" :size="48" />
                            <p class="mt-2 text-sm text-gray-500">No hay datos de ventas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actividad Reciente y Alertas -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Actividad Reciente -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Actividad Reciente</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="actividadReciente.length > 0" class="space-y-4">
                            <div
                                v-for="actividad in actividadReciente"
                                :key="actividad.id"
                                class="flex items-start space-x-3"
                            >
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center"
                                        :class="getActivityColor(actividad.tipo)"
                                    >
                                        <component :is="getActivityIcon(actividad.tipo)" :size="16" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900">{{ actividad.descripcion }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ actividad.tiempo }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <Activity class="mx-auto text-gray-400" :size="48" />
                            <p class="mt-2 text-sm text-gray-500">No hay actividad reciente</p>
                        </div>
                    </div>
                </div>

                <!-- Alertas y Notificaciones -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Alertas</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="alertas.length > 0" class="space-y-3">
                            <div
                                v-for="alerta in alertas"
                                :key="alerta.id"
                                class="p-3 rounded-lg border"
                                :class="getAlertClass(alerta.tipo)"
                            >
                                <div class="flex items-start">
                                    <component
                                        :is="getAlertIcon(alerta.tipo)"
                                        :size="18"
                                        class="flex-shrink-0 mt-0.5"
                                        :class="getAlertIconColor(alerta.tipo)"
                                    />
                                    <div class="ml-3">
                                        <p class="text-sm font-medium" :class="getAlertTextColor(alerta.tipo)">
                                            {{ alerta.titulo }}
                                        </p>
                                        <p class="text-xs mt-1" :class="getAlertTextColor(alerta.tipo)">
                                            {{ alerta.mensaje }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <CheckCircle class="mx-auto text-green-400" :size="48" />
                            <p class="mt-2 text-sm text-gray-500">Todo está en orden</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accesos Rápidos -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Accesos Rápidos</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        v-if="can('crear clientes')"
                        :href="route('clientes.create')"
                        class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition group"
                    >
                        <UserPlus :size="32" class="text-blue-600 group-hover:scale-110 transition-transform" />
                        <span class="mt-2 text-sm font-medium text-gray-700">Nuevo Cliente</span>
                    </Link>

                    <Link
                        v-if="can('crear ventas')"
                        :href="route('ventas.create')"
                        class="flex flex-col items-center justify-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition group"
                    >
                        <ShoppingBag :size="32" class="text-green-600 group-hover:scale-110 transition-transform" />
                        <span class="mt-2 text-sm font-medium text-gray-700">Nueva Venta</span>
                    </Link>

                    <Link
                        v-if="can('crear productos')"
                        :href="route('productos.create')"
                        class="flex flex-col items-center justify-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition group"
                    >
                        <PackagePlus :size="32" class="text-purple-600 group-hover:scale-110 transition-transform" />
                        <span class="mt-2 text-sm font-medium text-gray-700">Nuevo Producto</span>
                    </Link>

                    <Link
                        v-if="can('ver reportes')"
                        :href="route('reportes.index')"
                        class="flex flex-col items-center justify-center p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition group"
                    >
                        <FileText :size="32" class="text-orange-600 group-hover:scale-110 transition-transform" />
                        <span class="mt-2 text-sm font-medium text-gray-700">Reportes</span>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import {
    Users, ShoppingCart, Package, Clock, DollarSign, UserPlus,
    ShoppingBag, PackagePlus, FileText, Activity, CheckCircle,
    AlertTriangle, AlertCircle, Info
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            clientes: 0,
            clientes_trend: 0,
            ventas: 0,
            ventas_trend: 0,
            productos: 0,
            productos_trend: 0,
            pedidos_pendientes: 0
        })
    },
    ventasRecientes: {
        type: Array,
        default: () => []
    },
    productosMasVendidos: {
        type: Array,
        default: () => []
    },
    actividadReciente: {
        type: Array,
        default: () => []
    },
    alertas: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

// Fecha actual
const currentDate = computed(() => {
    return new Date().toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Función para verificar permisos
const can = (permission) => {
    return page.props.auth?.permissions?.includes(permission) || false;
};

// Formatear números
const formatNumber = (number) => {
    return new Intl.NumberFormat('es-BO').format(number);
};

// Obtener color de actividad
const getActivityColor = (tipo) => {
    const colors = {
        venta: 'bg-green-100 text-green-600',
        cliente: 'bg-blue-100 text-blue-600',
        producto: 'bg-purple-100 text-purple-600',
        sistema: 'bg-gray-100 text-gray-600'
    };
    return colors[tipo] || colors.sistema;
};

// Obtener icono de actividad
const getActivityIcon = (tipo) => {
    const icons = {
        venta: ShoppingCart,
        cliente: Users,
        producto: Package,
        sistema: Activity
    };
    return icons[tipo] || Activity;
};

// Estilos de alertas
const getAlertClass = (tipo) => {
    const classes = {
        error: 'bg-red-50 border-red-200',
        warning: 'bg-yellow-50 border-yellow-200',
        info: 'bg-blue-50 border-blue-200',
        success: 'bg-green-50 border-green-200'
    };
    return classes[tipo] || classes.info;
};

const getAlertIcon = (tipo) => {
    const icons = {
        error: AlertCircle,
        warning: AlertTriangle,
        info: Info,
        success: CheckCircle
    };
    return icons[tipo] || Info;
};

const getAlertIconColor = (tipo) => {
    const colors = {
        error: 'text-red-600',
        warning: 'text-yellow-600',
        info: 'text-blue-600',
        success: 'text-green-600'
    };
    return colors[tipo] || colors.info;
};

const getAlertTextColor = (tipo) => {
    const colors = {
        error: 'text-red-800',
        warning: 'text-yellow-800',
        info: 'text-blue-800',
        success: 'text-green-800'
    };
    return colors[tipo] || colors.info;
};
</script>
