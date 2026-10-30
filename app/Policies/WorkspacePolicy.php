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
     * Leaders and Admins can invite members.
     */
    public function addMember(User $user, FisheriesGroup $workspace): bool
    {
        return $workspace->isAdmin($user->id);
    }

    /**
     * Determine whether the user can update another member's role.
     *
     * Rules:
     * - ONLY a Leader can change roles.
     * - Admins cannot change any member's role.
     * - A member cannot change their own role.
     * - Founder (workspace creator) role is permanently protected and CANNOT be changed.
     * - New role must strictly be 'leader' or 'admin'.
     * - Workspaces support Multi-Leader and Multi-Admin without quantity limits.
     * - Leaders can promote Admins to Leader.
     * - Leaders can demote promoted Leaders back to Admin.
     */
    public function changeRole(User $user, FisheriesGroup $workspace, GroupMember $targetMember, string $newRole): bool
    {
        $currentUserRole = $workspace->getMemberRole($user->id);

        // Only a workspace Leader may change roles
        if ($currentUserRole !== 'leader') {
            return false;
        }

        // A member cannot change their own role
        if ($targetMember->user_id === $user->id) {
            return false;
        }

        // The founder (workspace creator) role is permanently protected
        if ($workspace->isFounder($targetMember->user_id)) {
            return false;
        }

        // New role must strictly be 'leader' or 'admin'
        if (!in_array($newRole, ['leader', 'admin'], true)) {
            return false;
        }

        // Target member must belong to this workspace and be active
        if ($targetMember->group_id !== $workspace->id || $targetMember->status !== 'Aktif') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can remove a member from the workspace.
     *
     * Rules:
     * - ONLY a Leader can remove members.
     * - Admins cannot remove members.
     * - The workspace founder CANNOT be removed from the workspace.
     * - A user cannot remove themselves via this endpoint (use leaveWorkspace instead).
     */
    public function removeMember(User $user, FisheriesGroup $workspace, GroupMember $targetMember): bool
    {
        $currentUserRole = $workspace->getMemberRole($user->id);

        // Only leaders can remove members
        if ($currentUserRole !== 'leader') {
            return false;
        }

        // The founder cannot be removed
        if ($workspace->isFounder($targetMember->user_id)) {
            return false;
        }

        // Cannot remove self via manage members
        if ($targetMember->user_id === $user->id) {
            return false;
        }

        return $targetMember->group_id === $workspace->id;
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

    /**
     * Determine whether the user can backup the entire workspace spreadsheet.
     */
    public function backup(User $user, FisheriesGroup $workspace): bool
    {
        return in_array($workspace->getMemberRole($user->id), ['leader', 'admin'], true);
    }
}
