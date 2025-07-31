<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    lawFirm: Object,
    clients: Array,
    userRole: String,
});

// Search and filter state
const searchQuery = ref('');
const typeFilter = ref('all');
const statusFilter = ref('all');

// Client being deleted
const currentClient = ref(null);
const showDeleteModal = ref(false);

// Filter clients by search and filters
const filteredClients = computed(() => {
    return props.clients.filter(client => {
        const matchesSearch = client.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (client.email && client.email.toLowerCase().includes(searchQuery.value.toLowerCase()));
        const matchesType = typeFilter.value === 'all' || client.type === typeFilter.value;
        const matchesStatus = statusFilter.value === 'all' || client.status === statusFilter.value;

        return matchesSearch && matchesType && matchesStatus;
    });
});

// Check if the user can edit/delete clients
const canEditClients = computed(() => {
    return props.userRole === 'admin' || props.userRole === 'attorney';
});

// Check if the user can delete clients
const canDeleteClients = computed(() => {
    return props.userRole === 'admin';
});

// Open delete confirmation modal
const confirmDelete = (client) => {
    currentClient.value = client;
    showDeleteModal.value = true;
};

// Delete client
const deleteClient = async () => {
    try {
        await axios.delete(route('clients.destroy', currentClient.value.id));
        window.location.reload();
    } catch (error) {
        console.error('Error deleting client:', error);
        // Handle error
    } finally {
        showDeleteModal.value = false;
    }
};

// Get type badge color
const getTypeColor = (type) => {
    switch (type) {
        case 'individual':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'organization':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

// Get status badge color
const getStatusColor = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
    }
};
</script>

<template>
    <Head title="Clients" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Clients
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Controls -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                            <!-- Search and Filters -->
                            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                                <!-- Search -->
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        class="pl-10 py-2 pr-4 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="Search clients..."
                                    >
                                </div>

                                <!-- Type Filter -->
                                <select
                                    v-model="typeFilter"
                                    class="block w-full md:w-40 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="all">All Types</option>
                                    <option value="individual">Individual</option>
                                    <option value="organization">Organization</option>
                                </select>

                                <!-- Status Filter -->
                                <select
                                    v-model="statusFilter"
                                    class="block w-full md:w-40 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <!-- Add Client Button -->
                            <div>
                                <Link
                                    :href="route('clients.create')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Client
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clients Grid -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="filteredClients.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="client in filteredClients" :key="client.id" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm overflow-hidden">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div v-if="client.type === 'individual'" class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div v-else class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-base font-medium text-gray-900 dark:text-white truncate">
                                                {{ client.name }}
                                            </h3>
                                            <div class="flex items-center space-x-2 mt-1">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getTypeColor(client.type)">
                                                    {{ client.type.charAt(0).toUpperCase() + client.type.slice(1) }}
                                                </span>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusColor(client.status)">
                                                    {{ client.status.charAt(0).toUpperCase() + client.status.slice(1) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                            {{ client.cases_count }} cases
                                        </span>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-1 gap-2">
                                        <div v-if="client.email" class="flex items-start">
                                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300 break-all">{{ client.email }}</span>
                                        </div>
                                        <div v-if="client.phone" class="flex items-start">
                                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">{{ client.phone }}</span>
                                        </div>
                                        <div v-if="client.address" class="flex items-start">
                                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                                                {{ client.address }}
                                                <span v-if="client.city || client.state">
                                                    <br>{{ [client.city, client.state, client.zip].filter(Boolean).join(', ') }}
                                                </span>
                                            </span>
                                        </div>
                                        <div v-if="client.contact_name && client.type === 'organization'" class="flex items-start">
                                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">{{ client.contact_name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-right border-t border-gray-200 dark:border-gray-600">
                                    <Link
                                        :href="route('clients.show', client.id)"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <span>View Details</span>
                                    </Link>
                                    <Link
                                        v-if="canEditClients"
                                        :href="route('clients.edit', client.id)"
                                        class="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <span>Edit</span>
                                    </Link>
                                    <button
                                        v-if="canDeleteClients"
                                        @click="confirmDelete(client)"
                                        class="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                {{ clients.length === 0 ? 'No clients yet' : 'No clients match your filters' }}
                            </h3>
                            <p v-if="clients.length === 0" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Get started by adding your first client.
                            </p>
                            <div class="mt-6">
                                <button
                                    v-if="clients.length === 0"
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    @click="$inertia.visit(route('clients.create'))"
                                >
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Client
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    @click="searchQuery = ''; typeFilter = 'all'; statusFilter = 'all'"
                                >
                                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Delete Client
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete this client? All associated data will be permanently removed. This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDeleteModal = false" class="mr-3">
                        Cancel
                    </SecondaryButton>

                    <DangerButton @click="deleteClient" :class="{ 'opacity-25': false }" :disabled="false">
                        Delete Client
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
