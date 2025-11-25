<script setup>
import { ref, reactive, computed } from 'vue';
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
import { Plus, Eye, X, Loader, ShoppingCart, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  ventas: Object,
  clientes: Array,
  productos: Array,
  filters: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
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
  tipo_pago: 'contado',
  descuento: 0,
  notas: '',
  fecha_vencimiento: '',
  detalles: []
});

const deleteForm = useForm({});

// Computed
const calcularSubtotal = computed(() => {
  return form.detalles.reduce((sum, det) => {
    return sum + (det.cantidad * det.precio_unitario);
  }, 0);
});

const calcularTotal = computed(() => {
  return calcularSubtotal.value - (form.descuento || 0);
});

// Funciones para abrir modales
const openCreateModal = () => {
  form.reset();
  form.clearErrors();
  form.cliente_id = '';
  form.fecha_venta = new Date().toISOString().slice(0, 16);
  form.tipo_pago = 'contado';
  form.descuento = 0;
  form.notas = '';
  form.fecha_vencimiento = '';
  form.detalles = [];
  showFormModal.value = true;
};

const openShowModal = (venta) => {
  selectedVenta.value = venta;
  showViewModal.value = true;
};

const openDeleteModal = (venta) => {
  selectedVenta.value = venta;
  showDeleteModal.value = true;
};

// Funciones para cerrar modales
const closeFormModal = () => {
  showFormModal.value = false;
  form.reset();
  form.clearErrors();
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedVenta.value = null;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedVenta.value = null;
};

// Manejo de productos en detalles
const addProducto = () => {
  form.detalles.push({
    producto: '',
    cantidad: 1,
    precio_unitario: 0
  });
};

const removeProducto = (index) => {
  form.detalles.splice(index, 1);
};

const updateProductoInfo = (index) => {
  const producto = props.productos.find(
    p => p.codigo === form.detalles[index].producto
  );
  if (producto) {
    form.detalles[index].precio_unitario = producto.precio;
  }
};

const calculateSubtotal = (index) => {
  const detalle = form.detalles[index];
  detalle.subtotal = detalle.cantidad * detalle.precio_unitario;
};

// Submit del formulario
const submitForm = () => {
  form.post(route('ventas.store'), {
    onSuccess: () => closeFormModal()
  });
};

// Eliminar venta (cancelar)
const deleteVenta = () => {
  deleteForm.delete(route('ventas.destroy', selectedVenta.value.codigo_venta), {
    onSuccess: () => closeDeleteModal()
  });
};

// Búsqueda con debounce
const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

// Aplicar filtros
const applyFilters = () => {
  router.get(route('ventas.index'), searchForm, {
    preserveState: true,
    preserveScroll: true
  });
};

// Limpiar filtros
const clearFilters = () => {
  searchForm.search = '';
  searchForm.estado = '';
  searchForm.tipo_pago = '';
  applyFilters();
};

// Formatear número
const formatNumber = (value) => {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value || 0);
};

// Formatear fecha
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <AuthenticatedLayout title="Ventas">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Ventas
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Plus :size="16" class="mr-2" />
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
            Limpiar Filtros
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
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                N° Venta
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Cliente
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Vendedor
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tipo Pago
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Total
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
            <tr v-for="venta in ventas.data" :key="venta.codigo_venta" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ venta.numero_venta }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ venta.cliente }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ venta.vendedor }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ venta.fecha_venta }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-semibold',
                    venta.tipo_pago === 'contado'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-yellow-100 text-yellow-800'
                  ]"
                >
                  {{ venta.tipo_pago === 'contado' ? 'Contado' : 'Crédito' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                Bs. {{ formatNumber(venta.total) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-semibold',
                    venta.estado === 'completada'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ venta.estado === 'completada' ? 'Completada' : 'Cancelada' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button
                  @click="openShowModal(venta)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver"
                >
                  <Eye :size="18" />
                </button>
                <button
                  v-if="can.delete && venta.estado === 'completada'"
                  @click="openDeleteModal(venta)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Cancelar venta"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="ventas.data.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500">
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

    <!-- Modal Crear Venta -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="4xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Nueva Venta
        </h3>
      </template>

      <form @submit.prevent="submitForm">
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="cliente_id" required>Cliente</InputLabel>
              <select
                id="cliente_id"
                v-model="form.cliente_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.cliente_id }"
                required
              >
                <option value="">Seleccione un cliente</option>
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
              <input
                id="fecha_venta"
                v-model="form.fecha_venta"
                type="datetime-local"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              />
            </div>

            <div>
              <InputLabel for="tipo_pago" required>Tipo de Pago</InputLabel>
              <select
                id="tipo_pago"
                v-model="form.tipo_pago"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              >
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
              </select>
            </div>

            <div v-if="form.tipo_pago === 'credito'">
              <InputLabel for="fecha_vencimiento" required>Fecha de Vencimiento</InputLabel>
              <input
                id="fecha_vencimiento"
                v-model="form.fecha_vencimiento"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.fecha_vencimiento }"
              />
              <p v-if="form.errors.fecha_vencimiento" class="mt-1 text-sm text-red-600">
                {{ form.errors.fecha_vencimiento }}
              </p>
            </div>

            <div>
              <InputLabel for="descuento">Descuento (Bs.)</InputLabel>
              <TextInput
                id="descuento"
                v-model="form.descuento"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
              />
            </div>
          </div>

          <!-- Productos -->
          <div>
            <div class="flex justify-between items-center mb-3">
              <InputLabel required>Productos</InputLabel>
              <SecondaryButton @click="addProducto" type="button">
                <Plus :size="16" class="mr-2" />
                Agregar Producto
              </SecondaryButton>
            </div>

            <div class="space-y-3">
              <div
                v-for="(detalle, index) in form.detalles"
                :key="index"
                class="grid grid-cols-12 gap-3 items-end bg-gray-50 p-3 rounded-lg"
              >
                <div class="col-span-5">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Producto</label>
                  <select
                    v-model="detalle.producto"
                    @change="updateProductoInfo(index)"
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Seleccione...</option>
                    <option v-for="prod in productos" :key="prod.codigo" :value="prod.codigo">
                      {{ prod.nombre }} (Stock: {{ prod.stock }} {{ prod.unidad }})
                    </option>
                  </select>
                </div>

                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad</label>
                  <input
                    v-model.number="detalle.cantidad"
                    type="number"
                    step="0.01"
                    min="0.01"
                    @input="calculateSubtotal(index)"
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Precio Unit.</label>
                  <input
                    v-model.number="detalle.precio_unitario"
                    type="number"
                    step="0.01"
                    min="0"
                    @input="calculateSubtotal(index)"
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Subtotal</label>
                  <input
                    :value="formatNumber(detalle.cantidad * detalle.precio_unitario)"
                    disabled
                    class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm"
                  />
                </div>

                <div class="col-span-1 flex justify-end">
                  <button
                    @click="removeProducto(index)"
                    type="button"
                    class="p-2 text-red-600 hover:text-red-900"
                  >
                    <X :size="18" />
                  </button>
                </div>
              </div>
            </div>
            <p v-if="form.errors.detalles" class="mt-1 text-sm text-red-600">
              {{ form.errors.detalles }}
            </p>
          </div>

          <!-- Totales -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-semibold">Bs. {{ formatNumber(calcularSubtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Descuento:</span>
                <span class="font-semibold">Bs. {{ formatNumber(form.descuento || 0) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold border-t pt-2">
                <span>Total:</span>
                <span class="text-blue-600">Bs. {{ formatNumber(calcularTotal) }}</span>
              </div>
            </div>
          </div>

          <!-- Notas -->
          <div>
            <InputLabel for="notas">Notas</InputLabel>
            <textarea
              id="notas"
              v-model="form.notas"
              rows="3"
              maxlength="1000"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Observaciones adicionales..."
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeFormModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            <Loader v-if="form.processing" :size="16" class="mr-2 animate-spin" />
            Guardar Venta
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Ver -->
    <Modal :show="showViewModal" @close="closeViewModal" max-width="3xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Detalles de la Venta
        </h3>
      </template>

      <div v-if="selectedVenta" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">N° Venta</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedVenta.numero_venta }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Cliente</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedVenta.cliente }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Vendedor</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedVenta.vendedor }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Fecha de Venta</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedVenta.fecha_venta }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Tipo de Pago</label>
            <p class="mt-1">
              <span
                :class="[
                  'px-2 py-1 rounded-full text-xs font-semibold',
                  selectedVenta.tipo_pago === 'contado'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-yellow-100 text-yellow-800'
                ]"
              >
                {{ selectedVenta.tipo_pago === 'contado' ? 'Contado' : 'Crédito' }}
              </span>
            </p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Estado</label>
            <p class="mt-1">
              <span
                :class="[
                  'px-2 py-1 rounded-full text-xs font-semibold',
                  selectedVenta.estado === 'completada'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'
                ]"
              >
                {{ selectedVenta.estado === 'completada' ? 'Completada' : 'Cancelada' }}
              </span>
            </p>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Total</label>
          <p class="mt-1 text-xl font-bold text-blue-600">
            Bs. {{ formatNumber(selectedVenta.total) }}
          </p>
        </div>

        <div v-if="selectedVenta.notas">
          <label class="text-sm font-medium text-gray-500">Notas</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedVenta.notas }}</p>
        </div>
      </div>

      <div class="flex justify-end mt-6">
        <SecondaryButton @click="closeViewModal">
          Cerrar
        </SecondaryButton>
      </div>
    </Modal>

    <!-- Modal Eliminar (Cancelar) -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Cancelar Venta
        </h3>
      </template>

      <div v-if="selectedVenta">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas cancelar la venta
          <span class="font-semibold">{{ selectedVenta.numero_venta }}</span>?
        </p>
        <p class="mt-2 text-sm text-red-600">
          Esta acción revertirá el stock de los productos vendidos.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeDeleteModal" type="button">
          No, volver
        </SecondaryButton>
        <DangerButton @click="deleteVenta" :disabled="deleteForm.processing">
          <Loader v-if="deleteForm.processing" :size="16" class="mr-2 animate-spin" />
          Sí, cancelar venta
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
