<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FisheriesGroup;
use App\Models\GroupMember;

class WorkspacePolicy
{
    /**
     * Determine whether the user can view the workspace.
     */
    public function view(User $user, FisheriesGroup $workspace): bool
    {
        return $workspace->isMember($user->id);
    }

    /**
     * Determine whether the user can update workspace settings (Leader only).
     */
    public function update(User $user, FisheriesGroup $workspace): bool
    {
        return $workspace->isLeader($user->id);
    }

    /**
     * Determine whether the user can delete the workspace (Leader only).
     */
    public function delete(User $user, FisheriesGroup $workspace): bool
    {
        return $workspace->isLeader($user->id);
    }

    /**
     * Determine whether the user can invite or add members.
     */
    public function addMember(User $user, FisheriesGroup $workspace): bool
    {
        return $workspace->isAdmin($user->id);
    }

    /**
     * Determine whether the user can update another member's role.
     */
    public function changeRole(User $user, FisheriesGroup $workspace, GroupMember $targetMember, string $newRole): bool
    {
        $currentUserRole = $workspace->getMemberRole($user->id);
        $targetRole = strtolower(trim($targetMember->role));
        if ($targetRole === 'ketua tambak') $targetRole = 'leader';

        // Only leaders or admins can change roles
        if (!in_array($currentUserRole, ['leader', 'admin'])) {
            return false;
        }

        // Users cannot change their own role through this action
        if ($targetMember->user_id === $user->id) {
            return false;
        }

        // An admin cannot modify a leader's role or promote anyone to leader
        if ($currentUserRole === 'admin') {
            if ($targetRole === 'leader' || $newRole === 'leader') {
                return false;
            }
        }

        // If target is leader being demoted, ensure at least one other active leader exists
        if ($targetRole === 'leader' && $newRole !== 'leader') {
            $otherLeaders = $workspace->members()
                ->where('id', '!=', $targetMember->id)
                ->whereIn('role', ['leader', 'Ketua Tambak'])
                ->where('status', 'Aktif')
                ->count();

            if ($otherLeaders === 0 && $workspace->created_by === $targetMember->user_id) {
                return false; // Cannot demote the last remaining leader / workspace owner
            }
        }

        return true;
    }

    /**
     * Determine whether the user can remove a member from the workspace.
     */
    public function removeMember(User $user, FisheriesGroup $workspace, GroupMember $targetMember): bool
    {
        $currentUserRole = $workspace->getMemberRole($user->id);
        $targetRole = strtolower(trim($targetMember->role));
        if ($targetRole === 'ketua tambak') $targetRole = 'leader';

        // Leader can remove anyone except workspace creator (unless creator deletes)
        if ($currentUserRole === 'leader') {
            return true;
        }

        // Admin can only remove karyawan, not leaders or other admins
        if ($currentUserRole === 'admin') {
            return $targetRole === 'karyawan';
        }

        return false;
    }

    /**
     * Determine whether the user can create, rename, or delete sheets.
     */
    public function manageSheets(User $user, FisheriesGroup $workspace): bool
    {
        return $workspace->isAdmin($user->id);
    }

    /**
     * Determine whether the user can edit cells, rows, or columns in spreadsheet.
     */
    public function editSpreadsheet(User $user, FisheriesGroup $workspace): bool
    {
        return $workspace->isMember($user->id);
    }
}
