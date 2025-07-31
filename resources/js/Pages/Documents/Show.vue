<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    document: Object,
    documentUrl: String,
});

const confirmDelete = () => {
    if (confirm('Are you sure you want to delete this document?')) {
        router.delete(route('documents.destroy', props.document.id));
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString();
};

const getTypeLabel = (type) => {
    switch (type) {
        case 'contract': return 'Contract';
        case 'case': return 'Case Law Document';
        case 'discovery': return 'Discovery Document';
        default: return 'General Legal Document';
    }
};

const getAnalysisContent = () => {
    if (!props.document.analysis) return null;

    switch (props.document.type) {
        case 'contract':
            return props.document.analysis.review;
        case 'case':
            return props.document.analysis.case_analysis;
        case 'discovery':
            return props.document.analysis.discovery_analysis;
        default:
            return props.document.analysis.analysis;
    }
};
</script>

<template>
    <Head :title="document.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                    {{ document.title }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('documents.index')"
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md text-sm hover:bg-gray-300 dark:hover:bg-gray-600"
                    >
                        Back to Documents
                    </Link>
                    <button
                        @click="confirmDelete"
                        class="px-3 py-1 bg-red-600 text-white rounded-md text-sm hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-start">
                            <!-- Document Info -->
                            <div class="w-full md:w-1/3 mb-6 md:mb-0 md:pr-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                    Document Information
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">File Name</div>
                                        <div class="text-gray-900 dark:text-gray-100">{{ document.title }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Document Type</div>
                                        <div class="text-gray-900 dark:text-gray-100">{{ getTypeLabel(document.type) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Uploaded On</div>
                                        <div class="text-gray-900 dark:text-gray-100">{{ formatDate(document.created_at) }}</div>
                                    </div>
                                    <div class="pt-4">
                                        <a
                                            :href="documentUrl"
                                            target="_blank"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Original Document
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Analysis Results -->
                            <div class="w-full md:w-2/3 md:border-l md:border-gray-200 md:dark:border-gray-700 md:pl-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                    AI Analysis Results
                                </h3>
                                <div v-if="getAnalysisContent()" class="prose dark:prose-invert max-w-none">
                                    <div class="whitespace-pre-wrap text-gray-800 dark:text-gray-200">
                                        {{ getAnalysisContent() }}
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 dark:text-gray-400 italic">
                                    No analysis available for this document.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
