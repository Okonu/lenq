<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Mobile Header -->
        <div class="lg:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between h-16 px-4">
                <!-- Menu Toggle -->
                <button
                    @click="toggleMobileSidebar"
                    class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                    data-mobile-toggle
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- Mobile Logo -->
                <Link :href="route('dashboard')" class="flex items-center">
                    <ApplicationLogo class="w-8 h-8 text-blue-600" />
                    <span class="ml-2 text-lg font-semibold text-gray-800 dark:text-white">
                        LegalAssist
                    </span>
                </Link>

                <!-- Mobile Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Notifications Button -->
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.405-3.405A2.032 2.032 0 0116 11.5V9.5a4 4 0 00-8 0v2a2.032 2.032 0 01-.595 1.595L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <!-- Unread badge -->
                        <span
                            v-if="unreadNotifications > 0"
                            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
                        >
                            {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
                        </span>
                    </button>

                    <!-- User Menu -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </div>

        <!-- Desktop/Tablet Sidebar -->
        <aside
            :class="[
                'fixed top-0 left-0 z-40 h-screen transition-transform bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700',
                'lg:translate-x-0 lg:w-64',
                showMobileSidebar ? 'translate-x-0 w-64' : '-translate-x-full w-64'
            ]"
        >
            <div class="flex flex-col h-full">
                <!-- Desktop Logo -->
                <div class="hidden lg:flex items-center justify-center h-16 px-6 border-b border-gray-200 dark:border-gray-700">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo class="w-8 h-8 text-blue-600" />
                        <span class="ml-3 text-xl font-semibold text-gray-800 dark:text-white">
                            LegalAssist
                        </span>
                    </Link>
                </div>

                <!-- Mobile Sidebar Header -->
                <div class="lg:hidden flex items-center justify-between h-16 px-6 border-b border-gray-200 dark:border-gray-700">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo class="w-8 h-8 text-blue-600" />
                        <span class="ml-3 text-xl font-semibold text-gray-800 dark:text-white">
                            LegalAssist
                        </span>
                    </Link>
                    <button
                        @click="toggleMobileSidebar"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 overflow-y-auto">
                    <!-- Main Navigation -->
                    <div class="space-y-2">
                        <Link
                            v-for="item in mainNavigation"
                            :key="item.route"
                            :href="route(item.route)"
                            :class="[
                                'flex items-center px-3 py-2 text-sm font-medium rounded-lg group transition-colors',
                                isActive(item.route)
                                    ? 'text-white bg-blue-600 hover:bg-blue-700'
                                    : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700'
                            ]"
                            @click="handleNavigationClick(item)"
                        >
                            <!-- Icon -->
                            <svg
                                class="mr-3 flex-shrink-0 w-5 h-5"
                                :class="isActive(item.route) ? 'text-white' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300'"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                v-html="getIcon(item.icon)"
                            />

                            <!-- Label -->
                            <span class="flex-1">{{ item.name }}</span>

                            <!-- Badge (if present) -->
                            <span
                                v-if="item.badge"
                                :class="[
                                    'ml-3 inline-block py-0.5 px-2 text-xs rounded-full',
                                    isActive(item.route)
                                        ? 'bg-blue-700 text-blue-100'
                                        : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300'
                                ]"
                            >
                                {{ item.badge }}
                            </span>
                        </Link>
                    </div>

                    <!-- My Work Section -->
                    <div class="mt-8">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            My Work
                        </h3>
                        <div class="mt-2 space-y-1">
                            <Link
                                v-for="item in myWorkNavigation"
                                :key="item.route"
                                :href="getRouteUrl(item)"
                                :class="[
                                    'flex items-center px-3 py-2 text-sm font-medium rounded-lg group transition-colors',
                                    isActive(item.route)
                                        ? 'text-white bg-blue-600 hover:bg-blue-700'
                                        : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700'
                                ]"
                                @click="handleNavigationClick(item)"
                            >
                                <svg
                                    class="mr-3 flex-shrink-0 w-5 h-5"
                                    :class="isActive(item.route) ? 'text-white' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    v-html="getIcon(item.icon)"
                                />
                                <span class="flex-1">{{ item.name }}</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Team Section -->
                    <div class="mt-8">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Team
                        </h3>
                        <div class="mt-2 space-y-1">
                            <Link
                                v-for="item in teamNavigation"
                                :key="item.route"
                                :href="route(item.route)"
                                :class="[
                                    'flex items-center px-3 py-2 text-sm font-medium rounded-lg group transition-colors',
                                    isActive(item.route)
                                        ? 'text-white bg-blue-600 hover:bg-blue-700'
                                        : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700'
                                ]"
                                @click="handleNavigationClick(item)"
                            >
                                <svg
                                    class="mr-3 flex-shrink-0 w-5 h-5"
                                    :class="isActive(item.route) ? 'text-white' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    v-html="getIcon(item.icon)"
                                />
                                <span class="flex-1">{{ item.name }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- User Profile Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-white">
                                    {{ $page.props.auth.user.name.charAt(0) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $page.props.auth.user.email }}
                            </p>
                        </div>
                        <div class="ml-2">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"></path>
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div
            v-if="showMobileSidebar"
            @click="toggleMobileSidebar"
            class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
        ></div>

        <!-- Main Content -->
        <div :class="['lg:ml-64', 'transition-all duration-200']">
            <!-- Desktop Header -->
            <header class="hidden lg:flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center space-x-4">
                    <!-- Breadcrumbs -->
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2">
                            <li v-for="(breadcrumb, index) in breadcrumbs" :key="index">
                                <div class="flex items-center">
                                    <svg v-if="index > 0" class="w-3 h-3 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <Link
                                        v-if="breadcrumb.href"
                                        :href="breadcrumb.href"
                                        class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                                    >
                                        {{ breadcrumb.name }}
                                    </Link>
                                    <span
                                        v-else
                                        class="text-sm text-gray-900 dark:text-white font-medium"
                                    >
                                        {{ breadcrumb.name }}
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Global Search -->
                    <div class="relative hidden md:block">
                        <input
                            type="text"
                            placeholder="Search cases, documents..."
                            class="w-64 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
                        />
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>

                    <!-- Notifications -->
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.405-3.405A2.032 2.032 0 0116 11.5V9.5a4 4 0 00-8 0v2a2.032 2.032 0 01-.595 1.595L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span
                            v-if="unreadNotifications > 0"
                            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
                        >
                            {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
                        </span>
                    </button>

                    <!-- User Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center space-x-2 p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">
                                        {{ $page.props.auth.user.name.charAt(0) }}
                                    </span>
                                </div>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Header Slot -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-6">
                <slot />
            </main>
        </div>

        <!-- Mobile Bottom Navigation -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-20">
            <div class="grid grid-cols-5 h-16">
                <Link
                    v-for="item in bottomNavigation"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'flex flex-col items-center justify-center space-y-1 text-xs',
                        isActive(item.route)
                            ? 'text-blue-600 dark:text-blue-400'
                            : 'text-gray-600 dark:text-gray-400'
                    ]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getIcon(item.icon)" />
                    <span>{{ item.name }}</span>
                </Link>
            </div>
        </div>

        <!-- Floating Action Button (Mobile) -->
        <div v-if="isMobile" class="lg:hidden fixed bottom-20 right-4 z-30">
            <div class="relative">
                <button
                    @click="showFAB = !showFAB"
                    class="w-14 h-14 bg-blue-600 rounded-full shadow-lg flex items-center justify-center text-white hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </button>

                <!-- FAB Menu -->
                <div v-if="showFAB" class="absolute bottom-16 right-0 space-y-3">
                    <Link :href="route('chat.create')" class="flex items-center justify-center w-12 h-12 bg-blue-500 rounded-full shadow-lg text-white hover:bg-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </Link>
                    <Link :href="route('documents.create')" class="flex items-center justify-center w-12 h-12 bg-green-500 rounded-full shadow-lg text-white hover:bg-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'

const page = usePage()

// State
const showMobileSidebar = ref(false)
const unreadNotifications = ref(0)
const showFAB = ref(false)

// Check if mobile (simple responsive check)
const isMobile = computed(() => {
    if (typeof window !== 'undefined') {
        return window.innerWidth < 768
    }
    return false
})

// SVG Icons
const icons = {
    home: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>`,
    folder: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>`,
    fileText: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>`,
    messageSquare: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>`,
    checkSquare: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>`,
    users: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>`,
    bookOpen: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>`,
    moreHorizontal: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"></path>`
}

// Navigation items
const mainNavigation = [
    { name: 'Dashboard', route: 'dashboard', icon: 'home' },
    { name: 'Cases', route: 'cases.index', icon: 'folder' },
    { name: 'Documents', route: 'documents.index', icon: 'fileText' },
    { name: 'Conversations', route: 'chat.index', icon: 'messageSquare' },
    { name: 'Knowledge Base', route: 'dashboard', icon: 'bookOpen' } // Using existing route for now
]

const myWorkNavigation = [
    { name: 'My Cases', route: 'cases.index', params: { assigned_to: 'me' }, icon: 'folder' },
    { name: 'My Tasks', route: 'tasks.index', params: { assigned_to: 'me' }, icon: 'checkSquare' },
    { name: 'Task Calendar', route: 'tasks.calendar', icon: 'checkSquare' }
]

const teamNavigation = [
    { name: 'Team Members', route: 'cases.index', icon: 'users' }, // Using existing route
    { name: 'Settings', route: 'profile.edit', icon: 'users' } // Using existing route
]

const bottomNavigation = [
    { name: 'Home', route: 'dashboard', icon: 'home' },
    { name: 'Cases', route: 'cases.index', icon: 'folder' },
    { name: 'Chat', route: 'chat.index', icon: 'messageSquare' },
    { name: 'Tasks', route: 'tasks.index', icon: 'checkSquare' },
    { name: 'More', route: 'dashboard', icon: 'moreHorizontal' }
]

// Current route detection
const currentRoute = computed(() => route().current())

// Breadcrumbs (you can customize this based on your needs)
const breadcrumbs = computed(() => {
    const routeName = currentRoute.value
    const breadcrumbs = [{ name: 'Dashboard', href: route('dashboard') }]

    if (routeName?.startsWith('cases.')) {
        breadcrumbs.push({ name: 'Cases', href: route('cases.index') })
        if (routeName === 'cases.show') {
            breadcrumbs.push({ name: 'Case Details' })
        } else if (routeName === 'cases.create') {
            breadcrumbs.push({ name: 'New Case' })
        }
    } else if (routeName?.startsWith('documents.')) {
        breadcrumbs.push({ name: 'Documents', href: route('documents.index') })
        if (routeName === 'documents.show') {
            breadcrumbs.push({ name: 'Document Details' })
        }
    } else if (routeName?.startsWith('chat.')) {
        breadcrumbs.push({ name: 'Conversations', href: route('chat.index') })
        if (routeName === 'chat.show') {
            breadcrumbs.push({ name: 'Conversation' })
        }
    } else if (routeName?.startsWith('tasks.')) {
        breadcrumbs.push({ name: 'Tasks', href: route('tasks.index') })
        if (routeName === 'tasks.calendar') {
            breadcrumbs.push({ name: 'Calendar' })
        }
    }

    return breadcrumbs
})

// Methods
const toggleMobileSidebar = () => {
    showMobileSidebar.value = !showMobileSidebar.value
}

const isActive = (routeName, params = {}) => {
    if (currentRoute.value === routeName) {
        // Check params if provided
        if (Object.keys(params).length > 0) {
            const urlParams = new URLSearchParams(window.location.search)
            for (const [key, value] of Object.entries(params)) {
                if (urlParams.get(key) !== value) {
                    return false
                }
            }
        }
        return true
    }

    return currentRoute.value?.startsWith(routeName.split('.')[0]) || false
}

const getIcon = (iconName) => {
    return icons[iconName] || icons.home
}

const getRouteUrl = (item) => {
    if (item.params) {
        return route(item.route, item.params)
    }
    return route(item.route)
}

const handleNavigationClick = (item) => {
    // Close mobile sidebar on navigation
    if (isMobile.value) {
        showMobileSidebar.value = false
    }
}

// Handle mobile responsiveness
const handleResize = () => {
    if (window.innerWidth >= 1024) { // lg breakpoint
        showMobileSidebar.value = false
    }
}

// Close mobile sidebar when clicking outside
const handleClickOutside = (event) => {
    if (!event.target.closest('aside') &&
        !event.target.closest('[data-mobile-toggle]') &&
        showMobileSidebar.value) {
        showMobileSidebar.value = false
    }
}

onMounted(() => {
    window.addEventListener('resize', handleResize)
    document.addEventListener('click', handleClickOutside)

    // Initialize notification count
    unreadNotifications.value = 0
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Ensure mobile bottom nav doesn't interfere with content */
@media (max-width: 1023px) {
    main {
        padding-bottom: 5rem; /* Account for bottom nav height */
    }
}

/* Touch-friendly interactions */
@media (hover: none) and (pointer: coarse) {
    button, .touch-target {
        min-height: 44px;
        min-width: 44px;
    }
}

/* Smooth transitions */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Ensure proper z-index stacking */
.z-50 {
    z-index: 50;
}

.z-40 {
    z-index: 40;
}

.z-30 {
    z-index: 30;
}

.z-20 {
    z-index: 20;
}

/* FAB animations */
.fab-menu {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
