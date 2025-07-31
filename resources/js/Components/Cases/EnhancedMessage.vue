<template>
    <div :class="['flex', message.is_user ? 'justify-end' : 'justify-start']">
        <div :class="['max-w-3xl', message.is_user ? 'order-1' : 'order-2']">
            <!-- Reply indicator -->
            <div v-if="message.reply_to_id" class="text-xs text-gray-500 dark:text-gray-400 mb-1 px-4">
                <Icon name="reply" class="w-3 h-3 inline mr-1" />
                Replying to {{ getReplyToUser(message.reply_to_id) }}
            </div>

            <!-- Message bubble -->
            <div :class="messageBubbleClass">
                <!-- Attachments -->
                <DocumentAttachments
                    v-if="message.attachments?.length"
                    :attachments="message.attachments"
                    :is-user="message.is_user"
                />

                <!-- Message content with markdown support -->
                <MessageContent
                    :content="message.content"
                    :is-user="message.is_user"
                />

                <!-- Message footer -->
                <div class="flex items-center justify-between mt-2 pt-2">
                    <span :class="timestampClass">
                        {{ formatTime(message.created_at) }}
                    </span>

                    <!-- Message actions -->
                    <MessageActions
                        v-if="!message.is_user"
                        :message="message"
                        @reply="$emit('reply', message)"
                        @bookmark="$emit('bookmark', message)"
                        @react="$emit('react', message, $event)"
                        @copy="copyMessage"
                    />
                </div>

                <!-- Reactions -->
                <MessageReactions
                    v-if="message.reactions?.length"
                    :reactions="message.reactions"
                    @toggle-reaction="$emit('react', message, $event)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import DocumentAttachments from './DocumentAttachments.vue'
import MessageContent from './MessageContent.vue'
import MessageActions from './MessageActions.vue'
import MessageReactions from './MessageReactions.vue'
import Icon from './Icon.vue'

const props = defineProps({
    message: {
        type: Object,
        required: true
    },
    previousMessages: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['reply', 'bookmark', 'react'])

const messageBubbleClass = computed(() => {
    const baseClass = 'rounded-2xl px-4 py-3 shadow-sm'

    if (props.message.is_user) {
        return `${baseClass} bg-blue-600 text-white`
    }

    return `${baseClass} bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700`
})

const timestampClass = computed(() => {
    if (props.message.is_user) {
        return 'text-xs text-blue-100'
    }
    return 'text-xs text-gray-500 dark:text-gray-400'
})

const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getReplyToUser = (replyToId) => {
    const replyMessage = props.previousMessages.find(m => m.id === replyToId)
    return replyMessage?.is_user ? 'You' : 'AI Assistant'
}

const copyMessage = () => {
    navigator.clipboard.writeText(props.message.content)
}
</script>
