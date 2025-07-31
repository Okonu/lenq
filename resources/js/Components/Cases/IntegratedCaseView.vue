<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import IntegratedChat from '@/Components/Cases/IntegratedChat.vue';
import DocumentUploader from '@/Components/Cases/DocumentUploader.vue';

const emit = defineEmits(['document-uploaded', 'select-document']);
const props = defineProps({
    case: Object,
    documents: Array,
});

const legalCase = computed(() => props.case);
const activeTab = ref('chat'); // 'chat' or 'documents'
const currentConversation = ref(null);

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

// Handle document upload completion
const handleDocumentUploaded = (document) => {
    // Notify parent or refresh document list
    emit('document-uploaded', document);
};

// Handle new conversation creation
const handleConversationCreated = (conversation) => {
    currentConversation.value = conversation;
};

const handleStartNewChat = () => {
    currentConversation.value = null;
    activeTab.value = 'chat';
};
</script>

<template>
    <div class="integrated-case-view">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <button
                    @click="activeTab = 'chat'"
                    :class="[
                        'py-4 px-6 text-sm font-medium',
                        activeTab === 'chat'
                            ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                    ]"
                >
                    AI Assistant
                </button>
                <button
                    @click="activeTab = 'documents'"
                    :class="[
                        'py-4 px-6 text-sm font-medium',
                        activeTab === 'documents'
                            ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                    ]"
                >
                    Documents
                </button>
            </nav>
        </div>

        <!-- Chat Tab -->
        <div v-if="activeTab === 'chat'" class="p-4">
            <div v-if="currentConversation" class="mb-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ currentConversation.title }}
                </h3>
                <button
                    @click="handleStartNewChat"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    Start New Chat
                </button>
            </div>

            <IntegratedChat
                :case-id="legalCase.id"
                :case-title="legalCase.title"
                :initial-conversation="currentConversation"
                :documents="props.documents"
                @conversation-created="handleConversationCreated"
            />
        </div>

        <!-- Documents Tab -->
        <div v-if="activeTab === 'documents'" class="p-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Case Documents
            </h3>

            <!-- Document List -->
            <div v-if="props.documents && props.documents.length > 0" class="mb-6 space-y-3">
                <div v-for="doc in props.documents" :key="doc.id"
                     class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-10 w-10 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ doc.title }}
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getDocTypeColor(doc.type)">
                                    {{ formatDocType(doc.type) }}
                                </span>
                            </div>
                            <div class="mt-1 flex justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Uploaded: {{ new Date(doc.created_at).toLocaleDateString() }}
                                </span>
                                <div class="space-x-2">
                                    <Link :href="route('documents.show', doc.id)" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        View
                                    </Link>
                                    <button
                                        @click="$emit('select-document', doc); activeTab = 'chat'"
                                        class="text-xs text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                    >
                                        Analyze in Chat
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload New Document -->
            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Upload New Document</h3>
                <DocumentUploader
                    :case-id="legalCase.id"
                    :inline-mode="false"
                    @document-uploaded="handleDocumentUploaded"
                />
            </div>
        </div>
    </div>
</template>
