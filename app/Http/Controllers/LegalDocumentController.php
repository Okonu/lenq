<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Services\PythonApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LegalDocumentController extends Controller
{
    protected $pythonApiService;

    public function __construct(PythonApiService $pythonApiService)
    {
        $this->pythonApiService = $pythonApiService;
    }

    /**
     * Display a listing of the legal documents.
     */
    public function index()
    {
        $documents = LegalDocument::where('user_id', auth()->id())
            ->with('legalCase:id,title')
            ->latest()
            ->get();

        $cases = LegalCase::where('user_id', auth()->id())
            ->select('id', 'title')
            ->get();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'cases' => $cases,
        ]);
    }

    /**
     * Show the form for creating a new legal document.
     */
    public function create()
    {
        $cases = LegalCase::where('user_id', auth()->id())
            ->select('id', 'title')
            ->get();

        return Inertia::render('Documents/Upload', [
            'cases' => $cases,
        ]);
    }

    /**
     * Store a newly created legal document in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf,doc,docx|max:10240',
            'type' => 'required|in:general,contract,case,discovery',
            'legal_case_id' => 'nullable|exists:legal_cases,id',
        ]);

        if ($request->legal_case_id) {
            $case = LegalCase::where('id', $request->legal_case_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        }

        $path = $request->file('file')->store('documents', 'public');

        \Log::info('Calling Python API for document analysis', [
            'type' => $request->type,
            'filename' => $request->file('file')->getClientOriginalName(),
            'api_base_url' => $this->pythonApiService->getBaseUrl()
        ]);

        try {
            $response = match($request->type) {
                'contract' => $this->pythonApiService->reviewContract($request->file('file')),
                'case' => $this->pythonApiService->searchCaseLaw($request->file('file')),
                'discovery' => $this->pythonApiService->analyzeDiscovery($request->file('file')),
                default => $this->pythonApiService->analyzeGeneralDocument($request->file('file')),
            };

            \Log::info('Python API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_preview' => substr($response->body(), 0, 500),
                'has_json' => $response->json() !== null
            ]);

            if (!$response->successful()) {
                \Log::error('Python API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return back()->withErrors([
                    'file' => 'Failed to analyze document. Please try again.'
                ])->withInput();
            }

            $analysisData = $response->json();

            if (isset($analysisData['error'])) {
                \Log::error('Python API returned error', [
                    'error' => $analysisData['error']
                ]);

                return back()->withErrors([
                    'file' => 'Document analysis failed: ' . $analysisData['error']
                ])->withInput();
            }

            $document = LegalDocument::create([
                'user_id' => auth()->id(),
                'legal_case_id' => $request->legal_case_id,
                'title' => $request->file('file')->getClientOriginalName(),
                'file_path' => $path,
                'type' => $request->type,
                'analysis' => $analysisData,
            ]);

            \Log::info('Document created successfully', [
                'document_id' => $document->id,
                'has_analysis' => !empty($analysisData)
            ]);

            return redirect()->route('documents.show', $document->id)
                ->with('success', 'Document uploaded and analyzed successfully!');

        } catch (\Exception $e) {
            \Log::error('Exception during document analysis', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return back()->withErrors([
                'file' => 'An error occurred while analyzing the document: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * Display the specified legal document.
     */
    public function show(LegalDocument $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        if ($document->legal_case_id) {
            $document->load('legalCase:id,title');
        }

        return Inertia::render('Documents/Show', [
            'document' => $document,
            'documentUrl' => asset('storage/' . $document->file_path),
        ]);
    }

    /**
     * Show the form for editing the specified legal document.
     */
    public function edit(LegalDocument $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        $cases = LegalCase::where('user_id', auth()->id())
            ->select('id', 'title')
            ->get();

        return Inertia::render('Documents/Edit', [
            'document' => $document,
            'cases' => $cases,
        ]);
    }

    /**
     * Update the specified legal document in storage.
     */
    public function update(Request $request, LegalDocument $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:general,contract,case,discovery',
            'legal_case_id' => 'nullable|exists:legal_cases,id',
        ]);

        if ($request->legal_case_id) {
            $case = LegalCase::where('id', $request->legal_case_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        }

        $document->update([
            'title' => $request->title,
            'type' => $request->type,
            'legal_case_id' => $request->legal_case_id,
        ]);

        return redirect()->route('documents.show', $document->id)
            ->with('success', 'Document updated successfully!');
    }

    /**
     * Remove the specified legal document from storage.
     */
    public function destroy(LegalDocument $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully!');
    }

    /**
     * Re-analyze an existing document
     */
    public function reanalyze(LegalDocument $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            if (!Storage::disk('public')->exists($document->file_path)) {
                return back()->withErrors([
                    'file' => 'Original file not found. Cannot re-analyze.'
                ]);
            }

            $filePath = Storage::disk('public')->path($document->file_path);

            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $filePath,
                $document->title,
                mime_content_type($filePath),
                null,
                true
            );

            \Log::info('Re-analyzing document', [
                'document_id' => $document->id,
                'type' => $document->type,
                'title' => $document->title
            ]);

            $response = match($document->type) {
                'contract' => $this->pythonApiService->reviewContract($uploadedFile),
                'case' => $this->pythonApiService->searchCaseLaw($uploadedFile),
                'discovery' => $this->pythonApiService->analyzeDiscovery($uploadedFile),
                default => $this->pythonApiService->analyzeGeneralDocument($uploadedFile),
            };

            if (!$response->successful()) {
                \Log::error('Re-analysis failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return back()->withErrors([
                    'file' => 'Failed to re-analyze document. Please try again.'
                ]);
            }

            $analysisData = $response->json();

            if (isset($analysisData['error'])) {
                return back()->withErrors([
                    'file' => 'Document re-analysis failed: ' . $analysisData['error']
                ]);
            }

            $document->update([
                'analysis' => $analysisData,
            ]);

            \Log::info('Document re-analyzed successfully', [
                'document_id' => $document->id
            ]);

            return redirect()->route('documents.show', $document->id)
                ->with('success', 'Document re-analyzed successfully!');

        } catch (\Exception $e) {
            \Log::error('Exception during document re-analysis', [
                'document_id' => $document->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'file' => 'An error occurred while re-analyzing the document: ' . $e->getMessage()
            ]);
        }
    }
}
