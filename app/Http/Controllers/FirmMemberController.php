<?php

namespace App\Http\Controllers;

use App\Models\FirmMember;
use App\Models\LawFirm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FirmMemberController extends Controller
{
    /**
     * Display a listing of the firm members.
     */
    public function index()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('viewMembers', $lawFirm);

        $members = FirmMember::where('law_firm_id', $lawFirm->id)
            ->with('user')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user ? $member->user->name : 'Invited User',
                    'email' => $member->user ? $member->user->email : '',
                    'role' => $member->role,
                    'title' => $member->title,
                    'department' => $member->department,
                    'status' => $member->status,
                    'invited_at' => $member->created_at,
                    'joined_at' => $member->invitation_accepted_at,
                ];
            });

        return Inertia::render('FirmMembers/Index', [
            'lawFirm' => $lawFirm,
            'members' => $members,
            'userRole' => $firmMember->role,
        ]);
    }

    /**
     * Show the form for creating a new firm member.
     */
    public function create()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('inviteMembers', $lawFirm);

        return Inertia::render('FirmMembers/Create', [
            'lawFirm' => $lawFirm,
        ]);
    }

    /**
     * Store a newly created firm member in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,attorney,staff',
            'title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('inviteMembers', $lawFirm);

        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            $existingMember = FirmMember::where('law_firm_id', $lawFirm->id)
                ->where('user_id', $existingUser->id)
                ->first();

            if ($existingMember) {
                return back()->with('error', 'This user is already a member of your firm.');
            }
        }

        $invitationToken = Str::random(32);

        $newMember = FirmMember::create([
            'law_firm_id' => $lawFirm->id,
            'user_id' => $existingUser ? $existingUser->id : null,
            'role' => $request->role,
            'title' => $request->title,
            'department' => $request->department,
            'status' => FirmMember::STATUS_INVITED,
            'invitation_token' => $invitationToken,
        ]);

        //TODO:: Add Send invitation email
        //Mail::to($request->email)->send(new MemberInvitation($lawFirm, $invitationToken));

        return redirect()->route('firm.members.index')->with('success', 'Invitation sent successfully!');
    }

    /**
     * Accept an invitation.
     */
    public function acceptInvitation(Request $request, $token)
    {
        $member = FirmMember::where('invitation_token', $token)
            ->where('status', FirmMember::STATUS_INVITED)
            ->first();

        if (!$member) {
            return redirect()->route('dashboard')->with('error', 'Invalid or expired invitation.');
        }

        $lawFirm = LawFirm::findOrFail($member->law_firm_id);

        if (Auth::check()) {
            $user = Auth::user();

            $member->update([
                'user_id' => $user->id,
                'status' => FirmMember::STATUS_ACTIVE,
                'invitation_token' => null,
                'invitation_accepted_at' => now(),
            ]);

            return redirect()->route('lawfirm.dashboard')->with('success', 'You have joined ' . $lawFirm->name . '!');
        } else {
            session(['invitation_token' => $token]);

            return redirect()->route('register')->with('message', 'Please create an account or log in to join ' . $lawFirm->name);
        }
    }

    /**
     * Update the specified firm member in storage.
     */
    public function update(Request $request, FirmMember $member)
    {
        $request->validate([
            'role' => 'required|in:admin,attorney,staff',
            'title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,invited',
        ]);

        $this->authorize('updateMember', $member);

        if ($member->isAdmin() && $request->role !== FirmMember::ROLE_ADMIN) {
            $adminCount = FirmMember::where('law_firm_id', $member->law_firm_id)
                ->where('role', FirmMember::ROLE_ADMIN)
                ->where('status', FirmMember::STATUS_ACTIVE)
                ->count();

            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot demote the last admin of the firm.');
            }
        }

        $member->update([
            'role' => $request->role,
            'title' => $request->title,
            'department' => $request->department,
            'status' => $request->status,
        ]);

        return redirect()->route('firm.members.index')->with('success', 'Member updated successfully!');
    }

    /**
     * Remove the specified firm member from storage.
     */
    public function destroy(FirmMember $member)
    {
        $this->authorize('removeMember', $member);

        if ($member->isAdmin()) {
            $adminCount = FirmMember::where('law_firm_id', $member->law_firm_id)
                ->where('role', FirmMember::ROLE_ADMIN)
                ->where('status', FirmMember::STATUS_ACTIVE)
                ->count();

            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot remove the last admin of the firm.');
            }
        }

        $member->delete();

        return redirect()->route('firm.members.index')->with('success', 'Member removed successfully!');
    }
}
