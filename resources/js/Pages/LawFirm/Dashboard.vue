<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    lawFirm: Object,
    memberCount: Number,
    caseCount: Number,
    clientCount: Number,
    userRole: String,
});

const isAdmin = computed(() => props.userRole === 'admin');

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Law Firm Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ lawFirm.name }} Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Firm Overview Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-start">
                            <!-- Firm Logo -->
                            <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                <div v-if="lawFirm.logo_path" class="w-28 h-28 rounded-md overflow-hidden border border-gray-200 dark:border-gray-700">
                                    <img :src="'/storage/' + lawFirm.logo_path" class="w-full h-full object-cover" alt="Firm Logo">
                                </div>
                                <div v-else class="w-28 h-28 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Firm Info -->
                            <div class="flex-1">
                                <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ lawFirm.name }}</h3>
                                        <p v-if="lawFirm.address" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ lawFirm.address }}, {{ lawFirm.city }}, {{ lawFirm.state }} {{ lawFirm.zip }}
                                        </p>
                                    </div>
                                    <div v-if="isAdmin" class="mt-4 md:mt-0">
                                        <Link
                                            :href="route('lawfirm.edit', lawFirm.id)"
                                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            Edit Firm
                                        </Link>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-semibold">Email:</span> {{ lawFirm.email || 'Not provided' }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <span class="font-semibold">Phone:</span> {{ lawFirm.phone || 'Not provided' }}
                                        </p>
                                        <p v-if="lawFirm.website" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <span class="font-semibold">Website:</span> <a :href="lawFirm.website" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">{{ lawFirm.website }}</a>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-semibold">Established:</span> {{ formatDate(lawFirm.created_at) }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <span class="font-semibold">Your Role:</span> {{ userRole.charAt(0).toUpperCase() + userRole.slice(1) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Team Members -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Team Members</h3>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ memberCount }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Your law firm team members.
                            </p>
                            <div class="mt-4">
                                <Link
                                    :href="route('firm.members.index')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition"
                                >
                                    View Team
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Clients -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Clients</h3>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ clientCount }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Active clients in your firm.
                            </p>
                            <div class="mt-4">
                                <Link
                                    :href="route('clients.index')"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition"
                                >
                                    Manage Clients
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Cases -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Cases</h3>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ caseCount }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Legal cases currently in progress.
                            </p>
                            <div class="mt-4">
                                <Link
                                    :href="route('cases.index')"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-900 focus:outline-none focus:border-purple-900 focus:ring ring-purple-300 disabled:opacity-25 transition"
                                >
                                    View Cases
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Link
                                :href="route('cases.create')"
                                class="flex flex-col items-center p-4 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">New Case</span>
                            </Link>

                            <Link
                                :href="route('clients.create')"
                                class="flex flex-col items-center p-4 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Add Client</span>
                            </Link>

                            <Link
                                :href="route('documents.create')"
                                class="flex flex-col items-center p-4 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <svg class="w-8 h-8 text-yellow-500 dark:text-yellow-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Upload Document</span>
                            </Link>

                            <Link
                                :href="route('tasks.create')"
                                class="flex flex-col items-center p-4 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <svg class="w-8 h-8 text-red-500 dark:text-red-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Create Task</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
