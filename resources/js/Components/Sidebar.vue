<template>
    <aside :class="[
        'fixed top-0 left-0 z-40 h-screen transition-transform',
        isOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:translate-x-0 w-64 bg-gray-800'
    ]">
        <div class="h-full px-3 py-4 overflow-y-auto pb-20">
            <!-- Logo/Título -->
            <div class="flex items-center justify-between mb-5 px-3">
                <span class="text-xl font-semibold text-white">Tienda Celestina</span>
                <button
                    @click="$emit('close')"
                    class="lg:hidden text-gray-400 hover:text-white"
                >
                    <X :size="24" />
                </button>
            </div>

            <!-- Navegación Dinámica -->
            <ul class="space-y-2 font-medium">
                <template v-for="item in menuItems" :key="item.id">
                    <!-- Separador -->
                    <li v-if="item.is_separator" class="pt-4 mt-4 border-t border-gray-700">
                        <span class="text-xs text-gray-400 uppercase px-3">
                            {{ item.separator_label }}
                        </span>
                    </li>

                    <!-- Item de menú sin hijos -->
                    <li v-else-if="!item.children || item.children.length === 0">
                        <Link
                            v-if="item.route"
                            :href="route(item.route)"
                            :class="linkClass(isCurrentRoute(item.route))"
                        >
                            <DynamicIcon :name="item.icon" :size="20" />
                            <span class="ml-3">{{ item.label }}</span>
                        </Link>
                        <a
                            v-else-if="item.url"
                            :href="item.url"
                            :class="linkClass(false)"
                        >
                            <DynamicIcon :name="item.icon" :size="20" />
                            <span class="ml-3">{{ item.label }}</span>
                        </a>
                    </li>

                    <!-- Item de menú con hijos (submenu) -->
                    <li v-else>
                        <button
                            @click="toggleSubmenu(item.id)"
                            :class="linkClass(false)"
                            class="w-full"
                        >
                            <DynamicIcon :name="item.icon" :size="20" />
                            <span class="ml-3 flex-1 text-left">{{ item.label }}</span>
                            <ChevronDown
                                :size="16"
                                class="transition-transform"
                                :class="{ 'rotate-180': openSubmenus.includes(item.id) }"
                            />
                        </button>

                        <!-- Submenu -->
                        <ul
                            v-show="openSubmenus.includes(item.id)"
                            class="ml-6 mt-2 space-y-2"
                        >
                            <li v-for="child in item.children" :key="child.id">
                                <Link
                                    v-if="child.route"
                                    :href="route(child.route)"
                                    :class="linkClass(isCurrentRoute(child.route))"
                                    class="text-sm"
                                >
                                    <DynamicIcon v-if="child.icon" :name="child.icon" :size="16" />
                                    <span :class="child.icon ? 'ml-2' : ''">{{ child.label }}</span>
                                </Link>
                            </li>
                        </ul>
                    </li>
                </template>
            </ul>

            <!-- Usuario info (footer) -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700 bg-gray-800">
                <div class="flex items-center" v-if="$page.props.auth.user">
                    <img
                        :src="$page.props.auth.user.profile_photo_url"
                        :alt="$page.props.auth.user.name"
                        class="w-8 h-8 rounded-full"
                    >
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm text-white truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-400">{{ userRole }}</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { X, ChevronDown } from 'lucide-vue-next';
import DynamicIcon from '@/Components/DynamicIcon.vue';

const page = usePage();

defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
});

defineEmits(['close']);

// Menús desde el backend
const menuItems = computed(() => page.props.menuItems || []);

// Controlar submenus abiertos
const openSubmenus = ref([]);

const toggleSubmenu = (id) => {
    const index = openSubmenus.value.indexOf(id);
    if (index > -1) {
        openSubmenus.value.splice(index, 1);
    } else {
        openSubmenus.value.push(id);
    }
};

// Verificar si la ruta actual coincide
const isCurrentRoute = (routeName) => {
    if (!routeName) return false;

    // Verificar coincidencia exacta
    if (route().current(routeName)) return true;

    // Verificar coincidencia con wildcard (ej: users.*)
    const routePattern = routeName.split('.')[0] + '.*';
    return route().current(routePattern);
};

// Obtener el rol principal del usuario
const userRole = computed(() => {
    const roles = page.props.auth?.roles || [];
    if (roles.length > 0) {
        return roles[0].charAt(0).toUpperCase() + roles[0].slice(1);
    }
    return 'Usuario';
});

// Clases para los links
const linkClass = (isActive) => {
    return [
        'flex items-center p-2 rounded-lg transition-colors',
        isActive
            ? 'bg-gray-700 text-white'
            : 'text-gray-300 hover:bg-gray-700 hover:text-white'
    ];
};
</script>
