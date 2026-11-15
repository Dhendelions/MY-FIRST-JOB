<?php

namespace App\Services;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class UserDeletionService
{
    /**
     * Delete a user account while preserving all shared workspace and company data.
     *
     * Only personal records and direct membership links are removed. Shared resources,
     * workspaces, operational records, and system-owned data remain intact.
     */
    public function delete(User $actor, User $target): void
    {
        $this->assertAuthorized($actor, $target);

        DB::transaction(function () use ($target) {
            $target->refresh();

            // Remove direct relationship rows that belong to the user, but do not
            // delete shared workspaces or company data.
            $target->memberships()->delete();
            $target->notifications()->delete();
            $target->friendships()->delete();
            $target->receivedFriendships()->delete();

            FisheriesGroup::query()
                ->where('created_by', $target->id)
                ->update(['created_by' => null]);

            $target->delete();
        }, 3);
    }

    protected function assertAuthorized(User $actor, User $target): void
    {
        if (!$actor->isOwner()) {
            abort(403, 'Akses ditolak: hanya Owner yang dapat menghapus akun pengguna.');
        }

        if ($actor->id === $target->id) {
            abort(422, 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($target->isOwner()) {
            $remainingOwners = User::query()
                ->where('role', 'owner')
                ->where('is_active', true)
                ->whereKeyNot($target->id)
                ->exists();

            if (!$remainingOwners) {
                abort(422, 'Tidak dapat menghapus Owner terakhir. Perusahaan wajib memiliki minimal satu Owner aktif.');
            }
        }
    }
}
