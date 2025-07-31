// composables/useNotifications.js
import { ref, reactive } from 'vue'
import axios from 'axios'

export function useNotifications() {
    const notifications = ref([])
    const unreadCount = ref(0)
    const toasts = ref([])
    const stats = reactive({
        unread_count: 0,
        high_priority_count: 0,
        today_count: 0,
        by_type: {}
    })

    /**
     * Fetch notifications from backend
     */
    const fetchNotifications = async (options = {}) => {
        try {
            const params = {
                limit: options.limit || 10,
                unread_only: options.unreadOnly || false
            }

            const response = await axios.get('/api/notifications', { params })

            if (response.data.success) {
                notifications.value = response.data.data.notifications
                Object.assign(stats, response.data.data.stats)
                unreadCount.value = stats.unread_count
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error)
        }
    }

    /**
     * Mark notifications as read
     */
    const markAsRead = async (notificationIds) => {
        try {
            const response = await axios.post('/api/notifications/mark-read', {
                notification_ids: notificationIds
            })

            if (response.data.success) {
                // Update local state
                notifications.value = notifications.value.map(notification => {
                    if (notificationIds.includes(notification.id)) {
                        return { ...notification, read_at: new Date().toISOString() }
                    }
                    return notification
                })

                // Update unread count
                unreadCount.value = Math.max(0, unreadCount.value - notificationIds.length)
            }
        } catch (error) {
            console.error('Failed to mark notifications as read:', error)
        }
    }

    /**
     * Mark all notifications as read
     */
    const markAllAsRead = async () => {
        try {
            const response = await axios.post('/api/notifications/mark-read', {
                mark_all: true
            })

            if (response.data.success) {
                // Update all notifications to read
                notifications.value = notifications.value.map(notification => ({
                    ...notification,
                    read_at: new Date().toISOString()
                }))

                unreadCount.value = 0
                stats.unread_count = 0
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error)
        }
    }

    /**
     * Show toast notification
     */
    const showToast = (notification) => {
        const toast = {
            id: Date.now(),
            ...notification,
            timestamp: new Date()
        }

        toasts.value.push(toast)

        // Auto-remove toast after delay
        const delay = getToastDelay(notification.priority)
        setTimeout(() => {
            removeToast(toast.id)
        }, delay)
    }

    /**
     * Remove toast notification
     */
    const removeToast = (toastId) => {
        const index = toasts.value.findIndex(toast => toast.id === toastId)
        if (index > -1) {
            toasts.value.splice(index, 1)
        }
    }

    /**
     * Get toast display duration based on priority
     */
    const getToastDelay = (priority) => {
        switch (priority) {
            case 'critical':
                return 10000 // 10 seconds
            case 'high':
                return 7000  // 7 seconds
            case 'normal':
                return 5000  // 5 seconds
            case 'low':
                return 3000  // 3 seconds
            default:
                return 5000
        }
    }

    /**
     * Subscribe to real-time notification updates
     */
    const subscribeToUpdates = () => {
        // Using Laravel Echo for real-time updates
        if (window.Echo) {
            window.Echo.private(`notifications.${window.Laravel.user.id}`)
                .listen('NotificationCreated', (event) => {
                    // Add new notification to list
                    notifications.value.unshift(event.notification)
                    unreadCount.value++

                    // Show toast for high priority notifications
                    if (['high', 'critical'].includes(event.notification.priority)) {
                        showToast(event.notification)
                    }
                })
                .listen('NotificationUpdated', (event) => {
                    // Update existing notification
                    const index = notifications.value.findIndex(
                        n => n.id === event.notification.id
                    )
                    if (index > -1) {
                        notifications.value[index] = event.notification
                    }
                })
        }

        // Fallback: Poll for updates if WebSocket not available
        if (!window.Echo) {
            setInterval(() => {
                fetchNotifications({ limit: 5, unreadOnly: true })
            }, 30000) // Poll every 30 seconds
        }
    }

    /**
     * Request notification permissions
     */
    const requestNotificationPermission = async () => {
        if ('Notification' in window && Notification.permission === 'default') {
            const permission = await Notification.requestPermission()
            return permission === 'granted'
        }
        return Notification.permission === 'granted'
    }

    /**
     * Show browser notification
     */
    const showBrowserNotification = (notification) => {
        if ('Notification' in window && Notification.permission === 'granted') {
            const browserNotification = new Notification(notification.title, {
                body: notification.message,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: `notification-${notification.id}`,
                requireInteraction: notification.priority === 'critical'
            })

            browserNotification.onclick = () => {
                if (notification.action_url) {
                    window.focus()
                    window.location.href = notification.action_url
                }
                browserNotification.close()
            }

            // Auto-close after delay
            setTimeout(() => {
                browserNotification.close()
            }, getToastDelay(notification.priority))
        }
    }

    /**
     * Send test notification (for debugging)
     */
    const sendTestNotification = async () => {
        try {
            await axios.post('/api/notifications/test', {
                title: 'Test Notification',
                message: 'This is a test notification',
                priority: 'normal'
            })
        } catch (error) {
            console.error('Failed to send test notification:', error)
        }
    }

    /**
     * Get notification icon based on type
     */
    const getNotificationIcon = (type) => {
        const icons = {
            'case_assignment': '📁',
            'task_assignment': '✅',
            'task_deadline_approaching': '⏰',
            'task_overdue': '🚨',
            'document_analysis': '📄',
            'case_conversation': '💬',
            'firm_announcement': '📢',
            'knowledge_base_update': '📚',
            'emergency': '🆘',
            'default': '🔔'
        }

        return icons[type] || icons.default
    }

    /**
     * Get notification color based on priority
     */
    const getNotificationColor = (priority) => {
        const colors = {
            'critical': 'red',
            'high': 'orange',
            'normal': 'blue',
            'low': 'gray'
        }

        return colors[priority] || colors.normal
    }

    /**
     * Format notification time
     */
    const formatNotificationTime = (timestamp) => {
        const date = new Date(timestamp)
        const now = new Date()
        const diffMs = now - date
        const diffMins = Math.floor(diffMs / 60000)
        const diffHours = Math.floor(diffMins / 60)
        const diffDays = Math.floor(diffHours / 24)

        if (diffMins < 1) return 'Just now'
        if (diffMins < 60) return `${diffMins}m ago`
        if (diffHours < 24) return `${diffHours}h ago`
        if (diffDays < 7) return `${diffDays}d ago`

        return date.toLocaleDateString()
    }

    /**
     * Filter notifications by type
     */
    const filterNotifications = (type) => {
        return notifications.value.filter(n => n.type === type)
    }

    /**
     * Get notifications for today
     */
    const getTodayNotifications = () => {
        const today = new Date().toDateString()
        return notifications.value.filter(n =>
            new Date(n.created_at).toDateString() === today
        )
    }

    return {
        // State
        notifications,
        unreadCount,
        toasts,
        stats,

        // Methods
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        showToast,
        removeToast,
        subscribeToUpdates,
        requestNotificationPermission,
        showBrowserNotification,
        sendTestNotification,

        // Utilities
        getNotificationIcon,
        getNotificationColor,
        formatNotificationTime,
        filterNotifications,
        getTodayNotifications
    }
}
