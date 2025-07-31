<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    userName: {
        type: String,
        required: true
    }
});
</script>

<template>
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-blue-900 rounded-lg shadow-sm overflow-hidden">
        <div class="relative">
            <!-- Abstract background pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg width="100%" height="100%" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 0h800v800H0z" fill="none" />
                    <path d="M769 229L1037 260.9M927 880L731 737 520 660 309 538 40 599 295 764 126.5 879.5 40 599-197 493 102 382-31 229 126.5 79.5-69-63" stroke="#FFF" stroke-width="100" fill="none" />
                    <path d="M-31 229L237 261 390 382 731 737M94 738L337 599 520 660 731 737" stroke="#FFF" stroke-width="40" fill="none" />
                    <path d="M520 660L578 842 731 737 840 599 769 229 520 660 520 660Z" stroke="#FFF" stroke-width="30" fill="none" />
                    <path d="M520 660L520 660 520 660Z" stroke="#FFF" stroke-width="100" fill="none" />
                </svg>
            </div>
            <div class="relative p-6 sm:p-8 text-white">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-6 md:mb-0">
                        <h2 class="text-2xl md:text-3xl font-bold">Welcome back, {{ userName }}</h2>
                        <p class="mt-2 text-blue-100 dark:text-blue-200 max-w-xl">
                            Your legal assistant is ready to help with document analysis, legal research, and professional conversations for all your legal matters.
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('documents.create')"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-blue-800 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white focus:ring-offset-blue-600 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Upload Document
                        </Link>
                        <Link
                            :href="route('chat.create')"
                            class="inline-flex items-center justify-center px-4 py-2 border border-white border-opacity-30 text-sm font-medium rounded-md shadow-sm text-white hover:bg-white hover:bg-opacity-10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white focus:ring-offset-blue-600 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            New Conversation
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
