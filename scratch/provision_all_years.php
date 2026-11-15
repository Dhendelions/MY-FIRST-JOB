<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CompanyWorkspaceInitializer;
use App\Models\OperationalYear;

$initializer = app(CompanyWorkspaceInitializer::class);
$owner = $initializer->resolveCompanyOwner();
echo "Resolved Owner: " . ($owner ? $owner->name . " (ID: " . $owner->id . ")" : "None") . PHP_EOL;

$years = OperationalYear::orderBy('year')->get();
foreach ($years as $oy) {
    echo "Provisioning year: {$oy->year}...\n";
    $result = $initializer->provisionYear($oy->year, $owner?->id, true);
    echo "  Done. Monthly workspaces: " . $result->monthlyWorkspaces()->count() . PHP_EOL;
}

echo "All years successfully provisioned!\n";

