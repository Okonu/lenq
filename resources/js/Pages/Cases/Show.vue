<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import IntegratedCaseView from '@/Components/Cases/IntegratedCaseView.vue';

const props = defineProps({
    case: Object,
});

// Create a reference to avoid using the reserved keyword 'case' in template expressions
const legalCase = computed(() => props.case);

// Active tab state
const activeTab = ref('overview'); // 'overview' or 'integrated'

const selectedDocument = ref(null);

// Status badge colors
const getStatusColor = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'closed':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
    }
};

// Format date
const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

// Computed properties for documents and conversations
const documents = computed(() => legalCase.value.documents || []);
const conversations = computed(() => legalCase.value.conversations || []);

// Format document type
const formatDocType = (type) => {
    return type.charAt(0).toUpperCase() + type.slice(1);
};

// Get document type color
const getDocTypeColor = (type) => {
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

// Delete case confirmation
const confirmDelete = () => {
    if (confirm('Are you sure you want to delete this case? This action cannot be undone.')) {
        // Submit form to delete the case
        document.getElementById('delete-case-form').submit();
    }
};

// Handle document uploaded event
const handleDocumentUploaded = (document) => {
    // Add to documents list locally
    documents.value.unshift(document);
};

// Handle selecting a document for analysis
const handleSelectDocument = (doc) => {
    selectedDocument.value = doc;
    activeTab.value = 'integrated'; // Switch to the integrated view
};
</script>

<template>
    <Head :title="legalCase.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                    {{ legalCase.title }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('cases.index')"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                    <Link
                        :href="route('cases.edit', legalCase.id)"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Edit
                    </Link>
                    <button
                        @click="confirmDelete"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>

                    <!-- Hidden form for delete action -->
                    <form id="delete-case-form" :action="route('cases.destroy', legalCase.id)" method="POST" class="hidden">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token">
                    </form>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Case Overview Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px">
                        <button
                            @click="activeTab = 'overview'"
                            :class="[
                                'py-4 px-6 text-sm font-medium',
                                activeTab === 'overview'
                                    ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                        >
                            Overview
                        </button>
                        <button
                            @click="activeTab = 'integrated'"
                            :class="[
                                'py-4 px-6 text-sm font-medium',
                                activeTab === 'integrated'
                                    ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                        >
                            Documents & Chat
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left Column - Case Info -->
                        <div class="md:col-span-2 space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Case Details</h3>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="space-y-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ legalCase.title }}</p>
                                        </div>

                                        <div v-if="legalCase.client">
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Client</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                                <Link :href="route('clients.show', legalCase.client.id)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                    {{ legalCase.client.name }}
                                                </Link>
                                            </p>
                                        </div>

                                        <div v-if="legalCase.description">
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ legalCase.description }}</p>
                                        </div>

                                        <div v-if="legalCase.case_number">
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Case Number</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ legalCase.case_number }}</p>
                                        </div>

                                        <div v-if="legalCase.jurisdiction">
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Jurisdiction</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ legalCase.jurisdiction }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Recent Activity</h3>

                                <div v-if="documents.length > 0 || conversations.length > 0" class="space-y-3">
                                    <!-- Recent Documents -->
                                    <div v-for="doc in documents.slice(0, 3)" :key="`doc-${doc.id}`" class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 h-8 w-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {{ doc.title }}
                                                    </div>
                                                    <span class="ml-2 text-xs px-2 py-0.5 rounded-full" :class="getDocTypeColor(doc.type)">
                                                        {{ formatDocType(doc.type) }}
                                                    </span>
                                                </div>
                                                <div class="mt-1 flex justify-between">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ formatDate(doc.created_at) }}
                                                    </span>
                                                    <button
                                                        @click="handleSelectDocument(doc)"
                                                        class="text-xs text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                                    >
                                                        Analyze in Chat
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recent Conversations -->
                                    <div v-for="conv in conversations.slice(0, 3)" :key="`conv-${conv.id}`" class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <Link :href="route('chat.show', conv.id)">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3 flex-1">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ conv.title }}
                                                    </div>
                                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ formatDate(conv.created_at) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </Link>
                                    </div>
                                </div>

                                <div v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    No activity yet. Start by adding documents or conversations.
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Status Info -->
                        <div class="space-y-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Status</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-medium rounded-full" :class="getStatusColor(legalCase.status)">
                                            {{ legalCase.status ? legalCase.status.charAt(0).toUpperCase() + legalCase.status.slice(1) : 'Active' }}
                                        </span>
                                    </div>

                                    <div>
                                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">Created</h4>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(legalCase.created_at) }}</p>
                                    </div>

                                    <div>
                                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">Last Updated</h4>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(legalCase.updated_at) }}</p>
                                    </div>

                                    <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">Documents</h4>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ documents.length }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">Conversations</h4>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ conversations.length }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Quick Actions</h3>
                                <div class="space-y-2">
                                    <button
                                        @click="activeTab = 'integrated'"
                                        class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        Open AI Assistant
                                    </button>

                                    <button
                                        @click="activeTab = 'integrated'"
                                        class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Manage Documents
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Integrated Documents & Chat Tab -->
                <div v-if="activeTab === 'integrated'" class="p-6">
                    <IntegratedCaseView
                        :case="legalCase"
                        :documents="documents"
                        :initial-selected-document="selectedDocument"
                        @document-uploaded="handleDocumentUploaded"
                        @select-document="handleSelectDocument"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
