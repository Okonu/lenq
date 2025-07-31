<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    document: Object,
});

// Format date for display
const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();

    // Check if date is today
    if (date.toDateString() === now.toDateString()) {
        return `Today at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }

    // Check if date is yesterday
    const yesterday = new Date();
    yesterday.setDate(now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return `Yesterday at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }

    // If within the last 7 days, show the day name
    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(now.getDate() - 7);
    if (date > oneWeekAgo) {
        return `${date.toLocaleDateString(undefined, { weekday: 'long' })} at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }

    // Otherwise, show full date
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};

// Get document type icon based on file extension
const getDocumentIcon = (title) => {
    const extension = title.split('.').pop().toLowerCase();

    switch (extension) {
        case 'pdf':
            return 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253';
        case 'doc':
        case 'docx':
            return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
        default:
            return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
    }
};

// Get document type color
const getDocumentTypeColor = (type) => {
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

// Truncate long text
const truncate = (text, length = 60) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out shadow-sm">
        <Link :href="route('documents.show', document.id)" class="block p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getDocumentIcon(document.title)" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ truncate(document.title) }}
                        </h3>
                        <div class="ml-2 flex-shrink-0 flex space-x-1">
                            <span :class="['px-2 py-0.5 text-xs rounded-full', getDocumentTypeColor(document.type)]">
                                {{ document.type.charAt(0).toUpperCase() + document.type.slice(1) }}
                            </span>
                            <span v-if="document.legal_case" class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ truncate(document.legal_case.title, 20) }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-1 flex items-center text-xs text-gray-500 dark:text-gray-400">
                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ formatDate(document.created_at) }}</span>
                    </div>
                    <div v-if="document.analysis && document.analysis.summary" class="mt-2">
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                            {{ truncate(document.analysis.summary, 100) }}
                        </p>
                    </div>
                </div>
            </div>
        </Link>
    </div>
</template>
