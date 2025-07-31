<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    lawFirm: Object,
});

const form = useForm({
    type: 'individual', // individual or organization
    name: '',
    contact_name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    status: 'active',
    notes: '',
});

const isOrganization = computed(() => form.type === 'organization');

// Clear contact name when switching from organization to individual
watch(() => form.type, (newType) => {
    if (newType === 'individual') {
        form.contact_name = '';
    }
});

const submit = () => {
    form.post(route('clients.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Add Client" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Add Client
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Client Information</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Add a new client to your firm. You can add both individuals and organizations.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Client Type Selection -->
                            <div>
                                <InputLabel value="Client Type" />
                                <div class="mt-1 grid grid-cols-2 gap-4">
                                    <div
                                        @click="form.type = 'individual'"
                                        :class="[
                                            'border rounded-lg p-4 cursor-pointer transition-all',
                                            form.type === 'individual'
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                                                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        ]"
                                    >
                                        <div class="flex items-center">
                                            <div class="flex items-center h-5">
                                                <input
                                                    id="type-individual"
                                                    v-model="form.type"
                                                    type="radio"
                                                    value="individual"
                                                    class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="type-individual" class="font-medium text-gray-900 dark:text-white">
                                                    Individual
                                                </label>
                                                <p class="text-gray-500 dark:text-gray-400">
                                                    A person or sole proprietor
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        @click="form.type = 'organization'"
                                        :class="[
                                            'border rounded-lg p-4 cursor-pointer transition-all',
                                            form.type === 'organization'
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                                                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        ]"
                                    >
                                        <div class="flex items-center">
                                            <div class="flex items-center h-5">
                                                <input
                                                    id="type-organization"
                                                    v-model="form.type"
                                                    type="radio"
                                                    value="organization"
                                                    class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="type-organization" class="font-medium text-gray-900 dark:text-white">
                                                    Organization
                                                </label>
                                                <p class="text-gray-500 dark:text-gray-400">
                                                    A company, non-profit, or other entity
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <InputError :message="form.errors.type" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Name -->
                                <div class="col-span-2">
                                    <InputLabel for="name" :value="isOrganization ? 'Organization Name' : 'Client Name'" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        autofocus
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <!-- Contact Name (Organizations only) -->
                                <div v-if="isOrganization" class="col-span-2">
                                    <InputLabel for="contact_name" value="Primary Contact Name" />
                                    <TextInput
                                        id="contact_name"
                                        v-model="form.contact_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.contact_name" class="mt-2" />
                                </div>

                                <!-- Status -->
                                <div>
                                    <InputLabel for="status" value="Status" />
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <InputError :message="form.errors.status" class="mt-2" />
                                </div>

                                <!-- Email -->
                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>

                                <!-- Phone -->
                                <div>
                                    <InputLabel for="phone" value="Phone" />
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.phone" class="mt-2" />
                                </div>

                                <!-- Address -->
                                <div class="col-span-2">
                                    <InputLabel for="address" value="Address" />
                                    <TextInput
                                        id="address"
                                        v-model="form.address"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.address" class="mt-2" />
                                </div>

                                <!-- City -->
                                <div>
                                    <InputLabel for="city" value="City" />
                                    <TextInput
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.city" class="mt-2" />
                                </div>

                                <!-- State/Province -->
                                <div>
                                    <InputLabel for="state" value="State/Province" />
                                    <TextInput
                                        id="state"
                                        v-model="form.state"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.state" class="mt-2" />
                                </div>

                                <!-- Zip/Postal Code -->
                                <div>
                                    <InputLabel for="zip" value="Zip/Postal Code" />
                                    <TextInput
                                        id="zip"
                                        v-model="form.zip"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.zip" class="mt-2" />
                                </div>

                                <!-- Notes -->
                                <div class="col-span-2">
                                    <InputLabel for="notes" value="Notes (Optional)" />
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                        rows="3"
                                    ></textarea>
                                    <InputError :message="form.errors.notes" class="mt-2" />
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <a
                                    :href="route('clients.index')"
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 mr-3"
                                >
                                    Cancel
                                </a>
                                <PrimaryButton
                                    class="ml-4"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing">Adding...</span>
                                    <span v-else>Add Client</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
