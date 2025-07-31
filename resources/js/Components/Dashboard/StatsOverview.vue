<script setup>
import StatCard from './StatCard.vue';

defineProps({
    casesCount: {
        type: Number,
        default: 0
    },
    documentsCount: {
        type: Number,
        default: 0
    },
    conversationsCount: {
        type: Number,
        default: 0
    }
});

const stats = [
    {
        name: 'Legal Cases',
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        color: 'bg-blue-500',
        textColor: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-900/20',
        viewAllRoute: 'cases.index'
    },
    {
        name: 'Documents',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        color: 'bg-green-500',
        textColor: 'text-green-600 dark:text-green-400',
        bgColor: 'bg-green-50 dark:bg-green-900/20',
        viewAllRoute: 'documents.index'
    },
    {
        name: 'Conversations',
        icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        color: 'bg-purple-500',
        textColor: 'text-purple-600 dark:text-purple-400',
        bgColor: 'bg-purple-50 dark:bg-purple-900/20',
        viewAllRoute: 'chat.index'
    }
];
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        <StatCard
            :name="stats[0].name"
            :target="casesCount"
            :icon="stats[0].icon"
            :color="stats[0].color"
            :text-color="stats[0].textColor"
            :bg-color="stats[0].bgColor"
            :view-all-route="stats[0].viewAllRoute"
        />

        <StatCard
            :name="stats[1].name"
            :target="documentsCount"
            :icon="stats[1].icon"
            :color="stats[1].color"
            :text-color="stats[1].textColor"
            :bg-color="stats[1].bgColor"
            :view-all-route="stats[1].viewAllRoute"
        />

        <StatCard
            :name="stats[2].name"
            :target="conversationsCount"
            :icon="stats[2].icon"
            :color="stats[2].color"
            :text-color="stats[2].textColor"
            :bg-color="stats[2].bgColor"
            :view-all-route="stats[2].viewAllRoute"
        />
    </div>
</template>
