<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    file: null,
    type: 'general',
});

const fileInputRef = ref(null);
const fileName = ref('');
const loading = ref(false);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        fileName.value = file.name;
    }
};

const submit = () => {
    loading.value = true;
    form.post(route('documents.store'), {
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};
</script>

<template>
    <Head title="Upload Document" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Upload Legal Document
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- File Upload -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Document File
                                </label>
                                <div
                                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="fileInputRef.click()"
                                >
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        class="hidden"
                                        @change="handleFileChange"
                                        accept=".pdf,.doc,.docx"
                                    />
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <div class="mt-2" v-if="!fileName">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      Click to upload or drag and drop
                    </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            PDF, DOC, or DOCX (max 10MB)
                                        </p>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-900 dark:text-gray-100" v-else>
                                        {{ fileName }}
                                    </div>
                                </div>
                                <div v-if="form.errors.file" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.file }}
                                </div>
                            </div>

                            <!-- Document Type -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Document Type
                                </label>
                                <select
                                    v-model="form.type"
                                    class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    <option value="general">General Legal Document</option>
                                    <option value="contract">Contract</option>
                                    <option value="case">Case Law Document</option>
                                    <option value="discovery">Discovery Document</option>
                                </select>
                                <div v-if="form.errors.type" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.type }}
                                </div>
                            </div>

                            <!-- Analysis Description -->
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    What happens when you upload?
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    When you upload a document, our AI will analyze it and extract key information based on the document type:
                                </p>
                                <ul class="mt-2 text-sm text-gray-500 dark:text-gray-400 list-disc list-inside">
                                    <li><span class="font-medium">General Documents:</span> Document type, key clauses, risks, and summary</li>
                                    <li><span class="font-medium">Contracts:</span> Standard vs. non-standard terms, obligations, deadlines, and recommendations</li>
                                    <li><span class="font-medium">Case Law:</span> Legal issues, jurisdiction, precedents, and potential arguments</li>
                                    <li><span class="font-medium">Discovery:</span> Key facts, contradictions, timeline, and follow-up questions</li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                    :disabled="!form.file || loading"
                                >
                                    <span v-if="loading">Processing...</span>
                                    <span v-else>Upload & Analyze</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
