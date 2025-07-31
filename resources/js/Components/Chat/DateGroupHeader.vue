<script setup>
defineProps({
    date: {
        type: String,
        required: true
    }
});
</script>

<template>
    <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700">
        <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ date }}</h3>
    </div>
</template>
