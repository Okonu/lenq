<template>
    <div class="integrated-chat flex flex-col h-full">
        <!-- Chat Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ caseTitle || 'Legal Assistant' }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ conversation ? 'Active conversation' : 'Start a new conversation' }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-2">
                <button
                    @click="showDocumentGenerator = true"
                    class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-1"
                >
                    <FileText :size="16" />
                    <span>Generate Doc</span>
                </button>

                <button
                    @click="toggleDocumentUpload"
                    class="px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-1"
                >
                    <Upload :size="16" />
                    <span>Upload</span>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
            <!-- Thread-based messages -->
            <div v-for="(message, index) in localMessages" :key="message.id">
                <!-- Date divider -->
                <div v-if="shouldShowDateDivider(message, index)" class="flex items-center my-6">
                    <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                    <div class="mx-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ formatDate(message.created_at) }}
                    </div>
                    <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                </div>

                <!-- Enhanced Message Component -->
                <EnhancedMessage
                    :message="message"
                    :previous-messages="localMessages.slice(0, index)"
                    @reply="handleReply"
                    @bookmark="handleBookmark"
                    @react="handleReaction"
                />
            </div>

            <!-- Typing indicator -->
            <div v-if="sending" class="flex justify-start">
                <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl px-4 py-3">
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                        <span class="text-sm text-gray-500">AI is thinking...</span>
                    </div>
                </div>
            </div>

            <div ref="messagesEndRef" />
        </div>

        <!-- Document Upload Area -->
        <DocumentUploader
            v-if="showDocumentUploader"
            :case-id="caseId"
            :conversation-id="conversation?.id"
            @document-uploaded="handleDocumentUploaded"
            @close="showDocumentUploader = false"
        />

        <!-- Reply indicator -->
        <div v-if="replyingTo" class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm text-blue-600 dark:text-blue-400">
                    Replying to {{ replyingTo.is_user ? 'your message' : 'AI response' }}
                </span>
                <button @click="replyingTo = null" class="text-gray-400 hover:text-gray-600">
                    <X :size="16" />
                </button>
            </div>
        </div>

        <!-- Message Input -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-end space-x-3">
                <!-- Attachment button -->
                <button
                    @click="toggleDocumentUpload"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <Paperclip :size="20" />
                </button>

                <!-- Message input -->
                <div class="flex-1">
                    <textarea
                        v-model="newMessage"
                        @keydown="handleKeydown"
                        @input="autoGrow"
                        placeholder="Ask a legal question, request analysis, or generate a document..."
                        rows="1"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none dark:bg-gray-700 dark:text-white max-h-32"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Press Ctrl+Enter to send • Type "/" for quick commands
                    </p>
                </div>

                <!-- Send button -->
                <button
                    @click="sendMessage"
                    :disabled="!newMessage.trim() || sending"
                    class="p-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <Send :size="20" />
                </button>
            </div>
        </div>

        <!-- Document Generator Modal -->
        <DocumentGenerator
            v-if="showDocumentGenerator"
            :conversation="conversation"
            :case-id="caseId"
            @close="showDocumentGenerator = false"
            @generated="handleDocumentGenerated"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { FileText, Upload, Paperclip, Send, X } from 'lucide-react'
import EnhancedMessage from './EnhancedMessage.vue'
import DocumentUploader from './DocumentUploader.vue'
import DocumentGenerator from './DocumentGenerator.vue'
import axios from 'axios'

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
    }
})

const emit = defineEmits(['conversation-created', 'message-sent', 'document-uploaded', 'document-generated'])

const conversation = ref(props.initialConversation)
const localMessages = ref([])
const newMessage = ref('')
const sending = ref(false)
const messagesContainer = ref(null)
const messagesEndRef = ref(null)
const showDocumentUploader = ref(false)
const showDocumentGenerator = ref(false)
const replyingTo = ref(null)

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value) return

    if (!conversation.value) {
        await createConversation()
    }

    sending.value = true

    try {
        const messageData = {
            message: newMessage.value,
            reply_to_id: replyingTo.value?.id || null
        }

        const response = await axios.post(`/chat/${conversation.value.id}/message`, messageData)

        if (response.data) {
            localMessages.value.push(response.data.userMessage)
            localMessages.value.push(response.data.aiMessage)

            newMessage.value = ''
            replyingTo.value = null

            emit('message-sent', response.data)
        }
    } catch (error) {
        console.error('Failed to send message:', error)
    } finally {
        sending.value = false
    }
}

const createConversation = async () => {
    if (conversation.value) return

    try {
        const title = `${props.caseTitle} - ${new Date().toLocaleDateString()}`

        const response = await axios.post('/api/chat/create', {
            title,
            legal_case_id: props.caseId,
            auto_generate_title: true
        })

        if (response.data.success) {
            conversation.value = response.data.conversation
            emit('conversation-created', conversation.value)
        }
    } catch (error) {
        console.error('Failed to create conversation:', error)
    }
}

const toggleDocumentUpload = () => {
    showDocumentUploader.value = !showDocumentUploader.value
}

const handleDocumentUploaded = (document) => {
    showDocumentUploader.value = false
    emit('document-uploaded', document)

    newMessage.value = `Please analyze the uploaded document: ${document.title}`
}

const handleDocumentGenerated = (document) => {
    showDocumentGenerator.value = false
    emit('document-generated', document)
}

const handleReply = (message) => {
    replyingTo.value = message
    nextTick(() => {
        document.querySelector('textarea').focus()
    })
}

const handleBookmark = (message) => {
    console.log('Bookmark toggled for message:', message.id)
}

const handleReaction = (message, emoji) => {
    console.log('Reaction added:', emoji, 'to message:', message.id)
}

const handleKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        sendMessage()
    }
}

const autoGrow = (event) => {
    const textarea = event.target
    textarea.style.height = 'auto'
    textarea.style.height = `${Math.min(textarea.scrollHeight, 128)}px`
}

const scrollToBottom = () => {
    if (messagesEndRef.value) {
        messagesEndRef.value.scrollIntoView({ behavior: 'smooth' })
    }
}

const shouldShowDateDivider = (message, index) => {
    if (index === 0) return true

    const prevDate = new Date(localMessages.value[index - 1].created_at).toDateString()
    const currDate = new Date(message.created_at).toDateString()

    return prevDate !== currDate
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric'
    })
}

watch(localMessages, () => {
    nextTick(() => {
        scrollToBottom()
    })
}, { deep: true })

onMounted(() => {
    if (conversation.value) {
        loadMessages()
    }
})

const loadMessages = async () => {
    try {
        const response = await axios.get(`/chat/${conversation.value.id}`)
        localMessages.value = response.data.messages || []
    } catch (error) {
        console.error('Failed to load messages:', error)
    }
}
</script>

<style scoped>
.integrated-chat {
    max-height: calc(100vh - 200px);
    min-height: 400px;
}
</style>
