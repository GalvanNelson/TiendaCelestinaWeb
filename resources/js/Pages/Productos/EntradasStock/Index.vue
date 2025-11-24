<template>
  <AuthenticatedLayout title="Entradas de Stock">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Entradas de Stock
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Plus :size="16" class="mr-2" />
          Nueva Entrada
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

    <!-- Tabla de entradas -->
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
            <tr v-for="entrada in entradas.data" :key="entrada.codigo_entrada" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                {{ entrada.codigo_entrada }}
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">
                {{ entrada.producto }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span class="px-2 py-1 bg-green-100 text-green-800 rounded font-semibold">
                  +{{ entrada.cantidad }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                Bs. {{ Number(entrada.monto_total).toFixed(2) }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ entrada.motivo }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ entrada.usuario }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ entrada.fecha }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button
                  @click="openShowModal(entrada)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver"
                >
                  <Eye :size="18" />
                </button>
                <button
                  v-if="can.edit"
                  @click="openEditModal(entrada)"
                  class="text-yellow-600 hover:text-yellow-900 inline-flex items-center"
                  title="Editar"
                >
                  <Edit :size="18" />
                </button>
                <button
                  v-if="can.delete"
                  @click="openDeleteModal(entrada)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Eliminar"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="entradas.data.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                No hay entradas de stock registradas
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="entradas.links && entradas.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="entradas.links"
          :from="entradas.from"
          :to="entradas.to"
          :total="entradas.total"
        />
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="2xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingEntrada ? 'Editar Entrada de Stock' : 'Nueva Entrada de Stock' }}
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

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="cantidad" required>Cantidad a Ingresar</InputLabel>
              <TextInput
                id="cantidad"
                v-model="form.cantidad"
                type="number"
                min="1"
                :error="form.errors.cantidad"
                required
                placeholder="0"
              />
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
            <InputLabel for="motivo" required>Motivo de la Entrada</InputLabel>
            <select
              id="motivo"
              v-model="form.motivo"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.motivo }"
              required
            >
              <option value="">Seleccionar motivo...</option>
              <option value="Compra a proveedor">Compra a proveedor</option>
              <option value="Devolución de cliente">Devolución de cliente</option>
              <option value="Ajuste de inventario">Ajuste de inventario</option>
              <option value="Producción interna">Producción interna</option>
              <option value="Donación recibida">Donación recibida</option>
              <option value="Transferencia entre sucursales">Transferencia entre sucursales</option>
              <option value="Reposición de stock">Reposición de stock</option>
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

          <div v-if="form.cantidad && selectedProducto" class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800 font-semibold">Nuevo stock estimado:</p>
            <p class="text-2xl text-green-900 font-bold mt-1">
              {{ Number(selectedProducto.stock_actual) + Number(form.cantidad) }} {{ selectedProducto.unidad_medida }}
            </p>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeFormModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            <Loader v-if="form.processing" :size="16" class="mr-2 animate-spin" />
            {{ editingEntrada ? 'Actualizar' : 'Registrar Entrada' }}
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Ver -->
    <Modal :show="showViewModal" @close="closeViewModal" max-width="lg">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Detalles de la Entrada de Stock
        </h3>
      </template>

      <div v-if="selectedEntrada" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-500">Código de Entrada</label>
          <p class="mt-1 text-base text-gray-900 font-mono">{{ selectedEntrada.codigo_entrada }}</p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Producto</label>
          <p class="mt-1 text-base text-gray-900 font-semibold">{{ selectedEntrada.producto }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Cantidad Ingresada</label>
            <p class="mt-1 text-xl text-green-600 font-bold">+{{ selectedEntrada.cantidad }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Monto Total</label>
            <p class="mt-1 text-xl text-gray-900 font-bold">
              Bs. {{ Number(selectedEntrada.monto_total).toFixed(2) }}
            </p>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Motivo</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedEntrada.motivo }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Usuario</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedEntrada.usuario }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Fecha</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedEntrada.fecha }}</p>
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
          Eliminar Entrada de Stock
        </h3>
      </template>

      <div v-if="selectedEntrada">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas eliminar esta entrada de stock?
        </p>
        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
          <p class="text-sm text-yellow-800">
            <span class="font-semibold">Producto:</span> {{ selectedEntrada.producto }}<br>
            <span class="font-semibold">Cantidad:</span> {{ selectedEntrada.cantidad }}<br>
            <span class="font-semibold">Fecha:</span> {{ selectedEntrada.fecha }}
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
        <DangerButton @click="deleteEntrada" :disabled="deleteForm.processing">
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
import { Plus, Eye, Edit, Trash2, Loader } from 'lucide-vue-next';

const props = defineProps({
  entradas: Object,
  productos: Array,
  filters: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const editingEntrada = ref(null);
const selectedEntrada = ref(null);

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

// Funciones para abrir modales
const openCreateModal = () => {
  editingEntrada.value = null;
  form.reset();
  form.fecha = new Date().toISOString().slice(0, 16);
  form.clearErrors();
  showFormModal.value = true;
};

const openEditModal = (entrada) => {
  editingEntrada.value = entrada;
  form.codigo_producto = entrada.codigo_producto;
  form.cantidad = entrada.cantidad;
  form.motivo = entrada.motivo;
  form.motivo_detalle = '';
  form.fecha = new Date(entrada.fecha_raw).toISOString().slice(0, 16);
  form.clearErrors();
  showFormModal.value = true;
};

const openShowModal = (entrada) => {
  selectedEntrada.value = entrada;
  showViewModal.value = true;
};

const openDeleteModal = (entrada) => {
  selectedEntrada.value = entrada;
  showDeleteModal.value = true;
};

// Funciones para cerrar modales
const closeFormModal = () => {
  showFormModal.value = false;
  editingEntrada.value = null;
  form.reset();
  form.clearErrors();
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedEntrada.value = null;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedEntrada.value = null;
};

// Cambio de producto
const onProductoChange = () => {
  // Opcional: resetear cantidad al cambiar producto
};

// Submit del formulario
const submitForm = () => {
  // Si seleccionó "Otro" y escribió un detalle, usar el detalle
  const motivoFinal = form.motivo === 'Otro' && form.motivo_detalle
    ? `Otro: ${form.motivo_detalle}`
    : form.motivo;

  const formData = {
    ...form.data(),
    motivo: motivoFinal
  };

  if (editingEntrada.value) {
    form.put(route('productos.entradas-stock.update', editingEntrada.value.codigo_entrada), {
      onSuccess: () => closeFormModal()
    });
  } else {
    form.post(route('productos.entradas-stock.store'), {
      onSuccess: () => closeFormModal()
    });
  }
};

// Eliminar entrada
const deleteEntrada = () => {
  deleteForm.delete(route('productos.entradas-stock.destroy', selectedEntrada.value.codigo_entrada), {
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
  router.get(route('productos.entradas-stock.index'), searchForm, {
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
