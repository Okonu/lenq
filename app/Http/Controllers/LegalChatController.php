<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\FirmMember;
use App\Models\GeneratedDocument;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Models\Message;
use App\Services\PythonApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
                    'api_document_id' => $apiDocumentId,
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

    /**
     * Generate document content (client-side download)
     */
    public function generateContent(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:contract,agreement,letter,memo,brief,motion,general',
            'instructions' => 'required|string|min:10|max:2000',
            'conversation_id' => 'sometimes|exists:conversations,id',
            'case_id' => 'sometimes|exists:legal_cases,id',
            'title' => 'sometimes|string|max:255',
        ]);

        try {
            // Build prompt for AI
            $prompt = $this->buildDocumentPrompt(
                $request->document_type,
                $request->instructions,
                $request->conversation_id,
                $request->case_id
            );

            // Call your Python API for content generation
            $response = $this->pythonApiService->generateDocumentContent([
                'document_type' => $request->document_type,
                'instructions' => $request->instructions,
                'prompt' => $prompt,
                'conversation_context' => $this->getConversationContext($request->conversation_id),
                'case_context' => $this->getCaseContext($request->case_id),
            ]);

            if (!$response->successful()) {
                // Try alternative method using chat endpoint
                $response = $this->pythonApiService->generateDocumentUsingChat([
                    'document_type' => $request->document_type,
                    'instructions' => $request->instructions,
                    'prompt' => $prompt,
                ]);
            }

            if (!$response->successful()) {
                throw new \Exception('AI service failed to generate content');
            }

            $aiResponse = $response->json();

            return response()->json([
                'success' => true,
                'content' => $aiResponse['content'] ?? $aiResponse['response'],
                'title' => $request->title ?: $this->generateDocumentTitle($request->document_type, $request->instructions),
            ]);

        } catch (\Exception $e) {
            Log::error('Document content generation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'document_type' => $request->document_type,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save generated document record (after client download)
     */
    public function saveGeneratedDocument(Request $request)
    {
        $request->validate([
            'conversation_id' => 'sometimes|exists:conversations,id',
            'legal_case_id' => 'sometimes|exists:legal_cases,id',
            'type' => 'required|in:contract,agreement,letter,memo,brief,motion,general',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'instructions' => 'required|string',
            'format' => 'required|in:pdf,docx,html',
        ]);

        try {
            $firmMember = FirmMember::where('user_id', auth()->id())->first();

            if (!$firmMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not associated with any firm'
                ], 403);
            }

            $document = GeneratedDocument::create([
                'user_id' => auth()->id(),
                'law_firm_id' => $firmMember->law_firm_id,
                'conversation_id' => $request->conversation_id,
                'legal_case_id' => $request->legal_case_id,
                'title' => $request->title,
                'type' => $request->type,
                'format' => $request->format,
                'generation_prompt' => $request->instructions,
                'content' => $request->content,
                'downloaded_at' => now(),
                'download_count' => 1,
                'generation_metadata' => [
                    'generated_at' => now(),
                    'client_generated' => true,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            return response()->json([
                'success' => true,
                'document' => $document->load(['conversation', 'legalCase']),
                'message' => 'Document record saved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save generated document record', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save document record',
            ], 500);
        }
    }

    /**
     * Track document download/regeneration
     */
    public function trackDocumentDownload(GeneratedDocument $document, Request $request)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'format' => 'sometimes|in:pdf,docx,html',
        ]);

        try {
            // Update download tracking
            $document->increment('download_count');
            $document->update([
                'downloaded_at' => now(),
                'format' => $request->format ?? $document->format,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Download tracked',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track download',
            ], 500);
        }
    }

    /**
     * Get generated documents for conversation/case
     */
    public function getGeneratedDocuments(Request $request)
    {
        $request->validate([
            'conversation_id' => 'sometimes|exists:conversations,id',
            'legal_case_id' => 'sometimes|exists:legal_cases,id',
            'type' => 'sometimes|in:contract,agreement,letter,memo,brief,motion,general',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        try {
            $query = GeneratedDocument::where('user_id', auth()->id())
                ->with(['conversation:id,title', 'legalCase:id,title'])
                ->orderBy('created_at', 'desc');

            if ($request->conversation_id) {
                $query->where('conversation_id', $request->conversation_id);
            }

            if ($request->legal_case_id) {
                $query->where('legal_case_id', $request->legal_case_id);
            }

            if ($request->type) {
                $query->where('type', $request->type);
            }

            $documents = $query->limit($request->input('limit', 20))->get();

            return response()->json([
                'success' => true,
                'documents' => $documents,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get documents',
            ], 500);
        }
    }

    /**
     * Build document generation prompt
     */
    private function buildDocumentPrompt(string $type, string $instructions, ?int $conversationId = null, ?int $caseId = null): string
    {
        $prompt = "Generate a professional {$type} based on these instructions:\n\n{$instructions}\n\n";

        // Add type-specific requirements
        $typePrompts = [
            'contract' => "Include standard legal clauses, clear terms, parties, consideration, obligations, and termination provisions.",
            'agreement' => "Structure with clear definitions, scope of work, deliverables, payment terms, and dispute resolution.",
            'letter' => "Format as professional legal correspondence with proper letterhead structure and formal tone.",
            'memo' => "Structure as legal memorandum with: Issue, Brief Answer, Facts, Discussion, and Conclusion.",
            'brief' => "Create legal brief with case citations, legal arguments, and proper court formatting.",
            'motion' => "Draft court motion with proper caption, background facts, legal standard, argument, and relief requested.",
        ];

        $prompt .= $typePrompts[$type] ?? "Create a well-structured professional legal document.";

        // Add context from conversation/case if available
        if ($conversationId) {
            $conversation = Conversation::find($conversationId);
            if ($conversation) {
                $recentMessages = $conversation->messages()
                    ->latest()
                    ->limit(3)
                    ->get();

                if ($recentMessages->isNotEmpty()) {
                    $prompt .= "\n\nContext from recent conversation:\n";
                    foreach ($recentMessages as $message) {
                        $snippet = substr($message->content, 0, 100);
                        $sender = $message->is_user ? 'User' : 'Assistant';
                        $prompt .= "- {$sender}: {$snippet}...\n";
                    }
                }
            }
        }

        if ($caseId) {
            $case = LegalCase::find($caseId);
            if ($case) {
                $prompt .= "\n\nCase Context:\n";
                $prompt .= "- Case: {$case->title}\n";
                if ($case->jurisdiction) {
                    $prompt .= "- Jurisdiction: {$case->jurisdiction}\n";
                }
                if ($case->case_number) {
                    $prompt .= "- Case Number: {$case->case_number}\n";
                }
            }
        }

        $prompt .= "\n\nEnsure the document is professional, legally sound, and ready for use.";

        return $prompt;
    }

    /**
     * Get conversation context for document generation
     */
    private function getConversationContext(?int $conversationId): ?array
    {
        if (!$conversationId) return null;

        $conversation = Conversation::find($conversationId);
        if (!$conversation) return null;

        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'recent_messages' => $messages->map(function ($message) {
                return [
                    'is_user' => $message->is_user,
                    'content' => substr($message->content, 0, 200),
                    'created_at' => $message->created_at,
                ];
            })->toArray(),
            'message_count' => $conversation->messages()->count(),
        ];
    }

    /**
     * Get case context for document generation
     */
    private function getCaseContext(?int $caseId): ?array
    {
        if (!$caseId) return null;

        $case = LegalCase::find($caseId);
        if (!$case) return null;

        return [
            'title' => $case->title,
            'jurisdiction' => $case->jurisdiction,
            'case_number' => $case->case_number,
            'status' => $case->status,
            'category' => $case->category,
            'description' => substr($case->description, 0, 500),
        ];
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
