<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap gap-1 px-6 py-4">
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="link.url === null"
                class="rounded-md px-3 py-1 text-sm text-gray-400 dark:text-gray-600"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                v-html="link.label"
                class="rounded-md px-3 py-1 text-sm"
                :class="link.active
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
            />
        </template>
    </div>
</template>
