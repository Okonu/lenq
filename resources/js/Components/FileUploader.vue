<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    accept: {
        type: String,
        default: '.pdf,.doc,.docx'
    },
    maxSize: {
        type: Number,
        default: 10 // In MB
    },
    modelValue: {
        type: [File, null],
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'fileSelected']);

const fileInputRef = ref(null);
const fileName = ref('');
const error = ref('');
const dragOver = ref(false);

watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        fileName.value = newValue.name;
    } else {
        fileName.value = '';
    }
});

const handleFileChange = (e) => {
    const file = e.target?.files?.[0] || null;
    validateAndUpdateFile(file);
};

const validateAndUpdateFile = (file) => {
    error.value = '';

    if (!file) {
        emit('update:modelValue', null);
        return;
    }

    // Check file size
    if (file.size > props.maxSize * 1024 * 1024) {
        error.value = `File size exceeds the maximum allowed size (${props.maxSize}MB).`;
        return;
    }

    // Check file type
    const acceptedTypes = props.accept.split(',');
    const fileExt = '.' + file.name.split('.').pop().toLowerCase();

    let isValidType = false;
    for (const type of acceptedTypes) {
        if (type.trim() === fileExt || type.trim() === file.type) {
            isValidType = true;
            break;
        }
    }

    if (!isValidType) {
        error.value = `Invalid file type. Accepted types: ${props.accept}`;
        return;
    }

    // File is valid
    fileName.value = file.name;
    emit('update:modelValue', file);
    emit('fileSelected', file);
};

const openFileSelector = () => {
    fileInputRef.value.click();
};

const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = () => {
    dragOver.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    const file = e.dataTransfer?.files?.[0] || null;
    validateAndUpdateFile(file);
};
</script>

<template>
    <div>
        <input
            ref="fileInputRef"
            type="file"
            class="hidden"
            :accept="accept"
            @change="handleFileChange"
        />

        <div
            :class="[
        'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer',
        dragOver ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
      ]"
            @click="openFileSelector"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
        >
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>

            <div class="mt-2" v-if="!fileName">
        <span class="text-sm text-gray-500 dark:text-gray-400">
          Click to upload or drag and drop
        </span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ accept.replace(/\./g, '').toUpperCase() }} (max {{ maxSize }}MB)
                </p>
            </div>

            <div v-else class="mt-2">
        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
          {{ fileName }}
        </span>
            </div>
        </div>

        <div v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>
    </div>
</template>
