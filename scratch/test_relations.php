<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$modelsWithRelations = [
    \App\Models\User::class => ['memberships', 'createdGroups', 'friendships', 'receivedFriendships', 'notifications', 'unreadNotifications', 'groups'],
    \App\Models\FisheriesGroup::class => ['creator', 'members', 'activeMembers', 'users', 'sheets', 'monthlyWorkspace'],
    \App\Models\GroupMember::class => ['group', 'user'],
    \App\Models\OperationalYear::class => ['monthlyWorkspaces', 'creator'],
    \App\Models\MonthlyWorkspace::class => ['year', 'group', 'sheets'],
    \App\Models\SpreadsheetSheet::class => ['group', 'monthlyWorkspace', 'columns', 'rows', 'cells'],
    \App\Models\SpreadsheetColumn::class => ['sheet', 'cells'],
    \App\Models\SpreadsheetRow::class => ['sheet', 'cells'],
    \App\Models\SpreadsheetCell::class => ['row', 'column'],
    \App\Models\SpreadsheetActivity::class => ['group', 'sheet', 'user'],
    \App\Models\Friendship::class => ['user', 'friend'],
    \App\Models\Notification::class => ['user'],
    \App\Models\CompanyActivityLog::class => ['user'],
];

echo "=== TESTING ELOQUENT RELATIONSHIP DEFINITIONS ===\n";
$errors = 0;
foreach ($modelsWithRelations as $modelClass => $relations) {
    $instance = new $modelClass();
    foreach ($relations as $relation) {
        if (!method_exists($instance, $relation)) {
            echo "ERROR: {$modelClass} is missing relationship method '{$relation}()'!\n";
            $errors++;
            continue;
        }
        try {
            $rel = $instance->$relation();
            if (!($rel instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
                echo "ERROR: {$modelClass}::{$relation}() does not return an Eloquent Relation instance!\n";
                $errors++;
            }
        } catch (\Throwable $e) {
            echo "EXCEPTION in {$modelClass}::{$relation}(): " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

if ($errors === 0) {
    echo "All relationship methods are properly defined and valid!\n";
} else {
    echo "Found {$errors} relationship issues.\n";
}

