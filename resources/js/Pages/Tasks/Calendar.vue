<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// Import FullCalendar core and plugins
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    lawFirm: Object,
    tasks: Array,
    teamMembers: Array,
    filters: Object,
    userRole: String,
});

// Calendar state
const calendar = ref(null);
const calendarEl = ref(null);
const selectedAssignee = ref(props.filters.assigned_to || 'all');

// Task details modal
const showTaskModal = ref(false);
const selectedTask = ref(null);

// Format tasks for FullCalendar
const calendarEvents = computed(() => {
    return props.tasks.map(task => {
        const priorityColors = {
            'urgent': '#EF4444', // red
            'high': '#F97316',   // orange
            'medium': '#EAB308', // yellow
            'low': '#22C55E'     // green
        };

        const statusColors = {
            'pending': '#9CA3AF',       // gray
            'in_progress': '#3B82F6',   // blue
            'completed': '#10B981',     // green
            'deferred': '#8B5CF6'       // purple
        };

        return {
            id: task.id,
            title: task.title,
            start: task.due_date,
            allDay: true,
            backgroundColor: priorityColors[task.priority] || '#9CA3AF',
            borderColor: statusColors[task.status] || '#9CA3AF',
            extendedProps: {
                taskData: task
            }
        };
    });
});

// Initialize calendar
onMounted(() => {
    if (calendarEl.value) {
        calendar.value = new Calendar(calendarEl.value, {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
            initialView: props.filters.view === 'day' ? 'timeGridDay' :
                props.filters.view === 'week' ? 'timeGridWeek' : 'dayGridMonth',
            initialDate: new Date(props.filters.year, props.filters.month - 1, props.filters.day),
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            events: calendarEvents.value,
            eventClick: info => {
                handleEventClick(info.event.extendedProps.taskData);
            },
            dateClick: info => {
                // Navigate to task create page with pre-filled date
                window.location.href = route('tasks.create', { due_date: info.dateStr });
            },
            height: 'auto',
            editable: false,
            selectable: true,
            weekNumbers: true,
            dayMaxEvents: true, // allow "more" link when too many events
            themeSystem: 'standard',
            nowIndicator: true,
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                day: 'Day',
                list: 'List'
            }
        });

        calendar.value.render();
    }
});

// Handle calendar view change
const handleViewChange = (viewName) => {
    let view = 'month';

    if (viewName === 'timeGridWeek') {
        view = 'week';
    } else if (viewName === 'timeGridDay') {
        view = 'day';
    }

    // Get the current date from the calendar
    const currentDate = calendar.value.getDate();

    // Navigate to the new view while preserving the date
    window.location.href = route('tasks.calendar', {
        year: currentDate.getFullYear(),
        month: currentDate.getMonth() + 1, // JavaScript months are 0-indexed
        day: currentDate.getDate(),
        view: view,
        assigned_to: selectedAssignee.value
    });
};

// Change assignee filter
const changeAssignee = () => {
    // Get the current date from the calendar
    const currentDate = calendar.value.getDate();
    const currentView = calendar.value.view.type;

    let view = 'month';
    if (currentView === 'timeGridWeek') {
        view = 'week';
    } else if (currentView === 'timeGridDay') {
        view = 'day';
    }

    window.location.href = route('tasks.calendar', {
        year: currentDate.getFullYear(),
        month: currentDate.getMonth() + 1,
        day: currentDate.getDate(),
        view: view,
        assigned_to: selectedAssignee.value
    });
};

// Handle task details view
const handleEventClick = (task) => {
    selectedTask.value = task;
    showTaskModal.value = true;
};

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return 'No due date';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
};

// Get assignee name
const getAssigneeName = (assigneeId) => {
    if (!assigneeId) return 'Unassigned';
    const member = props.teamMembers.find(m => m.id === assigneeId);
    return member ? member.name : 'Unknown';
};

// Format status display name
const formatStatus = (status) => {
    if (status === 'in_progress') return 'In Progress';
    return status.charAt(0).toUpperCase() + status.slice(1);
};

// Get status badge color
const getStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'in_progress': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'deferred': return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

// Get priority badge color
const getPriorityColor = (priority) => {
    switch (priority) {
        case 'urgent': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'high': return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
        case 'medium': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'low': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<template>
    <Head title="Task Calendar" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Task Calendar
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Calendar Controls -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                        <div class="flex items-center">
                            <div class="relative">
                                <select
                                    v-model="selectedAssignee"
                                    @change="changeAssignee"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="all">All Team Members</option>
                                    <option value="me">My Tasks</option>
                                    <option value="unassigned">Unassigned</option>
                                    <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                                        {{ member.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <Link
                                :href="route('tasks.create')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                New Task
                            </Link>

                            <Link
                                :href="route('tasks.index')"
                                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                List View
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Priority & Status Legend</h3>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-wrap gap-4">
                            <div class="flex flex-col space-y-2">
                                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">Priority:</h4>
                                <div class="flex space-x-2">
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block bg-red-600 rounded-full mr-1"></span>
                                        <span class="text-xs">Urgent</span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block bg-orange-500 rounded-full mr-1"></span>
                                        <span class="text-xs">High</span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block bg-yellow-500 rounded-full mr-1"></span>
                                        <span class="text-xs">Medium</span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block bg-green-500 rounded-full mr-1"></span>
                                        <span class="text-xs">Low</span>
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col space-y-2">
                                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">Status:</h4>
                                <div class="flex space-x-2">
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block border-2 border-gray-500 rounded-full mr-1"></span>
                                        <span class="text-xs">Pending</span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block border-2 border-blue-500 rounded-full mr-1"></span>
                                        <span class="text-xs">In Progress</span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block border-2 border-green-500 rounded-full mr-1"></span>
                                        <span class="text-xs">Completed</span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 inline-block border-2 border-purple-500 rounded-full mr-1"></span>
                                        <span class="text-xs">Deferred</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Container -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div ref="calendarEl" class="calendar-container"></div>
                    </div>
                </div>

                <!-- Empty state if no tasks -->
                <div v-if="!props.tasks || props.tasks.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No tasks found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Get started by creating a new task or adjust your filters.
                        </p>
                        <div class="mt-6">
                            <Link
                                :href="route('tasks.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Create Task
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Details Modal -->
        <Modal :show="showTaskModal" @close="showTaskModal = false">
            <div class="p-6" v-if="selectedTask">
                <div class="flex justify-between items-start">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ selectedTask.title }}</h2>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs rounded-full" :class="getPriorityColor(selectedTask.priority)">
                            {{ selectedTask.priority.charAt(0).toUpperCase() + selectedTask.priority.slice(1) }}
                        </span>
                        <span class="px-2 py-1 text-xs rounded-full" :class="getStatusColor(selectedTask.status)">
                            {{ formatStatus(selectedTask.status) }}
                        </span>
                    </div>
                </div>

                <div v-if="selectedTask.description" class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedTask.description }}</p>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Due Date:</span>
                        <span class="ml-2 font-medium">{{ formatDate(selectedTask.due_date) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Assigned To:</span>
                        <span class="ml-2 font-medium">{{ getAssigneeName(selectedTask.assigned_to) }}</span>
                    </div>
                    <div v-if="selectedTask.legalCase">
                        <span class="text-gray-500 dark:text-gray-400">Case:</span>
                        <span class="ml-2 font-medium">{{ selectedTask.legalCase.title }}</span>
                    </div>
                    <div v-if="selectedTask.completed_at">
                        <span class="text-gray-500 dark:text-gray-400">Completed:</span>
                        <span class="ml-2 font-medium">{{ formatDate(selectedTask.completed_at) }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showTaskModal = false">
                        Close
                    </SecondaryButton>
                    <Link
                        :href="route('tasks.edit', selectedTask.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Edit Task
                    </Link>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
/* FullCalendar custom styles */
.calendar-container {
    @apply font-sans;
}

/* Dark mode support */
.dark .fc-theme-standard .fc-toolbar,
.dark .fc-theme-standard .fc-view {
    @apply bg-gray-800 text-white;
}

.dark .fc-theme-standard td,
.dark .fc-theme-standard th {
    @apply border-gray-700;
}

.dark .fc-theme-standard .fc-list-day-cushion {
    @apply bg-gray-700;
}

.dark .fc-theme-standard .fc-list-event:hover td {
    @apply bg-gray-700;
}

.dark .fc-theme-standard .fc-list {
    @apply border-gray-700;
}

.dark .fc-theme-standard .fc-button {
    @apply bg-gray-700 text-white border-gray-600;
}

.dark .fc-theme-standard .fc-button:hover {
    @apply bg-gray-600;
}

.dark .fc-theme-standard .fc-button-active {
    @apply bg-blue-600 border-blue-700;
}

.dark .fc-daygrid-day-number,
.dark .fc-col-header-cell-cushion {
    @apply text-white;
}

/* Adjust height for day cells */
.fc-daygrid-day-frame {
    min-height: 100px;
}

/* Make events more interactive */
.fc-event {
    cursor: pointer;
    transition: transform 0.1s;
}

.fc-event:hover {
    transform: scale(1.02);
    z-index: 10;
}

/* Today highlight */
.fc-day-today {
    @apply bg-blue-50;
}

.dark .fc-day-today {
    @apply bg-blue-900/20;
}

/* Make the calendar responsive */
@media (max-width: 640px) {
    .fc-toolbar.fc-header-toolbar {
        flex-direction: column;
        gap: 0.5rem;
    }

    .fc-toolbar-chunk {
        display: flex;
        justify-content: center;
    }
}
</style>
