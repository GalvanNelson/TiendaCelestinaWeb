<template>
  <label :for="for" class="block text-sm font-medium text-gray-700 mb-1">
    <slot />
    <span v-if="required" class="text-red-500 ml-1">*</span>
  </label>
</template>

<script setup>
defineProps({
  for: String,
  required: {
    type: Boolean,
    default: false
  }
});
</script>
