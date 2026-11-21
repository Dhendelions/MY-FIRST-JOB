<?php

namespace App\Services;

use App\Models\FisheriesGroup;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class SpreadsheetWorkbookCalculator
{
    /**
     * Recalculate a complete workbook.  This remains the authoritative path
     * for imports and explicit recalculation, where PhpSpreadsheet's full
     * cross-sheet semantics are required.
     *
     * @return array<int, array{row_id:int, column_id:int, value:string, version:int}>
     */
    public function recalculate(FisheriesGroup $group): array
    {
        $hasFormulas = SpreadsheetCell::query()
            ->whereNotNull('formula')
            ->where('formula', '!=', '')
            ->whereHas('row.sheet', fn ($query) => $query->where('group_id', $group->id))
            ->exists();

        if (!$hasFormulas) {
            return [];
        }

        $sheets = $group->sheets()->with(['columns', 'rows.cells.column'])->orderBy('id')->get();
        if ($sheets->isEmpty()) {
            return [];
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
                'row_id' => $cell->row_id,
                'column_id' => $cell->column_id,
                'version' => (int) $cell->version,
            ];
        }

        $changed = [];
        if ($updates !== []) {
            $changed = DB::transaction(function () use ($updates) {
                $changed = [];
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
                    $changed[] = [
                        'row_id' => $update['row_id'],
                        'column_id' => $update['column_id'],
                        'value' => $storedValue,
                        'version' => $update['version'] + 1,
                    ];
                }

                return $changed;
            });
        }

        return $changed;
    }

    /**
     * Avoid constructing an in-memory workbook for an edit that cannot affect
     * a formula. Unknown/dynamic formulas deliberately fall back to the full
     * calculation path, preserving accounting correctness over optimisation.
     *
     * @return array<int, array{row_id:int, column_id:int, value:string, version:int}>
     */
    public function recalculateForCellChange(
        FisheriesGroup $group,
        SpreadsheetSheet $changedSheet,
        string $coordinate,
        bool $force = false,
    ): array {
        $formulaCells = SpreadsheetCell::query()
            ->select('spreadsheet_cells.formula')
            ->join('spreadsheet_rows', 'spreadsheet_rows.id', '=', 'spreadsheet_cells.row_id')
            ->join('spreadsheet_sheets', 'spreadsheet_sheets.id', '=', 'spreadsheet_rows.sheet_id')
            ->where('spreadsheet_sheets.group_id', $group->id)
            ->whereNotNull('spreadsheet_cells.formula')
            ->where('spreadsheet_cells.formula', '!=', '')
            ->get();

        if ($formulaCells->isEmpty()) {
            return [];
        }

        if ($force || $formulaCells->contains(fn ($cell) => $this->formulaMayDependOn(
            (string) $cell->formula,
            $changedSheet->name,
            $coordinate
        ))) {
            return $this->recalculate($group);
        }

        return [];
    }

    private function formulaMayDependOn(string $formula, string $sheetName, string $coordinate): bool
    {
        $formula = strtoupper($formula);
        $coordinate = strtoupper(str_replace('$', '', $coordinate));
        $normalizedSheet = strtoupper($sheetName);

        // Volatile and indirect references cannot safely be dependency-indexed.
        if (preg_match('/\b(INDIRECT|OFFSET|INDEX|MATCH)\s*\(/', $formula)) {
            return true;
        }

        // An explicit reference to this sheet is always potentially dependent.
        $quotedName = str_replace("'", "''", $normalizedSheet);
        $sheetPrefix = "(?:'" . preg_quote($quotedName, '/') . "'|" . preg_quote($normalizedSheet, '/') . ')';
        if (preg_match_all('/(?:(\'[^\']+\'|[A-Z0-9_ ]+)!)?(\$?[A-Z]{1,3}\$?\d+)(?::(\$?[A-Z]{1,3}\$?\d+))?/', $formula, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $prefix = $match[1] ?? '';
                if ($prefix !== '' && !preg_match('/^' . $sheetPrefix . '$/', $prefix)) {
                    continue;
                }
                $start = strtoupper(str_replace('$', '', $match[2]));
                $end = isset($match[3]) && $match[3] !== '' ? strtoupper(str_replace('$', '', $match[3])) : $start;
                if ($this->coordinateInRange($coordinate, $start, $end)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function coordinateInRange(string $target, string $start, string $end): bool
    {
        if (!preg_match('/^([A-Z]+)(\d+)$/', $target, $targetParts)
            || !preg_match('/^([A-Z]+)(\d+)$/', $start, $startParts)
            || !preg_match('/^([A-Z]+)(\d+)$/', $end, $endParts)) {
            return true;
        }

        $targetColumn = SpreadsheetFormulaEngine::columnLetterToIndex($targetParts[1]);
        $startColumn = SpreadsheetFormulaEngine::columnLetterToIndex($startParts[1]);
        $endColumn = SpreadsheetFormulaEngine::columnLetterToIndex($endParts[1]);

        return $targetColumn >= min($startColumn, $endColumn)
            && $targetColumn <= max($startColumn, $endColumn)
            && (int) $targetParts[2] >= min((int) $startParts[2], (int) $endParts[2])
            && (int) $targetParts[2] <= max((int) $startParts[2], (int) $endParts[2]);
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
