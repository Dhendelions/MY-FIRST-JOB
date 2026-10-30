<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Multi-Leader, Multi-Admin & Employee (Karyawan) Role Removal.
 *
 * 1. Convert all existing 'karyawan' (or any non-leader/admin roles) in group_members to 'admin'.
 * 2. Ensure every workspace creator (fisheries_groups.created_by) has an active 'leader' row.
 * 3. Allows multiple leaders and multiple admins per workspace without upper limits.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Update any 'karyawan' or non-leader/admin roles to 'admin'
            DB::table('group_members')
                ->whereNotIn(DB::raw('LOWER(TRIM(role))'), ['leader', 'admin'])
                ->update(['role' => 'admin']);

            // 2. Ensure each workspace creator has an active 'leader' member entry
            $groups = DB::table('fisheries_groups')->get(['id', 'created_by']);
            foreach ($groups as $group) {
                if (!$group->created_by) {
                    continue;
                }

                $creatorMember = DB::table('group_members')
                    ->where('group_id', $group->id)
                    ->where('user_id', $group->created_by)
                    ->first();

                if ($creatorMember) {
                    DB::table('group_members')
                        ->where('id', $creatorMember->id)
                        ->update([
                            'role' => 'leader',
                            'status' => 'Aktif',
                        ]);
                } else {
                    DB::table('group_members')->insert([
                        'group_id'   => $group->id,
                        'user_id'    => $group->created_by,
                        'role'       => 'leader',
                        'status'     => 'Aktif',
                        'joined_at'  => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Irreversible conversion of legacy roles to admin as karyawan is permanently removed
    }
};

