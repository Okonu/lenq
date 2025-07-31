<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Import dashboard components
import WelcomeBanner from '@/Components/Dashboard/WelcomeBanner.vue';
import StatsOverview from '@/Components/Dashboard/StatsOverview.vue';
import RecentDocuments from '@/Components/Dashboard/RecentDocuments.vue';
import RecentConversations from '@/Components/Dashboard/RecentConversations.vue';
import RecentCasesTable from '@/Components/Dashboard/RecentCasesTable.vue';
import GetStartedSection from '@/Components/Dashboard/GetStartedSection.vue';
import LegalTipsSection from '@/Components/Dashboard/LegalTipsSection.vue';

const props = defineProps({
    recentDocuments: Array,
    recentConversations: Array,
    recentCases: Array,
});

// Calculate counts for statistics
const casesCount = computed(() => props.recentCases?.length || 0);
const documentsCount = computed(() => props.recentDocuments?.length || 0);
const conversationsCount = computed(() => props.recentConversations?.length || 0);

// Determine if we have any recent items
const hasRecentActivity = computed(() => {
    return documentsCount.value > 0 ||
        conversationsCount.value > 0 ||
        casesCount.value > 0;
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Welcome Banner -->
            <WelcomeBanner :user-name="$page.props.auth.user.name" />

            <!-- Stats Overview -->
            <StatsOverview
                :cases-count="casesCount"
                :documents-count="documentsCount"
                :conversations-count="conversationsCount"
            />

            <!-- Recent Activity Section -->
            <div v-if="hasRecentActivity" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Documents -->
                <RecentDocuments :documents="recentDocuments" />

                <!-- Recent Conversations -->
                <RecentConversations :conversations="recentConversations" />
            </div>

            <!-- Getting Started Card (shown when no activity) -->
            <GetStartedSection v-if="!hasRecentActivity" />

            <!-- Recent Cases Section (when we have cases) -->
            <RecentCasesTable v-if="recentCases && recentCases.length > 0" :cases="recentCases" />

            <!-- Legal Tips Section -->
            <LegalTipsSection />
        </div>
    </AuthenticatedLayout>
</template>
