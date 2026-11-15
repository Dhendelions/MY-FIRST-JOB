<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Database: " . config('database.connections.mysql.database') . PHP_EOL;
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $tableName = array_values((array)$t)[0];
    $count = DB::table($tableName)->count();
    echo str_pad($tableName, 30) . ": " . $count . PHP_EOL;
}

