<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$models = [
    \App\Models\User::class,
    \App\Models\FisheriesGroup::class,
    \App\Models\GroupMember::class,
    \App\Models\SpreadsheetSheet::class,
    \App\Models\SpreadsheetColumn::class,
    \App\Models\SpreadsheetRow::class,
    \App\Models\SpreadsheetCell::class,
    \App\Models\SpreadsheetActivity::class,
    \App\Models\Friendship::class,
    \App\Models\Notification::class,
    \App\Models\OperationalYear::class,
    \App\Models\MonthlyWorkspace::class,
    \App\Models\CompanyActivityLog::class,
];

echo "=== ELOQUENT MODELS & TABLE MAPPING ===\n";
foreach ($models as $class) {
    $instance = new $class();
    echo sprintf(
        "%-25s => Table: %-25s | PK: %s\n",
        class_basename($class),
        $instance->getTable(),
        $instance->getKeyName()
    );
    echo "  Fillable: " . implode(', ', $instance->getFillable()) . "\n";
    echo "  Casts: " . json_encode($instance->getCasts()) . "\n";
    echo "\n";
}

