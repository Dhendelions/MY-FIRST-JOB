<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add an index on `formula` to speed up the EXISTS check in
 * SpreadsheetWorksheet::evaluateAllFormulas() which queries:
 *   WHERE row_id IN (...) AND formula IS NOT NULL AND formula != ''
 *
 * The composite index (row_id, formula) allows the engine to satisfy
 * both predicates with a single index range scan.
 *
 * Fixed: replaced getDoctrineSchemaManager() (requires doctrine/dbal, removed
 * in Laravel 11) with a raw information_schema query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spreadsheet_cells', function (Blueprint $table) {
            // Skip if the index already exists (idempotent migration).
            if (!$this->indexExists('spreadsheet_cells', 'spreadsheet_cells_row_id_formula_index')) {
                $table->index(['row_id', 'formula'], 'spreadsheet_cells_row_id_formula_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('spreadsheet_cells', function (Blueprint $table) {
            if ($this->indexExists('spreadsheet_cells', 'spreadsheet_cells_row_id_formula_index')) {
                $table->dropIndex('spreadsheet_cells_row_id_formula_index');
            }
        });
    }

    /**
     * Check whether a named index exists on the given table.
     * Uses Schema::hasIndex with MySQL information_schema fallback.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (method_exists(Schema::getFacadeRoot(), 'hasIndex') && Schema::hasIndex($table, $indexName)) {
            return true;
        }

        if (DB::getDriverName() === 'mysql') {
            $dbName  = config('database.connections.' . config('database.default') . '.database');
            $results = DB::select(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
                 LIMIT 1",
                [$dbName, $table, $indexName]
            );
            return count($results) > 0;
        }

        return false;
    }
};
