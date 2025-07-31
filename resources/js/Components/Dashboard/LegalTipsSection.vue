<script setup>
// No props needed for this component
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Legal Resources & Tips</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-5 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                    <div class="flex items-center mb-3">
                        <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">Document Organization</h4>
                    </div>
                    <p class="text-sm text-blue-700 dark:text-blue-200 mb-3">
                        Organize documents by case to maintain clear relationships between related files and conversations.
                    </p>
                    <ul class="text-xs text-blue-700 dark:text-blue-200 space-y-1 list-disc list-inside">
                        <li>Upload all relevant files to each case</li>
                        <li>Use clear naming conventions</li>
                        <li>Tag documents with appropriate types</li>
                    </ul>
                </div>

                <div class="p-5 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800">
                    <div class="flex items-center mb-3">
                        <svg class="w-6 h-6 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                        <h4 class="text-sm font-medium text-green-900 dark:text-green-100">Effective AI Questions</h4>
                    </div>
                    <p class="text-sm text-green-700 dark:text-green-200 mb-3">
                        For better AI responses, be specific and provide relevant context in your questions.
                    </p>
                    <ul class="text-xs text-green-700 dark:text-green-200 space-y-1 list-disc list-inside">
                        <li>Specify jurisdiction when relevant</li>
                        <li>Break complex questions into parts</li>
                        <li>Include key dates and figures</li>
                    </ul>
                </div>

                <div class="p-5 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800">
                    <div class="flex items-center mb-3">
                        <svg class="w-6 h-6 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <h4 class="text-sm font-medium text-purple-900 dark:text-purple-100">Document Analysis Tips</h4>
                    </div>
                    <p class="text-sm text-purple-700 dark:text-purple-200 mb-3">
                        Get more from document analysis by using these strategies:
                    </p>
                    <ul class="text-xs text-purple-700 dark:text-purple-200 space-y-1 list-disc list-inside">
                        <li>Upload clear, searchable PDFs when possible</li>
                        <li>Ask specific questions about uploaded documents</li>
                        <li>Use document analysis to compare similar files</li>
                    </ul>
                </div>
            </div>

            <div class="mt-5 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-medium text-gray-900 dark:text-white">Remember:</span> LegalAssist provides general legal information only, not legal advice.
                    <br class="hidden md:block" />Always consult with a qualified attorney for your specific situation.
                </p>
            </div>
        </div>
    </div>
</template>
