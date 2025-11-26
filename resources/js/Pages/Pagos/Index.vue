<script setup>
import { ref, reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Plus, Trash2, Loader, DollarSign, Search, CreditCard } from 'lucide-vue-next';

const props = defineProps({
  pagos: Object,
  ventasCredito: Array,
  filters: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const selectedPago = ref(null);

// Formulario de búsqueda
const searchForm = reactive({
  search: props.filters?.search || '',
  metodo_pago: props.filters?.metodo_pago || ''
});

// Formulario principal
const form = useForm({
  venta: '',
  monto: '',
  fecha_pago: new Date().toISOString().split('T')[0],
  metodo_pago: 'efectivo',
  referencia: '',
  notas: ''
});

const deleteForm = useForm({});

// Métodos de pago
const metodosPago = [
  { value: 'efectivo', label: 'Efectivo' },
  { value: 'transferencia', label: 'Transferencia' },
  { value: 'tarjeta', label: 'Tarjeta' },
  { value: 'cheque', label: 'Cheque' }
];

// Abrir modal de registro
const openCreateModal = () => {
  form.reset();
  form.fecha_pago = new Date().toISOString().split('T')[0];
  form.metodo_pago = 'efectivo';
  form.clearErrors();
  showFormModal.value = true;
};

// Cerrar modal de formulario
const closeFormModal = () => {
  showFormModal.value = false;
  form.reset();
  form.clearErrors();
};

// Abrir modal de eliminación
const openDeleteModal = (pago) => {
  selectedPago.value = pago;
  showDeleteModal.value = true;
};

// Cerrar modal de eliminación
const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedPago.value = null;
};

// Submit del formulario
const submitForm = () => {
  form.post(route('pagos.store'), {
    onSuccess: () => closeFormModal(),
    preserveScroll: true
  });
};

// Eliminar pago
const deletePago = () => {
  deleteForm.delete(route('pagos.destroy', selectedPago.value.codigo_pago), {
    onSuccess: () => closeDeleteModal()
  });
};

// Búsqueda con debounce
const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

// Aplicar filtros
const applyFilters = () => {
  router.get(route('pagos.index'), searchForm, {
    preserveState: true,
    preserveScroll: true
  });
};

// Limpiar filtros
const clearFilters = () => {
  searchForm.search = '';
  searchForm.metodo_pago = '';
  applyFilters();
};

// Calcular monto máximo permitido
const getMontoMaximo = () => {
  if (!form.venta) return null;
  const venta = props.ventasCredito.find(v => v.codigo_venta === form.venta);
  return venta ? venta.saldo_pendiente : null;
};

// Formatear moneda
const formatCurrency = (amount) => {
  return `Bs. ${Number(amount).toFixed(2)}`;
};

// Obtener clase de badge según método de pago
const getMetodoPagoBadgeClass = (metodo) => {
  const classes = {
    efectivo: 'bg-green-100 text-green-800',
    transferencia: 'bg-blue-100 text-blue-800',
    tarjeta: 'bg-purple-100 text-purple-800',
    cheque: 'bg-yellow-100 text-yellow-800'
  };
  return classes[metodo] || 'bg-gray-100 text-gray-800';
};

// Obtener etiqueta de método de pago
const getMetodoPagoLabel = (metodo) => {
  const labels = {
    efectivo: 'Efectivo',
    transferencia: 'Transferencia',
    tarjeta: 'Tarjeta',
    cheque: 'Cheque'
  };
  return labels[metodo] || metodo;
};
</script>

<template>
  <AuthenticatedLayout title="Pagos">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Gestión de Pagos
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Plus :size="16" class="mr-2" />
          Registrar Pago
        </PrimaryButton>
      </div>
    </template>

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
              placeholder="Número de venta o referencia..."
              class="pl-10"
              @input="debouncedSearch"
            />
          </div>
        </div>
        <div>
          <InputLabel for="metodo_pago">Método de Pago</InputLabel>
          <select
            id="metodo_pago"
            v-model="searchForm.metodo_pago"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todos los métodos</option>
            <option v-for="metodo in metodosPago" :key="metodo.value" :value="metodo.value">
              {{ metodo.label }}
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

    <!-- Tabla de pagos -->
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
                Monto
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Método
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Referencia
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Registrado por
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="pago in pagos.data" :key="pago.codigo_pago" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ pago.numero_venta }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ pago.cliente }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-green-600">
                  {{ formatCurrency(pago.monto) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ pago.fecha_pago }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-semibold',
                    getMetodoPagoBadgeClass(pago.metodo_pago)
                  ]"
                >
                  {{ getMetodoPagoLabel(pago.metodo_pago) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">
                  {{ pago.referencia || '-' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ pago.usuario_registro }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  v-if="can.delete"
                  @click="openDeleteModal(pago)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Eliminar"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="pagos.data.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                <DollarSign :size="48" class="mx-auto text-gray-300 mb-2" />
                No hay pagos registrados
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="pagos.links && pagos.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="pagos.links"
          :from="pagos.from"
          :to="pagos.to"
          :total="pagos.total"
        />
      </div>
    </div>

    <!-- Modal Registrar Pago -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="2xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Registrar Nuevo Pago
        </h3>
      </template>

      <form @submit.prevent="submitForm">
        <div class="space-y-4">
          <!-- Venta -->
          <div>
            <InputLabel for="venta" required>Venta a Crédito</InputLabel>
            <select
              id="venta"
              v-model="form.venta"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.venta }"
              required
            >
              <option value="">Seleccionar venta...</option>
              <option
                v-for="venta in ventasCredito"
                :key="venta.codigo_venta"
                :value="venta.codigo_venta"
              >
                {{ venta.numero_venta }} - {{ venta.cliente }}
                (Pendiente: {{ formatCurrency(venta.saldo_pendiente) }})
              </option>
            </select>
            <p v-if="form.errors.venta" class="mt-1 text-sm text-red-600">
              {{ form.errors.venta }}
            </p>
          </div>

          <!-- Información de saldo -->
          <div v-if="form.venta" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <CreditCard :size="20" class="text-blue-500 mt-0.5 mr-3" />
              <div>
                <p class="text-sm font-medium text-blue-900">Saldo Pendiente</p>
                <p class="text-lg font-bold text-blue-600">
                  {{ formatCurrency(getMontoMaximo()) }}
                </p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Monto -->
            <div>
              <InputLabel for="monto" required>Monto a Pagar (Bs.)</InputLabel>
              <TextInput
                id="monto"
                v-model="form.monto"
                type="number"
                step="0.01"
                :max="getMontoMaximo()"
                :error="form.errors.monto"
                required
                placeholder="0.00"
              />
            </div>

            <!-- Fecha -->
            <div>
              <InputLabel for="fecha_pago" required>Fecha de Pago</InputLabel>
              <TextInput
                id="fecha_pago"
                v-model="form.fecha_pago"
                type="date"
                :error="form.errors.fecha_pago"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Método de pago -->
            <div>
              <InputLabel for="metodo_pago" required>Método de Pago</InputLabel>
              <select
                id="metodo_pago"
                v-model="form.metodo_pago"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.metodo_pago }"
                required
              >
                <option v-for="metodo in metodosPago" :key="metodo.value" :value="metodo.value">
                  {{ metodo.label }}
                </option>
              </select>
              <p v-if="form.errors.metodo_pago" class="mt-1 text-sm text-red-600">
                {{ form.errors.metodo_pago }}
              </p>
            </div>

            <!-- Referencia -->
            <div>
              <InputLabel for="referencia">Referencia/N° Transacción</InputLabel>
              <TextInput
                id="referencia"
                v-model="form.referencia"
                :error="form.errors.referencia"
                placeholder="Ej: TRAN-12345"
              />
            </div>
          </div>

          <!-- Notas -->
          <div>
            <InputLabel for="notas">Notas</InputLabel>
            <textarea
              id="notas"
              v-model="form.notas"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Notas adicionales sobre el pago..."
            ></textarea>
            <p v-if="form.errors.notas" class="mt-1 text-sm text-red-600">
              {{ form.errors.notas }}
            </p>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeFormModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            <Loader v-if="form.processing" :size="16" class="mr-2 animate-spin" />
            Registrar Pago
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Eliminar -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Eliminar Pago
        </h3>
      </template>

      <div v-if="selectedPago">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas eliminar el pago de
          <span class="font-semibold">{{ formatCurrency(selectedPago.monto) }}</span>
          registrado para la venta
          <span class="font-semibold">{{ selectedPago.numero_venta }}</span>?
        </p>
        <p class="mt-2 text-sm text-red-600">
          Esta acción revertirá el pago y aumentará el saldo pendiente de la cuenta por cobrar.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeDeleteModal" type="button">
          Cancelar
        </SecondaryButton>
        <DangerButton @click="deletePago" :disabled="deleteForm.processing">
          <Loader v-if="deleteForm.processing" :size="16" class="mr-2 animate-spin" />
          Eliminar
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
