<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$backupDir = dirname(__DIR__) . '/database/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$timestamp = date('Y_m_d_His');

foreach (['dadhen_mandiri', 'dadhen_mandiri_v2'] as $dbName) {
    $filename = "{$backupDir}/backup_{$dbName}_{$timestamp}.sql";
    echo "Backing up {$dbName} to {$filename}...\n";

    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
    if (empty($tables)) {
        file_put_contents($filename, "-- Database: {$dbName} (EMPTY)\n");
        echo "  [Empty database, placeholder saved]\n";
        continue;
    }

    $sqlContent = "-- Full Database Backup: {$dbName}\n-- Date: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $t) {
        $table = $t->TABLE_NAME ?? $t->table_name;
        $createTable = DB::selectOne("SHOW CREATE TABLE `{$dbName}`.`{$table}`");
        $createSql = $createTable->{'Create Table'} ?? $createTable->{'create table'};
        $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n" . $createSql . ";\n\n";

        $rows = DB::select("SELECT * FROM `{$dbName}`.`{$table}`");
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $rowArr = (array) $row;
                $columns = array_map(fn($c) => "`{$c}`", array_keys($rowArr));
                $values = array_map(function($v) {
                    if (is_null($v)) return 'NULL';
                    return "'" . addslashes((string)$v) . "'";
                }, array_values($rowArr));

                $sqlContent .= sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $table,
                    implode(', ', $columns),
                    implode(', ', $values)
                );
            }
            $sqlContent .= "\n";
        }
    }

    $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
    file_put_contents($filename, $sqlContent);
    echo "  Backup completed successfully: " . strlen($sqlContent) . " bytes\n";
}

