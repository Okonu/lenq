<template>
    <div class="chat-document-uploader border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <!-- Header -->
        <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Upload Document</h3>
            <div class="flex items-center space-x-2">
                <!-- Context Selector -->
                <select
                    v-model="uploadContext"
                    class="text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 dark:bg-gray-700 dark:text-white"
                >
                    <option value="chat">Chat Upload</option>
                    <option value="case" v-if="caseId">Add to Case</option>
                    <option value="standalone">Standalone Review</option>
                </select>

                <button
                    @click="$emit('upload-cancelled')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    <X :size="16" />
                </button>
            </div>
        </div>

        <!-- Upload Area -->
        <div class="p-4">
            <!-- Drag & Drop Zone -->
            <div
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
                :class="[
                    'border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer',
                    dragOver
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                        : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                ]"
                @click="triggerFileInput"
            >
                <input
                    ref="fileInputRef"
                    type="file"
                    class="hidden"
                    accept=".pdf,.doc,.docx,.txt"
                    multiple
                    @change="handleFileSelect"
                />

                <div v-if="!uploading">
                    <Upload class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        Drop files here or click to browse
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        PDF, DOC, DOCX, TXT up to 10MB each
                    </p>
                </div>

                <!-- Upload Progress -->
                <div v-if="uploading" class="space-y-2">
                    <Loader class="mx-auto h-6 w-6 text-blue-600 animate-spin" />
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Uploading... {{ uploadProgress }}%
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: uploadProgress + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Document Type Selection -->
            <div v-if="!uploading" class="mt-4">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Document Type (Auto-detected, click to override)
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="type in documentTypes"
                        :key="type.value"
                        @click="documentType = type.value"
                        :class="[
                            'p-2 text-xs rounded border-2 transition-colors text-center',
                            documentType === type.value
                                ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                        ]"
                    >
                        <div class="text-lg mb-1">{{ type.icon }}</div>
                        <div>{{ type.label }}</div>
                    </button>
                </div>
            </div>

            <!-- Upload Queue -->
            <div v-if="uploadQueue.length > 0" class="mt-4 space-y-2">
                <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300">Upload Queue</h4>
                <div
                    v-for="item in uploadQueue"
                    :key="item.id"
                    class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded"
                >
                    <div class="flex items-center space-x-2">
                        <FileText :size="14" />
                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate">
                            {{ item.file.name }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span :class="getStatusColor(item.status)" class="text-xs px-2 py-1 rounded">
                            {{ item.status }}
                        </span>
                        <button
                            v-if="item.status === 'failed'"
                            @click="retryUpload(item)"
                            class="text-xs text-blue-600 hover:text-blue-800"
                        >
                            Retry
                        </button>
                        <button
                            @click="removeFromQueue(item.id)"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <X :size="12" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Documents (Quick Select) -->
            <div v-if="recentDocuments.length > 0 && !uploading" class="mt-4">
                <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Recent Documents (Click to reference)
                </h4>
                <div class="grid grid-cols-1 gap-1 max-h-24 overflow-y-auto">
                    <button
                        v-for="doc in recentDocuments.slice(0, 3)"
                        :key="doc.id"
                        @click="selectRecentDocument(doc)"
                        class="text-left p-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-center space-x-2">
                            <FileText :size="12" />
                            <span class="truncate">{{ doc.title }}</span>
                            <span class="text-xs text-gray-400">({{ formatDate(doc.created_at) }})</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Smart Suggestions -->
            <div v-if="!uploading && uploadContext === 'chat'" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h4 class="text-xs font-medium text-blue-800 dark:text-blue-300 mb-2">💡 Smart Tips</h4>
                <ul class="text-xs text-blue-700 dark:text-blue-200 space-y-1">
                    <li>• Upload contracts for clause analysis</li>
                    <li>• Add discovery documents for review</li>
                    <li>• Reference case law for precedent research</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Upload, X, FileText, Loader } from 'lucide-react'
import { useDocumentUpload } from '@/composables/useDocumentUpload.js'
import axios from 'axios'

const props = defineProps({
    caseId: {
        type: Number,
        required: true
    },
    conversationId: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['document-selected', 'upload-cancelled', 'document-uploaded'])

const {
    uploadQueue,
    uploadFiles,
    removeFromQueue,
    retryUpload
} = useDocumentUpload()

const uploadContext = ref('chat')
const documentType = ref('general')
const uploading = ref(false)
const uploadProgress = ref(0)
const dragOver = ref(false)
const fileInputRef = ref(null)
const recentDocuments = ref([])

const documentTypes = [
    { value: 'general', label: 'General', icon: '📄' },
    { value: 'contract', label: 'Contract', icon: '📋' },
    { value: 'legal', label: 'Legal Doc', icon: '⚖️' },
    { value: 'correspondence', label: 'Letter', icon: '✉️' },
    { value: 'research', label: 'Research', icon: '🔍' },
    { value: 'discovery', label: 'Discovery', icon: '🔎' }
]

const triggerFileInput = () => {
    fileInputRef.value?.click()
}

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files)
    processFiles(files)
}

const handleDrop = (event) => {
    event.preventDefault()
    dragOver.value = false
    const files = Array.from(event.dataTransfer.files)
    processFiles(files)
}

const handleDragOver = (event) => {
    event.preventDefault()
    dragOver.value = true
}

const handleDragLeave = () => {
    dragOver.value = false
}

const processFiles = async (files) => {
    if (files.length === 0) return

    uploading.value = true
    uploadProgress.value = 0

    try {
        if (files.length === 1) {
            documentType.value = detectDocumentType(files[0].name)
        }

        const uploadData = {
            files,
            context: uploadContext.value,
            type: documentType.value,
            legal_case_id: uploadContext.value === 'case' ? props.caseId : null,
            conversation_id: uploadContext.value === 'chat' ? props.conversationId : null
        }

        const results = await uploadFiles(uploadData)

        for (const result of results) {
            if (result.success) {
                emit('document-uploaded', result.document)
                emit('document-selected', result.document)
            }
        }

        await fetchRecentDocuments()

    } catch (error) {
        console.error('Upload failed:', error)
    } finally {
        uploading.value = false
        uploadProgress.value = 0

        if (fileInputRef.value) {
            fileInputRef.value.value = ''
        }
    }
}

const detectDocumentType = (filename) => {
    const name = filename.toLowerCase()

    if (name.includes('contract') || name.includes('agreement')) return 'contract'
    if (name.includes('letter') || name.includes('correspondence')) return 'correspondence'
    if (name.includes('discovery') || name.includes('deposition')) return 'discovery'
    if (name.includes('research') || name.includes('analysis')) return 'research'
    if (name.includes('motion') || name.includes('brief') || name.includes('filing')) return 'legal'

    return 'general'
}

const selectRecentDocument = (document) => {
    emit('document-selected', document)
}

const fetchRecentDocuments = async () => {
    try {
        const response = await axios.get('/api/documents/recent', {
            params: { limit: 5, context: uploadContext.value }
        })

        if (response.data.success) {
            recentDocuments.value = response.data.data.documents
        }
    } catch (error) {
        console.error('Failed to fetch recent documents:', error)
    }
}

const getStatusColor = (status) => {
    switch (status) {
        case 'completed': return 'text-green-700 bg-green-100 dark:bg-green-900 dark:text-green-300'
        case 'uploading': return 'text-blue-700 bg-blue-100 dark:bg-blue-900 dark:text-blue-300'
        case 'failed': return 'text-red-700 bg-red-100 dark:bg-red-900 dark:text-red-300'
        default: return 'text-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-300'
    }
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric'
    })
}

onMounted(() => {
    fetchRecentDocuments()
})
</script>

<style scoped>
.chat-document-uploader {
    max-width: 100%;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .grid-cols-3 {
        @apply grid-cols-2;
    }

    .p-6 {
        @apply p-4;
    }
}
</style>
