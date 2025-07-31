<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    lawFirm: Object,
    tasks: Array,
    teamMembers: Array,
    cases: Array,
    filters: Object,
    userRole: String,
});

// Task being viewed/deleted
const currentTask = ref(null);
const showDeleteModal = ref(false);
const showDetailsModal = ref(false);

// Status update
const updateTaskStatus = async (task, newStatus) => {
    try {
        await axios.patch(route('tasks.updateStatus', task.id), { status: newStatus });

        // Update task status locally
        task.status = newStatus;
        if (newStatus === 'completed') {
            task.completed_at = new Date().toISOString();
        } else {
            task.completed_at = null;
        }
    } catch (error) {
        console.error('Error updating task status:', error);
    }
};

// Delete task confirmation
const confirmDelete = (task) => {
    currentTask.value = task;
    showDeleteModal.value = true;
};

// Delete task
const deleteTask = async () => {
    try {
        await axios.delete(route('tasks.destroy', currentTask.value.id));
        window.location.reload();
    } catch (error) {
        console.error('Error deleting task:', error);
    } finally {
        showDeleteModal.value = false;
    }
};

// Show task details
const showDetails = (task) => {
    currentTask.value = task;
    showDetailsModal.value = true;
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return 'No due date';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

// Get due date status
const getDueStatus = (task) => {
    if (!task.due_date) return 'no_date';
    if (task.status === 'completed') return 'completed';

    const dueDate = new Date(task.due_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const nextWeek = new Date(today);
    nextWeek.setDate(nextWeek.getDate() + 7);

    if (dueDate < today) return 'overdue';
    if (dueDate.getTime() === today.getTime()) return 'today';
    if (dueDate.getTime() === tomorrow.getTime()) return 'tomorrow';
    if (dueDate < nextWeek) return 'this_week';

    return 'future';
};

// Group tasks by due date
const groupedTasks = computed(() => {
    const groups = {
        overdue: [],
        today: [],
        tomorrow: [],
        this_week: [],
        future: [],
        no_date: [],
        completed: [],
    };

    props.tasks.forEach(task => {
        const status = getDueStatus(task);
        groups[status].push(task);
    });

    // Sort tasks within each group by priority
    Object.keys(groups).forEach(key => {
        groups[key].sort((a, b) => {
            const priorityOrder = { urgent: 1, high: 2, medium: 3, low: 4 };
            return priorityOrder[a.priority] - priorityOrder[b.priority];
        });
    });

    return groups;
});

// Check if there are no tasks
const noTasks = computed(() => props.tasks.length === 0);

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

// Format status display name
const formatStatus = (status) => {
    if (status === 'in_progress') return 'In Progress';
    return status.charAt(0).toUpperCase() + status.slice(1);
};

// Get assignee name
const getAssigneeName = (assigneeId) => {
    if (!assigneeId) return 'Unassigned';
    const member = props.teamMembers.find(m => m.id === assigneeId);
    return member ? member.name : 'Unknown';
};

// Get case name
const getCaseName = (caseId) => {
    if (!caseId) return 'No Case';
    const legalCase = props.cases.find(c => c.id === caseId);
    return legalCase ? legalCase.title : 'Unknown Case';
};
</script>

<template>
    <Head title="Tasks" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Tasks
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Controls -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                        <!-- Filters -->
                        <div class="flex flex-wrap gap-4">
                            <Link
                                :href="route('tasks.index', {...filters, status: 'all'})"
                                class="inline-flex items-center px-3 py-1.5 text-sm rounded-full"
                                :class="filters.status === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                            >All</Link>
                            <Link
                                :href="route('tasks.index', {...filters, status: 'pending'})"
                                class="inline-flex items-center px-3 py-1.5 text-sm rounded-full"
                                :class="filters.status === 'pending' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                            >Pending</Link>
                            <Link
                                :href="route('tasks.index', {...filters, status: 'in_progress'})"
                                class="inline-flex items-center px-3 py-1.5 text-sm rounded-full"
                                :class="filters.status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                            >In Progress</Link>
                            <Link
                                :href="route('tasks.index', {...filters, status: 'completed'})"
                                class="inline-flex items-center px-3 py-1.5 text-sm rounded-full"
                                :class="filters.status === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                            >Completed</Link>
                        </div>

                        <!-- Buttons -->
                        <div>
                            <Link
                                :href="route('tasks.create')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                New Task
                            </Link>
                            <Link
                                :href="route('tasks.calendar')"
                                class="ml-2 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Calendar View
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Task Lists -->
                <div v-if="!noTasks" class="space-y-6">
                    <!-- Overdue Tasks -->
                    <div v-if="groupedTasks.overdue.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-red-600">Overdue Tasks</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <div v-for="task in groupedTasks.overdue" :key="task.id" class="p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start">
                                        <!-- Checkbox -->
                                        <div class="flex-shrink-0">
                                            <button
                                                @click="updateTaskStatus(task, 'completed')"
                                                class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center"
                                                title="Mark as completed"
                                            >
                                                <svg class="w-4 h-4 text-transparent hover:text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Task Content -->
                                        <div class="ml-3 flex-1">
                                            <div class="flex justify-between">
                                                <button
                                                    @click="showDetails(task)"
                                                    class="text-sm font-medium text-gray-900 hover:text-blue-600 text-left"
                                                >
                                                    {{ task.title }}
                                                </button>
                                                <div class="flex items-center space-x-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="getPriorityColor(task.priority)">
                                                        {{ task.priority.charAt(0).toUpperCase() + task.priority.slice(1) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="getStatusColor(task.status)">
                                                        {{ formatStatus(task.status) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ task.description }}</p>
                                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                                <div class="flex items-center">
                                                    <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="text-red-600 font-medium">Due {{ formatDate(task.due_date) }}</span>
                                                </div>
                                                <div class="flex items-center">
                                                    <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    <span>{{ getAssigneeName(task.assigned_to) }}</span>
                                                </div>
                                                <div v-if="task.legal_case_id" class="flex items-center">
                                                    <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                    <span>{{ getCaseName(task.legal_case_id) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('tasks.edit', task.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmDelete(task)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Tasks (similar structure as overdue) -->
                    <div v-if="groupedTasks.today.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-blue-600">Today</h3>
                        </div>
                        <!-- Similar task list structure as above -->
                    </div>

                    <!-- More task groups would follow the same pattern -->
                </div>

                <!-- Empty State -->
                <div v-if="noTasks" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new task.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('tasks.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                New Task
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete Task</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete this task? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDeleteModal = false" class="mr-3">Cancel</SecondaryButton>
                    <DangerButton @click="deleteTask">Delete Task</DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Task Details Modal -->
        <Modal :show="showDetailsModal" @close="showDetailsModal = false">
            <div class="p-6" v-if="currentTask">
                <div class="flex justify-between items-start">
                    <h2 class="text-lg font-medium text-gray-900">{{ currentTask.title }}</h2>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getPriorityColor(currentTask.priority)">
                            {{ currentTask.priority.charAt(0).toUpperCase() + currentTask.priority.slice(1) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(currentTask.status)">
                            {{ formatStatus(currentTask.status) }}
                        </span>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-gray-600">{{ currentTask.description }}</p>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Due Date:</span>
                        <span class="ml-2 font-medium">{{ formatDate(currentTask.due_date) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Assigned To:</span>
                        <span class="ml-2 font-medium">{{ getAssigneeName(currentTask.assigned_to) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Case:</span>
                        <span class="ml-2 font-medium">{{ getCaseName(currentTask.legal_case_id) }}</span>
                    </div>
                    <div v-if="currentTask.completed_at">
                        <span class="text-gray-500">Completed:</span>
                        <span class="ml-2 font-medium">{{ formatDate(currentTask.completed_at) }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDetailsModal = false">Close</SecondaryButton>
                    <Link
                        :href="route('tasks.edit', currentTask.id)"
                        class="ml-3 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                    >
                        Edit Task
                    </Link>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
