<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fisheries_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('fisheries_groups', 'password')) {
                $table->string('password', 255)->nullable()->after('invite_code');
            }
            if (Schema::hasColumn('fisheries_groups', 'location')) {
                $table->string('location')->default('Utama')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fisheries_groups', function (Blueprint $table) {
            if (Schema::hasColumn('fisheries_groups', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};
