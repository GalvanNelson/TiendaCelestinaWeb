<script setup>
import { FwbModal, FwbInput, FwbSpinner, FwbListGroup, FwbListGroupItem, FwbButton } from 'flowbite-vue';

const props = defineProps({
    search : String,
    isFetchingData: Boolean,
    results: Array,
    title: String,
    placeholder: String,
});

const emit = defineEmits(['update:search', 'select', 'close']);

</script>

<template>
    <FwbModal @close="$emit('close')">
        <template #header>
            <div class="flex items-center text-lg">{{ title }}</div>
        </template>
        <template #body>
            <FwbInput
                :modelValue="search"
                @update:modelValue="$emit('update:search', $event)"
                :placeholder="placeholder"
                label="Buscar"
                size="lg"
            >
                <template #prefix>
                    <svg
                        aria-hidden="true"
                        class="w-5 h-5 text-gray-500 dark:text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                        />
                    </svg>
                </template>
                <template v-if="isFetchingData" #suffix>
                    <FwbSpinner size="6">Search</FwbSpinner>
                </template>
            </FwbInput>

            <div v-if="search && results.length === 0" class="text-center mt-4">
                <p class="text-gray-500">No results found.</p>
            </div>

            <div v-else-if="search" class="relative">
                <FwbListGroup class="absolute w-full">
                    <FwbListGroupItem
                        v-for="result in results"
                        :key="result.id"
                        @click="$emit('select', result)"
                        hover
                    >
                        <template #prefix>
                            <slot name="prefix" />
                        </template>
                        <slot name="result" :result="result"></slot>
                    </FwbListGroupItem>
                </FwbListGroup>
            </div>
        </template>
        <template #footer>
            <div class="flex justify-between">
                <FwbButton @click="$emit('close')" color="alternative">
                    Decline
                </FwbButton>
                <FwbButton @click="$emit('close')" color="green">
                    I accept
                </FwbButton>
            </div>
        </template>
    </FwbModal>
</template>
