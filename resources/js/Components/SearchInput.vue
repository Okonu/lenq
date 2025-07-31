<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Search...'
    },
    debounce: {
        type: Number,
        default: 300 // ms
    }
});

const emit = defineEmits(['update:modelValue']);

const localValue = ref(props.modelValue);
let debounceTimeout = null;

watch(() => props.modelValue, (newValue) => {
    localValue.value = newValue;
});

const updateValue = (event) => {
    const value = event.target.value;
    localValue.value = value;

    clearTimeout(debounceTimeout);

    debounceTimeout = setTimeout(() => {
        emit('update:modelValue', value);
    }, props.debounce);
};

const clearSearch = () => {
    localValue.value = '';
    emit('update:modelValue', '');
};
</script>

<template>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <input
            type="text"
            :value="localValue"
            @input="updateValue"
            class="block w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-300"
            :placeholder="placeholder"
        />

        <div
            v-if="localValue"
            class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer"
            @click="clearSearch"
        >
            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>
    </div>
</template>
