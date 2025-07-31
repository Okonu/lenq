<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import axios from 'axios';
import ChatDocumentUploader from './ChatDocumentUploader.vue';

const props = defineProps({
    caseId: {
        type: Number,
        required: true
    },
    caseTitle: {
        type: String,
        required: true
    },
    initialConversation: {
        type: Object,
        default: null
    },
    documents: {
        type: Array,
        default: () => []
    },
    initialSelectedDocument: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['conversation-created', 'message-sent', 'document-uploaded']);

// State
const conversation = ref(props.initialConversation);
const localMessages = ref([]);
const newMessage = ref('');
const sending = ref(false);
const messagesContainer = ref(null);
const showDocumentUploader = ref(false);
const showDocumentSelector = ref(false);
const selectedDocument = ref(props.initialSelectedDocument);
const localDocuments = ref([...props.documents]);
const showTips = ref(false);
const attachedDocument = ref(null);
const documentAnalyzing = ref(false);
const justUploadedDocument = ref(null);
const documentStatusPolling = ref(null);


// Watch for changes to initialSelectedDocument prop
watch(() => props.initialSelectedDocument, (newDoc) => {
    if (newDoc) {
        selectDocument(newDoc);
    }
});

// Watch for document uploads to start polling for status
watch(justUploadedDocument, (newDoc) => {
    if (newDoc) {
        documentAnalyzing.value = true;
        startDocumentStatusPolling(newDoc);
    }
});

const createConversation = async () => {
    if (conversation.value) return true;

    try {
        console.log('Creating conversation with API for case ID:', props.caseId);

        if (!props.caseId) {
            console.error('Cannot create conversation: caseId is missing');
            alert('Missing case ID. Please refresh the page.');
            return false;
        }

        // Generate title using case name and date/time
        const now = new Date();
        const formattedDate = now.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        const formattedTime = now.toLocaleTimeString(undefined, {
            hour: '2-digit',
            minute: '2-digit'
        });

        const title = `${props.caseTitle || 'Untitled Case'} - ${formattedDate} ${formattedTime}`;

        // Use the API endpoint that doesn't cause navigation
        try {
            // Use route() helper function if available, or direct URL
            const apiUrl = route ? route('api.chat.create') : '/api/chat/create';

            const response = await axios.post(apiUrl, {
                title: title,
                legal_case_id: props.caseId,
                auto_generate_title: true
            });

            if (response.data && response.data.success && response.data.conversation) {
                conversation.value = response.data.conversation;
                emit('conversation-created', conversation.value);
                return true;
            } else {
                console.error('Conversation creation failed or invalid response format:', response.data);
                return false;
            }
        } catch (error) {
            console.error('Error creating conversation:', error);
            if (error.response) {
                console.error('API error details:', {
                    status: error.response.status,
                    data: error.response.data
                });
            }
            alert('Failed to create conversation. Please try again.');
            return false;
        }
    } catch (error) {
        console.error('Exception during conversation creation:', error);
        alert('Failed to create conversation. Please try again.');
        return false;
    }
};

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

// Load messages for existing conversation
const loadMessages = async () => {
    if (!conversation.value) return;

    try {
        const response = await axios.get(route('chat.show', conversation.value.id));
        localMessages.value = response.data.messages || [];
    } catch (error) {
        console.error('Error loading messages:', error);
    }
};

// Initial setup
onMounted(async () => {
    if (conversation.value) {
        await loadMessages();
    }
    scrollToBottom();
});

// Poll for document status updates
const startDocumentStatusPolling = (document) => {
    // Clear any existing polling
    if (documentStatusPolling.value) {
        clearInterval(documentStatusPolling.value);
    }

    // Set polling interval (every 3 seconds)
    documentStatusPolling.value = setInterval(async () => {
        try {
            const response = await axios.get(route('chat.document.status', document.id));

            // If analysis is complete, update the document
            if (response.data.status === 'completed') {
                // Update document in local array
                const index = localDocuments.value.findIndex(d => d.id === document.id);
                if (index !== -1) {
                    localDocuments.value[index] = response.data.document;
                }

                // Clear analysis status and polling
                if (justUploadedDocument.value && justUploadedDocument.value.id === document.id) {
                    documentAnalyzing.value = false;
                    justUploadedDocument.value = null;
                    clearInterval(documentStatusPolling.value);
                }
            }
        } catch (error) {
            console.error('Error checking document status:', error);
        }
    }, 3000);
};

// Delete a document
const deleteDocument = async (docId) => {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        try {
            await axios.delete(route('chat.document.delete', docId));

            // Remove from local documents array
            localDocuments.value = localDocuments.value.filter(d => d.id !== docId);

            // Clear selection if this was the selected document
            if (selectedDocument.value && selectedDocument.value.id === docId) {
                selectedDocument.value = null;
            }

            // Close document selector if empty
            if (localDocuments.value.length === 0) {
                showDocumentSelector.value = false;
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            alert('Failed to delete document. Please try again.');
        }
    }
};

// Toggle document uploader
const toggleDocumentUploader = () => {
    showDocumentUploader.value = !showDocumentUploader.value;
    if (showDocumentUploader.value) {
        showDocumentSelector.value = false;
    }
};

// Toggle document selector
const toggleDocumentSelector = () => {
    showDocumentSelector.value = !showDocumentSelector.value;
    if (showDocumentSelector.value) {
        showDocumentUploader.value = false;
    }
};

// Document uploaded handler
const handleDocumentUploaded = (document) => {
    localDocuments.value.unshift(document);
    showDocumentUploader.value = false;

    // Set as the just uploaded document to show analysis status
    justUploadedDocument.value = document;
    documentAnalyzing.value = true;

    // Optionally auto-select the document
    selectDocument(document);

    // Emit to parent component
    emit('document-uploaded', document);
};

// Handle document upload cancellation
const handleUploadCancelled = () => {
    showDocumentUploader.value = false;
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
        const textarea = window.document.getElementById('message-input');
        if (textarea) {
            textarea.focus();

            // Set cursor at the end of text
            const len = textarea.value.length;
            textarea.setSelectionRange(len, len);

            // Trigger autoGrow
            autoGrow({ target: textarea });
        }
    });
};

const handleDocumentSelected = (docObject) => {
    // Just attach the document to the current message, don't analyze yet
    attachedDocument.value = docObject;
    showDocumentUploader.value = false;

    console.log('Document attached:', docObject);

    // If the message input is empty, create a default prompt
    if (newMessage.value.trim() === '') {
        newMessage.value = `Please analyze this document: ${docObject.title}`;

        // Trigger autoGrow for textarea
        nextTick(() => {
            const textarea = window.document.getElementById('message-input');
            if (textarea) {
                textarea.focus();

                // Set cursor at the end of text
                const len = textarea.value.length;
                textarea.setSelectionRange(len, len);

                // Trigger autoGrow
                autoGrow({ target: textarea });
            }
        });
    }
};

// Clear document selection
const clearDocumentSelection = () => {
    selectedDocument.value = null;
};

// Send a new message
const sendMessage = async () => {
    // Check if both message and document are empty
    if ((newMessage.value.trim() === '' && !attachedDocument.value) || sending.value) {
        return;
    }

    // Create conversation if needed
    if (!conversation.value) {
        console.log('No active conversation, creating one...');
        const success = await createConversation();

        if (!success || !conversation.value) {
            console.error('Failed to create conversation, aborting sendMessage');
            return;
        }
    }

    console.log('Sending message to conversation ID:', conversation.value.id);
    sending.value = true;

    try {
        // Prepare data for message
        const messageData = {
            // If there's a document but no message, set a default message
            message: newMessage.value.trim() !== '' ? newMessage.value :
                (attachedDocument.value ? `Please analyze this document: ${attachedDocument.value.title}` : ''),
            document_id: attachedDocument.value ? attachedDocument.value.id : null
        };

        console.log('Sending message with data:', messageData);

        // Post the message using regular axios (this endpoint should return JSON)
        const response = await axios.post(
            `/chat/${conversation.value.id}/message`,
            messageData
        );

        console.log('Message sent successfully, response:', response.data);

        // Update conversation with potentially updated title
        if (response.data.conversation) {
            conversation.value = response.data.conversation;
        }

        // Add messages to local state
        localMessages.value.push(response.data.userMessage);
        localMessages.value.push(response.data.aiMessage);

        // Clear input and document attachment
        newMessage.value = '';
        attachedDocument.value = null;

        emit('message-sent', {
            messages: [response.data.userMessage, response.data.aiMessage],
            conversation: conversation.value
        });

    } catch (error) {
        console.error('Error sending message:', error);

        if (error.response) {
            console.error('API error details:', {
                status: error.response.status,
                data: error.response.data
            });
        }

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

// Resize textarea as content grows
const autoGrow = (event) => {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = `${textarea.scrollHeight}px`;
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
        const textarea = window.document.getElementById('message-input');
        if (textarea) {
            textarea.focus();

            // Set cursor at the end of text
            const len = textarea.value.length;
            textarea.setSelectionRange(len, len);

            // Trigger autoGrow
            autoGrow({ target: textarea });
        }
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

// Clean up on component unmount
onUnmounted(() => {
    if (documentStatusPolling.value) {
        clearInterval(documentStatusPolling.value);
    }
});

const clearAttachedDocument = () => {
    attachedDocument.value = null;
};
</script>

<template>
    <div class="integrated-chat h-full flex flex-col">
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

        <!-- Recently Uploaded Document Analysis Status -->
        <div v-if="justUploadedDocument && documentAnalyzing" class="bg-blue-50 dark:bg-blue-900/30 border-b border-blue-100 dark:border-blue-800 px-4 py-2 text-blue-800 dark:text-blue-200 text-sm mb-4 rounded-lg flex items-center justify-between">
            <div class="flex items-center">
                <svg class="h-4 w-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>
                    Analyzing document: <strong>{{ justUploadedDocument.title }}</strong>
                </span>
            </div>
        </div>

        <!-- Chat Interface -->
        <div class="flex-1 flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
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
                                <div class="whitespace-pre-wrap text-sm">
                                    <template v-if="!message.is_user && message.content.includes('I don\'t currently have the capability to analyze documents')">
                                        <!-- Special handling for generic "can't analyze document" responses -->
                                        <div class="text-orange-600 dark:text-orange-400 font-medium mb-2">
                                            Document Analysis Error
                                        </div>
                                        <p>There was an issue accessing the document content. This could be because:</p>
                                        <ul class="list-disc pl-5 my-2 space-y-1">
                                            <li>The document is still being processed</li>
                                            <li>The document wasn't properly uploaded to the AI system</li>
                                            <li>The document format is not supported</li>
                                        </ul>
                                        <p>Please try uploading the document again or contact support if the issue persists.</p>
                                    </template>
                                    <template v-else>
                                        {{ message.content }}
                                    </template>
                                </div>

                                <!-- Document reference (if any) -->
                                <div v-if="message.document_id" class="mt-2 pt-2 border-t border-blue-500/20 dark:border-blue-400/20">
                                    <div class="flex items-center text-xs" :class="message.is_user ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'">
                                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Referenced document: {{ message.document ? message.document.title : 'Document' }}
                                        <span v-if="message.document && message.document.type" class="ml-1 px-1.5 py-0.5 rounded-full text-xxs" :class="getDocTypeColor(message.document.type)">
                                            {{ formatDocType(message.document.type) }}
                                        </span>
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
                        Ask a question about this case, analyze a document, or get legal information.
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

            <!-- Document Uploader (if showing) -->
            <ChatDocumentUploader
                v-if="showDocumentUploader"
                :case-id="caseId"
                @document-selected="handleDocumentSelected"
                @upload-cancelled="showDocumentUploader = false"
            />

            <!-- Document Selector Dropdown (if showing) -->
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
                    <!-- Here's the fix - add the v-if to this div -->
                    <div v-if="localDocuments.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div v-for="doc in localDocuments" :key="doc.id"
                             class="text-left flex flex-col p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <!-- Document content -->
                        </div>
                    </div>
                    <!-- Now the v-else has a corresponding v-if -->
                    <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
                        No documents available.
                        <button @click="toggleDocumentUploader(); toggleDocumentSelector()" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                            Upload a document
                        </button>
                        first.
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Input -->
        <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800">
            <!-- Show attached document if there is one -->
            <div v-if="attachedDocument" class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="h-4 w-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Attached:</strong> {{ attachedDocument.title }}
                    </span>
                </div>
                <button @click="clearAttachedDocument" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="sendMessage" class="space-y-3">
                <div class="relative">
                    <textarea
                        id="message-input"
                        v-model="newMessage"
                        @keydown="handleKeydown"
                        @input="autoGrow"
                        :placeholder="attachedDocument ? 'Type instructions for analyzing this document...' : 'Type your legal question...'"
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

                        <div class="flex space-x-2">
                            <button
                                type="button"
                                @click="toggleDocumentUploader"
                                :disabled="attachedDocument !== null"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                                :class="{ 'bg-blue-50 border-blue-300 dark:bg-blue-900/20 dark:border-blue-800': showDocumentUploader }"
                            >
                                <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Attach Document
                            </button>

                            <button
                                type="button"
                                @click="toggleDocumentSelector"
                                :disabled="attachedDocument !== null"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                                :class="{ 'bg-blue-50 border-blue-300 dark:bg-blue-900/20 dark:border-blue-800': showDocumentSelector }"
                            >
                                <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Browse Documents
                            </button>
                        </div>
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
                            :disabled="(newMessage.trim() === '' && !attachedDocument) || sending"
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
    <div v-if="showTips" class="mt-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
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
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Case-Specific Questions</h4>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• Ask about legal strategies for this specific case</li>
                        <li>• Request analysis of case-specific documents</li>
                        <li>• Ask for relevant precedents that might apply</li>
                        <li>• Get help drafting case-related communications</li>
                    </ul>
                </div>
                <div class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Document Analysis Tips</h4>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• Upload a document directly in this conversation</li>
                        <li>• Reference existing case documents for analysis</li>
                        <li>• Ask about specific clauses or paragraphs</li>
                        <li>• Request comparisons between multiple documents</li>
                    </ul>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 italic text-center">
                Remember: This AI assistant provides general legal information only, not legal advice. Always consult with a qualified attorney for your specific situation.
            </div>
        </div>
    </div>
</template>

<style scoped>
.integrated-chat {
    max-height: calc(100vh - 200px);
    min-height: 400px;
}
</style>
