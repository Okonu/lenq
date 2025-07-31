<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, watch, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    conversation: Object,
    messages: Array,
    documents: Array, // Available documents to reference
});

const messagesContainer = ref(null);
const newMessage = ref('');
const localMessages = ref([...props.messages]);
const sending = ref(false);
const showTips = ref(false);
const editingMode = ref(false);
const editedTitle = ref(props.conversation.title);
const selectedDocument = ref(null);
const showDocumentSelector = ref(false);

// Scroll to bottom of messages
const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Watch for changes to messages and scroll to bottom
watch(localMessages, () => {
    nextTick(() => {
        scrollToBottom();
    });
});

// Scroll to bottom on initial load
onMounted(() => {
    scrollToBottom();
});

// Toggle document selector
const toggleDocumentSelector = () => {
    showDocumentSelector.value = !showDocumentSelector.value;
};

// Select a document
const selectDocument = (doc) => {
    selectedDocument.value = doc;
    showDocumentSelector.value = false;

    // Append document reference to message if message is not empty
    if (newMessage.value.trim() !== '') {
        newMessage.value += `\n\nReferencing document: ${doc.title}`;
    } else {
        newMessage.value = `Please analyze the document: ${doc.title}`;
    }

    // Focus the textarea
    nextTick(() => {
        const textarea = document.getElementById('message-input');
        textarea.focus();

        // Set cursor at the end of text
        const len = textarea.value.length;
        textarea.setSelectionRange(len, len);

        // Trigger autoGrow
        autoGrow({ target: textarea });
    });
};

// Clear document selection
const clearDocumentSelection = () => {
    selectedDocument.value = null;
};

// Send a new message
const sendMessage = async () => {
    if (newMessage.value.trim() === '' || sending.value) {
        return;
    }

    sending.value = true;

    try {
        // Create form data to include document_id if selected
        const formData = {
            message: newMessage.value,
            document_id: selectedDocument.value ? selectedDocument.value.id : null
        };

        const response = await axios.post(
            route('chat.message.store', props.conversation.id),
            formData
        );

        // Add messages to local state
        localMessages.value.push(response.data.userMessage);
        localMessages.value.push(response.data.aiMessage);

        // Clear input and document selection
        newMessage.value = '';
        selectedDocument.value = null;

    } catch (error) {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    } finally {
        sending.value = false;
    }
};

// Handle Ctrl+Enter or Cmd+Enter to send
const handleKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        sendMessage();
    }
};

// Confirm delete conversation
const confirmDelete = () => {
    if (confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
        router.delete(route('chat.destroy', props.conversation.id));
    }
};

// Start editing title
const startEditing = () => {
    editingMode.value = true;
    editedTitle.value = props.conversation.title;
    nextTick(() => {
        document.getElementById('conversation-title').focus();
    });
};

// Save edited title
const saveTitle = async () => {
    if (editedTitle.value.trim() === '') {
        return;
    }

    try {
        await axios.patch(
            route('chat.update', props.conversation.id),
            {
                title: editedTitle.value,
                legal_case_id: props.conversation.legal_case_id
            }
        );

        // Update local state
        props.conversation.title = editedTitle.value;
        editingMode.value = false;
    } catch (error) {
        console.error('Error updating title:', error);
        alert('Failed to update title. Please try again.');
    }
};

// Cancel editing
const cancelEditing = () => {
    editingMode.value = false;
    editedTitle.value = props.conversation.title;
};

// Format message timestamp
const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Determine if a new date divider should be shown
const shouldShowDateDivider = (message, index) => {
    if (index === 0) return true;

    const prevDate = new Date(localMessages.value[index - 1].created_at).toLocaleDateString();
    const currDate = new Date(message.created_at).toLocaleDateString();

    return prevDate !== currDate;
};

// Format date for display
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
};

// Resize textarea as content grows
const autoGrow = (event) => {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = `${textarea.scrollHeight}px`;
};

// Case information if available
const caseInfo = computed(() => {
    return props.conversation.legal_case || null;
});

// Quick responses
const quickResponses = [
    "Can you explain this legal concept?",
    "What are the key elements of this contract?",
    "What potential risks should I be aware of?",
    "What are my options in this situation?",
    "Can you summarize the main points?",
    "What precedents might apply here?"
];

// Insert quick response into input
const useQuickResponse = (response) => {
    newMessage.value = response;

    // Focus the textarea
    nextTick(() => {
        const textarea = document.getElementById('message-input');
        textarea.focus();

        // Set cursor at the end of text
        const len = textarea.value.length;
        textarea.setSelectionRange(len, len);

        // Trigger autoGrow
        autoGrow({ target: textarea });
    });
};

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
</script>

<template>
    <Head :title="conversation.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center">
                    <template v-if="editingMode">
                        <input
                            id="conversation-title"
                            v-model="editedTitle"
                            type="text"
                            class="flex-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            @keydown.enter="saveTitle"
                            @keydown.esc="cancelEditing"
                        />
                        <div class="flex items-center ml-2 space-x-2">
                            <button
                                @click="saveTitle"
                                class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                title="Save"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                            <button
                                @click="cancelEditing"
                                class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                title="Cancel"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <template v-else>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate flex items-center">
                            {{ conversation.title }}
                            <button
                                @click="startEditing"
                                class="ml-2 p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                title="Edit Title"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                        </h2>
                    </template>
                </div>

                <div class="flex items-center space-x-2">
                    <Link
                        :href="route('chat.index')"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
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
                </div>
            </div>
        </template>

        <div class="h-[calc(100vh-9rem)] flex flex-col">
            <!-- Case Info Bar (if associated with a case) -->
            <div v-if="caseInfo" class="bg-blue-50 dark:bg-blue-900/30 border-b border-blue-100 dark:border-blue-800 px-4 py-2 text-blue-800 dark:text-blue-200 text-sm mb-4 rounded-t-lg">
                <div class="flex items-center">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Associated Case: <strong>{{ caseInfo.title }}</strong></span>
                </div>
            </div>

            <!-- Selected Document Info -->
            <div v-if="selectedDocument" class="bg-green-50 dark:bg-green-900/30 border-b border-green-100 dark:border-green-800 px-4 py-2 text-green-800 dark:text-green-200 text-sm mb-4 rounded-lg flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>
                        Document selected: <strong>{{ selectedDocument.title }}</strong>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getDocTypeColor(selectedDocument.type)">
                            {{ formatDocType(selectedDocument.type) }}
                        </span>
                    </span>
                </div>
                <button @click="clearDocumentSelection" class="text-green-700 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Chat Interface -->
            <div class="flex-1 flex flex-col mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <!-- Messages Area -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                    <template v-if="localMessages.length">
                        <div v-for="(message, index) in localMessages" :key="message.id">
                            <!-- Date Divider -->
                            <div v-if="shouldShowDateDivider(message, index)" class="flex items-center my-6">
                                <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                                <div class="mx-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ formatDate(message.created_at) }}
                                </div>
                                <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                            </div>

                            <!-- Message -->
                            <div
                                :class="[
                                    'flex',
                                    message.is_user ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <div
                                    :class="[
                                        'max-w-2xl rounded-2xl px-4 py-3 shadow-sm',
                                        message.is_user
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                    ]"
                                >
                                    <!-- Message content -->
                                    <div class="whitespace-pre-wrap text-sm">{{ message.content }}</div>

                                    <!-- Document reference (if any) -->
                                    <div v-if="message.document_id" class="mt-2 pt-2 border-t border-blue-500/20 dark:border-blue-400/20">
                                        <div class="flex items-center text-xs" :class="message.is_user ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Referenced document: {{ message.document ? message.document.title : 'Document' }}
                                        </div>
                                    </div>

                                    <!-- Timestamp -->
                                    <div class="text-xs mt-1 flex justify-end"
                                         :class="message.is_user ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'"
                                    >
                                        {{ formatTime(message.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="h-full flex flex-col items-center justify-center p-8 text-center">
                        <div class="p-4 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                            <svg class="h-8 w-8 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Start the conversation</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-md">
                            Ask a question about legal concepts, document analysis, case research, or any other legal matter.
                        </p>

                        <!-- Quick Start Suggestions -->
                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-2 max-w-md">
                            <button v-for="response in quickResponses.slice(0, 4)" :key="response"
                                    @click="useQuickResponse(response)"
                                    class="text-left px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                {{ response }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Document Selector Dropdown -->
                <div v-if="showDocumentSelector" class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Select a Document to Reference</h3>
                        <button @click="toggleDocumentSelector" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-3 max-h-64 overflow-y-auto">
                        <div v-if="documents && documents.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button v-for="doc in documents" :key="doc.id" @click="selectDocument(doc)"
                                    class="text-left flex items-center p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <div class="flex-shrink-0 h-8 w-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-2">
                                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ doc.title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium mr-1"
                                              :class="getDocTypeColor(doc.type)">
                                            {{ formatDocType(doc.type) }}
                                        </span>
                                        <span v-if="doc.created_at" class="truncate">
                                            {{ formatDate(doc.created_at) }}
                                        </span>
                                    </p>
                                </div>
                            </button>
                        </div>
                        <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
                            No documents available.
                            <Link :href="route('documents.create')" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                                Upload a document
                            </Link>
                            first.
                        </div>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800">
                    <form @submit.prevent="sendMessage" class="space-y-3">
                        <div class="relative">
                            <textarea
                                id="message-input"
                                v-model="newMessage"
                                @keydown="handleKeydown"
                                @input="autoGrow"
                                placeholder="Type your legal question..."
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                rows="3"
                                style="min-height: 80px; max-height: 200px; resize: none;"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Press Ctrl+Enter to send
                                </div>
                                <button
                                    type="button"
                                    @click="toggleDocumentSelector"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Attach Document
                                </button>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button
                                    type="button"
                                    @click="showTips = !showTips"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tips
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                    :disabled="newMessage.trim() === '' || sending"
                                >
                                    <span v-if="sending">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Sending...
                                    </span>
                                    <span v-else class="flex items-center">
                                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                        Send
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Panel -->
            <div v-if="showTips" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mb-4">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 flex justify-between items-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Legal AI Assistant Tips</h3>
                    <button @click="showTips = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Effective Questions</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Be specific about the legal issue or document</li>
                                <li>• Provide relevant context and jurisdiction</li>
                                <li>• Ask one question at a time for detailed answers</li>
                                <li>• Include relevant dates, figures, and facts</li>
                            </ul>
                        </div>
                        <div class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Document Analysis Tips</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Use "Attach Document" to reference specific files</li>
                                <li>• Ask for summaries or key points in a document</li>
                                <li>• Request analysis of specific clauses or sections</li>
                                <li>• Ask for comparisons between different documents</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 italic text-center">
                        Remember: This AI assistant provides general legal information only, not legal advice. Always consult with a qualified attorney for your specific situation.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
