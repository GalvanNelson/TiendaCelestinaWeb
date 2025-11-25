<template>
  <AuthenticatedLayout title="Ventas">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Ventas
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <ShoppingCart :size="16" class="mr-2" />
          Nueva Venta
        </PrimaryButton>
      </div>
    </template>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <InputLabel for="search">Buscar</InputLabel>
          <TextInput
            id="search"
            v-model="searchForm.search"
            placeholder="Número de venta o cliente..."
            @input="debouncedSearch"
          />
        </div>
        <div>
          <InputLabel for="estado">Estado</InputLabel>
          <select
            id="estado"
            v-model="searchForm.estado"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="completada">Completada</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </div>
        <div>
          <InputLabel for="tipo_pago">Tipo de Pago</InputLabel>
          <select
            id="tipo_pago"
            v-model="searchForm.tipo_pago"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todos</option>
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
          </select>
        </div>
        <div class="flex items-end">
          <SecondaryButton @click="clearFilters" class="w-full">
            Limpiar
          </SecondaryButton>
        </div>
      </div>
    </div>

    <!-- Tabla de ventas -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                N° Venta
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Cliente
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Fecha
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Tipo Pago
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Total
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Estado
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="venta in ventas.data" :key="venta.codigo_venta" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-semibold text-gray-900">
                {{ venta.numero_venta }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                {{ venta.cliente }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ venta.fecha_venta }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span :class="[
                  'px-2 py-1 rounded-full text-xs font-semibold',
                  venta.tipo_pago === 'contado'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-orange-100 text-orange-800'
                ]">
                  {{ venta.tipo_pago === 'contado' ? 'Contado' : 'Crédito' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                Bs. {{ Number(venta.total).toFixed(2) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span :class="getEstadoBadgeClass(venta.estado)">
                  {{ getEstadoLabel(venta.estado) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <Link
                  :href="route('ventas.show', venta.codigo_venta)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver Detalles"
                >
                  <Eye :size="18" />
                </Link>
                <button
                  v-if="can.delete && venta.estado !== 'cancelada'"
                  @click="openCancelModal(venta)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Cancelar"
                >
                  <XCircle :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="ventas.data.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                No hay ventas registradas
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="ventas.links && ventas.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="ventas.links"
          :from="ventas.from"
          :to="ventas.to"
          :total="ventas.total"
        />
      </div>
    </div>

    <!-- Modal Nueva Venta -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="6xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Nueva Venta
        </h3>
      </template>

      <form @submit.prevent="submitForm">
        <div class="space-y-6">
          <!-- Información General -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <InputLabel for="cliente_id" required>Cliente</InputLabel>
              <select
                id="cliente_id"
                v-model="form.cliente_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.cliente_id }"
                required
              >
                <option value="">Seleccionar cliente...</option>
                <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                  {{ cliente.nombre }}
                </option>
              </select>
              <p v-if="form.errors.cliente_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.cliente_id }}
              </p>
            </div>

            <div>
              <InputLabel for="fecha_venta" required>Fecha de Venta</InputLabel>
              <TextInput
                id="fecha_venta"
                v-model="form.fecha_venta"
                type="datetime-local"
                :error="form.errors.fecha_venta"
                required
              />
            </div>

            <div>
              <InputLabel for="tipo_pago" required>Tipo de Pago</InputLabel>
              <select
                id="tipo_pago"
                v-model="form.tipo_pago"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.tipo_pago }"
                required
              >
                <option value="">Seleccionar...</option>
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
              </select>
              <p v-if="form.errors.tipo_pago" class="mt-1 text-sm text-red-600">
                {{ form.errors.tipo_pago }}
              </p>
            </div>
          </div>

          <!-- Fecha de vencimiento si es crédito -->
          <div v-if="form.tipo_pago === 'credito'">
            <InputLabel for="fecha_vencimiento" required>Fecha de Vencimiento</InputLabel>
            <TextInput
              id="fecha_vencimiento"
              v-model="form.fecha_vencimiento"
              type="date"
              :error="form.errors.fecha_vencimiento"
              required
            />
          </div>

          <!-- Productos -->
          <div>
            <div class="flex justify-between items-center mb-3">
              <InputLabel>Productos</InputLabel>
              <SecondaryButton @click="agregarProducto" type="button">
                <Plus :size="16" class="mr-2" />
                Agregar Producto
              </SecondaryButton>
            </div>

            <div class="border rounded-lg overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Producto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Cantidad</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Precio</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Subtotal</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(detalle, index) in form.detalles" :key="index">
                    <td class="px-4 py-2">
                      <select
                        v-model="detalle.producto"
                        @change="onProductoChange(index)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                        required
                      >
                        <option value="">Seleccionar...</option>
                        <option v-for="prod in productos" :key="prod.codigo" :value="prod.codigo">
                          {{ prod.nombre }} (Stock: {{ prod.stock }})
                        </option>
                      </select>
                    </td>
                    <td class="px-4 py-2">
                      <input
                        v-model.number="detalle.cantidad"
                        type="number"
                        min="0.01"
                        step="0.01"
                        @input="calcularSubtotal(index)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                        required
                      />
                    </td>
                    <td class="px-4 py-2">
                      <input
                        v-model.number="detalle.precio_unitario"
                        type="number"
                        min="0"
                        step="0.01"
                        @input="calcularSubtotal(index)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                        required
                      />
                    </td>
                    <td class="px-4 py-2 text-sm font-semibold">
                      Bs. {{ calcularSubtotalDetalle(detalle).toFixed(2) }}
                    </td>
                    <td class="px-4 py-2">
                      <button
                        @click="eliminarProducto(index)"
                        type="button"
                        class="text-red-600 hover:text-red-800"
                      >
                        <Trash2 :size="18" />
                      </button>
                    </td>
                  </tr>
                  <tr v-if="form.detalles.length === 0">
                    <td colspan="5" class="px-4 py-4 text-center text-gray-500 text-sm">
                      No hay productos agregados
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="form.errors.detalles" class="mt-1 text-sm text-red-600">
              {{ form.errors.detalles }}
            </p>
          </div>

          <!-- Resumen -->
          <div class="bg-gray-50 p-4 rounded-lg space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-semibold">Bs. {{ subtotal.toFixed(2) }}</span>
            </div>

            <div class="flex justify-between items-center">
              <InputLabel for="descuento">Descuento:</InputLabel>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Bs.</span>
                <input
                  id="descuento"
                  v-model.number="form.descuento"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
              </div>
            </div>

            <div class="flex justify-between text-lg font-bold border-t pt-2">
              <span>Total:</span>
              <span class="text-blue-600">Bs. {{ total.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Notas -->
          <div>
            <InputLabel for="notas">Notas</InputLabel>
            <textarea
              id="notas"
              v-model="form.notas"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Notas adicionales..."
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeFormModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing || form.detalles.length === 0">
            <Loader v-if="form.processing" :size="16" class="mr-2 animate-spin" />
            Registrar Venta
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Cancelar Venta -->
    <Modal :show="showCancelModal" @close="closeCancelModal" max-width="md">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Cancelar Venta
        </h3>
      </template>

      <div v-if="selectedVenta">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas cancelar esta venta?
        </p>
        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
          <p class="text-sm text-yellow-800">
            <span class="font-semibold">Número:</span> {{ selectedVenta.numero_venta }}<br>
            <span class="font-semibold">Cliente:</span> {{ selectedVenta.cliente }}<br>
            <span class="font-semibold">Total:</span> Bs. {{ Number(selectedVenta.total).toFixed(2) }}
          </p>
        </div>
        <p class="mt-3 text-sm text-red-600 font-semibold">
          ⚠️ Esta acción revertirá el stock de los productos.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeCancelModal" type="button">
          No, mantener
        </SecondaryButton>
        <DangerButton @click="cancelVenta" :disabled="cancelForm.processing">
          <Loader v-if="cancelForm.processing" :size="16" class="mr-2 animate-spin" />
          Sí, cancelar venta
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { ShoppingCart, Plus, Eye, Trash2, Loader, XCircle } from 'lucide-vue-next';

const props = defineProps({
  ventas: Object,
  clientes: Array,
  productos: Array,
  filters: Object,
  can: Object
});

// Estados
const showFormModal = ref(false);
const showCancelModal = ref(false);
const selectedVenta = ref(null);

// Formulario de búsqueda
const searchForm = reactive({
  search: props.filters?.search || '',
  estado: props.filters?.estado || '',
  tipo_pago: props.filters?.tipo_pago || ''
});

// Formulario principal
const form = useForm({
  cliente_id: '',
  fecha_venta: new Date().toISOString().slice(0, 16),
  tipo_pago: '',
  fecha_vencimiento: '',
  descuento: 0,
  notas: '',
  detalles: []
});

const cancelForm = useForm({});

// Computed
const subtotal = computed(() => {
  return form.detalles.reduce((sum, detalle) => {
    return sum + (detalle.cantidad * detalle.precio_unitario);
  }, 0);
});

const total = computed(() => {
  return Math.max(0, subtotal.value - (form.descuento || 0));
});

// Funciones
const openCreateModal = () => {
  form.reset();
  form.fecha_venta = new Date().toISOString().slice(0, 16);
  form.detalles = [];
  form.clearErrors();
  showFormModal.value = true;
};

const closeFormModal = () => {
  showFormModal.value = false;
  form.reset();
};

const agregarProducto = () => {
  form.detalles.push({
    producto: '',
    cantidad: 1,
    precio_unitario: 0
  });
};

const eliminarProducto = (index) => {
  form.detalles.splice(index, 1);
};

const onProductoChange = (index) => {
  const productoSeleccionado = props.productos.find(
    p => p.codigo === form.detalles[index].producto
  );

  if (productoSeleccionado) {
    form.detalles[index].precio_unitario = productoSeleccionado.precio;
  }
};

const calcularSubtotalDetalle = (detalle) => {
  return (detalle.cantidad || 0) * (detalle.precio_unitario || 0);
};

const calcularSubtotal = (index) => {
  // Trigger reactivity
  form.detalles[index] = { ...form.detalles[index] };
};

const submitForm = () => {
  form.post(route('ventas.store'), {
    onSuccess: () => closeFormModal()
  });
};

const openCancelModal = (venta) => {
  selectedVenta.value = venta;
  showCancelModal.value = true;
};

const closeCancelModal = () => {
  showCancelModal.value = false;
  selectedVenta.value = null;
};

const cancelVenta = () => {
  cancelForm.delete(route('ventas.destroy', selectedVenta.value.codigo_venta), {
    onSuccess: () => closeCancelModal()
  });
};

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'completada': 'Completada',
    'cancelada': 'Cancelada'
  };
  return labels[estado] || estado;
};

const getEstadoBadgeClass = (estado) => {
  const classes = {
    'pendiente': 'px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800',
    'completada': 'px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800',
    'cancelada': 'px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800'
  };
  return classes[estado] || '';
};

// Debounce
const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

const applyFilters = () => {
  router.get(route('ventas.index'), searchForm, {
    preserveState: true,
    preserveScroll: true
  });
};

const clearFilters = () => {
  searchForm.search = '';
  searchForm.estado = '';
  searchForm.tipo_pago = '';
  applyFilters();
};
</script>
