<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    searchQuery: {
        type: String,
        required: true
    },
    filterStatus: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:searchQuery', 'update:filterStatus']);

const updateSearchQuery = (event) => {
    emit('update:searchQuery', event.target.value);
};

const updateFilterStatus = (event) => {
    emit('update:filterStatus', event.target.value);
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <div class="flex-1 min-w-0 max-w-lg">
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        :value="searchQuery"
                        @input="updateSearchQuery"
                        type="text"
                        placeholder="Search conversations..."
                        class="block w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <select
                        :value="filterStatus"
                        @change="updateFilterStatus"
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="all">All Conversations</option>
                        <option value="case">Case-Related</option>
                        <option value="general">General</option>
                    </select>
                </div>

                <Link
                    :href="route('chat.create')"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Conversation
                </Link>
            </div>
        </div>
    </div>
</template>
