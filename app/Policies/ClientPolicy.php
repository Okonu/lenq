<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\FirmMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        return FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $client->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $client->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $member && ($member->isAdmin() || $member->isAttorney());
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $client->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        return $member && $member->isAdmin();
    }
}
