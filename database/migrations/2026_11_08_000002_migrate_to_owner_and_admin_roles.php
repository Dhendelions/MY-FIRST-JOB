<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add role column to users table if not already present
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('admin')->after('role_title');
            });
        }

        // 2. Safe Migration of Legacy Roles:
        // Established company accounts are promoted to Owner
        // All other accounts default to Admin (least privilege principle)
        $ownerEmails = [
            'dadhenmandiri001@gmail.com', // Official Dadhen Mandiri company account
            'yudimade979@gmail.com',      // Crissencio Dheni S. (Primary system administrator/developer)
        ];

        DB::table('users')
            ->whereIn('email', $ownerEmails)
            ->update([
                'role' => 'owner',
                'role_title' => 'Owner',
            ]);

        DB::table('users')
            ->whereNotIn('email', $ownerEmails)
            ->update([
                'role' => 'admin',
                'role_title' => 'Admin',
            ]);

        // Safety fallback: Ensure at least one active Owner exists
        $ownerExists = DB::table('users')->where('role', 'owner')->exists();
        if (!$ownerExists) {
            $firstUser = DB::table('users')->orderBy('id')->first();
            if ($firstUser) {
                DB::table('users')->where('id', $firstUser->id)->update([
                    'role' => 'owner',
                    'role_title' => 'Owner',
                ]);
            }
        }

        // 3. Migrate group_members roles to strictly 'owner' or 'admin'
        $ownerUserIds = DB::table('users')->where('role', 'owner')->pluck('id')->all();

        DB::table('group_members')
            ->whereIn('user_id', $ownerUserIds)
            ->update(['role' => 'owner']);

        DB::table('group_members')
            ->whereNotIn('user_id', $ownerUserIds)
            ->update(['role' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        DB::table('group_members')
            ->where('role', 'owner')
            ->update(['role' => 'leader']);
    }
};

