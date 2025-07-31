<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
    teamMembers: {
        type: Array,
        default: () => [],
    },
    case: {
        type: Object,
        default: () => ({
            id: null,
            title: '',
            description: '',
            case_number: '',
            jurisdiction: '',
            status: 'active',
            category: '',
            client_id: null,
            team_members: [],
        }),
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    clientId: {
        type: [Number, String, null],
        default: null
    }
});

// Prepare team members for editing case
const selectedTeamMembers = ref([]);
if (props.isEditing && props.case.teamMembers) {
    selectedTeamMembers.value = props.case.teamMembers.map(member => ({
        id: member.id,
        role: member.pivot.role,
    }));
}

// Use the provided clientId if available, otherwise use the case's client_id
const initialClientId = props.clientId !== null ? props.clientId : props.case.client_id;

const form = useForm({
    title: props.case.title || '',
    description: props.case.description || '',
    case_number: props.case.case_number || '',
    jurisdiction: props.case.jurisdiction || '',
    status: props.case.status || 'active',
    category: props.case.category || '',
    client_id: initialClientId,
    team_members: selectedTeamMembers.value,
});

// Check if the client is pre-selected (and should be hidden)
const isClientPreSelected = computed(() => props.clientId !== null);

// Find the pre-selected client name to display
const preSelectedClientName = computed(() => {
    if (!props.clientId || !props.clients || props.clients.length === 0) return '';
    const client = props.clients.find(c => c.id == props.clientId);
    return client ? client.name : '';
});

// Check if we have a lead attorney assigned
const hasLeadAttorney = computed(() => {
    return form.team_members.some(member => member.role === 'lead');
});

// Add team member
const addTeamMember = () => {
    form.team_members.push({
        id: null,
        role: 'associate',
    });
};

// Remove team member
const removeTeamMember = (index) => {
    form.team_members.splice(index, 1);
};

// Computed properties
const pageTitle = computed(() => props.isEditing ? 'Edit Case' : 'Create New Case');
const submitButtonText = computed(() => props.isEditing ? 'Update Case' : 'Create Case');

// Form submission
const submit = () => {
    if (props.isEditing) {
        form.put(route('cases.update', props.case.id));
    } else {
        form.post(route('cases.store'));
    }
};

// Get filtered attorneys for lead role
const getAttorneysForRole = (role) => {
    // For lead role, only show attorneys
    if (role === 'lead') {
        return props.teamMembers.filter(member => member.role === 'attorney' || member.role === 'admin');
    }

    // For other roles, show all team members
    return props.teamMembers;
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Case Information -->
            <div class="col-span-2">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Basic Case Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div class="col-span-2">
                        <InputLabel for="title" value="Case Title" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <!-- Client (shown only if not pre-selected) -->
                    <div v-if="!isClientPreSelected">
                        <InputLabel for="client_id" value="Client" />
                        <select
                            id="client_id"
                            v-model="form.client_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option :value="null">-- Select Client --</option>
                            <optgroup label="Individuals">
                                <option v-for="client in clients.filter(c => c.type === 'individual')" :key="`ind-${client.id}`" :value="client.id">
                                    {{ client.name }}
                                </option>
                            </optgroup>
                            <optgroup label="Organizations">
                                <option v-for="client in clients.filter(c => c.type === 'organization')" :key="`org-${client.id}`" :value="client.id">
                                    {{ client.name }}
                                </option>
                            </optgroup>
                        </select>
                        <InputError :message="form.errors.client_id" class="mt-2" />
                    </div>

                    <!-- Pre-selected Client (display only) -->
                    <div v-else>
                        <InputLabel value="Client" />
                        <div class="mt-1 block w-full p-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-500 dark:text-gray-400">
                            {{ preSelectedClientName }}
                            <input type="hidden" v-model="form.client_id" />
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <InputLabel for="status" value="Status" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            required
                        >
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="closed">Closed</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>

                    <!-- Case Number -->
                    <div>
                        <InputLabel for="case_number" value="Case Number" />
                        <TextInput
                            id="case_number"
                            v-model="form.case_number"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.case_number" class="mt-2" />
                    </div>

                    <!-- Jurisdiction -->
                    <div>
                        <InputLabel for="jurisdiction" value="Jurisdiction" />
                        <TextInput
                            id="jurisdiction"
                            v-model="form.jurisdiction"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.jurisdiction" class="mt-2" />
                    </div>

                    <!-- Category -->
                    <div>
                        <InputLabel for="category" value="Category" />
                        <select
                            id="category"
                            v-model="form.category"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">-- Select Category --</option>
                            <option value="corporate">Corporate</option>
                            <option value="litigation">Litigation</option>
                            <option value="family">Family Law</option>
                            <option value="criminal">Criminal Law</option>
                            <option value="estate">Estate Planning</option>
                            <option value="real_estate">Real Estate</option>
                            <option value="tax">Tax Law</option>
                            <option value="intellectual_property">Intellectual Property</option>
                            <option value="employment">Employment Law</option>
                            <option value="immigration">Immigration</option>
                            <option value="personal_injury">Personal Injury</option>
                            <option value="bankruptcy">Bankruptcy</option>
                            <option value="other">Other</option>
                        </select>
                        <InputError :message="form.errors.category" class="mt-2" />
                    </div>

                    <!-- Description -->
                    <div class="col-span-2">
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Team Assignments -->
            <div class="col-span-2" v-if="teamMembers.length > 0">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Team Assignments
                    </h3>
                    <button
                        type="button"
                        @click="addTeamMember"
                        class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Team Member
                    </button>
                </div>

                <div v-if="!form.team_members.length" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                    <p class="text-gray-600 dark:text-gray-400">
                        No team members assigned yet. Click "Add Team Member" to assign attorneys and staff to this case.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <div v-if="!hasLeadAttorney" class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-900 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    Please assign at least one lead attorney to this case.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-for="(member, index) in form.team_members" :key="index" class="flex items-center space-x-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <div class="flex-1">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Team Member -->
                                <div>
                                    <InputLabel :for="`team_member_${index}`" value="Team Member" />
                                    <select
                                        :id="`team_member_${index}`"
                                        v-model="member.id"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                        required
                                    >
                                        <option :value="null">-- Select Team Member --</option>
                                        <option v-for="teamMember in getAttorneysForRole(member.role)" :key="teamMember.id" :value="teamMember.id">
                                            {{ teamMember.name }} ({{ teamMember.role }})
                                        </option>
                                    </select>
                                    <InputError :message="form.errors[`team_members.${index}.id`]" class="mt-2" />
                                </div>

                                <!-- Role -->
                                <div>
                                    <InputLabel :for="`team_member_role_${index}`" value="Role" />
                                    <select
                                        :id="`team_member_role_${index}`"
                                        v-model="member.role"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                        required
                                    >
                                        <option value="lead">Lead Attorney</option>
                                        <option value="associate">Associate Attorney</option>
                                        <option value="paralegal">Paralegal</option>
                                        <option value="support">Support Staff</option>
                                    </select>
                                    <InputError :message="form.errors[`team_members.${index}.role`]" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <div>
                            <button
                                type="button"
                                @click="removeTeamMember(index)"
                                class="inline-flex items-center justify-center text-gray-400 hover:text-gray-500"
                            >
                                <span class="sr-only">Remove</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">

            <a :href="route('cases.index')"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
            Cancel
            </a>
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="form.processing"
            >
                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ submitButtonText }}
            </button>
        </div>
    </form>
</template>
