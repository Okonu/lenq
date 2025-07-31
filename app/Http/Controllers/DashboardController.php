<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $recentDocuments = LegalDocument::where('user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        $recentConversations = Conversation::where('user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        $recentCases = LegalCase::where('user_id', auth()->id())
            ->latest()
            ->take(3)
            ->get();

        return Inertia::render('Dashboard', [
            'recentDocuments' => $recentDocuments,
            'recentConversations' => $recentConversations,
            'recentCases' => $recentCases,
        ]);
    }
}
