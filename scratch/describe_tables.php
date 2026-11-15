<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['operational_years', 'monthly_workspaces', 'spreadsheet_activities'] as $table) {
    echo "TABLE: {$table}\n";
    $cols = DB::select("DESCRIBE dadhen_mandiri.{$table}");
    foreach ($cols as $c) {
        echo sprintf("  %-25s %-20s %-5s %-5s %s\n", $c->Field, $c->Type, $c->Null, $c->Key, $c->Default ?? 'NULL');
    }
    echo "\n";
}

