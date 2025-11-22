<template>
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex items-center flex-1">
                <div
                    class="flex-shrink-0 rounded-md p-3"
                    :class="bgColorClass"
                >
                    <DynamicIcon :name="icon" :size="24" class="text-white" />
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-sm font-medium text-gray-500">{{ title }}</h2>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ value }}</p>

                    <!-- Trend Indicator -->
                    <div v-if="trend !== null && trend !== undefined" class="flex items-center mt-2">
                        <component
                            :is="trend >= 0 ? TrendingUp : TrendingDown"
                            :size="16"
                            :class="trend >= 0 ? 'text-green-600' : 'text-red-600'"
                        />
                        <span
                            class="text-xs font-medium ml-1"
                            :class="trend >= 0 ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ Math.abs(trend) }}%
                        </span>
                        <span class="text-xs text-gray-500 ml-1">{{ trendLabel }}</span>
                    </div>

                    <!-- Badge -->
                    <div v-if="badge" class="mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ badge }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Optional Action Button -->
            <div v-if="$slots.action" class="ml-4">
                <slot name="action" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { TrendingUp, TrendingDown } from 'lucide-vue-next';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    value: {
        type: [String, Number],
        required: true
    },
    icon: {
        type: String,
        required: true
    },
    color: {
        type: String,
        default: 'blue',
        validator: (value) => ['blue', 'green', 'purple', 'orange', 'red', 'yellow', 'indigo', 'pink'].includes(value)
    },
    trend: {
        type: Number,
        default: null
    },
    trendLabel: {
        type: String,
        default: ''
    },
    badge: {
        type: String,
        default: ''
    }
});

const bgColorClass = computed(() => {
    const colors = {
        blue: 'bg-blue-500',
        green: 'bg-green-500',
        purple: 'bg-purple-500',
        orange: 'bg-orange-500',
        red: 'bg-red-500',
        yellow: 'bg-yellow-500',
        indigo: 'bg-indigo-500',
        pink: 'bg-pink-500'
    };
    return colors[props.color] || colors.blue;
});
</script>
