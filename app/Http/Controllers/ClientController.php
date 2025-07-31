<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FirmMember;
use App\Models\LawFirm;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     */
    public function index()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('viewClients', $lawFirm);

        $clients = Client::where('law_firm_id', $lawFirm->id)
            ->withCount('cases')
            ->get();

        return Inertia::render('Clients/Index', [
            'lawFirm' => $lawFirm,
            'clients' => $clients,
            'userRole' => $firmMember->role,
        ]);
    }

    /**
     * Show the form for creating a new client.
     */
    public function create()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('createClient', $lawFirm);

        return Inertia::render('Clients/Create', [
            'lawFirm' => $lawFirm,
        ]);
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,organization',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('createClient', $lawFirm);

        $client = Client::create([
            'law_firm_id' => $lawFirm->id,
            'name' => $request->name,
            'type' => $request->type,
            'contact_name' => $request->contact_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'status' => $request->status ?? Client::STATUS_ACTIVE,
            'notes' => $request->notes,
        ]);

        return redirect()->route('clients.show', $client)->with('success', 'Client created successfully!');
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        $this->authorize('view', $client);

        $cases = LegalCase::where('client_id', $client->id)
            ->latest()
            ->get();

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'cases' => $cases,
        ]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,organization',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $client->update([
            'name' => $request->name,
            'type' => $request->type,
            'contact_name' => $request->contact_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('clients.show', $client)->with('success', 'Client updated successfully!');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        $caseCount = LegalCase::where('client_id', $client->id)->count();

        if ($caseCount > 0) {
            return back()->with('error', 'Cannot delete client with associated cases.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully!');
    }
}
