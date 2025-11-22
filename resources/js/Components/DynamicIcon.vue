<template>
    <component :is="iconComponent" :size="size" :class="className" />
</template>

<script setup>
import { computed } from 'vue';
import * as LucideIcons from 'lucide-vue-next';

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    size: {
        type: [Number, String],
        default: 20
    },
    className: {
        type: String,
        default: ''
    }
});

const iconComponent = computed(() => {
    // Si el nombre del icono existe en Lucide, lo usamos
    if (LucideIcons[props.name]) {
        return LucideIcons[props.name];
    }

    // Icono por defecto si no se encuentra
    return LucideIcons.Circle;
});
</script>
