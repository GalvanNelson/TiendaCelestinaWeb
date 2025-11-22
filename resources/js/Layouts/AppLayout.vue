<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <Sidebar :isOpen="sidebarOpen" @close="sidebarOpen = false" />

        <!-- Overlay para móvil -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
        ></div>

        <!-- Contenido principal -->
        <div class="lg:ml-64">
            <!-- Top Navigation -->
            <nav class="bg-white border-b border-gray-200 px-4 py-3">
                <div class="flex items-center justify-between">
                    <!-- Botón menú móvil -->
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden text-gray-500 hover:text-gray-700"
                    >
                        <Menu :size="24" />
                    </button>

                    <!-- Título de página -->
                    <h1 class="text-xl font-semibold text-gray-800">
                        <slot name="header">Dashboard</slot>
                    </h1>

                    <!-- Acciones del usuario -->
                    <div class="flex items-center space-x-4">
                        <!-- Notificaciones -->
                        <button class="text-gray-500 hover:text-gray-700">
                            <Bell :size="20" />
                        </button>

                        <!-- Dropdown usuario -->
                        <JetDropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center text-sm">
                                    <img
                                        :src="$page.props.auth.user.profile_photo_url"
                                        :alt="$page.props.auth.user.name"
                                        class="h-8 w-8 rounded-full object-cover"
                                    >
                                </button>
                            </template>

                            <template #content>
                                <JetDropdownLink :href="route('profile.show')">
                                    Perfil
                                </JetDropdownLink>

                                <div class="border-t border-gray-100"></div>

                                <form @submit.prevent="logout">
                                    <JetDropdownLink as="button">
                                        Cerrar Sesión
                                    </JetDropdownLink>
                                </form>
                            </template>
                        </JetDropdown>
                    </div>
                </div>
            </nav>

            <!-- Contenido de la página -->
            <main class="p-4">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Menu, Bell } from 'lucide-vue-next';
import Sidebar from '@/Components/Sidebar.vue';
import JetDropdown from '@/Components/Dropdown.vue';
import JetDropdownLink from '@/Components/DropdownLink.vue';

const sidebarOpen = ref(false);

const logout = () => {
    router.post(route('logout'));
};
</script>
