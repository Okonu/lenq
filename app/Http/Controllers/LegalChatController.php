<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Models\Message;
use App\Services\PythonApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LegalChatController extends Controller
{
    protected $pythonApiService;

    public function __construct(PythonApiService $pythonApiService)
    {
        $this->pythonApiService = $pythonApiService;
    }

    public function index()
    {
        $conversations = Conversation::where('user_id', auth()->id())
            ->with('legalCase:id,title')
            ->latest()
            ->get();

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function create()
    {
        $cases = LegalCase::where('user_id', auth()->id())
            ->select('id', 'title')
            ->get();

        return Inertia::render('Chat/Create', [
            'cases' => $cases,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required_without:auto_generate_title|string|max:255|nullable',
            'legal_case_id' => 'nullable|exists:legal_cases,id',
            'auto_generate_title' => 'boolean',
        ]);

        if ($request->legal_case_id) {
            $case = LegalCase::where('id', $request->legal_case_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        }

        $title = $request->title;
        if ($request->auto_generate_title) {
            $title = 'New Conversation';
        }

        $conversation = Conversation::create([
            'user_id' => auth()->id(),
            'legal_case_id' => $request->legal_case_id,
            'title' => $title,
            'auto_generate_title' => $request->auto_generate_title ?? false,
        ]);

        return redirect()->route('chat.show', $conversation->id);
    }

    public function show(Conversation $conversation)
    {
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        if ($conversation->legal_case_id) {
            $conversation->load('legalCase:id,title');
        }

        $messages = $conversation->messages()
            ->with('document:id,title,type')
            ->orderBy('created_at')
            ->get();

        $documents = LegalDocument::where('user_id', auth()->id())
            ->select('id', 'title', 'type', 'created_at')
            ->latest()
            ->get();

        return Inertia::render('Chat/Show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'documents' => $documents,
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
            'document_id' => 'nullable|exists:legal_documents,id',
        ]);

        $document = null;
        $documentExists = false;
        if ($request->document_id) {
            $document = LegalDocument::where('id', $request->document_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            \Log::info('Document found for message', [
                'document_id' => $document->id,
                'api_document_id' => $document->api_document_id,
                'title' => $document->title
            ]);

            // Verify the document is accessible in the Python API's file storage
            $pythonApiPath = null;
            if (!empty($document->api_document_id)) {
                // Extract the filename from api_document_id (format: doc_filename.pdf)
                $filename = str_replace('doc_', '', $document->api_document_id);
                $pythonApiPath = "uploaded_files/{$filename}";

                \Log::info('Expected Python API document path', [
                    'api_document_id' => $document->api_document_id,
                    'python_api_path' => $pythonApiPath
                ]);
            }
        }

        $isFirstMessage = $conversation->messages()->count() === 0;

        try {
            $userMessage = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $request->message,
                'is_user' => true,
                'document_id' => $request->document_id,
                'is_first_message' => $isFirstMessage,
            ]);

            $contextMessages = $conversation->messages()
                ->orderBy('created_at')
                ->take(10)
                ->get();

            $context = $contextMessages->map(function ($message) {
                return [
                    'content' => $message->content,
                    'is_user' => $message->is_user,
                    'document_id' => $message->document_id,
                ];
            })->toArray();

            // Prepare document content if needed
            $documentContent = null;
            if ($document) {
                $documentContent = [
                    'id' => $document->id,
                    'title' => $document->title,
                    'type' => $document->type,
                    'analysis' => $document->analysis,
                    'api_document_id' => $document->api_document_id,
                ];

                \Log::info('Including document in chat request', [
                    'document_id' => $document->id,
                    'has_api_document_id' => !empty($document->api_document_id)
                ]);
            }

            // Use the pythonApiService to send the chat request
            $response = $this->pythonApiService->chat(
                $request->message,
                $context,
                $documentContent
            );

            \Log::info('Python API response received', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'response_length' => strlen($response->json('response', ''))
            ]);

            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $response->json('response', 'Sorry, I was unable to process your request.'),
                'is_user' => false,
            ]);

            if ($isFirstMessage && $conversation->auto_generate_title) {
                $suggestedTitle = $response->json('suggested_title') ?? $this->generateTitle($request->message, $response->json('response'));

                $conversation->update([
                    'title' => $suggestedTitle,
                    'auto_generate_title' => false,
                ]);

                $conversation->title = $suggestedTitle;
            }

            return [
                'userMessage' => $userMessage,
                'aiMessage' => $aiMessage,
                'conversation' => $conversation,
            ];
        } catch (\Exception $e) {
            \Log::error('Exception in sendMessage', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while processing your message',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf,doc,docx|max:10240',
            'type' => 'required|in:general,contract,case,discovery',
        ]);

        try {
            \Log::info('Uploading document to Python API', [
                'filename' => $request->file('file')->getClientOriginalName(),
                'type' => $request->type
            ]);

            $response = $this->pythonApiService->uploadChatDocument(
                $request->file('file'),
                $request->type
            );

            \Log::info('Python API response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful() && !$response->json('error')) {
                $apiResponse = $response->json();

                // Extract the document ID from the API response
                $apiDocumentId = null;
                if (isset($apiResponse['document']) && isset($apiResponse['document']['id'])) {
                    $apiDocumentId = $apiResponse['document']['id'];
                    \Log::info('Successfully extracted API document ID', ['api_document_id' => $apiDocumentId]);
                } else {
                    \Log::warning('API response does not contain document ID', ['response' => $apiResponse]);
                }

                $path = $request->file('file')->store('documents', 'public');

                $document = LegalDocument::create([
                    'user_id' => auth()->id(),
                    'legal_case_id' => $request->legal_case_id,
                    'title' => $request->file('file')->getClientOriginalName(),
                    'file_path' => $path,
                    'type' => $request->type,
                    'analysis' => null,
                    'api_document_id' => $apiDocumentId,  // Save the API document ID
                ]);

                \Log::info('Document created in database', [
                    'document_id' => $document->id,
                    'api_document_id' => $document->api_document_id
                ]);

                return [
                    'success' => true,
                    'document' => $document,
                    'status' => $apiResponse['status'] ?? 'processing',
                ];
            }

            \Log::error('Failed to upload document to Python API', [
                'error' => $response->json('error') ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'error' => $response->json('error') ?? 'Failed to upload document',
            ];
        } catch (\Exception $e) {
            \Log::error('Exception when uploading document', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get document status and analysis.
     */
    public function getDocumentStatus(Request $request, LegalDocument $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        if ($document->analysis) {
            return [
                'document' => $document,
                'status' => 'completed',
            ];
        }

        if ($document->api_document_id) {
            $response = $this->pythonApiService->getChatDocument($document->api_document_id);

            if ($response->successful()) {
                $apiResponse = $response->json();

                if (isset($apiResponse['status']) && $apiResponse['status'] === 'completed' && isset($apiResponse['analysis'])) {
                    $document->update([
                        'analysis' => $apiResponse['analysis'],
                    ]);

                    return [
                        'document' => $document->fresh(),
                        'status' => 'completed',
                    ];
                }

                return [
                    'document' => $document,
                    'status' => $apiResponse['status'] ?? 'processing',
                ];
            }
        }

        return [
            'document' => $document,
            'status' => 'processing',
        ];
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(Request $request, LegalDocument $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        if ($document->api_document_id) {
            $this->pythonApiService->deleteChatDocument($document->api_document_id);
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return [
            'success' => true,
        ];
    }

    private function generateTitle($userMessage, $aiResponse)
    {
        // Simple fallback title generation if the API doesn't provide one
        // In a real implementation, this would use more sophisticated NLP

        // Extract first few words from user message (up to 3 words)
        $words = explode(' ', $userMessage);
        $titleWords = array_slice($words, 0, 3);
        $title = implode(' ', $titleWords);

        if (count($words) > 6) {
            $title .= '...';
        }

        if (strlen($title) > 50) {
            $title = substr($title, 0, 47) . '...';
        }

        return $title;
    }

    public function apiCreateConversation(Request $request)
    {
        $request->validate([
            'title' => 'required_without:auto_generate_title|string|max:255|nullable',
            'legal_case_id' => 'nullable|exists:legal_cases,id',
            'auto_generate_title' => 'boolean',
        ]);

        if ($request->legal_case_id) {
            $case = LegalCase::where('id', $request->legal_case_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        }

        $title = $request->title;
        if ($request->auto_generate_title) {
            $title = 'New Conversation';
        }

        $conversation = Conversation::create([
            'user_id' => auth()->id(),
            'legal_case_id' => $request->legal_case_id,
            'title' => $title,
            'auto_generate_title' => $request->auto_generate_title ?? false,
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }

    public function update(Request $request, Conversation $conversation)
    {
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'legal_case_id' => 'nullable|exists:legal_cases,id',
        ]);

        if ($request->legal_case_id) {
            $case = LegalCase::where('id', $request->legal_case_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        }

        $conversation->update([
            'title' => $request->title,
            'legal_case_id' => $request->legal_case_id,
            'auto_generate_title' => false,
        ]);

        return redirect()->route('chat.show', $conversation->id);
    }

    public function destroy(Conversation $conversation)
    {
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $conversation->delete();

        return redirect()->route('chat.index');
    }
}
