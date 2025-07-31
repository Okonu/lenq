<script setup>
import { Link } from '@inertiajs/vue3';

// No props needed for this component
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="p-8">
            <div class="text-center">
                <svg class="mx-auto h-16 w-16 text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <h3 class="mt-4 text-xl font-medium text-gray-900 dark:text-white">Get Started with LegalAssist</h3>
                <p class="mt-2 text-base text-gray-500 dark:text-gray-400 max-w-xl mx-auto">
                    Upload documents, start conversations with our AI assistant, or create cases to organize your legal work.
                </p>

                <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-3xl mx-auto">
                    <Link
                        :href="route('documents.create')"
                        class="relative group"
                    >
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-600 to-primary-400 rounded-lg blur opacity-60 group-hover:opacity-100 transition duration-200"></div>
                        <div class="relative flex flex-col items-center justify-center px-4 py-5 bg-white dark:bg-gray-800 rounded-lg transition duration-200">
                            <svg class="w-10 h-10 mb-3 text-primary-500 group-hover:text-primary-600 transition duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-base font-medium text-gray-900 dark:text-white">Upload Document</span>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-center">
                                Upload legal documents for AI analysis
                            </p>
                        </div>
                    </Link>

                    <Link
                        :href="route('chat.create')"
                        class="relative group"
                    >
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-600 to-primary-400 rounded-lg blur opacity-60 group-hover:opacity-100 transition duration-200"></div>
                        <div class="relative flex flex-col items-center justify-center px-4 py-5 bg-white dark:bg-gray-800 rounded-lg transition duration-200">
                            <svg class="w-10 h-10 mb-3 text-primary-500 group-hover:text-primary-600 transition duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <span class="text-base font-medium text-gray-900 dark:text-white">New Conversation</span>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-center">
                                Chat with the AI legal assistant
                            </p>
                        </div>
                    </Link>

                    <Link
                        :href="route('cases.create')"
                        class="relative group"
                    >
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-600 to-primary-400 rounded-lg blur opacity-60 group-hover:opacity-100 transition duration-200"></div>
                        <div class="relative flex flex-col items-center justify-center px-4 py-5 bg-white dark:bg-gray-800 rounded-lg transition duration-200">
                            <svg class="w-10 h-10 mb-3 text-primary-500 group-hover:text-primary-600 transition duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span class="text-base font-medium text-gray-900 dark:text-white">Create Case</span>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-center">
                                Organize documents and conversations
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
