<script setup>
import DateGroupHeader from './DateGroupHeader.vue';
import ConversationItem from './ConversationItem.vue';
import EmptyState from './EmptyState.vue';
import NoResultsState from './NoResultsState.vue';

const props = defineProps({
    conversations: {
        type: Array,
        required: true
    },
    groupedConversations: {
        type: Object,
        required: true
    },
    hasConversations: {
        type: Boolean,
        required: true
    },
    hasFilteredResults: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['resetFilters']);

const handleResetFilters = () => {
    emit('resetFilters');
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div v-if="hasConversations && hasFilteredResults" class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-for="(group, date) in groupedConversations" :key="date" class="divide-y divide-gray-100 dark:divide-gray-800">
                <DateGroupHeader :date="date" />

                <ConversationItem
                    v-for="conversation in group"
                    :key="conversation.id"
                    :conversation="conversation"
                />
            </div>
        </div>

        <!-- Empty State - No conversations at all -->
        <EmptyState v-else-if="!hasConversations" />

        <!-- No Results from Search -->
        <NoResultsState
            v-else
            @reset-filters="handleResetFilters"
        />
    </div>
</template>
