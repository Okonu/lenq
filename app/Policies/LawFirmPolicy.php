<?php

namespace App\Policies;

use App\Models\FirmMember;
use App\Models\LawFirm;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LawFirmPolicy
{
    /**
     * Determine whether the user can view the law firm.
     */
    public function view(User $user, LawFirm $lawFirm): bool
    {
        return $user->isMemberOf($lawFirm);
    }

    /**
     * Determine whether the user can update the law firm.
     */
    public function update(User $user, LawFirm $lawFirm): bool
    {
        return $user->isAdminOf($lawFirm);
    }

    /**
     * Determine whether the user can delete the law firm.
     */
    public function delete(User $user, LawFirm $lawFirm): bool
    {
        return $user->isAdminOf($lawFirm);
    }

    /**
     * Determine whether the user can view firm members.
     */
    public function viewMembers(User $user, LawFirm $lawFirm): bool
    {
        return $user->isMemberOf($lawFirm);
    }

    /**
     * Determine whether the user can invite firm members.
     */
    public function inviteMembers(User $user, LawFirm $lawFirm): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $member && ($member->isAdmin() || $member->isAttorney());
    }

    /**
     * Determine whether the user can view clients.
     */
    public function viewClients(User $user, LawFirm $lawFirm): bool
    {
        return $user->isMemberOf($lawFirm);
    }

    /**
     * Determine whether the user can create clients.
     */
    public function createClient(User $user, LawFirm $lawFirm): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $member && ($member->isAdmin() || $member->isAttorney() || $member->isStaff());
    }

    /**
     * Determine whether the user can view cases.
     */
    public function viewCases(User $user, LawFirm $lawFirm): bool
    {
        return $user->isMemberOf($lawFirm);
    }

    /**
     * Determine whether the user can create cases.
     */
    public function createCase(User $user, LawFirm $lawFirm): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $member && ($member->isAdmin() || $member->isAttorney());
    }

    /**
     * Determine whether the user can view tasks.
     */
    public function viewTasks(User $user, LawFirm $lawFirm): bool
    {
        return $user->isMemberOf($lawFirm);
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function createTask(User $user, LawFirm $lawFirm): bool
    {
        return $user->isMemberOf($lawFirm);
    }
}
