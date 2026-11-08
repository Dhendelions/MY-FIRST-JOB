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
        // 1. Ensure role column exists with default 'admin'
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('admin')->after('role_title');
            });
        }

        // 2. Normalize raw database columns without overwriting existing roles:
        // Set role_title to 'Owner' where role is 'owner'
        DB::table('users')
            ->where('role', 'owner')
            ->update([
                'role_title' => 'Owner',
            ]);

        // Set role_title to 'Admin' where role is 'admin' or null
        DB::table('users')
            ->where(function ($q) {
                $q->where('role', 'admin')
                  ->orWhereNull('role')
                  ->orWhere('role', '');
            })
            ->update([
                'role' => 'admin',
                'role_title' => 'Admin',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op on down to preserve data integrity
    }
};

