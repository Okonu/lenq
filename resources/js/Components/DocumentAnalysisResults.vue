<script setup>
import { computed } from 'vue';

const props = defineProps({
    document: Object,
    type: String,
});

// Get the analysis content based on document type
const analysisContent = computed(() => {
    if (!props.document.analysis) return null;

    switch (props.document.type) {
        case 'contract':
            return props.document.analysis.review;
        case 'case':
            return props.document.analysis.case_analysis;
        case 'discovery':
            return props.document.analysis.discovery_analysis;
        default:
            return props.document.analysis.analysis;
    }
});

// Format the content with Markdown-style formatting (could be enhanced)
const formattedContent = computed(() => {
    if (!analysisContent.value) return '';

    //TODO: to add a document parser library
    return analysisContent.value
        .replace(/\n\n/g, '<br><br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>');
});
</script>

<template>
    <div class="prose dark:prose-invert max-w-none">
        <div v-if="analysisContent" class="text-gray-800 dark:text-gray-200">
            <div class="whitespace-pre-wrap" v-html="formattedContent"></div>
        </div>
        <div v-else class="text-gray-500 dark:text-gray-400 italic">
            No analysis available for this document.
        </div>
    </div>
</template>
