<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import {
  ArrowLeft,
  User,
  DollarSign,
  Calendar,
  FileText,
  CheckCircle,
  XCircle,
  Clock,
  CreditCard,
  Loader
} from 'lucide-vue-next';

const props = defineProps({
  cuenta: Object
});

// Modal de cuotas
const showCuotasModal = ref(false);

// Formulario de cuotas
const cuotasForm = useForm({
  numero_cuotas: 3,
  fecha_primera_cuota: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
});

// Abrir modal de cuotas
const openCuotasModal = () => {
  cuotasForm.reset();
  cuotasForm.fecha_primera_cuota = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
  showCuotasModal.value = true;
};

// Cerrar modal
const closeCuotasModal = () => {
  showCuotasModal.value = false;
  cuotasForm.reset();
  cuotasForm.clearErrors();
};

// Generar cuotas
const generarCuotas = () => {
  cuotasForm.post(route('cuentas-por-cobrar.generar-cuotas', props.cuenta.codigo_cuenta), {
    onSuccess: () => closeCuotasModal(),
    preserveScroll: true
  });
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
    vencido: 'bg-red-100 text-red-800 border-red-200',
    pagada: 'bg-green-100 text-green-800 border-green-200',
    vencida: 'bg-red-100 text-red-800 border-red-200'
  };
  return classes[estado] || 'bg-gray-100 text-gray-800 border-gray-200';
};

// Obtener etiqueta de estado
const getEstadoLabel = (estado) => {
  const labels = {
    pendiente: 'Pendiente',
    parcial: 'Pago Parcial',
    pagado: 'Pagado',
    vencido: 'Vencido',
    pagada: 'Pagada',
    vencida: 'Vencida'
  };
  return labels[estado] || estado;
};

// Obtener clase de badge de método de pago
const getMetodoPagoBadgeClass = (metodo) => {
  const classes = {
    efectivo: 'bg-green-100 text-green-800',
    transferencia: 'bg-blue-100 text-blue-800',
    tarjeta: 'bg-purple-100 text-purple-800',
    cheque: 'bg-yellow-100 text-yellow-800'
  };
  return classes[metodo] || 'bg-gray-100 text-gray-800';
};

// Calcular porcentaje de pago
const calcularPorcentajePago = () => {
  if (!props.cuenta.monto_total) return 0;
  return ((props.cuenta.monto_pagado / props.cuenta.monto_total) * 100).toFixed(1);
};

// Calcular monto por cuota
const calcularMontoCuota = () => {
  if (!cuotasForm.numero_cuotas || !props.cuenta.saldo_pendiente) return 0;
  return (props.cuenta.saldo_pendiente / cuotasForm.numero_cuotas).toFixed(2);
};

// Volver atrás
const goBack = () => {
  window.history.back();
};
</script>

<template>
  <AuthenticatedLayout :title="'Cuenta por Cobrar - ' + cuenta.numero_venta">
    <template #header>
      <div class="flex items-center space-x-4">
        <button
          @click="goBack"
          class="text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft :size="24" />
        </button>
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cuenta por Cobrar - Venta {{ cuenta.numero_venta }}
          </h2>
          <p class="text-sm text-gray-600">{{ cuenta.cliente }}</p>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Resumen de la cuenta -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
          <h3 class="text-lg font-semibold text-white">Resumen de la Cuenta</h3>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
              <DollarSign :size="32" class="mx-auto text-gray-600 mb-2" />
              <p class="text-sm text-gray-600 mb-1">Monto Total</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(cuenta.monto_total) }}</p>
            </div>

            <div class="text-center p-4 bg-green-50 rounded-lg">
              <CheckCircle :size="32" class="mx-auto text-green-600 mb-2" />
              <p class="text-sm text-gray-600 mb-1">Monto Pagado</p>
              <p class="text-2xl font-bold text-green-600">{{ formatCurrency(cuenta.monto_pagado) }}</p>
            </div>

            <div class="text-center p-4 bg-orange-50 rounded-lg">
              <Clock :size="32" class="mx-auto text-orange-600 mb-2" />
              <p class="text-sm text-gray-600 mb-1">Saldo Pendiente</p>
              <p class="text-2xl font-bold text-orange-600">{{ formatCurrency(cuenta.saldo_pendiente) }}</p>
            </div>

            <div class="text-center p-4 bg-blue-50 rounded-lg">
              <Calendar :size="32" class="mx-auto text-blue-600 mb-2" />
              <p class="text-sm text-gray-600 mb-1">Vencimiento</p>
              <p class="text-lg font-bold text-blue-600">{{ cuenta.fecha_vencimiento }}</p>
            </div>
          </div>

          <!-- Barra de progreso -->
          <div class="mt-6">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700">Progreso de Pago</span>
              <span class="text-sm font-semibold text-gray-900">{{ calcularPorcentajePago() }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
              <div
                class="bg-gradient-to-r from-green-400 to-green-600 h-4 rounded-full transition-all duration-300"
                :style="{ width: calcularPorcentajePago() + '%' }"
              ></div>
            </div>
          </div>

          <!-- Estado -->
          <div class="mt-6 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">Estado actual:</span>
            <span
              :class="[
                'px-4 py-2 rounded-full text-sm font-semibold border',
                getEstadoBadgeClass(cuenta.estado)
              ]"
            >
              {{ getEstadoLabel(cuenta.estado) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Historial de Pagos -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-white">Historial de Pagos</h3>
        </div>
        <div class="overflow-x-auto">
          <table v-if="cuenta.pagos.length > 0" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Fecha
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Monto
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Método
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Referencia
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Usuario
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="pago in cuenta.pagos" :key="pago.codigo_pago" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ pago.fecha_pago }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                  {{ formatCurrency(pago.monto) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="['px-2 py-1 rounded-full text-xs font-semibold', getMetodoPagoBadgeClass(pago.metodo_pago)]">
                    {{ pago.metodo_pago }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ pago.referencia || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ pago.usuario }}
                </td>
              </tr>
            </tbody>
          </table>
          <div v-else class="p-8 text-center text-gray-500">
            <CreditCard :size="48" class="mx-auto text-gray-300 mb-2" />
            <p>No hay pagos registrados</p>
          </div>
        </div>
      </div>

      <!-- Plan de Cuotas -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-white">Plan de Cuotas</h3>
          <PrimaryButton
            v-if="cuenta.saldo_pendiente > 0"
            @click="openCuotasModal"
            class="bg-white text-purple-600 hover:bg-purple-50"
          >
            <FileText :size="16" class="mr-2" />
            Generar Plan
          </PrimaryButton>
        </div>
        <div class="overflow-x-auto">
          <table v-if="cuenta.cuotas.length > 0" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  N° Cuota
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Monto
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Fecha Vencimiento
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Fecha Pago
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Estado
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="cuota in cuenta.cuotas" :key="cuota.codigo_cuota" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  Cuota {{ cuota.numero_cuota }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatCurrency(cuota.monto) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ cuota.fecha_vencimiento }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ cuota.fecha_pago || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', getEstadoBadgeClass(cuota.estado)]">
                    {{ getEstadoLabel(cuota.estado) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-else class="p-8 text-center text-gray-500">
            <FileText :size="48" class="mx-auto text-gray-300 mb-2" />
            <p class="mb-4">No hay plan de cuotas generado</p>
            <PrimaryButton
              v-if="cuenta.saldo_pendiente > 0"
              @click="openCuotasModal"
            >
              Generar Plan de Cuotas
            </PrimaryButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Generar Cuotas -->
    <Modal :show="showCuotasModal" @close="closeCuotasModal" max-width="lg">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Generar Plan de Cuotas
        </h3>
      </template>

      <form @submit.prevent="generarCuotas">
        <div class="space-y-4">
          <!-- Información del saldo -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-900 mb-1">Saldo pendiente a dividir:</p>
            <p class="text-2xl font-bold text-blue-600">
              {{ formatCurrency(cuenta.saldo_pendiente) }}
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Número de cuotas -->
            <div>
              <InputLabel for="numero_cuotas" required>Número de Cuotas</InputLabel>
              <TextInput
                id="numero_cuotas"
                v-model="cuotasForm.numero_cuotas"
                type="number"
                min="1"
                max="12"
                :error="cuotasForm.errors.numero_cuotas"
                required
              />
            </div>

            <!-- Fecha primera cuota -->
            <div>
              <InputLabel for="fecha_primera_cuota" required>Fecha Primera Cuota</InputLabel>
              <TextInput
                id="fecha_primera_cuota"
                v-model="cuotasForm.fecha_primera_cuota"
                type="date"
                :error="cuotasForm.errors.fecha_primera_cuota"
                required
              />
            </div>
          </div>

          <!-- Previsualización -->
          <div v-if="cuotasForm.numero_cuotas > 0" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Previsualización:</p>
            <div class="space-y-1">
              <p class="text-sm text-gray-600">
                <span class="font-semibold">{{ cuotasForm.numero_cuotas }}</span> cuotas de aproximadamente
                <span class="font-semibold text-green-600">{{ formatCurrency(calcularMontoCuota()) }}</span> c/u
              </p>
              <p class="text-xs text-gray-500">
                Las cuotas se generarán mensualmente a partir de la fecha indicada
              </p>
            </div>
          </div>

          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-800">
              <strong>Nota:</strong> Si ya existe un plan de cuotas, será reemplazado por el nuevo.
            </p>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeCuotasModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="cuotasForm.processing">
            <Loader v-if="cuotasForm.processing" :size="16" class="mr-2 animate-spin" />
            Generar Cuotas
          </PrimaryButton>
        </div>
      </form>
    </Modal>
  </AuthenticatedLayout>
</template>
