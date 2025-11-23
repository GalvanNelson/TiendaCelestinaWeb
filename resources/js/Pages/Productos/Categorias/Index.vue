<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
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
  categorias: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const editingCategoria = ref(null);
const selectedCategoria = ref(null);

// Formulario
const form = useForm({
    codigo: '',
    nombre: '',
});

const deleteForm = useForm({});

// Funciones para abrir modales
const openCreateModal = () => {
  editingCategoria.value = null;
  form.reset();
  form.clearErrors();
  showFormModal.value = true;
};

const openEditModal = (categoria) => {
  editingCategoria.value = categoria;
  cambiarPassword.value = false;
  form.codigo = categoria.codigo;
  form.nombre = categoria.nombre;
  form.clearErrors();
  showFormModal.value = true;
};

const openShowModal = (categoria) => {
  selectedCategoria.value = categoria;
  showViewModal.value = true;
};

const openDeleteModal = (categoria) => {
  selectedCategoria.value = categoria;
  showDeleteModal.value = true;
};

// Funciones para cerrar modales
const closeFormModal = () => {
  showFormModal.value = false;
  editingCategoria.value = null;
  form.reset();
  form.clearErrors();
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedCategoria.value = null;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedCategoria.value = null;
};

// Submit del formulario
const submitForm = () => {
  if (editingCategoria.value) {


    form.put(route('categorias.update', editingCategoria.value.id), {
      onSuccess: () => closeFormModal()
    });
  } else {
    form.post(route('categorias.store'), {
      onSuccess: () => closeFormModal()
    });
  }
};

// Eliminar categoria
const deleteCategoria = () => {
  deleteForm.delete(route('categorias.destroy', selectedCategoria.value.id), {
    onSuccess: () => closeDeleteModal()
  });
};

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


<template>
  <AuthenticatedLayout title="Categorías de Productos" :can="can">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Categorías de Productos
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Plus :size="16" class="mr-2" />
          Nueva Categoría
        </PrimaryButton>
      </div>
    </template>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Tabla de categorías -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Codigo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nombre
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="categoria in categorias.data" :key="categoria.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ categoria.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ categoria.nombre }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button
                  @click="openShowModal(categoria)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver"
                >
                  <Eye :size="18" />
                </button>
                <button
                  v-if="can.edit"
                  @click="openEditModal(categoria)"
                  class="text-yellow-600 hover:text-yellow-900 inline-flex items-center"
                  title="Editar"
                >
                  <Edit :size="18" />
                </button>
                <button
                  v-if="can.delete"
                  @click="openDeleteModal(categoria)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Eliminar"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="categorias.data.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                No hay categorías registradas
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="categorias.links && categorias.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="categorias.links"
          :from="categorias.from"
          :to="categorias.to"
          :total="categorias.total"
        />
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="2xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingCategoria ? 'Editar Categoría' : 'Nueva Categoría' }}
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
                placeholder="Nombre de la categoría"
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
            {{ editingCliente ? 'Actualizar' : 'Crear' }}
          </PrimaryButton>
        </div>
      </form>
    </Modal>

    <!-- Modal Ver -->
    <Modal :show="showViewModal" @close="closeViewModal" max-width="lg">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          Detalles del Categoría
        </h3>
      </template>

      <div v-if="selectedCategoria" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Nombre</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedCategoria.nombre }}</p>
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
          Eliminar Categoría
        </h3>
      </template>

      <div v-if="selectedCategoria">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas eliminar la categoría
          <span class="font-semibold">{{ selectedCategoria.nombre }}</span>?
        </p>
        <p class="mt-2 text-sm text-red-600">
          Esta acción no se puede deshacer.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeDeleteModal" type="button">
          Cancelar
        </SecondaryButton>
        <DangerButton @click="deleteCategoria" :disabled="deleteForm.processing">
          <Loader v-if="deleteForm.processing" :size="16" class="mr-2 animate-spin" />
          Eliminar
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

