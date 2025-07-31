<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Generate Legal Document
                </h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                    <X :size="20" />
                </button>
            </div>

            <!-- Document Type Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Document Type
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        v-for="type in documentTypes"
                        :key="type.value"
                        @click="selectedType = type.value"
                        :class="[
                            'p-4 text-left rounded-lg border-2 transition-colors',
                            selectedType === type.value
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                        ]"
                    >
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-lg">{{ type.icon }}</span>
                            <span class="font-medium">{{ type.label }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ type.description }}
                        </p>
                    </button>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Instructions & Requirements
                </label>
                <textarea
                    v-model="instructions"
                    rows="4"
                    placeholder="Describe the document you need. Include parties, terms, jurisdiction, and any specific requirements..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
            </div>

            <!-- Document Title -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Document Title (Optional)
                </label>
                <input
                    v-model="documentTitle"
                    type="text"
                    placeholder="Auto-generated if left blank"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
            </div>

            <!-- Format Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Download Format
                </label>
                <div class="flex space-x-3">
                    <label v-for="format in formats" :key="format.value" class="flex items-center">
                        <input
                            v-model="selectedFormat"
                            :value="format.value"
                            type="radio"
                            class="mr-2"
                        />
                        <span class="text-sm">{{ format.label }}</span>
                    </label>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="flex justify-end space-x-3">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                >
                    Cancel
                </button>
                <button
                    @click="generateDocument"
                    :disabled="!canGenerate || generating"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                >
                    <Loader v-if="generating" :size="16" class="animate-spin" />
                    <FileText v-else :size="16" />
                    <span>{{ generating ? 'Generating...' : 'Generate Document' }}</span>
                </button>
            </div>

            <!-- Generated Content Preview -->
            <div v-if="generatedContent && !generating" class="mt-6 border-t pt-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white">
                        Generated Document Preview
                    </h4>
                    <div class="flex space-x-2">
                        <button
                            @click="downloadDocument"
                            class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 flex items-center space-x-1"
                        >
                            <Download :size="14" />
                            <span>Download {{ selectedFormat.toUpperCase() }}</span>
                        </button>
                        <button
                            @click="saveToDatabase"
                            class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center space-x-1"
                        >
                            <Save :size="14" />
                            <span>Save Record</span>
                        </button>
                    </div>
                </div>

                <!-- Content Preview -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto">
                    <pre class="text-sm whitespace-pre-wrap">{{ generatedContent }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { X, FileText, Loader, Download, Save } from 'lucide-react'
import axios from 'axios'
import { jsPDF } from 'jspdf'

const props = defineProps({
    conversation: Object,
    caseId: Number
})

const emit = defineEmits(['close', 'generated'])

const selectedType = ref('contract')
const instructions = ref('')
const documentTitle = ref('')
const selectedFormat = ref('pdf')
const generating = ref(false)
const generatedContent = ref('')

const documentTypes = [
    {
        value: 'contract',
        label: 'Employment Contract',
        icon: '📄',
        description: 'Standard employment agreements with terms and conditions'
    },
    {
        value: 'agreement',
        label: 'Service Agreement',
        icon: '📋',
        description: 'Business service agreements and contracts'
    },
    {
        value: 'letter',
        label: 'Legal Letter',
        icon: '✉️',
        description: 'Formal legal correspondence and notices'
    },
    {
        value: 'memo',
        label: 'Legal Memo',
        icon: '📝',
        description: 'Internal legal memorandums and analysis'
    },
    {
        value: 'brief',
        label: 'Legal Brief',
        icon: '⚖️',
        description: 'Court briefs and legal arguments'
    },
    {
        value: 'motion',
        label: 'Court Motion',
        icon: '🏛️',
        description: 'Court motions and procedural documents'
    }
]

const formats = [
    { value: 'pdf', label: 'PDF Document' },
    { value: 'docx', label: 'Word Document' },
    { value: 'html', label: 'HTML Document' }
]

const canGenerate = computed(() => {
    return selectedType.value && instructions.value.trim().length > 10
})

const generateDocument = async () => {
    generating.value = true

    try {
        const response = await axios.post('/api/chat/generate-content', {
            document_type: selectedType.value,
            instructions: instructions.value,
            conversation_id: props.conversation?.id,
            case_id: props.caseId,
            title: documentTitle.value
        })

        if (response.data.success) {
            generatedContent.value = response.data.content
        } else {
            throw new Error(response.data.message || 'Generation failed')
        }
    } catch (error) {
        console.error('Document generation failed:', error)
        alert('Failed to generate document. Please try again.')
    } finally {
        generating.value = false
    }
}

const downloadDocument = () => {
    const title = documentTitle.value || `${selectedType.value}_${new Date().toISOString().split('T')[0]}`

    if (selectedFormat.value === 'pdf') {
        downloadAsPDF(title)
    } else if (selectedFormat.value === 'docx') {
        downloadAsWord(title)
    } else if (selectedFormat.value === 'html') {
        downloadAsHTML(title)
    }
}

const downloadAsPDF = (title) => {
    const doc = new jsPDF()

    doc.setFontSize(16)
    doc.text(title, 20, 30)

    doc.setFontSize(11)
    const splitText = doc.splitTextToSize(generatedContent.value, 170)
    doc.text(splitText, 20, 50)

    doc.save(`${title}.pdf`)
}

const downloadAsWord = (title) => {
    const blob = new Blob([generatedContent.value], {
        type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    })

    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${title}.docx`
    a.click()
    URL.revokeObjectURL(url)
}

const downloadAsHTML = (title) => {
    const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>${title}</title>
        <style>
            body { font-family: 'Times New Roman', serif; margin: 1in; line-height: 1.6; }
            h1 { text-align: center; }
            p { text-align: justify; }
        </style>
    </head>
    <body>
        <h1>${title}</h1>
        <pre style="white-space: pre-wrap; font-family: 'Times New Roman', serif;">${generatedContent.value}</pre>
    </body>
    </html>`

    const blob = new Blob([htmlContent], { type: 'text/html' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${title}.html`
    a.click()
    URL.revokeObjectURL(url)
}

const saveToDatabase = async () => {
    try {
        const response = await axios.post('/api/documents/save-generated', {
            conversation_id: props.conversation?.id,
            legal_case_id: props.caseId,
            type: selectedType.value,
            title: documentTitle.value || `Generated ${selectedType.value}`,
            content: generatedContent.value,
            instructions: instructions.value,
            format: selectedFormat.value
        })

        if (response.data.success) {
            emit('generated', response.data.document)
            alert('Document record saved successfully!')
        }
    } catch (error) {
        console.error('Failed to save document record:', error)
        alert('Failed to save document record.')
    }
}
</script>
