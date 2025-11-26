<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import {
  Search,
  TrendingUp,
  AlertCircle,
  CheckCircle,
  Clock,
  Eye,
  Calendar
} from 'lucide-vue-next';

const props = defineProps({
  cuentas: Object,
  estadisticas: Object,
  filters: Object,
  can: Object
});

// Estados
const showDetailModal = ref(false);
const selectedCuenta = ref(null);

// Formulario de búsqueda
const searchForm = reactive({
  search: props.filters?.search || '',
  estado: props.filters?.estado || ''
});

// Estados disponibles
const estados = [
  { value: 'pendiente', label: 'Pendiente', color: 'yellow' },
  { value: 'parcial', label: 'Pago Parcial', color: 'blue' },
  { value: 'pagado', label: 'Pagado', color: 'green' },
  { value: 'vencido', label: 'Vencido', color: 'red' }
];

// Abrir modal de detalle
const openDetailModal = (cuenta) => {
  router.visit(route('cuentas-por-cobrar.show', cuenta.codigo_cuenta));
};

// Búsqueda con debounce
const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

// Aplicar filtros
const applyFilters = () => {
  router.get(route('cuentas-por-cobrar.index'), searchForm, {
    preserveState: true,
    preserveScroll: true
  });
};

// Limpiar filtros
const clearFilters = () => {
  searchForm.search = '';
  searchForm.estado = '';
  applyFilters();
};

// Formatear moneda
const formatCurrency = (amount) => {
  return `Bs. ${Number(amount).toFixed(2)}`;
};

// Obtener clase de badge según estado
const getEstadoBadgeClass = (estado) => {
  const classes = {
    pendiente: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    parcial: 'bg-blue-100 text-blue-800 border-blue-200',
    pagado: 'bg-green-100 text-green-800 border-green-200',
    vencido: 'bg-red-100 text-red-800 border-red-200'
  };
  return classes[estado] || 'bg-gray-100 text-gray-800 border-gray-200';
};

// Obtener etiqueta de estado
const getEstadoLabel = (estado) => {
  const label = estados.find(e => e.value === estado);
  return label ? label.label : estado;
};

// Obtener clase de días de vencimiento
const getDiasVencimientoClass = (dias) => {
  if (dias === null) return 'text-gray-500';
  if (dias < 0) return 'text-red-600 font-semibold';
  if (dias <= 7) return 'text-orange-600 font-semibold';
  return 'text-gray-600';
};

// Formatear días de vencimiento
const formatDiasVencimiento = (dias) => {
  if (dias === null) return 'Sin fecha';
  if (dias < 0) return `Vencido hace ${Math.abs(dias)} días`;
  if (dias === 0) return 'Vence hoy';
  if (dias === 1) return 'Vence mañana';
  return `Vence en ${dias} días`;
};

// Calcular porcentaje de pago
const calcularPorcentajePago = (pagado, total) => {
  if (!total) return 0;
  return ((pagado / total) * 100).toFixed(1);
};
</script>

<template>
  <AuthenticatedLayout title="Cuentas por Cobrar">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Cuentas por Cobrar
        </h2>
      </div>
    </template>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <TrendingUp :size="32" class="text-blue-500" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Cuentas</p>
            <p class="text-2xl font-bold text-gray-900">
              {{ estadisticas.total_cuentas }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <Clock :size="32" class="text-yellow-500" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Por Cobrar</p>
            <p class="text-2xl font-bold text-gray-900">
              {{ formatCurrency(estadisticas.total_pendiente) }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <AlertCircle :size="32" class="text-red-500" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Cuentas Vencidas</p>
            <p class="text-2xl font-bold text-gray-900">
              {{ estadisticas.cuentas_vencidas }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <AlertCircle :size="32" class="text-red-500" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Monto Vencido</p>
            <p class="text-2xl font-bold text-red-600">
              {{ formatCurrency(estadisticas.monto_vencido) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="search">Buscar</InputLabel>
          <div class="relative">
            <Search :size="18" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <TextInput
              id="search"
              v-model="searchForm.search"
              placeholder="Cliente o número de venta..."
              class="pl-10"
              @input="debouncedSearch"
            />
          </div>
        </div>
        <div>
          <InputLabel for="estado">Estado</InputLabel>
          <select
            id="estado"
            v-model="searchForm.estado"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todos los estados</option>
            <option v-for="estado in estados" :key="estado.value" :value="estado.value">
              {{ estado.label }}
            </option>
          </select>
        </div>
        <div class="flex items-end">
          <SecondaryButton @click="clearFilters" class="w-full">
            Limpiar Filtros
          </SecondaryButton>
        </div>
      </div>
    </div>

    <!-- Tabla de cuentas -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                N° Venta
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Cliente
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto Total
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Pagado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Saldo Pendiente
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Progreso
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Vencimiento
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="cuenta in cuentas.data" :key="cuenta.codigo_cuenta" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ cuenta.numero_venta }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ cuenta.cliente }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-gray-900">
                  {{ formatCurrency(cuenta.monto_total) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-green-600 font-medium">
                  {{ formatCurrency(cuenta.monto_pagado) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-orange-600">
                  {{ formatCurrency(cuenta.saldo_pendiente) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                    <div
                      class="bg-green-500 h-2 rounded-full transition-all duration-300"
                      :style="{ width: calcularPorcentajePago(cuenta.monto_pagado, cuenta.monto_total) + '%' }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-600 whitespace-nowrap">
                    {{ calcularPorcentajePago(cuenta.monto_pagado, cuenta.monto_total) }}%
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <Calendar :size="14" class="mr-1 text-gray-400" />
                  <div>
                    <div class="text-sm text-gray-900">{{ cuenta.fecha_vencimiento || 'Sin fecha' }}</div>
                    <div
                      class="text-xs"
                      :class="getDiasVencimientoClass(cuenta.dias_vencimiento)"
                    >
                      {{ formatDiasVencimiento(cuenta.dias_vencimiento) }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-semibold border',
                    getEstadoBadgeClass(cuenta.estado)
                  ]"
                >
                  {{ getEstadoLabel(cuenta.estado) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="openDetailModal(cuenta)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver detalles"
                >
                  <Eye :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="cuentas.data.length === 0">
              <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                <CheckCircle :size="48" class="mx-auto text-gray-300 mb-2" />
                No hay cuentas por cobrar
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="cuentas.links && cuentas.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="cuentas.links"
          :from="cuentas.from"
          :to="cuentas.to"
          :total="cuentas.total"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
