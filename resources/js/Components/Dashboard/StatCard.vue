<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    target: {
        type: Number,
        default: 0
    },
    icon: {
        type: String,
        required: true
    },
    textColor: {
        type: String,
        default: 'text-blue-600 dark:text-blue-400'
    },
    bgColor: {
        type: String,
        default: 'bg-blue-50 dark:bg-blue-900/20'
    },
    color: {
        type: String,
        default: 'bg-blue-500'
    },
    viewAllRoute: {
        type: String,
        required: true
    }
});

const count = ref(0);

// Animate counter from 0 to target
const animateCounter = () => {
    const duration = 1000; // milliseconds
    const interval = 50; // update every 50ms
    const steps = duration / interval;
    const increment = props.target / steps;
    let currentCount = 0;

    const counterInterval = setInterval(() => {
        currentCount += increment;

        if (currentCount >= props.target) {
            count.value = props.target;
            clearInterval(counterInterval);
        } else {
            count.value = Math.floor(currentCount);
        }
    }, interval);
};

// Watch for changes in target value
watch(() => props.target, (newTarget) => {
    count.value = 0;
    if (newTarget > 0) {
        animateCounter();
    }
});

onMounted(() => {
    if (props.target > 0) {
        animateCounter();
    }
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 transition duration-300 hover:shadow-md">
        <div class="flex items-center">
            <div class="rounded-full p-3 mr-4" :class="bgColor">
                <svg class="w-6 h-6" :class="textColor" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ name }}</p>
                <div class="flex items-baseline space-x-1">
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ count }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-normal">total</p>
                </div>
            </div>
            <div class="ml-auto">
                <Link
                    :href="route(viewAllRoute)"
                    class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium inline-flex items-center"
                >
                    View All
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </Link>
            </div>
        </div>

        <!-- Progress indicator -->
        <div class="mt-4 h-1 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full rounded-full" :class="color" :style="{ width: target > 0 ? `${(count / target) * 100}%` : '0%' }"></div>
        </div>
    </div>
</template>
