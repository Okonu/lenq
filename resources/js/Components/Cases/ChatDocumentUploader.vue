<script setup>
import {ref} from 'vue';
import axios from 'axios';

const props = defineProps({
    caseId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['document-selected', 'upload-cancelled']);

// State
const documentType = ref('general');
const documentTypes = [
    { value: 'general', label: 'General Document' },
    { value: 'contract', label: 'Contract' },
    { value: 'case', label: 'Case Law' },
    { value: 'discovery', label: 'Discovery Material' }
];
const uploading = ref(false);
const uploadProgress = ref(0);
const fileInputRef = ref(null);
const dragOver = ref(false);

// Handle file upload - but only upload, don't analyze
const handleFileUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.value = true;
    uploadProgress.value = 0;

    try {
        // Create form data
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', documentType.value);
        formData.append('legal_case_id', props.caseId);

        console.log('Uploading document:', {
            fileName: file.name,
            fileSize: file.size,
            fileType: file.type,
            documentType: documentType.value,
            caseId: props.caseId
        });

        // Upload the file
        const response = await axios.post(
            route('chat.document.upload'),
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (progressEvent) => {
                    uploadProgress.value = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                }
            }
        );

        console.log('Document upload response:', response.data);

        // Process the response
        if (response.data && response.data.document) {
            // Check if api_document_id is set
            if (!response.data.document.api_document_id) {
                console.warn('Warning: Document uploaded but api_document_id is not set. The API integration may not be working properly.');

                // If the response contains a document ID directly in the response
                if (response.data.document_id) {
                    console.log('Found document_id in response, using it as api_document_id');
                    response.data.document.api_document_id = response.data.document_id;
                }

                // Check if there's a nested document object with an ID
                if (response.data.api_document && response.data.api_document.id) {
                    console.log('Found nested api_document.id, using it as api_document_id');
                    response.data.document.api_document_id = response.data.api_document.id;
                }
            }

            // Emit the document selected event
            emit('document-selected', response.data.document);
        } else {
            throw new Error('Document upload failed');
        }
    } catch (error) {
        console.error('Error uploading document:', error);
        if (error.response) {
            console.error('API error details:', {
                status: error.response.status,
                data: error.response.data
            });
        }
        alert('Failed to upload document. Please try again.');
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;

        // Clear file input
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    }
};

// Handle drag events
const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = () => {
    dragOver.value = false;
};

const handleDrop = async (e) => {
    e.preventDefault();
    dragOver.value = false;

    if (e.dataTransfer.files.length > 0) {
        const file = e.dataTransfer.files[0];

        // Create a synthetic event to reuse the upload handler
        await handleFileUpload({
            target: {
                files: [file]
            }
        });
    }
};

// Cancel upload
const cancelUpload = () => {
    emit('upload-cancelled');
};
</script>

<template>
    <div class="chat-document-uploader border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Upload Document</h3>
            <button
                @click="cancelUpload"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            :class="[
                'border-2 border-dashed rounded-lg p-4 flex flex-col items-center justify-center',
                dragOver ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'
            ]"
        >
            <div class="text-center">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <h4 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                    Drop your document here
                </h4>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    PDF, DOC, DOCX (max 10MB)
                </p>

                <div class="mt-4 flex justify-center">
                    <input
                        ref="fileInputRef"
                        type="file"
                        class="hidden"
                        accept=".pdf,.doc,.docx"
                        @change="handleFileUpload"
                    />
                    <button
                        type="button"
                        @click="fileInputRef?.click()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                        Browse files
                    </button>
                </div>

                <div class="mt-4 flex justify-center">
                    <select
                        v-model="documentType"
                        class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                    >
                        <option v-for="type in documentTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Upload progress indicator -->
        <div v-if="uploading" class="mt-4">
            <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ uploadProgress < 100 ? 'Uploading...' : 'Processing document...' }}
                </span>
                <span v-if="uploadProgress < 100" class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ uploadProgress }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                <div
                    class="bg-blue-600 h-1 rounded-full transition-all duration-300"
                    :style="{ width: `${uploadProgress}%` }"
                ></div>
            </div>
        </div>
    </div>
</template>
