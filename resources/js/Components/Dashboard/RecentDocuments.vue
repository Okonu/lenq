<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    documents: {
        type: Array,
        default: () => []
    }
});

// Format dates
const formatDate = (dateString) => {
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

// Format doc type
const formatDocType = (type) => {
    return type.charAt(0).toUpperCase() + type.slice(1);
};

// Get document type color class
const getDocTypeColorClass = (type) => {
    switch (type) {
        case 'contract':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'case':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'discovery':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Documents</h3>
            <Link
                :href="route('documents.index')"
                class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 inline-flex items-center"
            >
                View All
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </Link>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-if="documents.length > 0">
                <div v-for="document in documents" :key="document.id"
                     class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150"
                >
                    <Link :href="route('documents.show', document.id)" class="block">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="ml-4 flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ document.title }}
                                    </p>
                                    <div class="ml-2 flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="getDocTypeColorClass(document.type)"
                                        >
                                            {{ formatDocType(document.type) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-1 flex items-center">
                                    <span v-if="document.legal_case" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ document.legal_case.title }}
                                    </span>
                                    <span v-else class="text-xs text-gray-500 dark:text-gray-400">
                                        General
                                    </span>
                                    <span class="mx-1 text-xs text-gray-300 dark:text-gray-600">•</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDate(document.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
            <div v-else class="p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No documents</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by uploading a document.</p>
                <div class="mt-6">
                    <Link
                        :href="route('documents.create')"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Upload Document
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
