<template>
  <Link
    :href="href"
    :as="as"
    :method="method"
    class="flex items-center w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
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
