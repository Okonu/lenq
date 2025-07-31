<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FirmMember;
use App\Models\LawFirm;
use App\Models\LegalCase;
use App\Models\CaseAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LegalCaseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('viewCases', $lawFirm);

        if ($firmMember->isAdmin()) {
            $cases = LegalCase::where('law_firm_id', $lawFirm->id)
                ->with(['client:id,name', 'teamMembers:id,user_id'])
                ->withCount(['documents', 'conversations'])
                ->latest()
                ->get();
        } else {
            $cases = LegalCase::where('law_firm_id', $lawFirm->id)
                ->where(function ($query) use ($firmMember) {
                    $query->where('user_id', Auth::id())
                        ->orWhereHas('teamMembers', function ($q) use ($firmMember) {
                            $q->where('firm_member_id', $firmMember->id);
                        });
                })
                ->with(['client:id,name', 'teamMembers:id,user_id'])
                ->withCount(['documents', 'conversations'])
                ->latest()
                ->get();
        }

        return Inertia::render('Cases/Index', [
            'lawFirm' => $lawFirm,
            'cases' => $cases,
            'userRole' => $firmMember->role,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('createCase', $lawFirm);

        $clients = Client::where('law_firm_id', $lawFirm->id)
            ->where('status', Client::STATUS_ACTIVE)
            ->get(['id', 'name', 'type']);

        $teamMembers = FirmMember::where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->with('user:id,name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->name,
                    'role' => $member->role
                ];
            });

        $clientId = $request->client_id;

        if ($clientId) {
            $client = Client::where('id', $clientId)
                ->where('law_firm_id', $lawFirm->id)
                ->first();

            if (!$client) {
                $clientId = null;
            }
        }

        return Inertia::render('Cases/Create', [
            'lawFirm' => $lawFirm,
            'clients' => $clients,
            'teamMembers' => $teamMembers,
            'clientId' => $clientId,
        ]);
    }

    /**
     * Store a newly created legal case in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'description' => 'nullable|string',
            'case_number' => 'nullable|string|max:100',
            'jurisdiction' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,pending,closed',
            'category' => 'nullable|string|max:100',
            'team_members' => 'nullable|array',
            'team_members.*.id' => 'required|exists:firm_members,id',
            'team_members.*.role' => 'required|in:lead,associate,paralegal,support',
        ]);

        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('createCase', $lawFirm);

        if ($request->client_id) {
            $client = Client::findOrFail($request->client_id);
            if ($client->law_firm_id !== $lawFirm->id) {
                return back()->with('error', 'Selected client does not belong to your firm.');
            }
        }

        $case = LegalCase::create([
            'user_id' => Auth::id(),
            'law_firm_id' => $lawFirm->id,
            'client_id' => $request->client_id,
            'title' => $request->title,
            'description' => $request->description,
            'case_number' => $request->case_number,
            'jurisdiction' => $request->jurisdiction,
            'status' => $request->status ?? LegalCase::STATUS_ACTIVE,
            'category' => $request->category,
        ]);

        if ($request->team_members && is_array($request->team_members)) {
            foreach ($request->team_members as $member) {

                $memberRecord = FirmMember::where('id', $member['id'])
                    ->where('law_firm_id', $lawFirm->id)
                    ->first();

                if ($memberRecord) {
                    CaseAssignment::create([
                        'legal_case_id' => $case->id,
                        'firm_member_id' => $member['id'],
                        'role' => $member['role'],
                    ]);
                }
            }
        }

        $isCreatorAssigned = false;
        if ($request->team_members && is_array($request->team_members)) {
            foreach ($request->team_members as $member) {
                if ($member['id'] == $firmMember->id) {
                    $isCreatorAssigned = true;
                    break;
                }
            }
        }

        if (!$isCreatorAssigned) {
            CaseAssignment::create([
                'legal_case_id' => $case->id,
                'firm_member_id' => $firmMember->id,
                'role' => CaseAssignment::ROLE_LEAD,
            ]);
        }

        return redirect()->route('cases.show', $case->id)->with('success', 'Case created successfully!');
    }

    /**
     * Display the specified legal case.
     */
    public function show(LegalCase $case)
    {
        $this->authorize('view', $case);

        $case->load([
            'client',
            'teamMembers.user',
            'documents' => function ($query) {
                $query->latest();
            },
            'conversations' => function ($query) {
                $query->latest();
            },
        ]);

        $firmMember = FirmMember::where('user_id', Auth::id())->first();

        return Inertia::render('Cases/Show', [
            'case' => $case,
            'userRole' => $firmMember ? $firmMember->role : null,
        ]);
    }

    /**
     * Show the form for editing the specified legal case.
     */
    public function edit(LegalCase $case)
    {
        $this->authorize('update', $case);

        $case->load(['client', 'teamMembers']);

        $lawFirm = LawFirm::findOrFail($case->law_firm_id);

        $clients = Client::where('law_firm_id', $lawFirm->id)
            ->where('status', Client::STATUS_ACTIVE)
            ->get(['id', 'name', 'type']);

        $teamMembers = FirmMember::where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->with('user:id,name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->name,
                    'role' => $member->role
                ];
            });

        return Inertia::render('Cases/Edit', [
            'case' => $case,
            'clients' => $clients,
            'teamMembers' => $teamMembers,
        ]);
    }

    /**
     * Update the specified legal case in storage.
     */
    public function update(Request $request, LegalCase $case)
    {
        $this->authorize('update', $case);

        $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'description' => 'nullable|string',
            'case_number' => 'nullable|string|max:100',
            'jurisdiction' => 'nullable|string|max:100',
            'status' => 'required|string|in:active,pending,closed',
            'category' => 'nullable|string|max:100',
            'team_members' => 'nullable|array',
            'team_members.*.id' => 'required|exists:firm_members,id',
            'team_members.*.role' => 'required|in:lead,associate,paralegal,support',
        ]);

        if ($request->client_id) {
            $client = Client::findOrFail($request->client_id);
            if ($client->law_firm_id !== $case->law_firm_id) {
                return back()->with('error', 'Selected client does not belong to your firm.');
            }
        }

        $case->update([
            'client_id' => $request->client_id,
            'title' => $request->title,
            'description' => $request->description,
            'case_number' => $request->case_number,
            'jurisdiction' => $request->jurisdiction,
            'status' => $request->status,
            'category' => $request->category,
        ]);

        if ($request->team_members && is_array($request->team_members)) {
            CaseAssignment::where('legal_case_id', $case->id)->delete();

            foreach ($request->team_members as $member) {
                $memberRecord = FirmMember::where('id', $member['id'])
                    ->where('law_firm_id', $case->law_firm_id)
                    ->first();

                if ($memberRecord) {
                    CaseAssignment::create([
                        'legal_case_id' => $case->id,
                        'firm_member_id' => $member['id'],
                        'role' => $member['role'],
                    ]);
                }
            }
        }

        return redirect()->route('cases.show', $case->id)->with('success', 'Case updated successfully!');
    }

    /**
     * Remove the specified legal case from storage.
     */
    public function destroy(LegalCase $case)
    {
        $this->authorize('delete', $case);

        $case->delete();

        return redirect()->route('cases.index')->with('success', 'Case deleted successfully!');
    }
}
