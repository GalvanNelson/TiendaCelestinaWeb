<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ref } from 'vue';
import Paginator from '@/Components/Paginator.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps(['users'])
// Función para manejar la eliminación del usuario
const deleteUser = (user) => {
    if (selectedUser.value) {
        router.delete(route('user.destroy', { user: selectedUser.value.id }));
        showModal.value = false;
    }
};
// Estado para manejar la visibilidad del modal y el usuario seleccionado
const showModal = ref(false);
const selectedUser = ref(null);
// Función para abrir el modal de confirmación
const confirmDeleteUser = (user) => {
    selectedUser.value = user;
    showModal.value = true;
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Usuarios
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                  <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                   Lista de Usuarios 
                    <Link :href="route('user.create')" class="btn btn-success mr-2 inline-block">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo
                   </Link> 
                   <form :action="route('user.index')" method="get" class="flex mt-2">
                        <TextInput class="w-full" name="name" placeholder="Buscar por Nombre o Correo.."/>
                        <button type="submit" class="ml-2 btn btn-primary">Buscar</button>
                   </form>
                   <table class="table-auto w-full">
	                   <thead>
	                   		<tr>
	                   			<th class="p-3">Identificador</th>
	                   			<th class="p-3">Nombre</th>
	                   			<th class="p-3">Correo</th>
	                   			<th class="p-3">Acciones</th>
	                   		</tr>
	                   </thead>
	                   <tbody>
	                   		<tr v-for="user in users.data" :key="user">
	                   			<td class="p-3 border">{{ user.id }}</td>
	                   			<td class="p-3 border">{{ user.name }}</td>
	                   			<td class="p-3 border">{{ user.email }}</td>
	                   			<td class="p-3 border">
                                     <Link :href="route('user.show',{user: user})" class="btn btn-primary mr-2 inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 float-left">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        Mostrar
                                    </Link>

                                      <Link :href="route('user.edit',{user: user})" class="btn btn-primary mr-2 inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 float-left">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                        </svg>
                                        Editar
                                    </Link>
                                    
                                    <button @click="confirmDeleteUser(user)" class="btn btn-danger inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 float-left">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        Borrar
                                    </button>
                                </td>
	                   		</tr>
	                   </tbody>
                   </table>
                   <Paginator :paginator="users" class="mt-3"/>
	          </div>
                </div>
            </div>
        </div>    
        <DialogModal :show="showModal" @close="showModal = false">
            <template v-slot:title>
                <h1>Confirmar Eliminación</h1>
            </template>
            <template v-slot:content>
                <p>¿Estás seguro de que quieres eliminar al usuario <strong> {{ selectedUser?.name }} ({{  selectedUser?.email }}) </strong>?</p>
            </template>
            <template v-slot:footer>
                <PrimaryButton @click="deleteUser" class="bg-red-500 hover:bg-red-800">Eliminar</PrimaryButton>
                <button @click="showModal = false" class="ml-4">Cancelar</button>
            </template>
        </DialogModal>
   </AppLayout>
</template>
