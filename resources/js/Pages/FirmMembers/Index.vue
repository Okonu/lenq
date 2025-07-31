<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    lawFirm: Object,
    members: Array,
    userRole: String,
});

// Check if the user is an admin
const isAdmin = computed(() => props.userRole === 'admin');

// Search and filter state
const searchQuery = ref('');
const statusFilter = ref('all');
const roleFilter = ref('all');

// Member being edited/deleted
const currentMember = ref(null);
const showDeleteModal = ref(false);
const showEditModal = ref(false);

// Form data for edit modal
const editForm = ref({
    role: '',
    title: '',
    department: '',
    status: '',
});

// Filter members by search and filters
const filteredMembers = computed(() => {
    return props.members.filter(member => {
        const matchesSearch = member.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all' || member.status === statusFilter.value;
        const matchesRole = roleFilter.value === 'all' || member.role === roleFilter.value;

        return matchesSearch && matchesStatus && matchesRole;
    });
});

// Format date
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString();
};

// Open delete confirmation modal
const confirmDelete = (member) => {
    currentMember.value = member;
    showDeleteModal.value = true;
};

// Delete member
const deleteMember = async () => {
    try {
        await axios.delete(route('firm.members.destroy', currentMember.value.id));
        window.location.reload();
    } catch (error) {
        console.error('Error deleting member:', error);
        // Handle error
    } finally {
        showDeleteModal.value = false;
    }
};

// Open edit modal
const openEditModal = (member) => {
    currentMember.value = member;
    editForm.value = {
        role: member.role,
        title: member.title || '',
        department: member.department || '',
        status: member.status,
    };
    showEditModal.value = true;
};

// Update member
const updateMember = async () => {
    try {
        await axios.put(route('firm.members.update', currentMember.value.id), editForm.value);
        window.location.reload();
    } catch (error) {
        console.error('Error updating member:', error);
        // Handle error
    } finally {
        showEditModal.value = false;
    }
};

// Get status badge color
const getStatusColor = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'invited':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
    }
};

// Get role badge color
const getRoleColor = (role) => {
    switch (role) {
        case 'admin':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        case 'attorney':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'staff':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<template>
    <Head title="Team Members" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Team Members
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
                                        placeholder="Search members..."
                                    >
                                </div>

                                <!-- Status Filter -->
                                <select
                                    v-model="statusFilter"
                                    class="block w-full md:w-40 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="invited">Invited</option>
                                    <option value="inactive">Inactive</option>
                                </select>

                                <!-- Role Filter -->
                                <select
                                    v-model="roleFilter"
                                    class="block w-full md:w-40 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="all">All Roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="attorney">Attorney</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>

                            <!-- Invite Button -->
                            <div v-if="isAdmin || userRole === 'attorney'">
                                <Link
                                    :href="route('firm.members.create')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Invite Team Member
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Title
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Joined
                                    </th>
                                    <th v-if="isAdmin" scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="member in filteredMembers" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                                                        {{ member.name.charAt(0).toUpperCase() }}
                                                    </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ member.name }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ member.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ member.title || '—' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ member.department || '—' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getRoleColor(member.role)">
                                                {{ member.role.charAt(0).toUpperCase() + member.role.slice(1) }}
                                            </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusColor(member.status)">
                                                {{ member.status.charAt(0).toUpperCase() + member.status.slice(1) }}
                                            </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(member.joined_at) }}
                                    </td>
                                    <td v-if="isAdmin" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openEditModal(member)"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="confirmDelete(member)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredMembers.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No members found matching your filters.
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Remove Team Member
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to remove {{ currentMember?.name }} from your firm? This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDeleteModal = false" class="mr-3">
                        Cancel
                    </SecondaryButton>

                    <DangerButton @click="deleteMember" :class="{ 'opacity-25': false }" :disabled="false">
                        Remove Member
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Edit Member Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Edit Team Member
                </h2>

                <div class="mt-4">
                    <div>
                        <InputLabel for="role" value="Role" />
                        <select
                            id="role"
                            v-model="editForm.role"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="admin">Admin</option>
                            <option value="attorney">Attorney</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <InputLabel for="title" value="Title" />
                        <TextInput
                            id="title"
                            v-model="editForm.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., Senior Partner, Paralegal"
                        />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="department" value="Department" />
                        <TextInput
                            id="department"
                            v-model="editForm.department"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., Litigation, Corporate"
                        />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="status" value="Status" />
                        <select
                            id="status"
                            v-model="editForm.status"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="invited">Invited</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showEditModal = false" class="mr-3">
                        Cancel
                    </SecondaryButton>

                    <PrimaryButton @click="updateMember" :class="{ 'opacity-25': false }" :disabled="false">
                        Save Changes
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
