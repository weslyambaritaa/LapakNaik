<script setup>
import { computed } from 'vue';
import { formatRupiah, formatDate } from '@/formatters';

const props = defineProps({
    data: {
        type: Array,
        required: true, // [{ date, total }]
    },
});

const width = 640;
const height = 180;
const padding = 24;

const max = computed(() => Math.max(...props.data.map((point) => point.total), 1));

const barWidth = computed(() => (width - padding * 2) / props.data.length);

function barHeight(total) {
    return total === 0 ? 0 : (total / max.value) * (height - padding * 2);
}

function barX(index) {
    return padding + index * barWidth.value;
}

function barY(total) {
    return height - padding - barHeight(total);
}
</script>

<template>
    <svg :viewBox="`0 0 ${width} ${height}`" class="h-44 w-full" preserveAspectRatio="none">
        <line
            v-for="fraction in [0, 0.25, 0.5, 0.75, 1]"
            :key="fraction"
            :x1="padding"
            :x2="width - padding"
            :y1="height - padding - fraction * (height - padding * 2)"
            :y2="height - padding - fraction * (height - padding * 2)"
            class="stroke-gray-100 dark:stroke-gray-700"
            stroke-width="1"
        />

        <g v-for="(point, index) in data" :key="point.date">
            <title>{{ formatDate(point.date) }}: {{ formatRupiah(point.total) }}</title>
            <rect
                :x="barX(index) + barWidth * 0.15"
                :y="barY(point.total)"
                :width="barWidth * 0.7"
                :height="barHeight(point.total)"
                rx="2"
                class="fill-emerald-500 dark:fill-emerald-400"
            />
        </g>

        <line :x1="padding" :x2="width - padding" :y1="height - padding" :y2="height - padding" class="stroke-gray-200 dark:stroke-gray-600" stroke-width="1" />
    </svg>
</template>
