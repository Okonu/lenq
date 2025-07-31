<?php

namespace App\Policies;

use App\Models\FirmMember;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LegalCasePolicy
{
    /**
     * Determine whether the user can view the legal case.
     */
    public function view(User $user, LegalCase $case): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $case->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        if (!$member) {
            return false;
        }

        if ($member->isAdmin()) {
            return true;
        }

        $isTeamMember = $case->teamMembers()
            ->where('firm_member_id', $member->id)
            ->exists();

        $isCreator = $case->user_id === $user->id;

        return $isTeamMember || $isCreator;
    }

    /**
     * Determine whether the user can update the legal case.
     */
    public function update(User $user, LegalCase $case): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $case->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        if (!$member) {
            return false;
        }

        if ($member->isAdmin()) {
            return true;
        }

        if ($member->isAttorney()) {
            return $case->teamMembers()
                ->where('firm_member_id', $member->id)
                ->whereIn('role', [
                    'lead',
                    'associate',
                ])
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the legal case.
     */
    public function delete(User $user, LegalCase $case): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $case->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $member && $member->isAdmin();
    }
}
