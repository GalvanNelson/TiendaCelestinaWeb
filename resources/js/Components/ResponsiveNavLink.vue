<template>
  <Link
    :href="href"
    :as="as"
    :method="method"
    class="flex items-center w-full pl-3 pr-4 py-2 border-l-4 text-base font-medium leading-5 focus:outline-none transition"
    :class="{
      'border-blue-600 text-blue-700 bg-blue-50 focus:text-blue-800 focus:bg-blue-100 focus:border-blue-700':
        active,
      'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300':
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
  as: {
    type: String,
    default: 'a',
  },
  method: {
    type: String,
    default: 'get',
  },
});
</script>
