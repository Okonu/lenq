<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DocumentCard from '@/Components/DocumentCard.vue';

defineProps({
    documents: Array,
});

const searchQuery = ref('');
const selectedType = ref('all');

const filteredDocuments = (documents) => {
    return documents.filter((document) => {
        const matchesSearch = document.title.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesType = selectedType.value === 'all' || document.type === selectedType.value;
        return matchesSearch && matchesType;
    });
};
</script>

<template>
    <Head title="Documents" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Legal Documents
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Your Documents</h3>
                            <Link
                                :href="route('documents.create')"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700"
                            >
                                Upload New Document
                            </Link>
                        </div>

                        <!-- Search and Filter -->
                        <div class="mb-6 flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search documents..."
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300"
                                />
                            </div>
                            <div class="w-full md:w-48">
                                <select
                                    v-model="selectedType"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    <option value="all">All Types</option>
                                    <option value="general">General</option>
                                    <option value="contract">Contract</option>
                                    <option value="case">Case Law</option>
                                    <option value="discovery">Discovery</option>
                                </select>
                            </div>
                        </div>

                        <!-- Document List -->
                        <div v-if="documents.length">
                            <div v-if="filteredDocuments(documents).length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <DocumentCard
                                    v-for="document in filteredDocuments(documents)"
                                    :key="document.id"
                                    :document="document"
                                />
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No documents match your search criteria.
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            You haven't uploaded any documents yet.
                            <div class="mt-4">
                                <Link
                                    :href="route('documents.create')"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700"
                                >
                                    Upload Your First Document
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
