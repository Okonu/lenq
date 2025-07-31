<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    cases: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    title: '',
    legal_case_id: null,
    auto_generate_title: true, // Default to auto-generation
});

const suggestions = [
    "Contract Review Assistance",
    "Legal Research on Property Rights",
    "Divorce Proceedings Information",
    "Business Formation Advice",
    "Intellectual Property Strategy",
    "Tax Law Questions",
    "Employment Law Consultation",
    "Estate Planning Discussion"
];

const useSuggestion = (suggestion) => {
    form.title = suggestion;
    form.auto_generate_title = false; // Turn off auto-generation when a suggestion is selected
};

const submit = () => {
    form.post(route('chat.store'));
};

// Toggle auto title generation
const autoTitleEnabled = ref(true);

// Watch for changes in auto title toggle
watch(autoTitleEnabled, (newValue) => {
    form.auto_generate_title = newValue;

    // Clear title field if auto title is enabled
    if (newValue) {
        form.title = '';
    }
});
</script>

<template>
    <Head title="New Conversation" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                New Legal Conversation
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Main Form Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Auto Title Generation Toggle -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input
                                    id="auto-title"
                                    v-model="autoTitleEnabled"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label for="auto-title" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Generate title automatically from conversation
                                </label>
                            </div>
                            <div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    New feature
                                </span>
                            </div>
                        </div>

                        <!-- Title field (hidden when auto-generation is enabled) -->
                        <div v-if="!form.auto_generate_title">
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Conversation Title
                            </label>
                            <div class="mt-1">
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="e.g., Contract Negotiation Advice"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                    :required="!form.auto_generate_title"
                                    autocomplete="off"
                                />
                            </div>
                            <div v-if="form.errors.title" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- Legal Case Selection (if cases exist) -->
                        <div v-if="cases.length > 0">
                            <label for="legal-case" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Related Legal Case (Optional)
                            </label>
                            <div class="mt-1">
                                <select
                                    id="legal-case"
                                    v-model="form.legal_case_id"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                >
                                    <option :value="null">None (General Conversation)</option>
                                    <option v-for="legalCase in cases" :key="legalCase.id" :value="legalCase.id">
                                        {{ legalCase.title }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="form.errors.legal_case_id" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.legal_case_id }}
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Associating a conversation with a case helps keep your legal matters organized.
                            </p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a
                                :href="route('chat.index')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancel
                            </a>
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Start Conversation</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Suggested Conversation Titles (only shown when auto-generation is disabled) -->
            <div v-if="!form.auto_generate_title" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Suggested Conversation Topics</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion"
                            @click="useSuggestion(suggestion)"
                            class="text-left px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ suggestion }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Auto Title Generation Info (shown when auto-generation is enabled) -->
            <div v-if="form.auto_generate_title" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">About Automatic Title Generation</h3>
                </div>
                <div class="p-6">
                    <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400">
                        <p>
                            With automatic title generation enabled, you can skip naming your conversation.
                            Our AI will analyze your first message and generate a relevant title automatically.
                        </p>

                        <ul class="mt-4 list-disc list-inside">
                            <li>Start typing your first message right away</li>
                            <li>The AI will generate a title based on your conversation</li>
                            <li>You can always edit the title later if needed</li>
                            <li>This makes starting new conversations faster and more efficient</li>
                        </ul>

                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-900">
                            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">How it works</h4>
                            <p class="text-xs text-blue-800 dark:text-blue-400">
                                The title is generated based on the content of your first message and the AI's response.
                                A temporary title is shown until your conversation begins, then it's automatically updated.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About Legal AI Conversations -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">About Legal AI Conversations</h3>
                </div>
                <div class="p-6">
                    <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400">
                        <p>
                            Start a conversation with our legal AI assistant to get help with understanding legal concepts, drafting documents, researching precedents, and analyzing legal scenarios.
                        </p>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-900">
                                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">Research & Analysis</h4>
                                <ul class="text-xs text-blue-800 dark:text-blue-400 space-y-1 list-disc list-inside">
                                    <li>Legal precedent research</li>
                                    <li>Case law summaries</li>
                                    <li>Analysis of legal arguments</li>
                                    <li>State and federal statute exploration</li>
                                </ul>
                            </div>

                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-100 dark:border-green-900">
                                <h4 class="text-sm font-medium text-green-900 dark:text-green-300 mb-2">Document Assistance</h4>
                                <ul class="text-xs text-green-800 dark:text-green-400 space-y-1 list-disc list-inside">
                                    <li>Contract review</li>
                                    <li>Document drafting guidance</li>
                                    <li>Terms & conditions explanation</li>
                                    <li>Legal language clarification</li>
                                </ul>
                            </div>

                            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-900">
                                <h4 class="text-sm font-medium text-purple-900 dark:text-purple-300 mb-2">Legal Education</h4>
                                <ul class="text-xs text-purple-800 dark:text-purple-400 space-y-1 list-disc list-inside">
                                    <li>Legal concept explanations</li>
                                    <li>Terminology definitions</li>
                                    <li>Process overviews</li>
                                    <li>Comparison of legal approaches</li>
                                </ul>
                            </div>
                        </div>

                        <p class="mt-4 text-sm italic text-center">
                            Remember: This AI assistant provides general legal information only, not legal advice. Always consult with a qualified attorney for your specific situation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
