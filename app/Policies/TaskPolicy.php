<?php

namespace App\Policies;

use App\Models\FirmMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $task->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        if (!$member) {
            return false;
        }

        if ($member->isAdmin()) {
            return true;
        }

        $isCreator = $task->created_by === $member->id;
        $isAssigned = $task->assigned_to === $member->id;

        $isOnCase = false;
        if ($task->legal_case_id) {
            $isOnCase = $task->legalCase->teamMembers()
                ->where('firm_member_id', $member->id)
                ->exists();
        }

        return $isCreator || $isAssigned || $isOnCase;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $task->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        if (!$member) {
            return false;
        }

        if ($member->isAdmin()) {
            return true;
        }

        $isCreator = $task->created_by === $member->id;

        $isAssigned = $task->assigned_to === $member->id;

        $isLeadAttorney = false;
        if ($task->legal_case_id && $member->isAttorney()) {
            $isLeadAttorney = $task->legalCase->teamMembers()
                ->where('firm_member_id', $member->id)
                ->where('role', 'lead')
                ->exists();
        }

        return $isCreator || $isAssigned || $isLeadAttorney;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        $member = FirmMember::where('user_id', $user->id)
            ->where('law_firm_id', $task->law_firm_id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();

        if (!$member) {
            return false;
        }

        if ($member->isAdmin()) {
            return true;
        }

        $isCreator = $task->created_by === $member->id;

        $isLeadAttorney = false;
        if ($task->legal_case_id && $member->isAttorney()) {
            $isLeadAttorney = $task->legalCase->teamMembers()
                ->where('firm_member_id', $member->id)
                ->where('role', 'lead')
                ->exists();
        }

        return $isCreator || $isLeadAttorney;
    }
}
