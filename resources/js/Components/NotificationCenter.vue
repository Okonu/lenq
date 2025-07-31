<template>
    <div class="notification-center">
        <!-- Notification Bell -->
        <div class="relative">
            <button
                @click="toggleNotifications"
                class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none"
            >
                <Bell :size="20" />

                <!-- Unread count badge -->
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </button>

            <!-- Notifications Dropdown -->
            <div
                v-if="showNotifications"
                class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
            >
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Notifications
                    </h3>
                    <div class="flex items-center space-x-2">
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                        >
                            Mark all read
                        </button>
                        <button
                            @click="showNotifications = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <X :size="16" />
                        </button>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="max-h-96 overflow-y-auto">
                    <div v-if="notifications.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                        No notifications yet
                    </div>

                    <div v-else>
                        <NotificationItem
                            v-for="notification in notifications"
                            :key="notification.id"
                            :notification="notification"
                            @click="handleNotificationClick"
                            @mark-read="markAsRead"
                        />
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-3">
                    <Link
                        :href="route('notifications.index')"
                        class="block text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                    >
                        View all notifications
                    </Link>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div class="fixed top-4 right-4 z-50 space-y-2">
            <NotificationToast
                v-for="toast in toasts"
                :key="toast.id"
                :notification="toast"
                @close="removeToast"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Bell, X } from 'lucide-react'
import { useNotifications } from '@/composables/useNotifications'
import NotificationItem from './NotificationItem.vue'
import NotificationToast from './NotificationToast.vue'

const showNotifications = ref(false)

const {
    notifications,
    unreadCount,
    toasts,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    removeToast,
    subscribeToUpdates
} = useNotifications()

const toggleNotifications = async () => {
    showNotifications.value = !showNotifications.value

    if (showNotifications.value) {
        await fetchNotifications()
    }
}

const handleNotificationClick = (notification) => {
    if (!notification.read_at) {
        markAsRead([notification.id])
    }

    if (notification.action_url) {
        window.location.href = notification.action_url
    }

    showNotifications.value = false
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    const notificationCenter = event.target.closest('.notification-center')
    if (!notificationCenter) {
        showNotifications.value = false
    }
}

onMounted(() => {
    fetchNotifications()
    subscribeToUpdates()
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.notification-center {
    @apply relative;
}
</style>
