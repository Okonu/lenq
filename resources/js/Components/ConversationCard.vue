<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    conversation: Object,
});

// Format date for display
const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();

    // Check if date is today
    if (date.toDateString() === now.toDateString()) {
        return `Today at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }

    // Check if date is yesterday
    const yesterday = new Date();
    yesterday.setDate(now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return `Yesterday at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }

    // If within the last 7 days, show the day name
    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(now.getDate() - 7);
    if (date > oneWeekAgo) {
        return `${date.toLocaleDateString(undefined, { weekday: 'long' })} at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }

    // Otherwise, show full date
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};

// Truncate long text
const truncate = (text, length = 60) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out shadow-sm">
        <Link :href="route('chat.show', conversation.id)" class="block p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary-600 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ truncate(conversation.title) }}
                        </h3>
                        <div class="ml-2 flex-shrink-0 flex">
                            <span v-if="conversation.legal_case" class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ truncate(conversation.legal_case.title, 20) }}
                            </span>
                            <span v-else class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                General
                            </span>
                        </div>
                    </div>
                    <div class="mt-1 flex items-center text-xs text-gray-500 dark:text-gray-400">
                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ formatDate(conversation.created_at) }}</span>
                    </div>
                </div>
            </div>
        </Link>
    </div>
</template>
