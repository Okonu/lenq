<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => []
    }
});

// Format dates
const formatDate = (dateString) => {
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Conversations</h3>
            <Link
                :href="route('chat.index')"
                class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 inline-flex items-center"
            >
                View All
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </Link>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-if="conversations.length > 0">
                <div v-for="conversation in conversations" :key="conversation.id"
                     class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150"
                >
                    <Link :href="route('chat.show', conversation.id)" class="block">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="ml-4 flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ conversation.title }}
                                    </p>
                                    <div class="ml-2 flex-shrink-0">
                                        <span v-if="conversation.legal_case" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            Case
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            General
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-1 flex items-center">
                                    <span v-if="conversation.legal_case" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ conversation.legal_case.title }}
                                    </span>
                                    <span v-else class="text-xs text-gray-500 dark:text-gray-400">
                                        General Inquiry
                                    </span>
                                    <span class="mx-1 text-xs text-gray-300 dark:text-gray-600">•</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDate(conversation.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
            <div v-else class="p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No conversations</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new conversation.</p>
                <div class="mt-6">
                    <Link
                        :href="route('chat.create')"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Conversation
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
