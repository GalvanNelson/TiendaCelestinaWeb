<template>
  <AuthenticatedLayout title="Salidas de Stock">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Salidas de Stock
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Minus :size="16" class="mr-2" />
          Nueva Salida
        </PrimaryButton>
      </div>
    </template>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="search">Buscar</InputLabel>
          <TextInput
            id="search"
            v-model="searchForm.search"
            placeholder="Código, motivo o producto..."
            @input="debouncedSearch"
          />
        </div>
        <div>
          <InputLabel for="producto">Filtrar por Producto</InputLabel>
          <select
            id="producto"
            v-model="searchForm.producto"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todos los productos</option>
            <option v-for="prod in productos" :key="prod.codigo" :value="prod.codigo">
              {{ prod.nombre }}
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

    <!-- Tabla de salidas -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Código
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Producto
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Cantidad
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto Total
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Motivo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Usuario
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="salida in salidas.data" :key="salida.codigo_salida" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                {{ salida.codigo_salida }}
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">
                {{ salida.producto }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded font-semibold">
                  -{{ salida.cantidad }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                Bs. {{ Number(salida.monto_total).toFixed(2) }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ salida.motivo }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ salida.usuario }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ salida.fecha }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button
                  @click="openShowModal(salida)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver"
                >
                  <Eye :size="18" />
                </button>
                <button
                  v-if="can.edit"
                  @click="openEditModal(salida)"
                  class="text-yellow-600 hover:text-yellow-900 inline-flex items-center"
                  title="Editar"
                >
                  <Edit :size="18" />
                </button>
                <button
                  v-if="can.delete"
                  @click="openDeleteModal(salida)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Eliminar"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="salidas.data.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                No hay salidas de stock registradas
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="salidas.links && salidas.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="salidas.links"
          :from="salidas.from"
          :to="salidas.to"
          :total="salidas.total"
        />
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="2xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingSalida ? 'Editar Salida de Stock' : 'Nueva Salida de Stock' }}
        </h3>
      </template>

      <form @submit.prevent="submitForm">
        <div class="space-y-4">
          <div>
            <InputLabel for="codigo_producto" required>Producto</InputLabel>
            <select
              id="codigo_producto"
              v-model="form.codigo_producto"
              @change="onProductoChange"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.codigo_producto }"
              required
            >
              <option value="">Seleccionar producto...</option>
              <option v-for="prod in productos" :key="prod.codigo" :value="prod.codigo">
                {{ prod.nombre }} (Stock: {{ prod.stock_actual }} {{ prod.unidad_medida }})
              </option>
            </select>
            <p v-if="form.errors.codigo_producto" class="mt-1 text-sm text-red-600">
              {{ form.errors.codigo_producto }}
            </p>
          </div>

          <div v-if="selectedProducto" class="p-3 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-800">
              <span class="font-semibold">Stock actual:</span>
              {{ selectedProducto.stock_actual }} {{ selectedProducto.unidad_medida }}
            </p>
          </div>

          <!-- Alerta de stock bajo -->
          <div v-if="selectedProducto && selectedProducto.stock_actual < 10" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
              ⚠️ <span class="font-semibold">Stock bajo:</span> Este producto tiene poco stock disponible
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="cantidad" required>Cantidad a Retirar</InputLabel>
              <TextInput
                id="cantidad"
                v-model="form.cantidad"
                type="number"
                min="1"
                :max="selectedProducto?.stock_actual || undefined"
                :error="form.errors.cantidad"
                required
                placeholder="0"
              />
              <p v-if="stockInsuficiente" class="mt-1 text-sm text-red-600">
                ⚠️ La cantidad no puede ser mayor al stock disponible
              </p>
            </div>

            <div>
              <InputLabel for="fecha" required>Fecha</InputLabel>
              <TextInput
                id="fecha"
                v-model="form.fecha"
                type="datetime-local"
                :error="form.errors.fecha"
                required
              />
            </div>
          </div>

          <div>
            <InputLabel for="motivo" required>Motivo de la Salida</InputLabel>
            <select
              id="motivo"
              v-model="form.motivo"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.motivo }"
              required
            >
              <option value="">Seleccionar motivo...</option>
              <option value="Venta">Venta</option>
              <option value="Devolución a proveedor">Devolución a proveedor</option>
              <option value="Producto dañado">Producto dañado</option>
              <option value="Producto vencido">Producto vencido</option>
              <option value="Uso interno">Uso interno</option>
              <option value="Transferencia entre sucursales">Transferencia entre sucursales</option>
              <option value="Merma">Merma</option>
              <option value="Corrección de error">Corrección de error</option>
              <option value="Otro">Otro</option>
            </select>
            <p v-if="form.errors.motivo" class="mt-1 text-sm text-red-600">
              {{ form.errors.motivo }}
            </p>
          </div>

          <!-- Campo opcional si selecciona "Otro" -->
          <div v-if="form.motivo === 'Otro'">
            <InputLabel for="motivo_detalle">Especificar motivo</InputLabel>
            <TextInput
              id="motivo_detalle"
              v-model="form.motivo_detalle"
              placeholder="Describe el motivo específico..."
              maxlength="100"
            />
          </div>

          <div v-if="form.cantidad && selectedProducto && !stockInsuficiente" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800 font-semibold">Nuevo stock estimado:</p>
            <p class="text-2xl text-red-900 font-bold mt-1">
              {{ Number(selectedProducto.stock_actual) - Number(form.cantidad) }} {{ selectedProducto.unidad_medida }}
            </p>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeFormModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing || stockInsuficiente">
            <Loader v-if="form.processing" :size="16" class="mr-2 animate-spin" />
            {{ editingSalida ? 'Actualizar' : 'Registrar Salida' }}
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Ver -->
    <Modal :show="showViewModal" @close="closeViewModal" max-width="lg">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Detalles de la Salida de Stock
        </h3>
      </template>

      <div v-if="selectedSalida" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-500">Código de Salida</label>
          <p class="mt-1 text-base text-gray-900 font-mono">{{ selectedSalida.codigo_salida }}</p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Producto</label>
          <p class="mt-1 text-base text-gray-900 font-semibold">{{ selectedSalida.producto }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Cantidad Retirada</label>
            <p class="mt-1 text-xl text-red-600 font-bold">-{{ selectedSalida.cantidad }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Monto Total</label>
            <p class="mt-1 text-xl text-gray-900 font-bold">
              Bs. {{ Number(selectedSalida.monto_total).toFixed(2) }}
            </p>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Motivo</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedSalida.motivo }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Usuario</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedSalida.usuario }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Fecha</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedSalida.fecha }}</p>
          </div>
        </div>
      </div>

      <div class="flex justify-end mt-6">
        <SecondaryButton @click="closeViewModal">
          Cerrar
        </SecondaryButton>
      </div>
    </Modal>

    <!-- Modal Eliminar -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Eliminar Salida de Stock
        </h3>
      </template>

      <div v-if="selectedSalida">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas eliminar esta salida de stock?
        </p>
        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
          <p class="text-sm text-yellow-800">
            <span class="font-semibold">Producto:</span> {{ selectedSalida.producto }}<br>
            <span class="font-semibold">Cantidad:</span> {{ selectedSalida.cantidad }}<br>
            <span class="font-semibold">Fecha:</span> {{ selectedSalida.fecha }}
          </p>
        </div>
        <p class="mt-3 text-sm text-red-600 font-semibold">
          ⚠️ Esta acción revertirá el stock del producto y no se puede deshacer.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeDeleteModal" type="button">
          Cancelar
        </SecondaryButton>
        <DangerButton @click="deleteSalida" :disabled="deleteForm.processing">
          <Loader v-if="deleteForm.processing" :size="16" class="mr-2 animate-spin" />
          Eliminar
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Minus, Eye, Edit, Trash2, Loader } from 'lucide-vue-next';

const props = defineProps({
  salidas: Object,
  productos: Array,
  filters: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const editingSalida = ref(null);
const selectedSalida = ref(null);

// Formulario de búsqueda
const searchForm = reactive({
  search: props.filters?.search || '',
  producto: props.filters?.producto || ''
});

// Formulario principal
const form = useForm({
  codigo_producto: '',
  cantidad: '',
  motivo: '',
  motivo_detalle: '',
  fecha: new Date().toISOString().slice(0, 16)
});

const deleteForm = useForm({});

// Producto seleccionado
const selectedProducto = computed(() => {
  if (!form.codigo_producto) return null;
  return props.productos.find(p => p.codigo === form.codigo_producto);
});

// Verificar si hay stock insuficiente
const stockInsuficiente = computed(() => {
  if (!selectedProducto.value || !form.cantidad) return false;
  return Number(form.cantidad) > selectedProducto.value.stock_actual;
});

// Funciones para abrir modales
const openCreateModal = () => {
  editingSalida.value = null;
  form.reset();
  form.fecha = new Date().toISOString().slice(0, 16);
  form.clearErrors();
  showFormModal.value = true;
};

const openEditModal = (salida) => {
  editingSalida.value = salida;
  form.codigo_producto = salida.codigo_producto;
  form.cantidad = salida.cantidad;
  form.motivo = salida.motivo;
  form.motivo_detalle = '';
  form.fecha = new Date(salida.fecha_raw).toISOString().slice(0, 16);
  form.clearErrors();
  showFormModal.value = true;
};

const openShowModal = (salida) => {
  selectedSalida.value = salida;
  showViewModal.value = true;
};

const openDeleteModal = (salida) => {
  selectedSalida.value = salida;
  showDeleteModal.value = true;
};

// Funciones para cerrar modales
const closeFormModal = () => {
  showFormModal.value = false;
  editingSalida.value = null;
  form.reset();
  form.clearErrors();
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedSalida.value = null;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedSalida.value = null;
};

// Cambio de producto
const onProductoChange = () => {
  form.cantidad = '';
};

// Submit del formulario
const submitForm = () => {
  if (stockInsuficiente.value) {
    return;
  }

  // Si seleccionó "Otro" y escribió un detalle, usar el detalle
  const motivoFinal = form.motivo === 'Otro' && form.motivo_detalle
    ? `Otro: ${form.motivo_detalle}`
    : form.motivo;

  const formData = {
    ...form.data(),
    motivo: motivoFinal
  };

  if (editingSalida.value) {
    form.put(route('productos.salidas-stock.update', editingSalida.value.codigo_salida), {
      onSuccess: () => closeFormModal()
    });
  } else {
    form.post(route('productos.salidas-stock.store'), {
      onSuccess: () => closeFormModal()
    });
  }
};

// Eliminar salida
const deleteSalida = () => {
  deleteForm.delete(route('productos.salidas-stock.destroy', selectedSalida.value.codigo_salida), {
    onSuccess: () => closeDeleteModal()
  });
};

// Función debounce
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

// Búsqueda con debounce
const debouncedSearch = debounce(() => {
applyFilters();
}, 500);
// Aplicar filtros
const applyFilters = () => {
router.get(route('productos.salidas-stock.index'), searchForm, {
preserveState: true,
preserveScroll: true
});
};
// Limpiar filtros
const clearFilters = () => {
searchForm.search = '';
searchForm.producto = '';
applyFilters();
};
</script>
