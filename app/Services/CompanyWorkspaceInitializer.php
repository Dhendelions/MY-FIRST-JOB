<?php

namespace App\Services;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompanyWorkspaceInitializer
{
    public const TARGET_YEARS = [2024, 2025, 2026];

    public const STANDARD_SHEET_NAMES = [
        'Analisa' => 'Analisa',
        'Cost' => 'Cost',
        'Margin' => 'Margin',
        'pendukung' => 'Pendukung',
        'Rank' => 'Rank',
    ];

    private ?array $cachedTemplateData = null;

    public function __construct(
        private SpreadsheetFormulaReferenceRewriter $formulaReferenceRewriter,
        private SpreadsheetWorkbookCalculator $workbookCalculator
    ) {
    }

    /**
     * Run full initialization:
     * 1. Mark legacy groups
     * 2. Initialize 3 operational years (2024, 2025, 2026)
     * 3. Initialize 12 monthly workspaces per year (36 total)
     * 4. Initialize 5 canonical spreadsheets per month (180 total)
     *
     * @return array Summary of actions performed
     */
    public function initialize(): array
    {
        $owner = $this->resolveCompanyOwner();
        $ownerId = $owner?->id;

        // 1. Mark legacy groups
        $legacyCount = FisheriesGroup::whereNull('operational_year')
            ->where('is_legacy', false)
            ->update(['is_legacy' => true]);

        $initialWorkspaceCount = MonthlyWorkspace::count();
        $initialSheetCount = SpreadsheetSheet::whereNotNull('monthly_workspace_id')->count();
        $yearsCreated = 0;

        foreach (self::TARGET_YEARS as $year) {
            $existed = OperationalYear::where('year', $year)->exists();
            $this->provisionYear($year, $ownerId, true);
            if (!$existed) {
                $yearsCreated++;
            }
        }

        $finalWorkspaceCount = MonthlyWorkspace::count();
        $finalSheetCount = SpreadsheetSheet::whereNotNull('monthly_workspace_id')->count();

        return [
            'legacy_marked' => $legacyCount,
            'years_created' => $yearsCreated,
            'workspaces_created' => $finalWorkspaceCount - $initialWorkspaceCount,
            'sheets_created' => $finalSheetCount - $initialSheetCount,
            'total_years' => OperationalYear::count(),
            'total_monthly_workspaces' => $finalWorkspaceCount,
            'total_sheets_with_workspace' => $finalSheetCount,
        ];
    }

    /**
     * Dynamically provision an entire operational year with 12 monthly workspaces
     * and 60 spreadsheet records (5 standard sheets per month).
     *
     * @param int $year 4-digit calendar year
     * @param int|null $ownerId ID of the company owner
     * @param bool $allowRecovery Whether to complete an existing or partially provisioned year
     * @return OperationalYear
     * @throws \InvalidArgumentException
     */
    public function provisionYear(int $year, ?int $ownerId = null, bool $allowRecovery = true): OperationalYear
    {
        if ($year < 1000 || $year > 9999) {
            throw new \InvalidArgumentException("Format tahun tidak valid ({$year}). Harap masukkan 4 digit angka.");
        }

        if (!$ownerId) {
            $owner = $this->resolveCompanyOwner();
            $ownerId = $owner?->id;
        }

        $existing = OperationalYear::where('year', $year)->first();
        if ($existing) {
            $workspaceCount = $existing->monthlyWorkspaces()->count();
            $sheetCount = SpreadsheetSheet::whereIn('monthly_workspace_id', $existing->monthlyWorkspaces()->pluck('id'))->count();

            if ($workspaceCount === 12 && $sheetCount >= 60) {
                if (!$allowRecovery) {
                    throw new \InvalidArgumentException("Tahun operasional {$year} sudah ada.");
                }
                return $existing;
            }

            // Incomplete year recovery path
            $operationalYear = $existing;
        } else {
            $operationalYear = OperationalYear::create([
                'year' => $year,
                'is_active' => false, // Only set to true when provisioning succeeds!
                'is_archived' => false,
                'created_by' => $ownerId,
            ]);
        }

        $templateData = $this->loadTemplateData();

        DB::transaction(function () use ($operationalYear, $year, $ownerId, $templateData) {
            for ($month = 1; $month <= 12; $month++) {
                $monthName = MonthlyWorkspace::getMonthName($month);
                $groupName = "{$monthName} {$year}";

                $group = FisheriesGroup::firstOrCreate(
                    [
                        'operational_year' => $year,
                        'operational_month' => $month,
                    ],
                    [
                        'name' => $groupName,
                        'category' => 'Bulanan',
                        'location' => 'Utama',
                        'pond_count' => 5,
                        'invite_code' => FisheriesGroup::generateInviteCode('OPS'),
                        'created_by' => $ownerId,
                        'is_legacy' => false,
                    ]
                );

                $monthlyWorkspace = MonthlyWorkspace::firstOrCreate(
                    [
                        'year_id' => $operationalYear->id,
                        'month' => $month,
                    ],
                    [
                        'name' => $monthName,
                        'group_id' => $group->id,
                        'status' => 'active',
                    ]
                );

                if ($ownerId) {
                    GroupMember::firstOrCreate(
                        ['group_id' => $group->id, 'user_id' => $ownerId],
                        ['role' => 'owner', 'status' => 'Aktif', 'joined_at' => now()]
                    );
                }

                $this->attachDefaultMembers($group);

                $currentSheetCount = $group->sheets()->count();
                if ($currentSheetCount < 5) {
                    $this->instantiateTemplateForWorkspace($group, $monthlyWorkspace, $templateData);
                }
            }

            // Verify completeness before marking active
            $finalWorkspaces = $operationalYear->monthlyWorkspaces()->count();
            $finalSheets = SpreadsheetSheet::whereIn('monthly_workspace_id', $operationalYear->monthlyWorkspaces()->pluck('id'))->count();

            if ($finalWorkspaces === 12 && $finalSheets >= 60) {
                $operationalYear->update(['is_active' => true]);
            }
        });

        return $operationalYear->fresh(['monthlyWorkspaces.sheets']);
    }

    /**
     * Attach all existing active users to the group so users have workspace access.
     */
    private function attachDefaultMembers(FisheriesGroup $group): void
    {
        $users = User::all();
        foreach ($users as $user) {
            $role = ($user->isOwner() || $user->id === $group->created_by) ? 'owner' : 'admin';
            GroupMember::firstOrCreate(
                ['group_id' => $group->id, 'user_id' => $user->id],
                ['role' => $role, 'status' => 'Aktif', 'joined_at' => now()]
            );
        }
    }

    /**
     * Resolve the primary Company Owner account.
     */
    public function resolveCompanyOwner(): ?User
    {
        return User::where('role', 'owner')->first()
            ?? User::where('email', 'dadhenmandiri001@gmail.com')->first()
            ?? User::where('role_title', 'Owner')->first()
            ?? User::first();
    }

    /**
     * Pre-parse the Excel template file into in-memory array structures.
     */
    public function loadTemplateData(?string $path = null): array
    {
        if ($this->cachedTemplateData !== null) {
            return $this->cachedTemplateData;
        }

        $path ??= base_path('DATA & TEMPLATE EXCELL/Okt Analisa .xlsx');
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('File template XLSX tidak ditemukan di: ' . $path);
        }

        $workbook = IOFactory::load($path);
        $templateData = [];

        // Build sheet name mapping to standardize 'pendukung' -> 'Pendukung'
        $sheetNameMap = [];
        foreach ($workbook->getWorksheetIterator() as $ws) {
            $rawTitle = $ws->getTitle();
            $sheetNameMap[$rawTitle] = self::STANDARD_SHEET_NAMES[$rawTitle] ?? $rawTitle;
        }

        foreach ($workbook->getWorksheetIterator() as $worksheet) {
            $rawTitle = $worksheet->getTitle();
            $standardName = self::STANDARD_SHEET_NAMES[$rawTitle] ?? $rawTitle;

            $maxRow = max(1, $worksheet->getHighestRow());
            $maxColumn = max(1, Coordinate::columnIndexFromString($worksheet->getHighestColumn()));

            $defaultRowHeight = $worksheet->getDefaultRowDimension()->getRowHeight();
            if ($defaultRowHeight <= 0) $defaultRowHeight = 15;
            $defaultColumnWidth = $worksheet->getDefaultColumnDimension()->getWidth();
            if ($defaultColumnWidth <= 0) $defaultColumnWidth = 8.43;

            $columns = [];
            for ($index = 1; $index <= $maxColumn; $index++) {
                $letter = Coordinate::stringFromColumnIndex($index);
                $dim = $worksheet->getColumnDimension($letter);
                $excelWidth = $dim->getWidth();
                $columns[] = [
                    'letter' => $letter,
                    'title' => '',
                    'width' => OktAnalisaTemplateImporter::calculateReadableWidth($standardName, $index, (float)$excelWidth, (float)$defaultColumnWidth),
                    'sort_order' => $index,
                ];
            }

            $rows = [];
            for ($index = 1; $index <= $maxRow; $index++) {
                $dim = $worksheet->getRowDimension($index);
                $rows[] = [
                    'sort_order' => $index,
                    'height' => $dim->getRowHeight() > 0 ? $dim->getRowHeight() : null,
                ];
            }

            $cells = [];
            foreach ($worksheet->getCoordinates() as $coord) {
                $c = $worksheet->getCell($coord);
                $colIndex = Coordinate::columnIndexFromString($c->getColumn());
                $rowIndex = $c->getRow();

                $formula = $c->isFormula()
                    ? $this->formulaReferenceRewriter->rewrite($c->getValue(), $sheetNameMap)
                    : null;

                $val = $formula !== null
                    ? $c->getOldCalculatedValue()
                    : $this->cleanTemplateValue($c->getValue(), $c->getDataType());

                $style = $worksheet->getStyle($coord)->exportArray();
                $format = $style === [] ? [] : ['xlsx' => $style];

                if ($formula !== null && !$this->formulaReferencesCell($formula)) {
                    $format['template_static_formula'] = true;
                    $val = '';
                }

                if ($formula !== null && str_contains($formula, '[')) {
                    $format['external_reference'] = true;
                } elseif ($formula === null) {
                    $format['xlsx_data_type'] = $c->getDataType();
                }

                if ($val === null && $formula === null && $c->getXfIndex() === 0) {
                    continue;
                }

                $cells[] = [
                    'row_sort_order' => $rowIndex,
                    'col_sort_order' => $colIndex,
                    'col_letter' => Coordinate::stringFromColumnIndex($colIndex),
                    'value' => $val === null ? '' : (string) $val,
                    'formula' => $formula,
                    'format' => $format,
                ];
            }

            $identityKey = SpreadsheetSheet::identityColorKeyForName($standardName);

            $templateData[$standardName] = [
                'name' => $standardName,
                'category' => 'okt-analisa',
                'metadata' => [
                    'identity_color' => $identityKey,
                    'template_identity' => $identityKey,
                    'merged_cells' => array_values($worksheet->getMergeCells()),
                    'freeze_pane' => $worksheet->getFreezePane(),
                    'show_gridlines' => $worksheet->getShowGridlines(),
                    'default_row_height' => $defaultRowHeight,
                    'default_column_width' => $defaultColumnWidth,
                ],
                'columns' => $columns,
                'rows' => $rows,
                'cells' => $cells,
            ];
        }

        $this->cachedTemplateData = $templateData;
        return $this->cachedTemplateData;
    }

    /**
     * Instantiate the 5 template spreadsheets for a given monthly workspace.
     */
    private function instantiateTemplateForWorkspace(
        FisheriesGroup $group,
        MonthlyWorkspace $monthlyWorkspace,
        array $templateData
    ): int {
        $sheetsCreated = 0;

        DB::transaction(function () use ($group, $monthlyWorkspace, $templateData, &$sheetsCreated) {
            foreach ($templateData as $sheetName => $data) {
                // Check if sheet already exists in this group
                if ($group->sheets()->where('name', $sheetName)->exists()) {
                    continue;
                }

                $sheet = SpreadsheetSheet::create([
                    'group_id' => $group->id,
                    'monthly_workspace_id' => $monthlyWorkspace->id,
                    'name' => $sheetName,
                    'category' => $data['category'],
                    'metadata' => $data['metadata'],
                ]);

                $sheetsCreated++;

                // 1. Bulk insert columns
                $columnsToInsert = [];
                $now = now();
                foreach ($data['columns'] as $col) {
                    $columnsToInsert[] = [
                        'sheet_id' => $sheet->id,
                        'letter' => $col['letter'],
                        'title' => $col['title'],
                        'width' => $col['width'],
                        'sort_order' => $col['sort_order'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                SpreadsheetColumn::insert($columnsToInsert);

                // Fetch column IDs mapped by letter
                $columnIdMap = SpreadsheetColumn::where('sheet_id', $sheet->id)
                    ->pluck('id', 'letter')
                    ->all();

                // 2. Bulk insert rows
                $rowsToInsert = [];
                foreach ($data['rows'] as $row) {
                    $rowsToInsert[] = [
                        'sheet_id' => $sheet->id,
                        'sort_order' => $row['sort_order'],
                        'height' => $row['height'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                SpreadsheetRow::insert($rowsToInsert);

                // Fetch row IDs mapped by sort_order
                $rowIdMap = SpreadsheetRow::where('sheet_id', $sheet->id)
                    ->pluck('id', 'sort_order')
                    ->all();

                // 3. Bulk insert cells in batches of 500
                $cellsToInsert = [];
                foreach ($data['cells'] as $cell) {
                    $rowId = $rowIdMap[$cell['row_sort_order']] ?? null;
                    $colId = $columnIdMap[$cell['col_letter']] ?? null;

                    if (!$rowId || !$colId) {
                        continue;
                    }

                    $cellsToInsert[] = [
                        'row_id' => $rowId,
                        'column_id' => $colId,
                        'value' => $cell['value'],
                        'formula' => $cell['formula'],
                        'format' => json_encode($cell['format']),
                        'version' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($cellsToInsert) >= 500) {
                        SpreadsheetCell::insert($cellsToInsert);
                        $cellsToInsert = [];
                    }
                }

                if (!empty($cellsToInsert)) {
                    SpreadsheetCell::insert($cellsToInsert);
                }
            }

            // Recalculate formulas for this workspace
            $this->workbookCalculator->recalculate($group);
        });

        return $sheetsCreated;
    }

    private function cleanTemplateValue(mixed $value, string $dataType): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_numeric($value) || $dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
            return '';
        }

        return $value;
    }

    private function formulaReferencesCell(string $formula): bool
    {
        return preg_match('/\$?[A-Z]{1,3}\$?[1-9][0-9]*/i', $formula) === 1;
    }
}

