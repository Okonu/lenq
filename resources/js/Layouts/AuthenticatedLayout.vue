<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const showSidebar = ref(true);
const page = usePage();

const currentRoute = computed(() => route().current());

// Enhanced sidebar items with case assignments section
const sidebarItems = [
    { name: 'Dashboard', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Legal Cases', route: 'cases.index', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { name: 'Documents', route: 'documents.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Conversations', route: 'chat.index', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
];

// Add My Assignments section
const assignmentItems = [
    { name: 'My Cases', route: 'cases.index', params: { assigned_to: 'me' }, icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
    { name: 'My Tasks', route: 'tasks.index', params: { assigned_to: 'me' }, icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    { name: 'Task Calendar', route: 'tasks.calendar', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
];

// Add Clients section
const clientItems = [
    { name: 'Clients', route: 'clients.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Add Client', route: 'clients.create', icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' },
];

const toggleSidebar = () => {
    showSidebar.value = !showSidebar.value;
};

const isActive = (routeName, params = {}) => {
    if (currentRoute.value === routeName) {
        // If we have params, check if they match
        if (Object.keys(params).length > 0) {
            const urlParams = new URLSearchParams(window.location.search);
            for (const [key, value] of Object.entries(params)) {
                if (urlParams.get(key) !== value) {
                    return false;
                }
            }
        }
        return true;
    }

    return currentRoute.value.startsWith(routeName.split('.')[0]);
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Mobile Sidebar Toggle Button -->
        <div class="fixed top-0 left-0 z-40 p-4 sm:hidden">
            <button @click="toggleSidebar" class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed top-0 left-0 z-30 w-64 h-screen transition-transform bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700',
                showSidebar ? 'transform-none' : '-translate-x-full sm:translate-x-0'
            ]"
        >
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-6 border-b border-gray-200 dark:border-gray-700">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo class="w-10 h-10 text-primary-600 dark:text-white" />
                        <span class="ml-3 text-xl font-semibold text-gray-800 dark:text-white">LegalAssist</span>
                    </Link>
                </div>

                <!-- Sidebar Navigation -->
                <nav class="flex-1 px-3 py-4 overflow-y-auto">
                    <!-- Main Navigation -->
                    <ul class="space-y-2">
                        <li v-for="item in sidebarItems" :key="item.name">
                            <Link
                                :href="route(item.route)"
                                :class="[
                                    'flex items-center p-3 text-base font-medium rounded-lg group',
                                    isActive(item.route)
                                        ? 'text-white bg-primary-600 hover:bg-primary-700'
                                        : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700'
                                ]"
                            >
                                <svg
                                    class="w-6 h-6 transition duration-75"
                                    :class="isActive(item.route) ? 'text-white' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
                                </svg>
                                <span class="ml-3">{{ item.name }}</span>
                            </Link>
                        </li>
                    </ul>

                    <!-- My Assignments Section -->
                    <div class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                        <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            My Assignments
                        </p>
                        <ul class="space-y-1">
                            <li v-for="item in assignmentItems" :key="item.name">
                                <Link
                                    :href="item.params ? route(item.route, item.params) : route(item.route)"
                                    :class="[
                                        'flex items-center p-2 pl-4 text-sm font-medium rounded-lg group',
                                        isActive(item.route, item.params)
                                            ? 'text-white bg-primary-600 hover:bg-primary-700'
                                            : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <svg
                                        class="w-5 h-5 transition duration-75"
                                        :class="isActive(item.route, item.params) ? 'text-white' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
                                    </svg>
                                    <span class="ml-3">{{ item.name }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Clients Section -->
                    <div class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                        <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Clients
                        </p>
                        <ul class="space-y-1">
                            <li v-for="item in clientItems" :key="item.name">
                                <Link
                                    :href="route(item.route)"
                                    :class="[
                                        'flex items-center p-2 pl-4 text-sm font-medium rounded-lg group',
                                        isActive(item.route)
                                            ? 'text-white bg-primary-600 hover:bg-primary-700'
                                            : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <svg
                                        class="w-5 h-5 transition duration-75"
                                        :class="isActive(item.route) ? 'text-white' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
                                    </svg>
                                    <span class="ml-3">{{ item.name }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Quick Actions -->
                    <div class="pt-6 mt-6 space-y-2 border-t border-gray-200 dark:border-gray-700">
                        <Link
                            :href="route('documents.create')"
                            class="flex items-center p-3 text-base font-medium text-gray-900 rounded-lg bg-gray-100 hover:bg-gray-200 dark:text-white dark:bg-gray-700 dark:hover:bg-gray-600 group"
                        >
                            <svg class="w-6 h-6 text-gray-800 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span class="ml-3">Upload Document</span>
                        </Link>
                        <Link
                            :href="route('chat.create')"
                            class="flex items-center p-3 text-base font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700 group"
                        >
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span class="ml-3">New Conversation</span>
                        </Link>
                    </div>
                </nav>

                <!-- User Profile -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <div class="flex items-center">
                                    <div class="relative w-8 h-8 mr-3 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                                          {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-sm font-semibold">{{ page.props.auth.user.name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ page.props.auth.user.email }}</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">
                                Profile Settings
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div :class="['sm:ml-64', showSidebar ? '' : 'ml-0']">
            <!-- Top Navigation Bar -->
            <header class="flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 sm:px-6">
                <div class="flex items-center">
                    <button @click="toggleSidebar" class="p-2 mr-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 sm:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 v-if="$slots.header" class="text-xl font-semibold text-gray-900 dark:text-white">
                        <slot name="header" />
                    </h1>
                </div>

                <div class="flex items-center">
                    <!-- Add any top bar actions here if needed -->
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
:root {
    --color-primary-50: 240, 249, 255;
    --color-primary-100: 224, 242, 254;
    --color-primary-200: 186, 230, 253;
    --color-primary-300: 125, 211, 252;
    --color-primary-400: 56, 189, 248;
    --color-primary-500: 14, 165, 233;
    --color-primary-600: 2, 132, 199;
    --color-primary-700: 3, 105, 161;
    --color-primary-800: 7, 89, 133;
    --color-primary-900: 12, 74, 110;
    --color-primary-950: 8, 47, 73;
}

.text-primary-600 {
    color: rgb(var(--color-primary-600));
}

.bg-primary-600 {
    background-color: rgb(var(--color-primary-600));
}

.hover\:bg-primary-700:hover {
    background-color: rgb(var(--color-primary-700));
}

.bg-primary-700 {
    background-color: rgb(var(--color-primary-700));
}
</style>
