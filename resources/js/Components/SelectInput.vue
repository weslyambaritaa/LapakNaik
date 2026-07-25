<script setup>
defineProps({
    modelValue: [String, Number],
});

defineEmits(['update:modelValue']);
</script>

<template>
    <select
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <slot />
    </select>
</template>
