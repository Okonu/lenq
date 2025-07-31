<?php

namespace App\Http\Controllers;

use App\Models\LawFirm;
use App\Models\FirmMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LawFirmController extends Controller
{
    /**
     * Display the law firm dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->with('lawFirm')->first();

        if (!$firmMember) {
            return Inertia::render('LawFirm/Setup');
        }

        $lawFirm = $firmMember->lawFirm;

        $memberCount = FirmMember::where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->count();

        $caseCount = $lawFirm->cases()->count();
        $clientCount = $lawFirm->clients()->count();

        return Inertia::render('LawFirm/Dashboard', [
            'lawFirm' => $lawFirm,
            'memberCount' => $memberCount,
            'caseCount' => $caseCount,
            'clientCount' => $clientCount,
            'userRole' => $firmMember->role,
        ]);
    }

    /**
     * Show the form for creating a new law firm.
     */
    public function create()
    {
        return Inertia::render('LawFirm/Create');
    }

    /**
     * Store a newly created law firm in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        $lawFirm = LawFirm::create([
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'phone' => $request->phone,
            'email' => $request->email,
            'website' => $request->website,
            'logo_path' => $logoPath,
        ]);

        FirmMember::create([
            'law_firm_id' => $lawFirm->id,
            'user_id' => Auth::id(),
            'role' => FirmMember::ROLE_ADMIN,
            'status' => FirmMember::STATUS_ACTIVE,
            'invitation_accepted_at' => now(),
        ]);

        return redirect()->route('lawfirm.dashboard')->with('success', 'Law firm created successfully!');
    }

    /**
     * Display the specified law firm.
     */
    public function show(LawFirm $lawFirm)
    {
        $this->authorize('view', $lawFirm);

        return Inertia::render('LawFirm/Show', [
            'lawFirm' => $lawFirm,
        ]);
    }

    /**
     * Show the form for editing the specified law firm.
     */
    public function edit(LawFirm $lawFirm)
    {
        $this->authorize('update', $lawFirm);

        return Inertia::render('LawFirm/Edit', [
            'lawFirm' => $lawFirm,
        ]);
    }

    /**
     * Update the specified law firm in storage.
     */
    public function update(Request $request, LawFirm $lawFirm)
    {
        $this->authorize('update', $lawFirm);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($lawFirm->logo_path) {
                Storage::disk('public')->delete($lawFirm->logo_path);
            }

            $logoPath = $request->file('logo')->store('logos', 'public');
            $lawFirm->logo_path = $logoPath;
        }

        $lawFirm->update([
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'phone' => $request->phone,
            'email' => $request->email,
            'website' => $request->website,
        ]);

        return redirect()->route('lawfirm.show', $lawFirm)->with('success', 'Law firm updated successfully!');
    }

    /**
     * Remove the specified law firm from storage.
     */
    public function destroy(LawFirm $lawFirm)
    {
        $this->authorize('delete', $lawFirm);

        if ($lawFirm->logo_path) {
            Storage::disk('public')->delete($lawFirm->logo_path);
        }

        $lawFirm->delete();

        return redirect()->route('dashboard')->with('success', 'Law firm deleted successfully!');
    }
}
