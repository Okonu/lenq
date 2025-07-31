<?php

namespace App\Policies;

use App\Models\FirmMember;
use App\Models\LawFirm;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FirmMemberPolicy
{
    public function viewMembers(User $user, LawFirm $lawFirm): bool
    {
        $userMember = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $userMember !== null;
    }

    /**
     * Determine whether the user can invite members to the law firm.
     */
    public function inviteMembers(User $user, LawFirm $lawFirm): bool
    {
        $userMember = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $userMember && $userMember->isAdmin();
    }

    /**
     * Determine whether the user can update the firm member.
     */
    public function updateMember(User $user, FirmMember $member): bool
    {
        $userMember = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $member->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $userMember && $userMember->isAdmin();
    }

    /**
     * Determine whether the user can remove the firm member.
     */
    public function removeMember(User $user, FirmMember $member): bool
    {
        $userMember = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $member->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $userMember && $userMember->isAdmin() && $userMember->id !== $member->id;
    }
}
