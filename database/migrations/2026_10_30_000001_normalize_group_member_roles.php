<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Normalize group_members.role to canonical values: leader, admin, karyawan.
 *
 * This migration:
 * 1. Normalizes all existing legacy role strings to the canonical set.
 * 2. Ensures the fisheries_groups.created_by owner always has a matching
 *    group_members row with role = 'leader' (creates one if missing).
 * 3. Enforces uniqueness: at most one active leader per workspace by ensuring
 *    the created_by owner is THE leader; any other member previously tagged
 *    leader is demoted to admin.
 *
 * All changes are wrapped in a transaction so the schema stays consistent.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // ── 1. Map legacy role strings → canonical values ──────────────
            $legacyToCanonical = [
                'ketua tambak'          => 'leader',
                'pemilik/pengelola tambak' => 'leader',
                'owner'                 => 'leader',
                'ketua'                 => 'leader',
                'teknisi kolam'         => 'admin',
                'staff keuangan'        => 'admin',
                'administrator'         => 'admin',
                'manager'               => 'admin',
                'anggota'               => 'karyawan',
                'pegawai'               => 'karyawan',
                'staff'                 => 'karyawan',
                'member'                => 'karyawan',
            ];

            foreach ($legacyToCanonical as $legacy => $canonical) {
                DB::table('group_members')
                    ->whereRaw('LOWER(TRIM(role)) = ?', [$legacy])
                    ->update(['role' => $canonical]);
            }

            // Normalize any remaining non-canonical roles to 'karyawan'
            DB::table('group_members')
                ->whereNotIn(DB::raw('LOWER(TRIM(role))'), ['leader', 'admin', 'karyawan'])
                ->update(['role' => 'karyawan']);

            // ── 2. Ensure workspace creator has exactly one leader member row ─
            $groups = DB::table('fisheries_groups')->get(['id', 'created_by']);
            foreach ($groups as $group) {
                $existing = DB::table('group_members')
                    ->where('group_id', $group->id)
                    ->where('user_id', $group->created_by)
                    ->first();

                if ($existing) {
                    // Ensure their role is leader
                    if (strtolower(trim($existing->role)) !== 'leader') {
                        DB::table('group_members')
                            ->where('id', $existing->id)
                            ->update(['role' => 'leader']);
                    }
                    // Ensure they are active
                    if ($existing->status !== 'Aktif') {
                        DB::table('group_members')
                            ->where('id', $existing->id)
                            ->update(['status' => 'Aktif']);
                    }
                } else {
                    // Creator has no member row yet – create one
                    DB::table('group_members')->insert([
                        'group_id'  => $group->id,
                        'user_id'   => $group->created_by,
                        'role'      => 'leader',
                        'status'    => 'Aktif',
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Demote any OTHER active leaders in this workspace to admin
                // (keeps only one authoritative leader entry per workspace)
                DB::table('group_members')
                    ->where('group_id', $group->id)
                    ->where('user_id', '!=', $group->created_by)
                    ->where('role', 'leader')
                    ->where('status', 'Aktif')
                    ->update(['role' => 'admin']);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * The normalization is non-destructive (no columns dropped / no rows removed),
     * so rolling back simply leaves the canonical values in place. There is no
     * meaningful way to restore the original legacy strings.
     */
    public function down(): void
    {
        // Intentionally left blank – canonical values are backwards-compatible.
    }
};
