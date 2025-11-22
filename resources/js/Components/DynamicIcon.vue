<template>
  <component :is="iconComponent" :size="size" :stroke-width="strokeWidth" />
</template>

<script setup>
import { computed } from 'vue';
import * as LucideIcons from 'lucide-vue-next';

const props = defineProps({
  name: {
    type: String,
    required: true,
    default: 'Circle'
  },
  size: {
    type: Number,
    default: 24
  },
  strokeWidth: {
    type: Number,
    default: 2
  }
});

// Convertir el nombre del icono a PascalCase si es necesario
const iconComponent = computed(() => {
  // Si el nombre ya está en PascalCase, usarlo directamente
  if (props.name && LucideIcons[props.name]) {
    return LucideIcons[props.name];
  }

  // Convertir de kebab-case o snake_case a PascalCase
  const pascalCaseName = props.name
    .split(/[-_]/)
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join('');

  // Retornar el icono o un icono por defecto
  return LucideIcons[pascalCaseName] || LucideIcons.Circle;
});
</script>
