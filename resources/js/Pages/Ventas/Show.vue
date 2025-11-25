<template>
  <div class="p-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-start mb-6">
      <div>
        <button
          @click="goBack"
          class="flex items-center text-gray-600 hover:text-gray-900 mb-3"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver a ventas
        </button>
        <h1 class="text-3xl font-bold text-gray-900">Detalle de Venta</h1>
        <p class="text-gray-600 mt-1">{{ venta.numero_venta }}</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="printVenta"
          class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Imprimir
        </button>
        <span :class="[
          'px-4 py-2 rounded-lg font-semibold',
          venta.estado === 'completada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        ]">
          {{ venta.estado === 'completada' ? 'COMPLETADA' : 'CANCELADA' }}
        </span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Información Principal -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Información General -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Información General</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-600">Cliente</p>
              <p class="text-base font-medium text-gray-900">{{ venta.cliente }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Vendedor</p>
              <p class="text-base font-medium text-gray-900">{{ venta.vendedor }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Fecha de Venta</p>
              <p class="text-base font-medium text-gray-900">{{ venta.fecha_venta }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Tipo de Pago</p>
              <span :class="[
                'inline-block px-3 py-1 text-sm font-semibold rounded-full',
                venta.tipo_pago === 'contado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ venta.tipo_pago === 'contado' ? 'Contado' : 'Crédito' }}
              </span>
            </div>
          </div>
          <div v-if="venta.notas" class="mt-4 pt-4 border-t">
            <p class="text-sm text-gray-600">Notas</p>
            <p class="text-base text-gray-900 mt-1">{{ venta.notas }}</p>
          </div>
        </div>

        <!-- Detalles de Productos -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Productos</h2>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(detalle, index) in venta.detalles" :key="index">
                  <td class="px-4 py-3 text-sm text-gray-900">{{ detalle.producto }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatNumber(detalle.cantidad) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">Bs. {{ formatNumber(detalle.precio_unitario) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">Bs. {{ formatNumber(detalle.subtotal) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Historial de Pagos (si existen) -->
        <div v-if="venta.pagos && venta.pagos.length > 0" class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Historial de Pagos</h2>
          <div class="space-y-3">
            <div
              v-for="pago in venta.pagos"
              :key="pago.codigo_pago"
              class="flex justify-between items-center p-4 bg-gray-50 rounded-lg"
            >
              <div>
                <p class="font-medium text-gray-900">{{ pago.metodo_pago }}</p>
                <p class="text-sm text-gray-600">{{ pago.fecha_pago }}</p>
                <p class="text-sm text-gray-600">Por: {{ pago.usuario }}</p>
                <p v-if="pago.referencia" class="text-xs text-gray-500 mt-1">Ref: {{ pago.referencia }}</p>
              </div>
              <div class="text-right">
                <p class="text-lg font-bold text-green-600">Bs. {{ formatNumber(pago.monto) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resumen Lateral -->
      <div class="lg:col-span-1 space-y-6">
        <!-- Resumen de Montos -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Resumen</h2>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-semibold text-gray-900">Bs. {{ formatNumber(venta.subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Descuento:</span>
              <span class="font-semibold text-red-600">- Bs. {{ formatNumber(venta.descuento) }}</span>
            </div>
            <div class="flex justify-between text-lg font-bold pt-3 border-t">
              <span>Total:</span>
              <span class="text-blue-600">Bs. {{ formatNumber(venta.total) }}</span>
            </div>
          </div>
        </div>

        <!-- Información de Crédito (si aplica) -->
        <div v-if="venta.cuenta_por_cobrar" class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Estado de Crédito</h2>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Monto Total:</span>
              <span class="font-semibold text-gray-900">Bs. {{ formatNumber(venta.cuenta_por_cobrar.monto_total) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Monto Pagado:</span>
              <span class="font-semibold text-green-600">Bs. {{ formatNumber(venta.cuenta_por_cobrar.monto_pagado) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Saldo Pendiente:</span>
              <span class="font-semibold text-red-600">Bs. {{ formatNumber(venta.cuenta_por_cobrar.saldo_pendiente) }}</span>
            </div>
            <div class="pt-3 border-t">
              <p class="text-sm text-gray-600">Fecha de Vencimiento</p>
              <p class="font-semibold text-gray-900">{{ venta.cuenta_por_cobrar.fecha_vencimiento }}</p>
            </div>
            <div class="pt-2">
              <span :class="[
                'inline-block px-3 py-1 text-sm font-semibold rounded-full w-full text-center',
                getEstadoCreditoClass(venta.cuenta_por_cobrar.estado)
              ]">
                {{ getEstadoCreditoLabel(venta.cuenta_por_cobrar.estado) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Progreso de Pago (si es crédito) -->
        <div v-if="venta.cuenta_por_cobrar" class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-3">Progreso de Pago</h2>
          <div class="space-y-2">
            <div class="flex justify-between text-sm text-gray-600">
              <span>Pagado</span>
              <span>{{ calculatePorcentajePagado(venta.cuenta_por_cobrar) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div
                class="bg-green-600 h-3 rounded-full transition-all duration-300"
                :style="{ width: calculatePorcentajePagado(venta.cuenta_por_cobrar) + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
  venta: Object,
});

const goBack = () => {
  router.get(route('ventas.index'));
};

const printVenta = () => {
  window.print();
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0);
};

const getEstadoCreditoClass = (estado) => {
  const classes = {
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'parcial': 'bg-blue-100 text-blue-800',
    'pagado': 'bg-green-100 text-green-800',
    'vencido': 'bg-red-100 text-red-800',
  };
  return classes[estado] || 'bg-gray-100 text-gray-800';
};

const getEstadoCreditoLabel = (estado) => {
  const labels = {
    'pendiente': 'PENDIENTE',
    'parcial': 'PARCIALMENTE PAGADO',
    'pagado': 'PAGADO',
    'vencido': 'VENCIDO',
  };
  return labels[estado] || estado.toUpperCase();
};

const calculatePorcentajePagado = (cuentaPorCobrar) => {
  if (!cuentaPorCobrar || cuentaPorCobrar.monto_total === 0) return 0;
  return Math.round((cuentaPorCobrar.monto_pagado / cuentaPorCobrar.monto_total) * 100);
};
</script>

<style scoped>
@media print {
  button {
    display: none !important;
  }

  .no-print {
    display: none !important;
  }
}
</style>
