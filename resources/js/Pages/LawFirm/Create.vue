<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    name: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    phone: '',
    email: '',
    website: '',
    logo: null,
});

const logoPreview = ref(null);
const logoInput = ref(null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];

    if (file) {
        form.logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const clearLogo = () => {
    form.logo = null;
    logoPreview.value = null;
    if (logoInput.value) {
        logoInput.value.value = '';
    }
};

const submit = () => {
    form.post(route('lawfirm.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            logoPreview.value = null;
        },
    });
};
</script>

<template>
    <Head title="Create Law Firm" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Create Your Law Firm
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Welcome to LegalAssist</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                To get started, please create your law firm profile. This will allow you to invite team members,
                                manage clients, and organize your legal cases.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Firm Name -->
                                <div class="col-span-2">
                                    <InputLabel for="name" value="Firm Name" />
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

                                <!-- Logo Upload -->
                                <div class="col-span-2 md:col-span-1">
                                    <InputLabel value="Firm Logo (Optional)" />
                                    <div class="mt-1 flex items-center">
                                        <div v-if="logoPreview" class="relative w-24 h-24 overflow-hidden rounded-md border-2 border-gray-200 dark:border-gray-700">
                                            <img :src="logoPreview" class="w-full h-full object-cover" />
                                            <button
                                                type="button"
                                                @click="clearLogo"
                                                class="absolute top-0 right-0 p-1 bg-red-500 text-white rounded-bl-md"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                        <div v-else class="w-24 h-24 bg-gray-100 dark:bg-gray-700 flex items-center justify-center rounded-md border-2 border-dashed border-gray-300 dark:border-gray-600">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <button
                                            type="button"
                                            class="ml-5 bg-white dark:bg-gray-700 py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            @click="logoInput.click()"
                                        >
                                            Change
                                        </button>
                                        <input
                                            ref="logoInput"
                                            type="file"
                                            class="hidden"
                                            @change="handleLogoChange"
                                            accept="image/*"
                                        />
                                    </div>
                                    <InputError :message="form.errors.logo" class="mt-2" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Upload a logo for your firm. Recommended size: 200x200 pixels.
                                    </p>
                                </div>

                                <!-- Contact Information -->
                                <div class="col-span-2 md:col-span-1 space-y-4">
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
                                    <div>
                                        <InputLabel for="website" value="Website (Optional)" />
                                        <TextInput
                                            id="website"
                                            v-model="form.website"
                                            type="url"
                                            class="mt-1 block w-full"
                                            placeholder="https://example.com"
                                        />
                                        <InputError :message="form.errors.website" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Address -->
                                <div>
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

                                <!-- State -->
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

                                <!-- Zip -->
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
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton
                                    class="ml-4"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Create Law Firm
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
