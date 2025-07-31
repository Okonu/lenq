<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class PythonApiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.python_api.url', env('PYTHON_API_URL'));
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Analyze document with API document ID and prompt (for chat functionality)
     */
    public function analyzeDocumentById(string $apiDocumentId, string $prompt)
    {
        return Http::post($this->baseUrl . "/legal/chat/document/{$apiDocumentId}/analyze", [
            'prompt' => $prompt
        ]);
    }

    /**
     * Analyze general document by uploading file directly
     */
    public function analyzeGeneralDocument(UploadedFile $file)
    {
        return Http::attach(
            'file', file_get_contents($file->path()), $file->getClientOriginalName()
        )->post($this->baseUrl . '/legal/document-analysis/');
    }

    /**
     * Review contract document
     */
    public function reviewContract(UploadedFile $file)
    {
        return Http::attach(
            'file', file_get_contents($file->path()), $file->getClientOriginalName()
        )->post($this->baseUrl . '/legal/contract-review/');
    }

    /**
     * Search case law with document
     */
    public function searchCaseLaw(UploadedFile $file)
    {
        return Http::attach(
            'file', file_get_contents($file->path()), $file->getClientOriginalName()
        )->post($this->baseUrl . '/legal/case-law-search/');
    }

    /**
     * Analyze discovery document
     */
    public function analyzeDiscovery(UploadedFile $file)
    {
        return Http::attach(
            'file', file_get_contents($file->path()), $file->getClientOriginalName()
        )->post($this->baseUrl . '/legal/discovery-analysis/');
    }

    /**
     * Upload document for chat functionality
     */
    public function uploadChatDocument(UploadedFile $file, string $documentType = 'general')
    {
        return Http::attach(
            'file', file_get_contents($file->path()), $file->getClientOriginalName()
        )->post($this->baseUrl . '/legal/chat/upload', [
            'document_type' => $documentType
        ]);
    }

    /**
     * Get chat document details
     */
    public function getChatDocument(string $documentId)
    {
        return Http::get($this->baseUrl . "/legal/chat/document/{$documentId}");
    }

    /**
     * Fetch document content
     */
    public function fetchDocumentContent(string $apiDocumentId)
    {
        return Http::get($this->baseUrl . "/legal/chat/document/{$apiDocumentId}/content");
    }

    /**
     * Delete chat document
     */
    public function deleteChatDocument(string $documentId)
    {
        return Http::delete($this->baseUrl . "/legal/chat/document/{$documentId}");
    }

    /**
     * Send chat message with optional document context
     */
    public function chat($message, $context = [], $documentContent = null)
    {
        $data = [
            'message' => $message,
            'context' => $context
        ];

        if ($documentContent) {
            $document = [
                'title' => $documentContent['title'],
                'type' => $documentContent['type']
            ];

            if (!empty($documentContent['api_document_id'])) {
                $filename = str_replace('doc_', '', $documentContent['api_document_id']);

                $document['content'] = "uploaded_files/{$filename}";

                \Log::info('Setting document content path for Python API', [
                    'api_document_id' => $documentContent['api_document_id'],
                    'content_path' => $document['content']
                ]);
            }

            if (!empty($documentContent['analysis'])) {
                $document['analysis'] = $documentContent['analysis'];
            }

            $data['document'] = $document;

            $data['system_message'] = "The user is referencing a document titled '{$documentContent['title']}'. " .
                "Please analyze this document thoroughly based on the content.";
        }

        try {
            \Log::info('Sending chat request to Python API', [
                'message_length' => strlen($message),
                'context_count' => count($context),
                'has_document' => !is_null($documentContent),
                'document_title' => $documentContent['title'] ?? null,
                'document_has_content_path' => isset($data['document']['content']),
                'document_has_analysis' => isset($data['document']['analysis']),
                'has_system_message' => isset($data['system_message'])
            ]);

            \Log::debug('Full chat request payload:', ['data' => json_encode($data)]);

            $response = Http::post($this->baseUrl . '/legal/chat/', $data);

            \Log::info('Python API chat response', [
                'status' => $response->status(),
                'body_size' => strlen($response->body()),
                'has_response' => isset($response->json()['response'])
            ]);

            return $response;
        } catch (\Exception $e) {
            \Log::error('Exception in PythonApiService::chat', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }



// Add these methods to your existing PythonApiService class

    /**
     * Generate document content from instructions
     */
    public function generateDocumentContent(array $data)
    {
        $requestData = [
            'document_type' => $data['document_type'],
            'instructions' => $data['instructions'],
            'prompt' => $data['prompt'] ?? $data['instructions']
        ];

        try {
            \Log::info('Sending document generation request to Python API', [
                'document_type' => $data['document_type'],
                'instructions_length' => strlen($data['instructions'])
            ]);

            $response = Http::timeout(120) // Longer timeout for generation
            ->post($this->baseUrl . '/legal/generate-document/', $requestData);

            \Log::info('Python API document generation response', [
                'status' => $response->status(),
                'has_content' => isset($response->json()['content']) || isset($response->json()['response'])
            ]);

            return $response;
        } catch (\Exception $e) {
            \Log::error('Exception in PythonApiService::generateDocumentContent', [
                'message' => $e->getMessage(),
                'document_type' => $data['document_type']
            ]);

            throw $e;
        }
    }

    /**
     * Alternative method using the existing chat endpoint for document generation
     */
    public function generateDocumentUsingChat(array $data)
    {
        $prompt = $this->buildDocumentGenerationPrompt($data);

        $requestData = [
            'message' => $prompt,
            'context' => [
                'task' => 'document_generation',
                'document_type' => $data['document_type'],
                'format_requirements' => $this->getFormatRequirements($data['document_type'])
            ]
        ];

        try {
            \Log::info('Using chat endpoint for document generation', [
                'document_type' => $data['document_type'],
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::timeout(120)
                ->post($this->baseUrl . '/legal/chat/', $requestData);

            return $response;
        } catch (\Exception $e) {
            \Log::error('Exception in PythonApiService::generateDocumentUsingChat', [
                'message' => $e->getMessage(),
                'document_type' => $data['document_type']
            ]);

            throw $e;
        }
    }

    /**
     * Build specialized prompt for document generation
     */
    private function buildDocumentGenerationPrompt(array $data): string
    {
        $type = $data['document_type'];
        $instructions = $data['instructions'];

        $prompts = [
            'contract' => "Generate a professional employment contract based on these requirements:\n\n{$instructions}\n\nInclude standard legal clauses, clear terms for both parties, consideration, obligations, termination provisions, and governing law. Format as a complete, legally sound contract ready for review.",

            'agreement' => "Create a comprehensive service agreement with the following specifications:\n\n{$instructions}\n\nStructure with clear definitions, scope of work, deliverables, payment terms, dispute resolution, and termination clauses. Ensure professional formatting.",

            'letter' => "Draft a professional legal letter based on these requirements:\n\n{$instructions}\n\nFormat with proper legal letterhead structure, professional tone, clear purpose, and appropriate closing. Include all necessary legal formalities.",

            'memo' => "Prepare a legal memorandum addressing:\n\n{$instructions}\n\nStructure as: Issue, Brief Answer, Facts, Discussion (with legal analysis), and Conclusion. Use professional legal memo formatting.",

            'brief' => "Create a legal brief covering:\n\n{$instructions}\n\nInclude proper case citations, legal arguments, factual background, and conclusion with specific relief requested. Use court brief formatting standards.",

            'motion' => "Draft a court motion for:\n\n{$instructions}\n\nInclude proper caption, background facts, applicable legal standard, detailed argument with citations, and specific relief requested from the court."
        ];

        $basePrompt = $prompts[$type] ?? "Generate a professional {$type} document based on:\n\n{$instructions}\n\nEnsure proper legal formatting and professional language.";

        if (isset($data['conversation_context'])) {
            $basePrompt .= "\n\nContext from conversation: " . json_encode($data['conversation_context']);
        }

        if (isset($data['case_context'])) {
            $basePrompt .= "\n\nCase context: " . json_encode($data['case_context']);
        }

        return $basePrompt;
    }

    /**
     * Get format requirements for document types
     */
    private function getFormatRequirements(string $documentType): array
    {
        $requirements = [
            'contract' => [
                'sections' => ['parties', 'recitals', 'terms', 'signatures'],
                'clauses' => ['termination', 'governing_law', 'dispute_resolution'],
                'format' => 'formal_legal'
            ],
            'agreement' => [
                'sections' => ['parties', 'scope', 'deliverables', 'payment'],
                'format' => 'business_formal'
            ],
            'letter' => [
                'sections' => ['header', 'date', 'recipient', 'body', 'closing'],
                'format' => 'business_letter'
            ],
            'memo' => [
                'sections' => ['header', 'issue', 'brief_answer', 'facts', 'discussion', 'conclusion'],
                'format' => 'legal_memo'
            ],
            'brief' => [
                'sections' => ['caption', 'table_of_contents', 'statement', 'argument', 'conclusion'],
                'format' => 'court_brief',
                'citations' => 'required'
            ],
            'motion' => [
                'sections' => ['caption', 'introduction', 'background', 'argument', 'conclusion'],
                'format' => 'court_motion'
            ]
        ];

        return $requirements[$documentType] ?? ['format' => 'professional'];
    }

    /**
     * Analyze document and suggest improvements
     */
    public function analyzeAndSuggestImprovements(string $content, string $documentType)
    {
        $requestData = [
            'content' => $content,
            'document_type' => $documentType,
            'task' => 'improvement_analysis'
        ];

        try {
            $response = Http::post($this->baseUrl . '/legal/analyze-document/', $requestData);
            return $response;
        } catch (\Exception $e) {
            \Log::error('Exception in document improvement analysis', [
                'message' => $e->getMessage(),
                'document_type' => $documentType
            ]);
            throw $e;
        }
    }

    /**
     * Get document templates by type
     */
    public function getDocumentTemplates(string $documentType)
    {
        try {
            $response = Http::get($this->baseUrl . "/legal/templates/{$documentType}");
            return $response;
        } catch (\Exception $e) {
            \Log::error('Exception fetching document templates', [
                'message' => $e->getMessage(),
                'document_type' => $documentType
            ]);
            throw $e;
        }
    }
}
