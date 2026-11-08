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
        Schema::table('operational_years', function (Blueprint $table) {
            if (!Schema::hasColumn('operational_years', 'password')) {
                $table->string('password')->nullable()->after('is_archived');
            }
            if (!Schema::hasColumn('operational_years', 'password_version')) {
                $table->unsignedInteger('password_version')->default(1)->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_years', function (Blueprint $table) {
            if (Schema::hasColumn('operational_years', 'password_version')) {
                $table->dropColumn('password_version');
            }
            if (Schema::hasColumn('operational_years', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};

