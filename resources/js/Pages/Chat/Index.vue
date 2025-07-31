<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Import components
import SearchFilterBar from '@/Components/Chat/SearchFilterBar.vue';
import ConversationList from '@/Components/Chat/ConversationList.vue';

// Props
const props = defineProps({
    conversations: Array,
});

// State
const searchQuery = ref('');
const filterStatus = ref('all'); // 'all', 'case', 'general'

// Filter conversations based on search query and filter status
const filteredConversations = computed(() => {
    return props.conversations.filter((conversation) => {
        // Text search
        const matchesSearch = conversation.title.toLowerCase().includes(searchQuery.value.toLowerCase());

        // Status filter
        const matchesStatus =
            filterStatus.value === 'all' ||
            (filterStatus.value === 'case' && conversation.legal_case_id) ||
            (filterStatus.value === 'general' && !conversation.legal_case_id);

        return matchesSearch && matchesStatus;
    });
});

// Computed properties to check if we have conversations
const hasConversations = computed(() => props.conversations.length > 0);
const hasFilteredResults = computed(() => Object.keys(groupedConversations.value).length > 0);

// Format date for display
const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();

    // Today
    if (date.toDateString() === now.toDateString()) {
        return 'Today';
    }

    // Yesterday
    const yesterday = new Date();
    yesterday.setDate(now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    // Last 7 days
    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(now.getDate() - 7);
    if (date > oneWeekAgo) {
        return date.toLocaleDateString(undefined, { weekday: 'long' });
    }

    // Default format
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};

// Group conversations by date
const groupedConversations = computed(() => {
    const groups = {};

    filteredConversations.value.forEach(conversation => {
        const date = formatDate(conversation.created_at);
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(conversation);
    });

    return groups;
});

// Reset filters
const resetFilters = () => {
    searchQuery.value = '';
    filterStatus.value = 'all';
};
</script>

<template>
    <Head title="Legal Conversations" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Legal Conversations
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Search and Filter Bar -->
            <SearchFilterBar
                :search-query="searchQuery"
                :filter-status="filterStatus"
                @update:search-query="searchQuery = $event"
                @update:filter-status="filterStatus = $event"
            />

            <!-- Conversation List -->
            <ConversationList
                :conversations="props.conversations"
                :grouped-conversations="groupedConversations"
                :has-conversations="hasConversations"
                :has-filtered-results="hasFilteredResults"
                @reset-filters="resetFilters"
            />
        </div>
    </AuthenticatedLayout>
</template>
