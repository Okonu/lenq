<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    url: {
        type: String,
        required: true
    },
    title: {
        type: String,
        default: 'Document'
    }
});

const isLoading = ref(true);
const error = ref(null);

onMounted(() => {
    checkDocument();
});

const checkDocument = async () => {
    try {
        const response = await fetch(props.url, { method: 'HEAD' });
        if (!response.ok) {
            error.value = 'Document could not be loaded';
        }
        isLoading.value = false;
    } catch (err) {
        error.value = 'Error accessing document';
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="w-full h-full">
        <div v-if="isLoading" class="flex justify-center items-center h-64">
            <svg class="animate-spin h-10 w-10 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div v-else-if="error" class="flex justify-center items-center h-64">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ error }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Please try downloading the document instead.
                </p>
                <div class="mt-6">
                    <a
                        :href="url"
                        download
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Download document
                    </a>
                </div>
            </div>
        </div>

        <div v-else class="w-full h-full">
            <!-- For PDFs, we display an iframe -->
            <iframe
                v-if="url.toLowerCase().endsWith('.pdf')"
                :src="url"
                class="w-full h-[600px] border-0 rounded-lg"
                title="Document Viewer"
            ></iframe>

            <!-- For other file types, we offer a download button -->
            <div v-else class="text-center py-10">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ title }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    This document type cannot be previewed in the browser.
                </p>
                <div class="mt-6">
                    <a
                        :href="url"
                        download
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Download document
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
