<template>
    <Link
        :href="getRouteUrl()"
        :class="[
            'flex items-center px-3 py-2 text-sm font-medium rounded-lg group transition-colors',
            isActive
                ? 'text-white bg-blue-600 hover:bg-blue-700'
                : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700'
        ]"
        @click="$emit('click', item)"
    >
        <!-- Icon -->
        <component
            :is="item.icon"
            :size="20"
            :class="[
                'mr-3 flex-shrink-0',
                isActive
                    ? 'text-white'
                    : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300'
            ]"
        />

        <!-- Label -->
        <span class="flex-1">{{ item.name }}</span>

        <!-- Badge (if present) -->
        <span
            v-if="item.badge"
            :class="[
                'ml-3 inline-block py-0.5 px-2 text-xs rounded-full',
                isActive
                    ? 'bg-blue-700 text-blue-100'
                    : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300'
            ]"
        >
            {{ item.badge }}
        </span>

        <!-- External link indicator -->
        <ExternalLink
            v-if="item.external"
            :size="14"
            :class="[
                'ml-2 flex-shrink-0',
                isActive ? 'text-blue-200' : 'text-gray-400'
            ]"
        />
    </Link>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ExternalLink } from 'lucide-react'

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    isActive: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['click'])

const getRouteUrl = () => {
    if (props.item.external) {
        return props.item.href
    }

    if (props.item.params) {
        return route(props.item.route, props.item.params)
    }

    return route(props.item.route)
}
</script>
