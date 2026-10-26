<?php

namespace App\Services;

use App\Models\FisheriesGroup;
use App\Models\SpreadsheetCell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class SpreadsheetWorkbookCalculator
{
    public function recalculate(FisheriesGroup $group): void
    {
        $hasFormulas = SpreadsheetCell::query()
            ->whereNotNull('formula')
            ->where('formula', '!=', '')
            ->whereHas('row.sheet', fn ($query) => $query->where('group_id', $group->id))
            ->exists();

        if (!$hasFormulas) {
            return;
        }

        $sheets = $group->sheets()->with(['columns', 'rows.cells.column'])->orderBy('id')->get();
        if ($sheets->isEmpty()) {
            return;
        }

        $workbook = new Spreadsheet();
        $workbook->removeSheetByIndex(0);
        $formulaCells = [];

        foreach ($sheets as $sheet) {
            $worksheet = $workbook->createSheet();
            $worksheet->setTitle($sheet->name);

            foreach ($sheet->rows as $row) {
                foreach ($row->cells as $cell) {
                    $letter = $cell->column->letter;
                    if (preg_match('/^C(\d+)$/', $letter, $cm)) {
                        $letter = SpreadsheetFormulaEngine::indexToColumnLetter((int) $cm[1]);
                    }
                    $coordinate = $letter . $row->sort_order;
                    if ($cell->formula) {
                        if (!empty($cell->format['template_static_formula'])) {
                            // Preserve the formula in storage while treating a
                            // template's hard-coded sample expression as a
                            // neutral zero for all dependent calculations.
                            $worksheet->setCellValueExplicit($coordinate, 0, DataType::TYPE_NUMERIC);
                            continue;
                        }
                        if (str_contains($cell->formula, '[')) {
                            $this->setCachedValue($worksheet, $coordinate, $cell);
                            continue;
                        }

                        $worksheet->setCellValue($coordinate, $cell->formula);
                        $formulaCells[] = [$worksheet, $coordinate, $cell];
                        continue;
                    }

                    $this->setStoredValue($worksheet, $coordinate, $cell);
                }
            }
        }

        \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($workbook)->clearCalculationCache();

        $updates = [];
        foreach ($formulaCells as [$worksheet, $coordinate, $cell]) {
            try {
                $calcVal = $worksheet->getCell($coordinate)->getCalculatedValue();
                if ($calcVal === '#DIV/0!' || (is_string($calcVal) && str_contains($calcVal, '#DIV/0!'))) {
                    $calcVal = 0;
                }
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'Division by zero') || $e instanceof \DivisionByZeroError) {
                    $calcVal = 0;
                } else {
                    $calcVal = '#VALUE!';
                }
            }
            $updates[$cell->id] = [
                'original' => (string) ($cell->value ?? ''),
                'value' => $calcVal,
            ];
        }

        if ($updates !== []) {
            DB::transaction(function () use ($updates) {
                foreach ($updates as $id => $update) {
                    $storedValue = $update['value'] === null ? '' : (string) $update['value'];
                    if ($update['original'] === $storedValue) {
                        continue;
                    }

                    SpreadsheetCell::whereKey($id)->update([
                        'value' => $storedValue,
                        'version' => DB::raw('version + 1'),
                        'updated_at' => now(),
                    ]);
                }
            });
        }
    }

    private function setStoredValue(Worksheet $worksheet, string $coordinate, SpreadsheetCell $cell): void
    {
        $value = $cell->value ?? '';
        if ($value === '' || $value === '-' || $value === null) {
            // In Excel arithmetic and financial formulas, empty cells and "-" (accounting zero)
            // evaluate to numeric 0. Setting explicit 0 prevents #VALUE! in operations like K$4%
            $worksheet->setCellValueExplicit($coordinate, 0, DataType::TYPE_NUMERIC);
            return;
        }

        $dataType = $cell->format['xlsx_data_type'] ?? null;

        if ($dataType === DataType::TYPE_NUMERIC || ($dataType === null && is_numeric($value))) {
            $worksheet->setCellValueExplicit($coordinate, (float) $value, DataType::TYPE_NUMERIC);
            return;
        }

        $extracted = SpreadsheetFormulaEngine::extractNumber($value);
        if ($extracted !== null) {
            $worksheet->setCellValueExplicit($coordinate, $extracted, DataType::TYPE_NUMERIC);
            return;
        }

        $worksheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
    }

    private function setCachedValue(Worksheet $worksheet, string $coordinate, SpreadsheetCell $cell): void
    {
        $value = $cell->value ?? '';
        if ($value === '' || $value === '-' || $value === null) {
            $worksheet->setCellValueExplicit($coordinate, 0, DataType::TYPE_NUMERIC);
            return;
        }

        if ($value !== '' && is_numeric($value)) {
            $worksheet->setCellValueExplicit($coordinate, (float) $value, DataType::TYPE_NUMERIC);
            return;
        }

        $extracted = SpreadsheetFormulaEngine::extractNumber($value);
        if ($extracted !== null) {
            $worksheet->setCellValueExplicit($coordinate, $extracted, DataType::TYPE_NUMERIC);
            return;
        }

        $worksheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
    }
}
