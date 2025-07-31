<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseEntry;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    protected $knowledgeBaseService;

    public function __construct(KnowledgeBaseService $knowledgeBaseService)
    {
        $this->knowledgeBaseService = $knowledgeBaseService;
    }

    /**
     * Display knowledge base entries
     */
    public function index(Request $request)
    {
        $request->validate([
            'query' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:conversation,document_analysis,case_insights,precedent',
            'category' => 'sometimes|string|max:50',
            'tags' => 'sometimes|array',
            'per_page' => 'sometimes|integer|min:5|max:50',
        ]);

        $entries = $this->knowledgeBaseService->search($request->all());
        $stats = $this->knowledgeBaseService->getStatistics();

        return Inertia::render('KnowledgeBase/Index', [
            'entries' => $entries,
            'stats' => $stats,
            'filters' => $request->only(['query', 'type', 'category', 'tags']),
        ]);
    }

    /**
     * Show specific knowledge base entry
     */
    public function show(KnowledgeBaseEntry $entry)
    {
        $entry->load(['user:id,name', 'source']);
        $related = $this->knowledgeBaseService->getRelatedEntries($entry);

        return Inertia::render('KnowledgeBase/Show', [
            'entry' => $entry,
            'related' => $related,
        ]);
    }

    /**
     * Search knowledge base via API
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:conversation,document_analysis,case_insights,precedent',
            'category' => 'sometimes|string|max:50',
            'tags' => 'sometimes|array',
            'per_page' => 'sometimes|integer|min:5|max:50',
        ]);

        try {
            $results = $this->knowledgeBaseService->search($request->all());

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get knowledge base statistics
     */
    public function getStatistics()
    {
        try {
            $stats = $this->knowledgeBaseService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get related entries
     */
    public function getRelated(KnowledgeBaseEntry $entry)
    {
        try {
            $related = $this->knowledgeBaseService->getRelatedEntries($entry);

            return response()->json([
                'success' => true,
                'data' => $related,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get related entries: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create legal precedent entry
     */
    public function createPrecedent(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'citation' => 'sometimes|string|max:255',
            'court' => 'sometimes|string|max:100',
            'jurisdiction' => 'sometimes|string|max:100',
            'date_decided' => 'sometimes|date',
            'legal_issues' => 'sometimes|array',
            'legal_issues.*' => 'string|max:255',
            'summary' => 'required|string|max:2000',
            'key_holdings' => 'sometimes|array',
            'key_holdings.*' => 'string|max:500',
            'relevance_notes' => 'sometimes|string|max:1000',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:50',
            'is_public' => 'sometimes|boolean',
        ]);

        try {
            $precedentData = array_merge($request->all(), [
                'category' => 'case_law',
                'content' => $this->formatPrecedentContent($request->all()),
            ]);

            $entry = $this->knowledgeBaseService->createPrecedent($precedentData);

            return response()->json([
                'success' => true,
                'data' => $entry,
                'message' => 'Legal precedent created successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create precedent: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update knowledge base entry
     */
    public function update(Request $request, KnowledgeBaseEntry $entry)
    {
        if ($entry->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:50',
            'category' => 'sometimes|string|max:50',
            'is_public' => 'sometimes|boolean',
        ]);

        try {
            $entry->update($request->only(['title', 'tags', 'category', 'is_public']));

            return response()->json([
                'success' => true,
                'data' => $entry,
                'message' => 'Entry updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update entry: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete knowledge base entry
     */
    public function destroy(KnowledgeBaseEntry $entry)
    {
        if ($entry->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $entry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entry deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete entry: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Format precedent content
     */
    private function formatPrecedentContent(array $data): string
    {
        $content = "## Case Summary\n\n";
        $content .= $data['summary'] . "\n\n";

        if (!empty($data['citation'])) {
            $content .= "**Citation:** {$data['citation']}\n\n";
        }

        if (!empty($data['court'])) {
            $content .= "**Court:** {$data['court']}\n\n";
        }

        if (!empty($data['date_decided'])) {
            $content .= "**Date Decided:** {$data['date_decided']}\n\n";
        }

        if (!empty($data['key_holdings'])) {
            $content .= "## Key Holdings\n\n";
            foreach ($data['key_holdings'] as $holding) {
                $content .= "- {$holding}\n";
            }
            $content .= "\n";
        }

        if (!empty($data['legal_issues'])) {
            $content .= "## Legal Issues\n\n";
            foreach ($data['legal_issues'] as $issue) {
                $content .= "- {$issue}\n";
            }
            $content .= "\n";
        }

        if (!empty($data['relevance_notes'])) {
            $content .= "## Relevance Notes\n\n";
            $content .= $data['relevance_notes'] . "\n\n";
        }

        return $content;
    }
}
