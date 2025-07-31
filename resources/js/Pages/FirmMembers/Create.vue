<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    lawFirm: Object,
});

const form = useForm({
    email: '',
    role: 'staff',
    title: '',
    department: '',
});

const submit = () => {
    form.post(route('firm.members.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Invite Team Member" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Invite Team Member
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Team Member Information</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Invite a new member to join your law firm. They will receive an email with instructions to accept the invitation.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="email" value="Email Address" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="role" value="Role" />
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                    required
                                >
                                    <option value="admin">Administrator</option>
                                    <option value="attorney">Attorney</option>
                                    <option value="staff">Staff</option>
                                </select>
                                <InputError :message="form.errors.role" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="form.role === 'admin'">Administrators have full access to all firm settings and data.</span>
                                    <span v-else-if="form.role === 'attorney'">Attorneys can manage cases, clients, and documents, but have limited access to firm settings.</span>
                                    <span v-else>Staff members have limited access based on case assignments.</span>
                                </p>
                            </div>

                            <div>
                                <InputLabel for="title" value="Title (Optional)" />
                                <TextInput
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., Partner, Associate, Paralegal"
                                />
                                <InputError :message="form.errors.title" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="department" value="Department (Optional)" />
                                <TextInput
                                    id="department"
                                    v-model="form.department"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., Litigation, Corporate, Family Law"
                                />
                                <InputError :message="form.errors.department" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton
                                    class="ml-4"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing">Sending...</span>
                                    <span v-else>Send Invitation</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Role Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-full mr-2">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Administrator</h4>
                                </div>
                                <ul class="text-xs text-gray-600 dark:text-gray-400 ml-4 space-y-1 list-disc">
                                    <li>Manage firm settings and billing</li>
                                    <li>Add/remove team members</li>
                                    <li>Full access to all cases and clients</li>
                                    <li>View and edit all documents</li>
                                    <li>Manage permissions and roles</li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-full mr-2">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Attorney</h4>
                                </div>
                                <ul class="text-xs text-gray-600 dark:text-gray-400 ml-4 space-y-1 list-disc">
                                    <li>Create and manage cases</li>
                                    <li>Add and manage clients</li>
                                    <li>Upload and edit documents</li>
                                    <li>Invite team members</li>
                                    <li>Assign tasks to team members</li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-full mr-2">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Staff</h4>
                                </div>
                                <ul class="text-xs text-gray-600 dark:text-gray-400 ml-4 space-y-1 list-disc">
                                    <li>View assigned cases</li>
                                    <li>View documents for assigned cases</li>
                                    <li>Upload documents to assigned cases</li>
                                    <li>Complete assigned tasks</li>
                                    <li>Limited access to client information</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
