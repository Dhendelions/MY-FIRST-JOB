<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('spreadsheet_cells', 'version')) {
            Schema::table('spreadsheet_cells', function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('spreadsheet_cells', 'version')) {
            Schema::table('spreadsheet_cells', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }
    }
};
