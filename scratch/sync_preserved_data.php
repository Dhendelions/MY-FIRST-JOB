<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\OperationalYear;
use App\Models\CompanyActivityLog;
use App\Models\Notification;
use App\Services\CompanyWorkspaceInitializer;

$sourcePdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=dadhen_mandiri', 'root', 'edurank123', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== STARTING SAFE DATA SYNCHRONIZATION ===\n";

DB::transaction(function () use ($sourcePdo) {
    // 1. Sync users
    echo "1. Syncing Users...\n";
    $users = $sourcePdo->query("SELECT * FROM users")->fetchAll();
    foreach ($users as $u) {
        DB::table('users')->updateOrInsert(
            ['id' => $u['id']],
            [
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => $u['password'],
                'whatsapp' => $u['whatsapp'],
                'role_title' => $u['role_title'],
                'role' => $u['role'],
                'is_active' => $u['is_active'],
                'last_active_at' => $u['last_active_at'],
                'avatar' => $u['avatar'],
                'remember_token' => $u['remember_token'],
                'created_at' => $u['created_at'],
                'updated_at' => $u['updated_at'],
            ]
        );
        echo "   User [{$u['id']}] {$u['name']} ({$u['email']}) synced.\n";
    }

    // Ensure a default Owner exists if no owner is found, so owner protection & analytics can function
    $ownerExists = DB::table('users')->where('role', 'owner')->exists();
    if (!$ownerExists) {
        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Owner Dadhen Mandiri',
            'email' => 'dadhenmandiri001@gmail.com',
            'password' => '$2y$12$C4GUozXN1YEhJdiHNmLfTeJjfpu7yf.P50fm1kKrrWseCo42uGeAu', // same secure hash
            'whatsapp' => '081234567890',
            'role_title' => 'Owner',
            'role' => 'owner',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Created primary Owner account [{$ownerId}] dadhenmandiri001@gmail.com (role: owner).\n";
    }

    // 2. Sync Operational Years
    echo "2. Syncing Operational Years...\n";
    $years = $sourcePdo->query("SELECT * FROM operational_years")->fetchAll();
    foreach ($years as $y) {
        DB::table('operational_years')->updateOrInsert(
            ['id' => $y['id']],
            [
                'year' => $y['year'],
                'is_active' => $y['is_active'],
                'is_archived' => $y['is_archived'],
                'password' => $y['password'],
                'password_version' => $y['password_version'],
                'created_by' => $y['created_by'],
                'created_at' => $y['created_at'],
                'updated_at' => $y['updated_at'],
            ]
        );
        echo "   Year [{$y['id']}] {$y['year']} (active: {$y['is_active']}, archived: {$y['is_archived']}) synced.\n";
    }

    // 3. Sync Company Activity Logs
    echo "3. Syncing Company Activity Logs...\n";
    $logs = $sourcePdo->query("SELECT * FROM company_activity_logs")->fetchAll();
    foreach ($logs as $l) {
        DB::table('company_activity_logs')->updateOrInsert(
            ['id' => $l['id']],
            [
                'user_id' => $l['user_id'],
                'user_name' => $l['user_name'],
                'action' => $l['action'],
                'resource_type' => $l['resource_type'],
                'resource_id' => $l['resource_id'],
                'description' => $l['description'],
                'properties' => $l['properties'],
                'created_at' => $l['created_at'],
            ]
        );
    }
    echo "   " . count($logs) . " activity logs synced.\n";

    // 4. Sync Notifications
    echo "4. Syncing Notifications...\n";
    $notifs = $sourcePdo->query("SELECT * FROM notifications")->fetchAll();
    foreach ($notifs as $n) {
        DB::table('notifications')->updateOrInsert(
            ['id' => $n['id']],
            [
                'user_id' => $n['user_id'],
                'type' => $n['type'],
                'title' => $n['title'],
                'description' => $n['description'],
                'is_read' => $n['is_read'],
                'created_at' => $n['created_at'],
                'updated_at' => $n['updated_at'],
            ]
        );
    }
    echo "   " . count($notifs) . " notifications synced.\n";
});

echo "=== DATA SYNCHRONIZATION COMPLETED SUCCESSFULLY ===\n";

