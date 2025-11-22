<template>
  <AuthenticatedLayout title="Clientes">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Clientes
        </h2>
        <PrimaryButton v-if="can.create" @click="openCreateModal">
          <Plus :size="16" class="mr-2" />
          Nuevo Cliente
        </PrimaryButton>
      </div>
    </template>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Tabla de clientes -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nombre Completo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Email
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Teléfono
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="cliente in clientes.data" :key="cliente.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ cliente.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ cliente.nombre_completo }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ cliente.email }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ cliente.telefono || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button
                  @click="openShowModal(cliente)"
                  class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                  title="Ver"
                >
                  <Eye :size="18" />
                </button>
                <button
                  v-if="can.edit"
                  @click="openEditModal(cliente)"
                  class="text-yellow-600 hover:text-yellow-900 inline-flex items-center"
                  title="Editar"
                >
                  <Edit :size="18" />
                </button>
                <button
                  v-if="can.delete"
                  @click="openDeleteModal(cliente)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Eliminar"
                >
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
            <tr v-if="clientes.data.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                No hay clientes registrados
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="clientes.links && clientes.links.length > 3" class="px-6 py-4 border-t border-gray-200">
        <Pagination
          :links="clientes.links"
          :from="clientes.from"
          :to="clientes.to"
          :total="clientes.total"
        />
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showFormModal" @close="closeFormModal" max-width="2xl">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingCliente ? 'Editar Cliente' : 'Nuevo Cliente' }}
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
                placeholder="Juan"
              />
            </div>

            <div>
              <InputLabel for="apellido" required>Apellido</InputLabel>
              <TextInput
                id="apellido"
                v-model="form.apellido"
                :error="form.errors.apellido"
                required
                placeholder="Pérez"
              />
            </div>
          </div>

          <div>
            <InputLabel for="email" required>Email</InputLabel>
            <TextInput
              id="email"
              v-model="form.email"
              type="email"
              :error="form.errors.email"
              required
              placeholder="juan@example.com"
            />
          </div>

          <div v-if="!editingCliente" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="password" required>Contraseña</InputLabel>
              <TextInput
                id="password"
                v-model="form.password"
                type="password"
                :error="form.errors.password"
                required
                placeholder="Mínimo 8 caracteres"
              />
            </div>

            <div>
              <InputLabel for="password_confirmation" required>Confirmar Contraseña</InputLabel>
              <TextInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                :error="form.errors.password_confirmation"
                required
                placeholder="Repite la contraseña"
              />
            </div>
          </div>

          <div v-else class="space-y-4">
            <div class="flex items-center">
              <input
                id="cambiar_password"
                v-model="cambiarPassword"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="cambiar_password" class="ml-2 text-sm text-gray-700">
                Cambiar contraseña
              </label>
            </div>

            <div v-if="cambiarPassword" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel for="password">Nueva Contraseña</InputLabel>
                <TextInput
                  id="password"
                  v-model="form.password"
                  type="password"
                  :error="form.errors.password"
                  placeholder="Mínimo 8 caracteres"
                />
              </div>

              <div>
                <InputLabel for="password_confirmation">Confirmar Contraseña</InputLabel>
                <TextInput
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  :error="form.errors.password_confirmation"
                  placeholder="Repite la contraseña"
                />
              </div>
            </div>
          </div>

          <div>
            <InputLabel for="telefono">Teléfono</InputLabel>
            <TextInput
              id="telefono"
              v-model="form.telefono"
              :error="form.errors.telefono"
              placeholder="70123456"
            />
          </div>

          <div>
            <InputLabel for="domicilio">Domicilio</InputLabel>
            <textarea
              id="domicilio"
              v-model="form.domicilio"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Calle Principal #123"
            ></textarea>
            <p v-if="form.errors.domicilio" class="mt-1 text-sm text-red-600">
              {{ form.errors.domicilio }}
            </p>
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
          Detalles del Cliente
        </h3>
      </template>

      <div v-if="selectedCliente" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Nombre</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedCliente.nombre }}</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-500">Apellido</label>
            <p class="mt-1 text-base text-gray-900">{{ selectedCliente.apellido }}</p>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Email</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedCliente.email }}</p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Teléfono</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedCliente.telefono || 'No registrado' }}</p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Domicilio</label>
          <p class="mt-1 text-base text-gray-900">{{ selectedCliente.domicilio || 'No registrado' }}</p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-500">Fecha de registro</label>
          <p class="mt-1 text-base text-gray-900">{{ formatDate(selectedCliente.created_at) }}</p>
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
          Eliminar Cliente
        </h3>
      </template>

      <div v-if="selectedCliente">
        <p class="text-gray-700">
          ¿Estás seguro de que deseas eliminar al cliente
          <span class="font-semibold">{{ selectedCliente.nombre_completo }}</span>?
        </p>
        <p class="mt-2 text-sm text-red-600">
          Esta acción no se puede deshacer.
        </p>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <SecondaryButton @click="closeDeleteModal" type="button">
          Cancelar
        </SecondaryButton>
        <DangerButton @click="deleteCliente" :disabled="deleteForm.processing">
          <Loader v-if="deleteForm.processing" :size="16" class="mr-2 animate-spin" />
          Eliminar
        </DangerButton>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

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
  clientes: Object,
  can: Object
});

// Estados de los modales
const showFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const editingCliente = ref(null);
const selectedCliente = ref(null);
const cambiarPassword = ref(false);

// Formulario
const form = useForm({
  nombre: '',
  apellido: '',
  email: '',
  password: '',
  password_confirmation: '',
  telefono: '',
  domicilio: ''
});

const deleteForm = useForm({});

// Funciones para abrir modales
const openCreateModal = () => {
  editingCliente.value = null;
  cambiarPassword.value = false;
  form.reset();
  form.clearErrors();
  showFormModal.value = true;
};

const openEditModal = (cliente) => {
  editingCliente.value = cliente;
  cambiarPassword.value = false;
  form.nombre = cliente.nombre;
  form.apellido = cliente.apellido;
  form.email = cliente.email;
  form.telefono = cliente.telefono || '';
  form.domicilio = cliente.domicilio || '';
  form.password = '';
  form.password_confirmation = '';
  form.clearErrors();
  showFormModal.value = true;
};

const openShowModal = (cliente) => {
  selectedCliente.value = cliente;
  showViewModal.value = true;
};

const openDeleteModal = (cliente) => {
  selectedCliente.value = cliente;
  showDeleteModal.value = true;
};

// Funciones para cerrar modales
const closeFormModal = () => {
  showFormModal.value = false;
  editingCliente.value = null;
  cambiarPassword.value = false;
  form.reset();
  form.clearErrors();
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedCliente.value = null;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedCliente.value = null;
};

// Submit del formulario
const submitForm = () => {
  if (editingCliente.value) {
    // Si no se va a cambiar la password, eliminar esos campos
    if (!cambiarPassword.value) {
      form.password = null;
      form.password_confirmation = null;
    }

    form.put(route('clientes.update', editingCliente.value.id), {
      onSuccess: () => closeFormModal()
    });
  } else {
    form.post(route('clientes.store'), {
      onSuccess: () => closeFormModal()
    });
  }
};

// Eliminar cliente
const deleteCliente = () => {
  deleteForm.delete(route('clientes.destroy', selectedCliente.value.id), {
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
