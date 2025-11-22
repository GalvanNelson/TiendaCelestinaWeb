<template>
  <Link
    :href="href"
    class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition"
    :class="{
      'border-blue-600 text-gray-900 focus:border-blue-700': active,
      'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300':
        !active,
    }"
  >
    <slot />
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  href: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
});
</script>
