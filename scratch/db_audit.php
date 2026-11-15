<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== USERS in dadhen_mandiri ===\n";
$users = DB::select('SELECT id, name, email, role, role_title, is_active, created_at FROM dadhen_mandiri.users');
print_r($users);

echo "\n=== OPERATIONAL YEARS in dadhen_mandiri ===\n";
$years = DB::select('SELECT id, year, is_active, is_archived, password_version, created_at FROM dadhen_mandiri.operational_years');
print_r($years);

echo "\n=== ACTIVITY LOGS in dadhen_mandiri ===\n";
$logs = DB::select('SELECT id, user_name, action, description, created_at FROM dadhen_mandiri.company_activity_logs ORDER BY id DESC LIMIT 5');
print_r($logs);
