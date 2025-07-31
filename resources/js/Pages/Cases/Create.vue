<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CaseForm from './Form.vue';
import { usePage } from '@inertiajs/vue3';

// Get the page props to check for client_id
const page = usePage();
const clientId = computed(() => page.props.clientId || null);
</script>

<template>
    <Head title="Create New Case" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Create New Legal Case
            </h2>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <CaseForm :is-editing="false" :client-id="clientId" />
                </div>
            </div>

            <!-- Help Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">About Legal Cases</h3>
                </div>
                <div class="p-6">
                    <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400">
                        <p>
                            Legal cases help you organize related documents and conversations in one place.
                            Creating a case allows you to:
                        </p>

                        <ul class="mt-4 list-disc list-inside">
                            <li>Group all documents related to a specific legal matter</li>
                            <li>Maintain conversations specific to the case</li>
                            <li>Track the status and progress of legal matters</li>
                            <li>Quickly find all information related to a particular case</li>
                        </ul>

                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-900">
                            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">Pro Tip</h4>
                            <p class="text-xs text-blue-800 dark:text-blue-400">
                                Providing detailed information like case numbers and jurisdiction helps the AI assistant provide more targeted and relevant legal information.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
