<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$logs = DB::select("SELECT DISTINCT user_id, user_name FROM dadhen_mandiri.company_activity_logs");
echo "=== DISTINCT USERS IN LOGS ===\n";
print_r($logs);

$allUsersInDadhen = DB::select("SELECT * FROM dadhen_mandiri.users");
echo "\n=== ALL USERS IN dadhen_mandiri ===\n";
print_r($allUsersInDadhen);

