<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add an index on `formula` to speed up the EXISTS check in
 * SpreadsheetWorksheet::evaluateAllFormulas() which queries:
 *   WHERE row_id IN (...) AND formula IS NOT NULL AND formula != ''
 *
 * The composite index (row_id, formula) allows the engine to satisfy
 * both predicates with a single index range scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spreadsheet_cells', function (Blueprint $table) {
            // Skip if the index already exists (idempotent migration).
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $existingIndexes = array_keys($sm->listTableIndexes('spreadsheet_cells'));
            if (!in_array('spreadsheet_cells_row_id_formula_index', $existingIndexes, true)) {
                $table->index(['row_id', 'formula'], 'spreadsheet_cells_row_id_formula_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('spreadsheet_cells', function (Blueprint $table) {
            $table->dropIndex('spreadsheet_cells_row_id_formula_index');
        });
    }
};
