<?php

namespace App\Services;

use App\Models\KnowledgeBaseEntry;
use App\Models\LegalCase;
use App\Models\Conversation;
use App\Models\LegalDocument;
use App\Models\FirmMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KnowledgeBaseService
{
    /**
     * Share conversation with firm knowledge base
     */
    public function shareConversation(Conversation $conversation, array $metadata = []): KnowledgeBaseEntry
    {
        $entry = KnowledgeBaseEntry::create([
            'law_firm_id' => $this->getCurrentFirmId(),
            'user_id' => auth()->id(),
            'type' => 'conversation',
            'source_id' => $conversation->id,
            'source_type' => Conversation::class,
            'title' => $conversation->title,
            'content' => $this->extractConversationContent($conversation),
            'tags' => $this->generateTagsFromConversation($conversation),
            'category' => $metadata['category'] ?? $this->detectCategory($conversation),
            'is_public' => $metadata['is_public'] ?? true,
            'metadata' => array_merge($metadata, [
                'message_count' => $conversation->messages()->count(),
                'case_id' => $conversation->legal_case_id,
                'created_by' => auth()->user()->name,
                'shared_at' => now(),
            ]),
        ]);

        Log::info('Conversation shared to knowledge base', [
            'conversation_id' => $conversation->id,
            'entry_id' => $entry->id,
            'firm_id' => $this->getCurrentFirmId(),
        ]);

        return $entry;
    }

    /**
     * Share document analysis with firm
     */
    public function shareDocumentAnalysis(LegalDocument $document, array $metadata = []): KnowledgeBaseEntry
    {
        return KnowledgeBaseEntry::create([
            'law_firm_id' => $this->getCurrentFirmId(),
            'user_id' => auth()->id(),
            'type' => 'document_analysis',
            'source_id' => $document->id,
            'source_type' => LegalDocument::class,
            'title' => "Analysis: {$document->title}",
            'content' => $this->extractDocumentAnalysis($document),
            'tags' => $this->generateTagsFromDocument($document),
            'category' => $document->type,
            'is_public' => $metadata['is_public'] ?? true,
            'metadata' => array_merge($metadata, [
                'document_type' => $document->type,
                'case_id' => $document->legal_case_id,
                'file_size' => $document->file_size,
                'analysis_score' => $document->analysis['score'] ?? null,
            ]),
        ]);
    }

    /**
     * Share case insights and strategies
     */
    public function shareCaseInsights(LegalCase $case, array $insights, array $metadata = []): KnowledgeBaseEntry
    {
        return KnowledgeBaseEntry::create([
            'law_firm_id' => $this->getCurrentFirmId(),
            'user_id' => auth()->id(),
            'type' => 'case_insights',
            'source_id' => $case->id,
            'source_type' => LegalCase::class,
            'title' => "Case Insights: {$case->title}",
            'content' => $this->formatCaseInsights($insights),
            'tags' => $this->generateTagsFromCase($case),
            'category' => $case->category ?? 'general',
            'is_public' => $metadata['is_public'] ?? false, // Case insights more sensitive
            'metadata' => array_merge($metadata, [
                'case_status' => $case->status,
                'jurisdiction' => $case->jurisdiction,
                'case_number' => $case->case_number,
                'insights_type' => $metadata['insights_type'] ?? 'strategy',
            ]),
        ]);
    }

    /**
     * Create legal precedent entry
     */
    public function createPrecedent(array $data): KnowledgeBaseEntry
    {
        return KnowledgeBaseEntry::create([
            'law_firm_id' => $this->getCurrentFirmId(),
            'user_id' => auth()->id(),
            'type' => 'precedent',
            'title' => $data['title'],
            'content' => $this->formatPrecedentContent($data),
            'tags' => $data['tags'] ?? [],
            'category' => $data['category'] ?? 'case_law',
            'is_public' => $data['is_public'] ?? true,
            'metadata' => [
                'citation' => $data['citation'] ?? null,
                'court' => $data['court'] ?? null,
                'jurisdiction' => $data['jurisdiction'] ?? null,
                'date_decided' => $data['date_decided'] ?? null,
                'legal_issues' => $data['legal_issues'] ?? [],
                'relevance_score' => $data['relevance_score'] ?? null,
            ],
        ]);
    }

    /**
     * Search knowledge base with advanced filters
     */
    public function search(array $criteria): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = KnowledgeBaseEntry::where('law_firm_id', $this->getCurrentFirmId());

        // Apply search filters
        if (!empty($criteria['query'])) {
            $searchTerm = $criteria['query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('content', 'LIKE', "%{$searchTerm}%")
                    ->orWhereJsonContains('tags', $searchTerm);
            });
        }

        if (!empty($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        if (!empty($criteria['category'])) {
            $query->where('category', $criteria['category']);
        }

        if (!empty($criteria['tags'])) {
            foreach ($criteria['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (!empty($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }

        // Apply access controls
        $query->where(function ($q) {
            $q->where('is_public', true)
                ->orWhere('user_id', auth()->id());
        });

        return $query->with(['user:id,name', 'source'])
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 20);
    }

    /**
     * Get related knowledge base entries
     */
    public function getRelatedEntries(KnowledgeBaseEntry $entry, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $tags = $entry->tags ?? [];

        if (empty($tags)) {
            return collect();
        }

        return KnowledgeBaseEntry::where('law_firm_id', $this->getCurrentFirmId())
            ->where('id', '!=', $entry->id)
            ->where('is_public', true)
            ->where(function ($query) use ($tags) {
                foreach ($tags as $tag) {
                    $query->orWhereJsonContains('tags', $tag);
                }
            })
            ->with(['user:id,name'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get knowledge base statistics for firm
     */
    public function getStatistics(): array
    {
        $firmId = $this->getCurrentFirmId();

        return [
            'total_entries' => KnowledgeBaseEntry::where('law_firm_id', $firmId)->count(),
            'by_type' => KnowledgeBaseEntry::where('law_firm_id', $firmId)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
            'recent_activity' => KnowledgeBaseEntry::where('law_firm_id', $firmId)
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'top_contributors' => KnowledgeBaseEntry::where('law_firm_id', $firmId)
                ->select('user_id', DB::raw('count(*) as contributions'))
                ->with('user:id,name')
                ->groupBy('user_id')
                ->orderByDesc('contributions')
                ->limit(5)
                ->get(),
            'popular_tags' => $this->getPopularTags($firmId),
        ];
    }

    /**
     * Extract and format conversation content
     */
    protected function extractConversationContent(Conversation $conversation): string
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        $content = "## Conversation Summary\n\n";

        if ($conversation->legal_case_id) {
            $content .= "**Case:** {$conversation->legalCase->title}\n\n";
        }

        $content .= "**Messages:** {$messages->count()}\n\n";
        $content .= "## Key Exchanges\n\n";

        foreach ($messages as $message) {
            $sender = $message->is_user ? 'User' : 'AI Assistant';
            $content .= "**{$sender}:** " . substr($message->content, 0, 200) . "...\n\n";
        }

        return $content;
    }

    /**
     * Extract document analysis content
     */
    protected function extractDocumentAnalysis(LegalDocument $document): string
    {
        $content = "## Document Analysis Summary\n\n";
        $content .= "**Document:** {$document->title}\n";
        $content .= "**Type:** {$document->type}\n";
        $content .= "**Size:** " . $this->formatFileSize($document->file_size) . "\n\n";

        if ($document->analysis) {
            $analysis = $document->analysis;

            if (isset($analysis['summary'])) {
                $content .= "## Summary\n{$analysis['summary']}\n\n";
            }

            if (isset($analysis['key_points'])) {
                $content .= "## Key Points\n";
                foreach ($analysis['key_points'] as $point) {
                    $content .= "- {$point}\n";
                }
                $content .= "\n";
            }

            if (isset($analysis['risks'])) {
                $content .= "## Identified Risks\n";
                foreach ($analysis['risks'] as $risk) {
                    $content .= "- {$risk}\n";
                }
                $content .= "\n";
            }

            if (isset($analysis['recommendations'])) {
                $content .= "## Recommendations\n";
                foreach ($analysis['recommendations'] as $rec) {
                    $content .= "- {$rec}\n";
                }
            }
        }

        return $content;
    }

    /**
     * Format case insights
     */
    protected function formatCaseInsights(array $insights): string
    {
        $content = "## Case Insights\n\n";

        foreach ($insights as $category => $items) {
            $content .= "### " . ucfirst(str_replace('_', ' ', $category)) . "\n";

            if (is_array($items)) {
                foreach ($items as $item) {
                    $content .= "- {$item}\n";
                }
            } else {
                $content .= "{$items}\n";
            }

            $content .= "\n";
        }

        return $content;
    }

    /**
     * Generate tags from conversation
     */
    protected function generateTagsFromConversation(Conversation $conversation): array
    {
        $tags = [];

        // Add case-related tags
        if ($conversation->legal_case_id) {
            $case = $conversation->legalCase;
            $tags[] = $case->category ?? 'general';
            if ($case->jurisdiction) {
                $tags[] = strtolower($case->jurisdiction);
            }
        }

        // Extract keywords from conversation content
        $messages = $conversation->messages()->limit(5)->get();
        foreach ($messages as $message) {
            $keywords = $this->extractKeywords($message->content);
            $tags = array_merge($tags, $keywords);
        }

        return array_unique(array_filter($tags));
    }

    /**
     * Generate tags from document
     */
    protected function generateTagsFromDocument(LegalDocument $document): array
    {
        $tags = [$document->type];

        if ($document->legal_case_id) {
            $case = $document->legalCase;
            if ($case->category) {
                $tags[] = $case->category;
            }
        }

        // Extract keywords from analysis
        if ($document->analysis && isset($document->analysis['keywords'])) {
            $tags = array_merge($tags, $document->analysis['keywords']);
        }

        return array_unique($tags);
    }

    /**
     * Simple keyword extraction
     */
    protected function extractKeywords(string $content, int $limit = 5): array
    {
        // Basic keyword extraction - could be enhanced with NLP
        $words = str_word_count(strtolower($content), 1);
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'will', 'would', 'could', 'should'];

        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        $frequency = array_count_values($keywords);
        arsort($frequency);

        return array_slice(array_keys($frequency), 0, $limit);
    }

    /**
     * Get current user's firm ID
     */
    protected function getCurrentFirmId(): int
    {
        $firmMember = FirmMember::where('user_id', auth()->id())->first();

        if (!$firmMember) {
            throw new \Exception('User is not associated with any firm');
        }

        return $firmMember->law_firm_id;
    }

    /**
     * Format file size for display
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Get popular tags for firm
     */
    protected function getPopularTags(int $firmId, int $limit = 10): array
    {
        $entries = KnowledgeBaseEntry::where('law_firm_id', $firmId)
            ->whereNotNull('tags')
            ->pluck('tags');

        $allTags = [];
        foreach ($entries as $tags) {
            if (is_array($tags)) {
                $allTags = array_merge($allTags, $tags);
            }
        }

        $tagCounts = array_count_values($allTags);
        arsort($tagCounts);

        return array_slice($tagCounts, 0, $limit, true);
    }
}
