<template>
  <AuthenticatedLayout title="Unidades de Medida">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Unidades de Medida
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Plus :size="16" class="mr-2" />
          Nueva Unidad
        </PrimaryButton>
      </div>
    </template>

    <!-- Buscador -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="max-w-md">
        <InputLabel for="search">Buscar Unidad de Medida</InputLabel>
        <TextInput
          id="search"
          v-model="searchForm.search"
          placeholder="Nombre, código o abreviatura..."
          @input="debouncedSearch"
        />
      </div>
    </div>

    <!-- Tabla de unidades -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Código
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nombre
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Descripción
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Productos
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(unidad, index) in unidades.data" :key="unidad.codigo_unidad" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                {{  index + 1 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ unidad.nombre }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ unidad.descripcion || 'Sin descripción' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                  {{ unidad.productos_count || 0 }} productos
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button
                  @click="openShowModal(unidad)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver"
                >
                  <Eye :size="18" />
                </button>
                <button
                  v-if="can.edit"
                  @click="openEditModal(unidad)"
                  class="text-yellow-600 hover:text-yellow-900 inline-flex items-center"
                  title="Editar"
                >
                  <Edit :size="18" />
                </button>
                <button
                  v-if="can.delete"
                  @click="openDeleteModal(unidad)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Eliminar"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="unidades.data.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                No hay unidades de medida registradas
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="unidades.links && unidades.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="unidades.links"
          :from="unidades.from"
          :to="unidades.to"
          :total="unidades.total"
        />
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="2xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingUnidad ? 'Editar Unidad de Medida' : 'Nueva Unidad de Medida' }}
        </h3>
      </template>

      <form @submit.prevent="submitForm">
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="nombre" required>Nombre</InputLabel>
              <TextInput
                id="nombre"
                v-model="form.nombre"
                :error="form.errors.nombre"
                required
                placeholder="Ej: Kilogramo"
              />
            </div>
          </div>

          <div>
            <InputLabel for="descripcion">Descripción</InputLabel>
            <textarea
              id="descripcion"
              v-model="form.descripcion"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Describe esta unidad de medida..."
            ></textarea>
            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">
              {{ form.errors.descripcion }}
            </p>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeFormModal" type="button">
            Cancelar
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            <Loader v-if="form.processing" :size="16" class="mr-2 animate-spin" />
            {{ editingUnidad ? 'Actualizar' : 'Crear' }}
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Ver -->
    <Modal :show="showViewModal" @close="closeViewModal" max-width="lg">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Detalles de la Unidad de Medida
        </h3>
      </template>

      <div v-if="selectedUnidad" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-500">Código</label>
          <p class="mt-1 text-base text-gray-900 font-mono">{{ selectedUnidad.codigo_unidad }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Nombre</label>
            <p class="mt-1 text-base text-gray-900 font-semibold">{{ selectedUnidad.nombre }}</p>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Descripción</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedUnidad.descripcion || 'Sin descripción' }}</p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Total de productos</label>
          <p class="mt-1 text-base text-gray-900">
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
              {{ selectedUnidad.productos_count || 0 }} productos
            </span>
          </p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Fecha de creación</label>
          <p class="mt-1 text-base text-gray-900">{{ formatDate(selectedUnidad.created_at) }}</p>
        </div>

        <div v-if="selectedUnidad.updated_at">
          <label class="text-sm font-medium text-gray-500">Última actualización</label>
          <p class="mt-1 text-base text-gray-900">{{ formatDate(selectedUnidad.updated_at) }}</p>
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
          Eliminar Unidad de Medida
        </h3>
      </template>

      <div v-if="selectedUnidad">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas eliminar la unidad de medida
          <span class="font-semibold">{{ selectedUnidad.nombre }} </span>?
        </p>
        <p class="mt-2 text-sm text-red-600">
          Esta acción no se puede deshacer. Si tiene productos asociados, no podrá eliminarse.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeDeleteModal" type="button">
          Cancelar
        </SecondaryButton>
        <DangerButton @click="deleteUnidad" :disabled="deleteForm.processing">
          <Loader v-if="deleteForm.processing" :size="16" class="mr-2 animate-spin" />
          Eliminar
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
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
  unidades: Object,
  filters: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const editingUnidad = ref(null);
const selectedUnidad = ref(null);

// Formulario de búsqueda
const searchForm = reactive({
  search: props.filters?.search || ''
});

// Formulario principal
const form = useForm({
  nombre: '',
  descripcion: ''
});

const deleteForm = useForm({});

// Funciones para abrir modales
const openCreateModal = () => {
  editingUnidad.value = null;
  form.reset();
  form.clearErrors();
  showFormModal.value = true;
};

const openEditModal = (unidad) => {
  editingUnidad.value = unidad;
  form.nombre = unidad.nombre;
  form.descripcion = unidad.descripcion || '';
  form.clearErrors();
  showFormModal.value = true;
};

const openShowModal = (unidad) => {
  selectedUnidad.value = unidad;
  showViewModal.value = true;
};

const openDeleteModal = (unidad) => {
  selectedUnidad.value = unidad;
  showDeleteModal.value = true;
};

// Funciones para cerrar modales
const closeFormModal = () => {
  showFormModal.value = false;
  editingUnidad.value = null;
  form.reset();
  form.clearErrors();
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedUnidad.value = null;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedUnidad.value = null;
};

// Submit del formulario
const submitForm = () => {
  if (editingUnidad.value) {
    form.put(route('productos.unidades.update', editingUnidad.value.codigo_unidad), {
      onSuccess: () => closeFormModal()
    });
  } else {
    form.post(route('productos.unidades.store'), {
      onSuccess: () => closeFormModal()
    });
  }
};

// Eliminar unidad
const deleteUnidad = () => {
  deleteForm.delete(route('productos.unidades.destroy', selectedUnidad.value.codigo_unidad), {
    onSuccess: () => closeDeleteModal()
  });
};

// Función debounce simple
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
  router.get(route('productos.unidades.index'), searchForm, {
    preserveState: true,
    preserveScroll: true
  });
}, 500);

// Formatear fecha
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
