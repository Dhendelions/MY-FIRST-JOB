<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FisheriesGroup;
use App\Models\SpreadsheetSheet;
use App\Models\SpreadsheetCell;

$rankSheet = SpreadsheetSheet::where('name', 'Rank')->first();
if (!$rankSheet) {
    echo "No rank sheet found\n";
    exit;
}

echo "Found Rank Sheet ID: {$rankSheet->id} in Group: {$rankSheet->group->name}\n";
$cells = SpreadsheetCell::whereHas('row', function($q) use ($rankSheet) {
    $q->where('sheet_id', $rankSheet->id);
})->with(['row', 'column'])->get();

foreach ($cells as $cell) {
    $r = $cell->row->sort_order;
    $c = $cell->column->letter;
    if (in_array($r, [1, 2, 3, 4, 5, 6, 7, 10, 11, 13])) {
        echo "Cell {$c}{$r}: val='{$cell->value}' formula='{$cell->formula}'\n";
    }
}

