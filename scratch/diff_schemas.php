<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function getTableSchema($dbName, $tableName) {
    $cols = DB::select("
        SELECT column_name, data_type, column_type, is_nullable, column_default, column_key
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = ?
        ORDER BY column_name
    ", [$dbName, $tableName]);

    $res = [];
    foreach ($cols as $c) {
        $res[$c->COLUMN_NAME] = [
            'type' => $c->COLUMN_TYPE,
            'nullable' => $c->IS_NULLABLE,
            'default' => $c->COLUMN_DEFAULT,
            'key' => $c->COLUMN_KEY,
        ];
    }
    return $res;
}

$tables1 = array_map(fn($t) => $t->TABLE_NAME, DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'dadhen_mandiri'"));
$tables2 = array_map(fn($t) => $t->TABLE_NAME, DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'dadhen_mandiri_v2'"));

sort($tables1);
sort($tables2);

echo "Tables in dadhen_mandiri: " . implode(', ', $tables1) . "\n\n";
echo "Tables in dadhen_mandiri_v2: " . implode(', ', $tables2) . "\n\n";

$diff1 = array_diff($tables1, $tables2);
$diff2 = array_diff($tables2, $tables1);

if (!empty($diff1)) echo "Missing in v2: " . implode(', ', $diff1) . "\n";
if (!empty($diff2)) echo "Missing in original: " . implode(', ', $diff2) . "\n";

foreach ($tables1 as $tbl) {
    if (!in_array($tbl, $tables2)) continue;
    $s1 = getTableSchema('dadhen_mandiri', $tbl);
    $s2 = getTableSchema('dadhen_mandiri_v2', $tbl);

    $allCols = array_unique(array_merge(array_keys($s1), array_keys($s2)));
    foreach ($allCols as $col) {
        if (!isset($s1[$col])) {
            echo "TABLE {$tbl}: column {$col} exists in v2 but not in dadhen_mandiri!\n";
        } elseif (!isset($s2[$col])) {
            echo "TABLE {$tbl}: column {$col} exists in dadhen_mandiri but not in v2!\n";
        } elseif ($s1[$col] != $s2[$col]) {
            echo "TABLE {$tbl}: column {$col} mismatch:\n";
            echo "  dadhen_mandiri   : " . json_encode($s1[$col]) . "\n";
            echo "  dadhen_mandiri_v2: " . json_encode($s2[$col]) . "\n";
        }
    }
}
echo "\nSchema comparison completed.\n";

