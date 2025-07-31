<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CaseForm from './Form.vue';

const props = defineProps({
    case: Object,
});

const legalCase = computed(() => props.case);
</script>

<template>
    <Head :title="`Edit - ${legalCase.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Legal Case
            </h2>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <CaseForm :case="legalCase" :is-editing="true" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
