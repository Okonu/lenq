<script setup>
defineProps({
    message: Object,
});

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <div
        :class="[
      'flex',
      message.is_user ? 'justify-end' : 'justify-start'
    ]"
    >
        <div
            :class="[
        'max-w-3/4 rounded-lg p-3 break-words',
        message.is_user
          ? 'bg-blue-100 dark:bg-blue-900 text-gray-900 dark:text-gray-100'
          : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
      ]"
            style="max-width: 80%;"
        >
            <div class="whitespace-pre-wrap">{{ message.content }}</div>
            <div class="text-xs text-right mt-1 text-gray-500 dark:text-gray-400">
                {{ formatTime(message.created_at) }}
            </div>
        </div>
    </div>
</template>
