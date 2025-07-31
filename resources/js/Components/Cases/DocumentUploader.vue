<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    caseId: {
        type: Number,
        required: true
    },
    onSuccess: {
        type: Function,
        default: () => {}
    },
    inlineMode: {
        type: Boolean,
        default: false
    },
    uploadRoute: {
        type: String,
        default: 'documents.store'
    }
});

const emit = defineEmits(['document-uploaded', 'analysis-complete']);

const documentTypes = [
    { value: 'general', label: 'General Document' },
    { value: 'contract', label: 'Contract' },
    { value: 'case', label: 'Case Law' },
    { value: 'discovery', label: 'Discovery Material' }
];

const form = useForm({
    file: null,
    type: 'general', // Default type
    legal_case_id: props.caseId,
});

const fileInputRef = ref(null);
const uploadProgress = ref(0);
const isAnalyzing = ref(false);
const analysis = ref(null);
const fileName = ref('');
const dragOver = ref(false);

// File selection or drag and drop
const selectFile = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        fileName.value = file.name;
    }
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;

    if (e.dataTransfer.files.length > 0) {
        const file = e.dataTransfer.files[0];
        form.file = file;
        fileName.value = file.name;
    }
};

const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = () => {
    dragOver.value = false;
};

const clearFile = () => {
    form.file = null;
    fileName.value = '';
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

// Form submission
const submit = () => {
    uploadProgress.value = 0;
    isAnalyzing.value = false;
    analysis.value = null;

    form.post(route(props.uploadRoute), {
        preserveScroll: true,
        forceFormData: true,
        onProgress: (progress) => {
            uploadProgress.value = progress.percentage;
        },
        onSuccess: (response) => {
            // Reset form
            form.file = null;
            fileName.value = '';

            // Update UI to show analysis is happening
            isAnalyzing.value = true;

            // Get the uploaded document
            const document = response.props.flash.document || response.props.document;

            // Emit events for parent components
            emit('document-uploaded', document);

            // In a real app, you might poll for analysis completion
            // For now, we'll simulate with a timeout
            setTimeout(() => {
                isAnalyzing.value = false;
                analysis.value = document;

                // Emit analysis complete
                emit('analysis-complete', document);

                // Call success callback
                props.onSuccess(document);
            }, 2000);
        }
    });
};

// Computed to check if upload is ready
const canUpload = computed(() => {
    return form.file !== null && !form.processing;
});

// For file size display
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
    <div :class="['document-uploader', inlineMode ? 'inline-mode' : 'card-mode']">
        <!-- Card-style uploader (default) -->
        <div v-if="!inlineMode" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Upload Document</h3>
            </div>

            <div class="p-6">
                <form @submit.prevent="submit">
                    <!-- File Drop Area -->
                    <div
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop"
                        :class="[
                            'border-2 border-dashed rounded-lg p-6 flex flex-col items-center justify-center',
                            dragOver ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'
                        ]"
                    >
                        <div v-if="!fileName" class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                            </svg>
                            <h4 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Drag and drop your file here
                            </h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Or click to browse (PDF, DOC, DOCX up to 10MB)
                            </p>
                            <input
                                ref="fileInputRef"
                                type="file"
                                class="hidden"
                                accept=".pdf,.doc,.docx"
                                @change="selectFile"
                            />
                            <button
                                type="button"
                                @click="fileInputRef?.click()"
                                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Select File
                            </button>
                        </div>
                        <div v-else class="w-full">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                            {{ fileName }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatFileSize(form.file?.size || 0) }}
                                        </div>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="clearFile"
                                    class="ml-2 p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Document Type Selection -->
                    <div class="mt-6">
                        <label for="document-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Document Type
                        </label>
                        <div class="mt-1">
                            <select
                                id="document-type"
                                v-model="form.type"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="type in documentTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Selecting the correct document type helps the AI provide better analysis.
                        </p>
                    </div>

                    <!-- Upload Progress -->
                    <div v-if="form.processing" class="mt-6">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Uploading...</span>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" :style="{ width: `${uploadProgress}%` }"></div>
                        </div>
                    </div>

                    <!-- Analyzing Indicator -->
                    <div v-if="isAnalyzing" class="mt-6">
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Analyzing document...</span>
                        </div>
                    </div>

                    <!-- Analysis Results Preview -->
                    <div v-if="analysis" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Analysis Complete</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                Success
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Document successfully uploaded and analyzed. You can now reference this document in conversations.
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            :disabled="!canUpload"
                        >
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Upload & Analyze
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inline-style uploader -->
        <div v-else>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <!-- File Selection -->
                    <div class="flex-1">
                        <div
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                            @drop="handleDrop"
                            :class="[
                                'border-2 border-dashed rounded-md p-3 flex items-center justify-between',
                                dragOver ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'
                            ]"
                        >
                            <div class="flex items-center">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                                <div class="ml-3">
                                    <div v-if="!fileName" class="text-sm text-gray-500 dark:text-gray-400">
                                        Drop file here or click to browse
                                    </div>
                                    <div v-else class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                        {{ fileName }}
                                    </div>
                                </div>
                            </div>
                            <input
                                ref="fileInputRef"
                                type="file"
                                class="hidden"
                                accept=".pdf,.doc,.docx"
                                @change="selectFile"
                            />
                            <button
                                type="button"
                                @click="fileInputRef?.click()"
                                class="ml-2 inline-flex items-center px-2 py-1 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                Browse
                            </button>
                        </div>
                    </div>

                    <!-- Document Type -->
                    <div class="w-48">
                        <select
                            v-model="form.type"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-xs dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="type in documentTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Upload Button -->
                    <button
                        type="submit"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-blue-500 disabled:opacity-50"
                        :disabled="!canUpload"
                    >
                        <span v-if="form.processing || isAnalyzing">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span v-else>
                            Upload & Analyze
                        </span>
                    </button>
                </div>

                <!-- Upload Progress -->
                <div v-if="form.processing" class="w-full">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                        <div class="bg-blue-600 h-1 rounded-full" :style="{ width: `${uploadProgress}%` }"></div>
                    </div>
                </div>

                <!-- Analysis Complete -->
                <div v-if="analysis" class="text-xs text-green-600 dark:text-green-400">
                    Document successfully uploaded and analyzed.
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.document-uploader.inline-mode {
    max-width: 100%;
}
</style>
