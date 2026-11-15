<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$dbName = 'dadhen_mandiri';
$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$dbName]);

echo "=== DETAILED SCHEMA IN dadhen_mandiri ===\n\n";

foreach ($tables as $t) {
    $table = $t->TABLE_NAME ?? $t->table_name;
    echo "TABLE: {$table}\n";
    echo str_repeat('-', 80) . "\n";

    // Columns
    $cols = DB::select("
        SELECT column_name, data_type, column_type, is_nullable, column_default, column_key, extra
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = ?
        ORDER BY ordinal_position
    ", [$dbName, $table]);

    foreach ($cols as $c) {
        echo sprintf(
            "  %-25s %-20s NULL:%-3s DEF:%-15s KEY:%-4s %s\n",
            $c->COLUMN_NAME,
            $c->COLUMN_TYPE,
            $c->IS_NULLABLE,
            $c->COLUMN_DEFAULT ?? 'NULL',
            $c->COLUMN_KEY,
            $c->EXTRA
        );
    }

    // Indexes
    $indexes = DB::select("
        SELECT index_name, non_unique, column_name, seq_in_index
        FROM information_schema.statistics
        WHERE table_schema = ? AND table_name = ?
        ORDER BY index_name, seq_in_index
    ", [$dbName, $table]);

    $groupedIndexes = [];
    foreach ($indexes as $idx) {
        $groupedIndexes[$idx->INDEX_NAME]['unique'] = $idx->NON_UNIQUE == 0;
        $groupedIndexes[$idx->INDEX_NAME]['columns'][] = $idx->COLUMN_NAME;
    }

    echo "  INDEXES:\n";
    foreach ($groupedIndexes as $name => $info) {
        echo sprintf(
            "    %-30s %s (%s)\n",
            $name,
            $info['unique'] ? '[UNIQUE]' : '[INDEX] ',
            implode(', ', $info['columns'])
        );
    }

    // Foreign Keys
    $fks = DB::select("
        SELECT constraint_name, column_name, referenced_table_name, referenced_column_name
        FROM information_schema.key_column_usage
        WHERE table_schema = ? AND table_name = ? AND referenced_table_name IS NOT NULL
    ", [$dbName, $table]);

    if (!empty($fks)) {
        echo "  FOREIGN KEYS:\n";
        foreach ($fks as $fk) {
            echo sprintf(
                "    %-30s %s -> %s(%s)\n",
                $fk->CONSTRAINT_NAME,
                $fk->COLUMN_NAME,
                $fk->REFERENCED_TABLE_NAME,
                $fk->REFERENCED_COLUMN_NAME
            );
        }
    }

    echo "\n";
}

