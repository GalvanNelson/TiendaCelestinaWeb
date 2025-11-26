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
import { Plus, Eye, Edit, Trash2, Loader, Package } from 'lucide-vue-next';

const props = defineProps({
  productos: Object,
  categorias: Array,
  unidades: Array,
  filters: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const editingProducto = ref(null);
const selectedProducto = ref(null);
const imagePreview = ref(null);

// Formulario de búsqueda
const searchForm = reactive({
  search: props.filters?.search || '',
  categoria: props.filters?.categoria || ''
});

// Formulario principal
const form = useForm({
  nombre: '',
  descripcion: '',
  precio_unitario: '',
  stock: '',
  categoria_codigo: '',
  unidad_codigo: '',
  imagen: null
});

const deleteForm = useForm({});

// Funciones para abrir modales
const openCreateModal = () => {
  editingProducto.value = null;
  imagePreview.value = null;
  form.reset();
  form.clearErrors();
  showFormModal.value = true;
};

const openEditModal = (producto) => {

  editingProducto.value = producto;
  imagePreview.value = null;

  form.nombre = producto.nombre;
  form.descripcion = producto.descripcion || '';
  form.precio_unitario = producto.precio_unitario;
  form.stock = producto.stock;
  form.categoria_codigo = Number(producto.categoria_codigo);
  form.unidad_codigo = Number(producto.unidad_codigo);
  form.imagen = null;
  form.clearErrors();

  showFormModal.value = true;
};

const openShowModal = (producto) => {
  selectedProducto.value = producto;
  showViewModal.value = true;
};

const openDeleteModal = (producto) => {
  selectedProducto.value = producto;
  showDeleteModal.value = true;
};

// Funciones para cerrar modales
const closeFormModal = () => {
  showFormModal.value = false;
  editingProducto.value = null;
  imagePreview.value = null;
  form.reset();
  form.clearErrors();
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedProducto.value = null;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedProducto.value = null;
};

// Manejar cambio de imagen
const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.imagen = file;

    // Crear preview
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const submitForm = () => {
  if (editingProducto.value) {
    form.transform((data) => ({
      ...data,
      _method: 'PUT',
      // Convertir a número solo al enviar
      categoria_codigo: Number(data.categoria_codigo),
      unidad_codigo: Number(data.unidad_codigo)
    })).post(route('productos.update', editingProducto.value.codigo_producto), {
      onSuccess: () => {
        closeFormModal();
      },
      forceFormData: true,
      preserveScroll: true
    });
  } else {
    form.post(route('productos.store'), {
      onSuccess: () => closeFormModal(),
      forceFormData: true,
      preserveScroll: true
    });
  }
};

// Eliminar producto
const deleteProducto = () => {
  deleteForm.delete(route('productos.destroy', selectedProducto.value.codigo_producto), {
    onSuccess: () => closeDeleteModal()
  });
};

// Búsqueda con debounce
const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

// Aplicar filtros
const applyFilters = () => {
  router.get(route('productos.index'), searchForm, {
    preserveState: true,
    preserveScroll: true
  });
};

// Limpiar filtros
const clearFilters = () => {
  searchForm.search = '';
  searchForm.categoria = '';
  applyFilters();
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
  <AuthenticatedLayout title="Productos">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Productos
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Plus :size="16" class="mr-2" />
          Nuevo Producto
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
            placeholder="Nombre o código..."
            @input="debouncedSearch"
          />
        </div>
        <div>
          <InputLabel for="categoria">Categoría</InputLabel>
          <select
            id="categoria"
            v-model="searchForm.categoria"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todas las categorías</option>
            <option v-for="cat in categorias" :key="cat.codigo" :value="cat.codigo">
              {{ cat.nombre }}
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

    <!-- Tabla de productos -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Imagen
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Código
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nombre
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Categoría
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Precio
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Stock
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="producto in productos.data" :key="producto.codigo_producto" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <img
                  v-if="producto.imagen"
                  :src="producto.imagen"
                  :alt="producto.nombre"
                  class="h-12 w-12 object-cover rounded"
                />
                <div v-else class="h-12 w-12 bg-gray-200 rounded flex items-center justify-center">
                  <Package :size="24" class="text-gray-400" />
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ producto.codigo_producto }}
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">
                {{ producto.nombre }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ producto.categoria }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                Bs. {{ Number(producto.precio_unitario).toFixed(2) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-semibold',
                    producto.stock > 10
                      ? 'bg-green-100 text-green-800'
                      : producto.stock > 0
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ producto.stock }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button
                  @click="openShowModal(producto)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver"
                >
                  <Eye :size="18" />
                </button>
                <button
                  v-if="can.edit"
                  @click="openEditModal(producto)"
                  class="text-yellow-600 hover:text-yellow-900 inline-flex items-center"
                  title="Editar"
                >
                  <Edit :size="18" />
                </button>
                <button
                  v-if="can.delete"
                  @click="openDeleteModal(producto)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Eliminar"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="productos.data.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                No hay productos registrados
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="productos.links && productos.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="productos.links"
          :from="productos.from"
          :to="productos.to"
          :total="productos.total"
        />
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="3xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingProducto ? 'Editar Producto' : 'Nuevo Producto' }}
        </h3>
      </template>

      <form @submit.prevent="submitForm" enctype="multipart/form-data">
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="nombre" required>Nombre del Producto</InputLabel>
              <TextInput
                id="nombre"
                v-model="form.nombre"
                :error="form.errors.nombre"
                required
                placeholder="Ej: Laptop Dell"
              />
            </div>

            <div>
              <InputLabel for="precio_unitario" required>Precio Unitario (Bs.)</InputLabel>
              <TextInput
                id="precio_unitario"
                v-model="form.precio_unitario"
                type="number"
                step="0.01"
                :error="form.errors.precio_unitario"
                required
                placeholder="0.00"
              />
            </div>
          </div>

          <div>
            <InputLabel for="descripcion">Descripción</InputLabel>
            <textarea
              id="descripcion"
              v-model="form.descripcion"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Descripción del producto..."
            ></textarea>
            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">
              {{ form.errors.descripcion }}
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <InputLabel for="stock" required>Stock</InputLabel>
              <TextInput
                id="stock"
                v-model="form.stock"
                type="number"
                :error="form.errors.stock"
                required
                placeholder="0"
              />
            </div>

            <div>
              <InputLabel for="categoria_codigo" required>Categoría</InputLabel>
              <select
                id="categoria_codigo"
                v-model="form.categoria_codigo"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.categoria_codigo }"
                required
              >
                <option value="">Seleccionar...</option>
                <option v-for="cat in categorias" :key="cat.codigo" :value="cat.codigo">
                  {{ cat.nombre }}
                </option>
              </select>
              <p v-if="form.errors.categoria_codigo" class="mt-1 text-sm text-red-600">
                {{ form.errors.categoria_codigo }}
              </p>
            </div>

            <div>
              <InputLabel for="unidad_codigo" required>Unidad de Medida</InputLabel>
              <select
                id="unidad_codigo"
                v-model="form.unidad_codigo"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.unidad_codigo }"
                required
              >
                <option value="">Seleccionar...</option>
                <option v-for="unidad in unidades" :key="unidad.codigo" :value="unidad.codigo">
                  {{ unidad.nombre }}
                </option>
              </select>
              <p v-if="form.errors.unidad_codigo" class="mt-1 text-sm text-red-600">
                {{ form.errors.unidad_codigo }}
              </p>
            </div>
          </div>

          <div>
            <InputLabel for="imagen">Imagen del Producto</InputLabel>
            <input
              id="imagen"
              type="file"
              @change="handleFileChange"
              accept="image/*"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p v-if="form.errors.imagen" class="mt-1 text-sm text-red-600">
              {{ form.errors.imagen }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
              Formatos: JPG, PNG. Tamaño máximo: 2MB
            </p>

            <!-- Preview de imagen -->
            <div v-if="imagePreview || (editingProducto && editingProducto.imagen)" class="mt-3">
              <img
                :src="imagePreview || editingProducto.imagen"
                alt="Preview"
                class="h-32 w-32 object-cover rounded border"
              />
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeFormModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            <Loader v-if="form.processing" :size="16" class="mr-2 animate-spin" />
            {{ editingProducto ? 'Actualizar' : 'Crear' }}
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Ver -->
    <Modal :show="showViewModal" @close="closeViewModal" max-width="2xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Detalles del Producto
        </h3>
      </template>

      <div v-if="selectedProducto" class="space-y-4">
        <div v-if="selectedProducto.imagen" class="flex justify-center">
          <img
            :src="selectedProducto.imagen"
            :alt="selectedProducto.nombre"
            class="h-48 w-48 object-cover rounded-lg shadow"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Código</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedProducto.codigo_producto }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Nombre</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedProducto.nombre }}</p>
          </div>
        </div>

        <div v-if="selectedProducto.descripcion">
          <label class="text-sm font-medium text-gray-500">Descripción</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedProducto.descripcion }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Precio Unitario</label>
            <p class="mt-1 text-base text-gray-900 font-semibold">
              Bs. {{ Number(selectedProducto.precio_unitario).toFixed(2) }}
            </p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Stock</label>
            <p class="mt-1 text-base text-gray-900">
              {{ selectedProducto.stock }} {{ selectedProducto.unidad_medida }}
            </p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Categoría</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedProducto.categoria }}</p>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Fecha de registro</label>
          <p class="mt-1 text-base text-gray-900">{{ formatDate(selectedProducto.created_at) }}</p>
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
          Eliminar Producto
        </h3>
      </template>

      <div v-if="selectedProducto">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas eliminar el producto
          <span class="font-semibold">{{ selectedProducto.nombre }}</span>?
        </p>
        <p class="mt-2 text-sm text-red-600">
          Esta acción no se puede deshacer y se eliminará también su imagen.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeDeleteModal" type="button">
          Cancelar
        </SecondaryButton>
        <DangerButton @click="deleteProducto" :disabled="deleteForm.processing">
          <Loader v-if="deleteForm.processing" :size="16" class="mr-2 animate-spin" />
          Eliminar
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

