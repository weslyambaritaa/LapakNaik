<script setup>
import { computed } from 'vue';

const props = defineProps({
    score: {
        type: Number,
        required: true,
    },
});

const radius = 54;
const circumference = 2 * Math.PI * radius;

const offset = computed(() => circumference * (1 - props.score / 100));

const colorClass = computed(() => {
    if (props.score >= 70) return 'stroke-emerald-500 dark:stroke-emerald-400';
    if (props.score >= 40) return 'stroke-amber-500 dark:stroke-amber-400';
    return 'stroke-red-500 dark:stroke-red-400';
});
</script>

<template>
    <svg viewBox="0 0 140 140" class="h-32 w-32">
        <circle cx="70" cy="70" :r="radius" fill="none" class="stroke-gray-100 dark:stroke-gray-700" stroke-width="12" />
        <circle
            cx="70"
            cy="70"
            :r="radius"
            fill="none"
            stroke-width="12"
            stroke-linecap="round"
            :class="colorClass"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="offset"
            transform="rotate(-90 70 70)"
            style="transition: stroke-dashoffset 0.6s ease"
        />
        <text x="70" y="66" text-anchor="middle" class="fill-gray-900 text-3xl font-bold dark:fill-gray-100" style="font-size: 32px">{{ score }}</text>
        <text x="70" y="88" text-anchor="middle" class="fill-gray-400 dark:fill-gray-500" style="font-size: 11px">dari 100</text>
    </svg>
</template>
