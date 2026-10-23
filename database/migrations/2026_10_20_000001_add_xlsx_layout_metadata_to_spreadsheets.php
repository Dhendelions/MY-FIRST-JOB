<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('spreadsheet_sheets', 'metadata')) {
            Schema::table('spreadsheet_sheets', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }

        if (!Schema::hasColumn('spreadsheet_rows', 'height')) {
            Schema::table('spreadsheet_rows', function (Blueprint $table) {
                $table->decimal('height', 8, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('spreadsheet_rows', 'height')) {
            Schema::table('spreadsheet_rows', function (Blueprint $table) {
                $table->dropColumn('height');
            });
        }

        if (Schema::hasColumn('spreadsheet_sheets', 'metadata')) {
            Schema::table('spreadsheet_sheets', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
