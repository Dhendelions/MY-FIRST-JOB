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
        // Add formula and format to spreadsheet_cells
        Schema::table('spreadsheet_cells', function (Blueprint $table) {
            if (!Schema::hasColumn('spreadsheet_cells', 'formula')) {
                $table->string('formula', 500)->nullable()->after('value');
            }
            if (!Schema::hasColumn('spreadsheet_cells', 'format')) {
                $table->json('format')->nullable()->after('formula');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spreadsheet_cells', function (Blueprint $table) {
            if (Schema::hasColumn('spreadsheet_cells', 'format')) {
                $table->dropColumn('format');
            }
            if (Schema::hasColumn('spreadsheet_cells', 'formula')) {
                $table->dropColumn('formula');
            }
        });
    }
};
