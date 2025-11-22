<template>
    <aside :class="[
        'fixed top-0 left-0 z-40 h-screen transition-transform',
        isOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:translate-x-0 w-64 bg-gray-800'
    ]">
        <div class="h-full px-3 py-4 overflow-y-auto pb-20">
            <!-- Logo/Título -->
            <div class="flex items-center justify-between mb-5 px-3">
                <Link :href="route('dashboard')" class="flex items-center">
                    <Store class="h-8 w-8 text-blue-400" />
                    <span class="ml-2 text-xl font-semibold text-white">Tienda Celestina</span>
                </Link>
                <button
                    @click="$emit('close')"
                    class="lg:hidden text-gray-400 hover:text-white transition"
                >
                    <X :size="24" />
                </button>
            </div>

            <!-- Navegación Dinámica -->
            <ul class="space-y-2 font-medium">
                <template v-for="item in filteredMenuItems" :key="item.id">
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
                            <span
                                v-if="item.badge"
                                class="ml-auto bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full"
                            >
                                {{ item.badge }}
                            </span>
                        </Link>
                        <a
                            v-else-if="item.url"
                            :href="item.url"
                            :target="item.target || '_self'"
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
                            :class="linkClass(isSubmenuActive(item))"
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
                        <Transition
                            enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 max-h-0"
                            enter-to-class="opacity-100 max-h-96"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 max-h-96"
                            leave-to-class="opacity-0 max-h-0"
                        >
                            <ul
                                v-show="openSubmenus.includes(item.id)"
                                class="ml-6 mt-2 space-y-2 overflow-hidden"
                            >
                                <li v-for="child in filterMenuChildren(item.children)" :key="child.id">
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
                        </Transition>
                    </li>
                </template>
            </ul>

            <!-- Usuario info (footer) -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700 bg-gray-800">
                <div class="flex items-center" v-if="$page.props.auth.user">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold">
                        {{ userInitials }}
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm text-white truncate font-medium">{{ $page.props.auth.user.name }}</p>
                        <div class="flex items-center gap-1 mt-0.5">
                            <span
                                v-for="role in userRoles"
                                :key="role"
                                class="text-xs px-1.5 py-0.5 rounded bg-blue-600 text-white"
                            >
                                {{ role }}
                            </span>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        class="text-gray-400 hover:text-white transition"
                        title="Cerrar sesión"
                    >
                        <LogOut :size="18" />
                    </button>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { X, ChevronDown, Store, LogOut } from 'lucide-vue-next';
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

// Función para verificar permisos
const can = (permission) => {
    if (!permission) return true;
    return page.props.auth?.permissions?.includes(permission) || false;
};

// Filtrar items de menú según permisos
const filteredMenuItems = computed(() => {
    return menuItems.value.filter(item => {
        // Si es separador, siempre mostrar
        if (item.is_separator) return true;

        // Verificar permiso del item
        if (item.permission && !can(item.permission)) return false;

        // Si tiene hijos, verificar que al menos uno sea visible
        if (item.children && item.children.length > 0) {
            const visibleChildren = item.children.filter(child =>
                !child.permission || can(child.permission)
            );
            return visibleChildren.length > 0;
        }

        return true;
    });
});

// Filtrar hijos del menú según permisos
const filterMenuChildren = (children) => {
    if (!children) return [];
    return children.filter(child => !child.permission || can(child.permission));
};

// Toggle submenu
const toggleSubmenu = (id) => {
    const index = openSubmenus.value.indexOf(id);
    if (index > -1) {
        openSubmenus.value.splice(index, 1);
    } else {
        openSubmenus.value.push(id);
    }
};

// Verificar si algún hijo del submenu está activo
const isSubmenuActive = (item) => {
    if (!item.children) return false;
    return item.children.some(child => isCurrentRoute(child.route));
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

// Abrir automáticamente submenus activos al cargar
onMounted(() => {
    filteredMenuItems.value.forEach(item => {
        if (item.children && isSubmenuActive(item)) {
            openSubmenus.value.push(item.id);
        }
    });
});

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

// Función de logout
const logout = () => {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        router.post(route('logout'));
    }
};

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
