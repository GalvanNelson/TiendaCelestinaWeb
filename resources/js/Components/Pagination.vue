<template>
  <div v-if="links && links.length > 3" class="flex justify-between items-center">
    <div class="text-sm text-gray-700">
      Mostrando <span class="font-medium">{{ from }}</span> a
      <span class="font-medium">{{ to }}</span> de
      <span class="font-medium">{{ total }}</span> resultados
    </div>

    <div class="flex space-x-1">
      <template v-for="(link, index) in links" :key="index">
        <Link
          v-if="link.url && link.active"
          :href="link.url"
          v-html="link.label"
          class="px-3 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white"
          preserve-scroll
        />

        <Link
          v-else-if="link.url"
          :href="link.url"
          v-html="link.label"
          class="px-3 py-2 text-sm font-medium rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition"
          preserve-scroll
        />

        <span
          v-else
          v-html="link.label"
          class="px-3 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  links: Array,
  from: Number,
  to: Number,
  total: Number
});
</script>
