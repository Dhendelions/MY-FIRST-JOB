<?php

namespace App\Services;

use App\Models\FisheriesGroup;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class OktAnalisaTemplateImporter
{
    public function __construct(
        private SpreadsheetFormulaReferenceRewriter $formulaReferenceRewriter
    ) {
    }

    public function import(FisheriesGroup $group, ?string $path = null): void
    {
        $path ??= base_path('DATA & TEMPLATE EXCELL/Okt Analisa .xlsx');
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('File template XLSX tidak ditemukan atau tidak dapat dibaca.');
        }

        $workbook = IOFactory::load($path);

        if ($workbook->getSheetCount() > 20) {
            throw new \RuntimeException('Workbook melebihi batas 20 worksheet yang didukung.');
        }

        $usedNames = $group->sheets()->pluck('name')->all();
        $sheetNameMap = [];
        foreach ($workbook->getWorksheetIterator() as $worksheet) {
            $sheetNameMap[$worksheet->getTitle()] = $this->uniqueSheetName($worksheet->getTitle(), $usedNames);
        }

        foreach ($workbook->getWorksheetIterator() as $worksheet) {
            $this->importWorksheet($group, $worksheet, $sheetNameMap[$worksheet->getTitle()], $sheetNameMap);
        }

        app(SpreadsheetWorkbookCalculator::class)->recalculate($group);
    }

    private function importWorksheet(
        FisheriesGroup $group,
        Worksheet $worksheet,
        string $sheetName,
        array $sheetNameMap
    ): void
    {
        $maxRow = max(1, $worksheet->getHighestRow());
        $maxColumn = max(1, Coordinate::columnIndexFromString($worksheet->getHighestColumn()));
        if ($maxRow * $maxColumn > 100_000) {
            throw new \RuntimeException("Worksheet {$worksheet->getTitle()} melebihi batas 100.000 sel.");
        }

        $defaultRowHeight = $worksheet->getDefaultRowDimension()->getRowHeight();
        if ($defaultRowHeight <= 0) $defaultRowHeight = 15;
        $defaultColumnWidth = $worksheet->getDefaultColumnDimension()->getWidth();
        if ($defaultColumnWidth <= 0) $defaultColumnWidth = 8.43;

        $sheet = $group->sheets()->create([
            'name' => $sheetName,
            'category' => 'okt-analisa',
            'metadata' => [
                'identity_color' => SpreadsheetSheet::identityColorKeyForName($sheetName)
                    ?? SpreadsheetSheet::cyclicIdentityColorKey($group->sheets()->count()),
                'template_identity' => SpreadsheetSheet::identityColorKeyForName($sheetName),
                'merged_cells' => array_values($worksheet->getMergeCells()),
                'freeze_pane' => $worksheet->getFreezePane(),
                'show_gridlines' => $worksheet->getShowGridlines(),
                'default_row_height' => $defaultRowHeight,
                'default_column_width' => $defaultColumnWidth,
            ],
        ]);

        $columns = [];
        for ($index = 1; $index <= $maxColumn; $index++) {
            $letter = Coordinate::stringFromColumnIndex($index);
            $dimension = $worksheet->getColumnDimension($letter);
            $excelWidth = $dimension->getWidth();
            $columns[$letter] = $sheet->columns()->create([
                'letter' => $letter,
                'title' => '',
                'width' => self::calculateReadableWidth($sheetName, $index, (float) $excelWidth, (float) $defaultColumnWidth),
                'sort_order' => $index,
            ]);
        }

        $rows = [];
        for ($index = 1; $index <= $maxRow; $index++) {
            $dimension = $worksheet->getRowDimension($index);
            $rows[$index] = $sheet->rows()->create([
                'sort_order' => $index,
                'height' => $dimension->getRowHeight() > 0 ? $dimension->getRowHeight() : null,
            ]);
        }

        foreach ($worksheet->getCoordinates() as $coordinate) {
            $cell = $worksheet->getCell($coordinate);
            $columnIndex = Coordinate::columnIndexFromString($cell->getColumn());
            $rowIndex = $cell->getRow();
            $formula = $cell->isFormula()
                ? $this->formulaReferenceRewriter->rewrite($cell->getValue(), $sheetNameMap)
                : null;
            // This importer is only used for a newly created template
            // workspace. Keep all workbook formulas/styles, but discard
            // imported numeric sample transactions so no user receives the
            // source workbook's balances, quantities, or prices as their own.
            $value = $formula !== null
                ? $cell->getOldCalculatedValue()
                : $this->templateValue($cell->getValue(), $cell->getDataType());
            $style = $worksheet->getStyle($coordinate)->exportArray();
            $format = $style === [] ? [] : ['xlsx' => $style];

            // Formulae made solely of hard-coded source values are sample
            // transactions, not live calculations. Keep their original
            // formula for auditability/template fidelity, but give them a
            // neutral cached value so a new workspace never displays a
            // carried-over price or balance.
            if ($formula !== null && !$this->formulaReferencesCell($formula)) {
                $format['template_static_formula'] = true;
                $value = '';
            }

            if ($formula !== null && str_contains($formula, '[')) {
                $format['external_reference'] = true;
            } elseif ($formula === null) {
                $format['xlsx_data_type'] = $cell->getDataType();
            }

            if ($value === null && $formula === null && $cell->getXfIndex() === 0) {
                continue;
            }

            SpreadsheetCell::create([
                'row_id' => $rows[$rowIndex]->id,
                'column_id' => $columns[Coordinate::stringFromColumnIndex($columnIndex)]->id,
                'value' => $value === null ? '' : (string) $value,
                'formula' => $formula,
                'format' => $format,
            ]);
        }
    }

    private function uniqueSheetName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $suffix = 2;
        while (collect($usedNames)->contains(fn ($used) => strcasecmp($used, $candidate) === 0)) {
            $ending = ' (' . $suffix++ . ')';
            $candidate = Str::substr($name, 0, 31 - Str::length($ending)) . $ending;
        }

        $usedNames[] = $candidate;

        return $candidate;
    }

    private function templateValue(mixed $value, string $dataType): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Numeric and date serial cells are transaction inputs in this
        // workbook. Text is intentionally retained because it contains the
        // labels, units, and instructions that define the template structure.
        if (is_numeric($value) || $dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
            return '';
        }

        return $value;
    }

    private function formulaReferencesCell(string $formula): bool
    {
        return preg_match('/\$?[A-Z]{1,3}\$?[1-9][0-9]*/i', $formula) === 1;
    }

    public static function calculateReadableWidth(string $sheetName, int $index, float $excelWidth, float $defaultColumnWidth): int
    {
        $name = strtolower($sheetName);
        if (str_contains($name, 'cost')) {
            if ($index === 1) return 65;
            if ($index === 2) return 240;
            if ($index === 3) return 120;
            if ($index === 4) return 110;
            return 125;
        }
        if (str_contains($name, 'margin')) {
            if ($index === 1) return 130;
            if ($index === 2) return 110;
            $rem = ($index - 3) % 3;
            if ($rem === 0) return 105;
            if ($rem === 1) return 115;
            return 135;
        }
        if (str_contains($name, 'analisa') || str_contains($name, 'analysis')) {
            if ($index === 1) return 140;
            if ($index === 2) return 110;
            if (in_array($index, [3, 5, 7, 9, 11, 13, 15], true)) return 120;
            if (in_array($index, [4, 6, 8, 10, 12, 14], true)) return 95;
            return 110;
        }
        if (str_contains($name, 'pendukung') || str_contains($name, 'supporting')) {
            if ($index === 1) return 190;
            if ($index === 2) return 160;
            if ($index === 3) return 130;
            return 120;
        }
        if (str_contains($name, 'rank')) {
            if ($index === 1) return 65;
            if ($index === 2) return 180;
            return 130;
        }

        $calc = (int) round(($excelWidth > 0 ? $excelWidth : $defaultColumnWidth) * 9.5 + 16);
        return max(110, min(500, $calc));
    }
}
