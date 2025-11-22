<template>
  <div class="min-h-screen bg-gray-100">
    <Head :title="title" />

    <!-- Sidebar -->
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <!-- Overlay para móvil -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-gray-900 bg-opacity-50 z-30 lg:hidden"
    ></div>

    <!-- Main Content -->
    <div class="lg:ml-64">
      <!-- Top Navbar -->
      <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-20">
        <div class="px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16">
            <!-- Hamburger button -->
            <div class="flex items-center">
              <button
                @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition"
              >
                <Menu v-if="!sidebarOpen" :size="24" />
                <X v-else :size="24" />
              </button>
            </div>

            <!-- Right side -->
            <div class="flex items-center space-x-4">
              <!-- Search bar (opcional) -->
              <div class="hidden md:block">
                <div class="relative">
                  <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" :size="18" />
                  <input
                    type="text"
                    placeholder="Buscar..."
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64"
                  />
                </div>
              </div>

              <!-- Notifications -->
              <button class="p-2 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition relative">
                <Bell :size="20" />
                <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full"></span>
              </button>

              <!-- User Dropdown -->
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button class="flex items-center text-sm focus:outline-none transition">
                    <div class="flex items-center">
                      <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold mr-2">
                        {{ userInitials }}
                      </div>
                      <span class="hidden md:block text-gray-700 font-medium">
                        {{ $page.props.auth.user?.name }}
                      </span>
                      <ChevronDown :size="16" class="ml-1 text-gray-500" />
                    </div>
                  </button>
                </template>

                <template #content>
                  <div class="px-4 py-3 border-b border-gray-100">
                    <div class="text-sm font-medium text-gray-900">
                      {{ $page.props.auth.user?.name }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                      {{ $page.props.auth.user?.email }}
                    </div>
                    <div v-if="userRoles.length" class="mt-2 flex flex-wrap gap-1">
                      <span
                        v-for="role in userRoles"
                        :key="role"
                        class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                      >
                        {{ role }}
                      </span>
                    </div>
                  </div>

                 <DropdownLink :href="route('profile.show')">
                    <Settings :size="16" class="mr-2" />
                    Mi Perfil
                  </DropdownLink>

                  <DropdownLink :href="route('logout')" method="post" as="button">
                    <LogOut :size="16" class="mr-2" />
                    Cerrar Sesión
                  </DropdownLink>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>
      </nav>

      <!-- Page Heading -->
      <header v-if="$slots.header" class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8 py-6">
          <slot name="header" />
        </div>
      </header>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="px-4 sm:px-6 lg:px-8 mt-4">
        <Transition
          enter-active-class="transition ease-out duration-300"
          enter-from-class="opacity-0 transform translate-y-2"
          enter-to-class="opacity-100 transform translate-y-0"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="showSuccessMessage && $page.props.flash.success"
            class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between shadow-sm"
          >
            <div class="flex items-center">
              <CheckCircle :size="20" class="mr-2 flex-shrink-0" />
              <span>{{ $page.props.flash.success }}</span>
            </div>
            <button @click="showSuccessMessage = false" class="text-green-600 hover:text-green-800 ml-4">
              <X :size="18" />
            </button>
          </div>
        </Transition>

        <Transition
          enter-active-class="transition ease-out duration-300"
          enter-from-class="opacity-0 transform translate-y-2"
          enter-to-class="opacity-100 transform translate-y-0"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="showErrorMessage && $page.props.flash.error"
            class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between shadow-sm"
          >
            <div class="flex items-center">
              <AlertCircle :size="20" class="mr-2 flex-shrink-0" />
              <span>{{ $page.props.flash.error }}</span>
            </div>
            <button @click="showErrorMessage = false" class="text-red-600 hover:text-red-800 ml-4">
              <X :size="18" />
            </button>
          </div>
        </Transition>
      </div>

      <!-- Page Content -->
      <main class="p-4 sm:p-6 lg:p-8">
        <slot />
      </main>

      <!-- Footer -->
      <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="px-4 sm:px-6 lg:px-8 py-4">
          <p class="text-center text-sm text-gray-500">
            &copy; {{ new Date().getFullYear() }} Tienda Celestina. Todos los derechos reservados.
          </p>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import {
  Menu, X, Search, Bell, ChevronDown, Settings, LogOut,
  CheckCircle, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
  title: {
    type: String,
    default: ''
  }
});

const page = usePage();
const sidebarOpen = ref(false);
const showSuccessMessage = ref(true);
const showErrorMessage = ref(true);

// Observar cambios en los mensajes flash para mostrarlos nuevamente
watch(() => page.props.flash, () => {
  showSuccessMessage.value = true;
  showErrorMessage.value = true;

  // Auto-ocultar mensajes después de 5 segundos
  if (page.props.flash?.success) {
    setTimeout(() => {
      showSuccessMessage.value = false;
    }, 5000);
  }

  if (page.props.flash?.error) {
    setTimeout(() => {
      showErrorMessage.value = false;
    }, 5000);
  }
}, { deep: true });

// Obtener iniciales del usuario
const userInitials = computed(() => {
  const name = page.props.auth?.user?.name || 'U';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
});

// Obtener roles del usuario
const userRoles = computed(() => {
  const roles = page.props.auth?.user?.roles || page.props.auth?.roles || [];
  return roles.map(role => {
    const roleName = typeof role === 'string' ? role : role.name;
    return roleName.charAt(0).toUpperCase() + roleName.slice(1);
  });
});
</script>
