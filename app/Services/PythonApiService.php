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
}
