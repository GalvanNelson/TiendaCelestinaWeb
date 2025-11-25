<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Ventas</h1>
        <p class="text-gray-600 mt-1">Gestiona las ventas del sistema</p>
      </div>
      <button
        v-if="can.create"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nueva Venta
      </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Número de venta o cliente..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
          <select
            v-model="filters.estado"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @change="applyFilters"
          >
            <option value="">Todos</option>
            <option value="completada">Completada</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Pago</label>
          <select
            v-model="filters.tipo_pago"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @change="applyFilters"
          >
            <option value="">Todos</option>
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Venta</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendedor</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo Pago</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
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
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ venta.vendedor }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ venta.fecha_venta }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full',
                  venta.tipo_pago === 'contado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                ]">
                  {{ venta.tipo_pago === 'contado' ? 'Contado' : 'Crédito' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                Bs. {{ formatNumber(venta.total) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full',
                  venta.estado === 'completada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]">
                  {{ venta.estado === 'completada' ? 'Completada' : 'Cancelada' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <button
                  @click="viewVenta(venta.codigo_venta)"
                  class="text-blue-600 hover:text-blue-900"
                  title="Ver detalles"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button
                  v-if="can.delete && venta.estado === 'completada'"
                  @click="confirmCancel(venta)"
                  class="text-red-600 hover:text-red-900"
                  title="Cancelar venta"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Mostrando {{ ventas.from }} a {{ ventas.to }} de {{ ventas.total }} resultados
        </div>
        <div class="flex gap-2">
          <button
            v-for="link in ventas.links"
            :key="link.label"
            @click="changePage(link.url)"
            :disabled="!link.url"
            :class="[
              'px-3 py-1 text-sm rounded',
              link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
              !link.url ? 'opacity-50 cursor-not-allowed' : ''
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <!-- Modal Nueva Venta -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeCreateModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
          <h2 class="text-2xl font-bold text-gray-900">Nueva Venta</h2>
          <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cliente *</label>
              <select
                v-model="form.cliente_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Seleccione un cliente</option>
                <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                  {{ cliente.nombre }}
                </option>
              </select>
              <span v-if="errors.cliente_id" class="text-red-600 text-sm">{{ errors.cliente_id }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Venta *</label>
              <input
                v-model="form.fecha_venta"
                type="datetime-local"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Pago *</label>
              <select
                v-model="form.tipo_pago"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
              </select>
            </div>

            <div v-if="form.tipo_pago === 'credito'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Vencimiento *</label>
              <input
                v-model="form.fecha_vencimiento"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
              <span v-if="errors.fecha_vencimiento" class="text-red-600 text-sm">{{ errors.fecha_vencimiento }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Descuento (Bs.)</label>
              <input
                v-model.number="form.descuento"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Productos -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Productos *</h3>
              <button
                @click="addProducto"
                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
              >
                + Agregar Producto
              </button>
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
                    class="px-2 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <span v-if="errors.detalles" class="text-red-600 text-sm">{{ errors.detalles }}</span>
          </div>

          <!-- Totales -->
          <div class="bg-gray-50 rounded-lg p-4 mb-6">
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
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
            <textarea
              v-model="form.notas"
              rows="3"
              maxlength="1000"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Observaciones adicionales..."
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3">
            <button
              @click="closeCreateModal"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              @click="submitVenta"
              :disabled="processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ processing ? 'Guardando...' : 'Guardar Venta' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Confirmar Cancelación -->
    <div
      v-if="showCancelModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showCancelModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Cancelación</h3>
        <p class="text-gray-600 mb-6">
          ¿Está seguro de cancelar la venta <strong>{{ ventaToCancel?.numero_venta }}</strong>?
          Esta acción revertirá el stock de los productos.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="showCancelModal = false"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
          >
            No, volver
          </button>
          <button
            @click="cancelVenta"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
          >
            Sí, cancelar venta
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  ventas: Object,
  clientes: Array,
  productos: Array,
  filters: Object,
  can: Object,
});

const showCreateModal = ref(false);
const showCancelModal = ref(false);
const ventaToCancel = ref(null);
const processing = ref(false);
const errors = ref({});

const filters = ref({
  search: props.filters.search || '',
  estado: props.filters.estado || '',
  tipo_pago: props.filters.tipo_pago || '',
});

const form = ref({
  cliente_id: '',
  fecha_venta: new Date().toISOString().slice(0, 16),
  tipo_pago: 'contado',
  descuento: 0,
  notas: '',
  fecha_vencimiento: '',
  detalles: [],
});

let debounceTimer = null;

const calcularSubtotal = computed(() => {
  return form.value.detalles.reduce((sum, det) => {
    return sum + (det.cantidad * det.precio_unitario);
  }, 0);
});

const calcularTotal = computed(() => {
  return calcularSubtotal.value - (form.value.descuento || 0);
});

const debouncedSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    applyFilters();
  }, 500);
};

const applyFilters = () => {
  router.get(route('ventas.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

const changePage = (url) => {
  if (!url) return;
  router.get(url, {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const addProducto = () => {
  form.value.detalles.push({
    producto: '',
    cantidad: 1,
    precio_unitario: 0,
  });
};

const removeProducto = (index) => {
  form.value.detalles.splice(index, 1);
};

const updateProductoInfo = (index) => {
  const producto = props.productos.find(
    p => p.codigo === form.value.detalles[index].producto
  );
  if (producto) {
    form.value.detalles[index].precio_unitario = producto.precio;
  }
};

const calculateSubtotal = (index) => {
  const detalle = form.value.detalles[index];
  detalle.subtotal = detalle.cantidad * detalle.precio_unitario;
};

const submitVenta = () => {
  processing.value = true;
  errors.value = {};

  router.post(route('ventas.store'), form.value, {
    onSuccess: () => {
      closeCreateModal();
    },
    onError: (err) => {
      errors.value = err;
      processing.value = false;
    },
    onFinish: () => {
      processing.value = false;
    },
  });
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  form.value = {
    cliente_id: '',
    fecha_venta: new Date().toISOString().slice(0, 16),
    tipo_pago: 'contado',
    descuento: 0,
    notas: '',
    fecha_vencimiento: '',
    detalles: [],
  };
  errors.value = {};
};

const viewVenta = (codigoVenta) => {
  router.get(route('ventas.show', codigoVenta));
};

const confirmCancel = (venta) => {
  ventaToCancel.value = venta;
  showCancelModal.value = true;
};

const cancelVenta = () => {
  router.delete(route('ventas.destroy', ventaToCancel.value.codigo_venta), {
    onSuccess: () => {
      showCancelModal.value = false;
      ventaToCancel.value = null;
    },
  });
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0);
};

onMounted(() => {
  // Inicialización si es necesaria
});
</script>
