<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\LegalCase;
use App\Models\Conversation;
use App\Models\FirmMember;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class DocumentGenerationService
{
    protected $pythonApiService;

    public function __construct(PythonApiService $pythonApiService)
    {
        $this->pythonApiService = $pythonApiService;
    }

    /**
     * Generate document content and return for client-side download
     */
    public function generateContent(array $data): array
    {
        $userId = auth()->id();
        $documentType = $data['document_type'];
        $instructions = $data['instructions'];

        Log::info('Starting document content generation', [
            'type' => $documentType,
            'user_id' => $userId,
            'instructions_length' => strlen($instructions),
        ]);

        try {
            // Build comprehensive prompt
            $prompt = $this->buildPromptForDocumentType($documentType, $instructions, $data);

            // Get AI-generated content
            $aiContent = $this->generateContentWithAI($documentType, $prompt, $data);

            // Format content for document type
            $formattedContent = $this->formatContentForType($aiContent, $documentType);

            // Generate title if not provided
            $title = $data['title'] ?? $this->generateDocumentTitle($documentType, $instructions);

            Log::info('Document content generation completed', [
                'type' => $documentType,
                'title' => $title,
                'content_length' => strlen($formattedContent),
            ]);

            return [
                'success' => true,
                'content' => $formattedContent,
                'title' => $title,
                'type' => $documentType,
                'generated_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Document content generation failed', [
                'error' => $e->getMessage(),
                'type' => $documentType,
                'user_id' => $userId,
            ]);

            throw new \Exception('Failed to generate document content: ' . $e->getMessage());
        }
    }

    /**
     * Save generated document record after client download
     */
    public function saveDocumentRecord(array $data): GeneratedDocument
    {
        $firmMember = FirmMember::where('user_id', auth()->id())->first();

        if (!$firmMember) {
            throw new \Exception('User not associated with any firm');
        }

        $document = GeneratedDocument::create([
            'user_id' => auth()->id(),
            'law_firm_id' => $firmMember->law_firm_id,
            'conversation_id' => $data['conversation_id'] ?? null,
            'legal_case_id' => $data['legal_case_id'] ?? null,
            'title' => $data['title'],
            'type' => $data['type'],
            'format' => $data['format'],
            'generation_prompt' => $data['instructions'],
            'content' => $data['content'],
            'downloaded_at' => now(),
            'download_count' => 1,
            'generation_metadata' => [
                'generated_at' => now(),
                'client_generated' => true,
                'content_length' => strlen($data['content']),
                'generation_method' => 'ai_assisted',
            ],
        ]);

        Log::info('Document record saved', [
            'document_id' => $document->id,
            'type' => $document->type,
            'title' => $document->title,
        ]);

        return $document;
    }

    /**
     * Generate content using AI service
     */
    protected function generateContentWithAI(string $type, string $prompt, array $context): string
    {
        try {
            // Try dedicated document generation endpoint first
            $response = $this->pythonApiService->generateDocumentContent([
                'document_type' => $type,
                'instructions' => $prompt,
                'context' => $context,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['content'] ?? $data['response'] ?? '';
            }

            // Fallback to chat endpoint
            $response = $this->pythonApiService->generateDocumentUsingChat([
                'document_type' => $type,
                'instructions' => $prompt,
                'context' => $context,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['response'] ?? $data['content'] ?? '';
            }

            throw new \Exception('AI service failed to generate content');

        } catch (\Exception $e) {
            Log::error('AI content generation failed', [
                'error' => $e->getMessage(),
                'type' => $type,
            ]);
            throw $e;
        }
    }

    /**
     * Build specialized prompts for different document types
     */
    protected function buildPromptForDocumentType(string $type, string $instructions, array $context): string
    {
        $basePrompt = "Generate a professional {$type} based on the following instructions:\n\n{$instructions}\n\n";

        $typeSpecificPrompts = [
            'contract' => $basePrompt . "Create a comprehensive employment contract including:\n" .
                "- Clear identification of parties\n" .
                "- Job title, duties, and responsibilities\n" .
                "- Compensation and benefits\n" .
                "- Work schedule and location\n" .
                "- Confidentiality and non-disclosure clauses\n" .
                "- Termination procedures and notice periods\n" .
                "- Governing law and dispute resolution\n" .
                "- Signature blocks\n\n" .
                "Ensure all clauses are legally sound and enforceable.",

            'agreement' => $basePrompt . "Structure the agreement with:\n" .
                "- Clear definitions section\n" .
                "- Scope of work and deliverables\n" .
                "- Payment terms and schedule\n" .
                "- Performance standards\n" .
                "- Intellectual property rights\n" .
                "- Limitation of liability\n" .
                "- Termination and renewal clauses\n" .
                "- Dispute resolution mechanisms",

            'letter' => $basePrompt . "Format as a professional legal letter with:\n" .
                "- Proper letterhead and date\n" .
                "- Recipient's name and address\n" .
                "- Professional salutation\n" .
                "- Clear statement of purpose\n" .
                "- Supporting facts and legal basis\n" .
                "- Specific action requested\n" .
                "- Professional closing\n" .
                "- Signature block",

            'memo' => $basePrompt . "Structure as a legal memorandum with:\n" .
                "- Header (TO, FROM, DATE, RE)\n" .
                "- ISSUE: Concise legal question\n" .
                "- BRIEF ANSWER: Short summary of conclusion\n" .
                "- FACTS: Relevant factual background\n" .
                "- DISCUSSION: Detailed legal analysis with citations\n" .
                "- CONCLUSION: Final recommendations",

            'brief' => $basePrompt . "Create a legal brief with:\n" .
                "- Proper case caption\n" .
                "- Table of contents and authorities\n" .
                "- Statement of issues\n" .
                "- Statement of the case\n" .
                "- Summary of argument\n" .
                "- Detailed argument with case citations\n" .
                "- Conclusion with specific relief requested",

            'motion' => $basePrompt . "Draft a court motion including:\n" .
                "- Proper caption and case information\n" .
                "- Introduction and relief sought\n" .
                "- Background facts\n" .
                "- Legal standard applicable\n" .
                "- Argument with supporting authorities\n" .
                "- Conclusion and prayer for relief\n" .
                "- Certificate of service",
        ];

        $prompt = $typeSpecificPrompts[$type] ?? $basePrompt;

        // Add context from conversation if available
        if (isset($context['conversation_id']) && $context['conversation_id']) {
            $conversationContext = $this->getConversationContext($context['conversation_id']);
            if ($conversationContext) {
                $prompt .= "\n\nContext from conversation:\n" . $conversationContext;
            }
        }

        // Add context from case if available
        if (isset($context['case_id']) && $context['case_id']) {
            $caseContext = $this->getCaseContext($context['case_id']);
            if ($caseContext) {
                $prompt .= "\n\nCase context:\n" . $caseContext;
            }
        }

        // Add jurisdiction if specified
        if (isset($context['jurisdiction'])) {
            $prompt .= "\n\nJurisdiction: {$context['jurisdiction']}";
        }

        // Add parties if specified
        if (isset($context['parties']) && is_array($context['parties'])) {
            $prompt .= "\n\nParties involved: " . implode(', ', $context['parties']);
        }

        $prompt .= "\n\nEnsure the document is professional, legally appropriate, and ready for use. Use proper legal formatting and language.";

        return $prompt;
    }

    /**
     * Format content based on document type
     */
    protected function formatContentForType(string $content, string $type): string
    {
        // Add document-specific formatting
        switch ($type) {
            case 'contract':
                return $this->formatContractContent($content);
            case 'letter':
                return $this->formatLetterContent($content);
            case 'memo':
                return $this->formatMemoContent($content);
            default:
                return $this->formatGeneralContent($content);
        }
    }

    /**
     * Format contract content
     */
    protected function formatContractContent(string $content): string
    {
        // Add contract-specific formatting
        $formatted = "EMPLOYMENT CONTRACT\n";
        $formatted .= str_repeat("=", 50) . "\n\n";
        $formatted .= $content;
        $formatted .= "\n\n" . str_repeat("-", 50);
        $formatted .= "\nThis contract is generated using AI assistance and should be reviewed by legal counsel before execution.";

        return $formatted;
    }

    /**
     * Format letter content
     */
    protected function formatLetterContent(string $content): string
    {
        $date = now()->format('F j, Y');
        $formatted = "[Your Law Firm Letterhead]\n\n";
        $formatted .= $date . "\n\n";
        $formatted .= $content;
        $formatted .= "\n\nSincerely,\n\n";
        $formatted .= "_________________________\n";
        $formatted .= "[Your Name]\n[Your Title]";

        return $formatted;
    }

    /**
     * Format memo content
     */
    protected function formatMemoContent(string $content): string
    {
        $date = now()->format('F j, Y');
        $formatted = "MEMORANDUM\n";
        $formatted .= str_repeat("=", 30) . "\n\n";
        $formatted .= "TO: [Recipient]\n";
        $formatted .= "FROM: [Your Name]\n";
        $formatted .= "DATE: {$date}\n";
        $formatted .= "RE: [Subject]\n\n";
        $formatted .= str_repeat("-", 30) . "\n\n";
        $formatted .= $content;

        return $formatted;
    }

    /**
     * Format general content
     */
    protected function formatGeneralContent(string $content): string
    {
        $formatted = strtoupper(auth()->user()->name ?? 'Legal Document') . "\n";
        $formatted .= now()->format('F j, Y') . "\n";
        $formatted .= str_repeat("=", 50) . "\n\n";
        $formatted .= $content;
        $formatted .= "\n\n" . str_repeat("-", 50);
        $formatted .= "\nDocument generated on " . now()->format('F j, Y \a\t g:i A');

        return $formatted;
    }

    /**
     * Get conversation context
     */
    protected function getConversationContext(int $conversationId): ?string
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) return null;

        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($messages->isEmpty()) return null;

        $context = "Recent conversation messages:\n";
        foreach ($messages->reverse() as $message) {
            $sender = $message->is_user ? 'User' : 'AI';
            $snippet = substr($message->content, 0, 150);
            $context .= "- {$sender}: {$snippet}...\n";
        }

        return $context;
    }

    /**
     * Get case context
     */
    protected function getCaseContext(int $caseId): ?string
    {
        $case = LegalCase::find($caseId);
        if (!$case) return null;

        $context = "Case Information:\n";
        $context .= "- Title: {$case->title}\n";

        if ($case->case_number) {
            $context .= "- Case Number: {$case->case_number}\n";
        }

        if ($case->jurisdiction) {
            $context .= "- Jurisdiction: {$case->jurisdiction}\n";
        }

        if ($case->category) {
            $context .= "- Category: {$case->category}\n";
        }

        if ($case->description) {
            $context .= "- Description: " . substr($case->description, 0, 200) . "...\n";
        }

        return $context;
    }

    /**
     * Generate document title from type and instructions
     */
    protected function generateDocumentTitle(string $type, string $instructions): string
    {
        $baseTitle = ucfirst($type);

        // Extract key phrases from instructions
        $words = str_word_count(strtolower($instructions), 1);
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an'];
        $keywords = array_filter($words, fn($word) => strlen($word) > 3 && !in_array($word, $stopWords));

        if (!empty($keywords)) {
            $topKeywords = array_slice(array_unique($keywords), 0, 2);
            return $baseTitle . ': ' . ucwords(implode(' ', $topKeywords));
        }

        return $baseTitle . ' - ' . now()->format('M j, Y');
    }

    /**
     * Generate document as PDF (server-side alternative)
     */
    public function generatePDF(string $content, string $title, string $type): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Times New Roman');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->contentToHTML($content, $title, $type);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $this->generateFilename($title, 'pdf');
        $directory = 'generated-documents/' . date('Y/m');
        $filePath = "{$directory}/{$filename}";

        Storage::disk('public')->put($filePath, $dompdf->output());

        return $filePath;
    }

    /**
     * Generate document as DOCX (server-side alternative)
     */
    public function generateDOCX(string $content, string $title, string $type): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 1440,    // 1 inch
            'marginRight' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
        ]);

        $this->addContentToWord($section, $content, $title);

        $filename = $this->generateFilename($title, 'docx');
        $directory = 'generated-documents/' . date('Y/m');

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filePath = storage_path("app/public/{$directory}/{$filename}");

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);

        return "{$directory}/{$filename}";
    }

    /**
     * Convert content to HTML for PDF generation
     */
    protected function contentToHTML(string $content, string $title, string $type): string
    {
        $css = '
        <style>
            body {
                font-family: "Times New Roman", serif;
                font-size: 12px;
                line-height: 1.6;
                margin: 1in;
            }
            h1 {
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 20px;
            }
            h2 {
                font-size: 14px;
                font-weight: bold;
                margin-top: 20px;
                margin-bottom: 10px;
            }
            p {
                margin-bottom: 10px;
                text-align: justify;
            }
            .signature-block {
                margin-top: 40px;
                page-break-inside: avoid;
            }
            .date-line {
                border-bottom: 1px solid #000;
                width: 200px;
                display: inline-block;
            }
        </style>';

        $html = '<!DOCTYPE html><html><head>' . $css . '</head><body>';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<div>' . nl2br(htmlspecialchars($content)) . '</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Add content to Word document
     */
    protected function addContentToWord($section, string $content, string $title): void
    {
        // Add title
        $section->addText(
            $title,
            ['bold' => true, 'size' => 16],
            ['alignment' => 'center', 'spaceAfter' => 240]
        );

        // Add content
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                $section->addTextBreak();
                continue;
            }

            $section->addText(
                $line,
                ['size' => 12],
                ['alignment' => 'both', 'spaceAfter' => 120]
            );
        }

        // Add signature block
        $section->addTextBreak(2);
        $section->addText('_________________________    Date: ___________');
        $section->addText('Signature');
    }

    /**
     * Generate filename
     */
    protected function generateFilename(string $title, string $extension): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "{$sanitized}_{$timestamp}.{$extension}";
    }
}
