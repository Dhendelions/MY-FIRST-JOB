<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SpreadsheetSheet;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetCell;
use App\Models\GroupMember;
use App\Models\SpreadsheetActivity;
use App\Services\SpreadsheetActivityRecorder;
use App\Services\SpreadsheetFormulaEngine;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SpreadsheetWorksheet extends Component
{
    public int $sheetId;
    public ?SpreadsheetSheet $sheet = null;

    // Grid data structures
    public array $columns = [];
    public array $rows = [];
    /** Rendered canvas extends the persisted grid without creating empty cells. */
    public array $gridColumns = [];
    public array $gridRows = [];
    public int $gridColumnLimit = 26;
    public int $gridRowLimit = 100;
    public array $cellsData = []; // key: "{rowId}_{colId}" => raw value or calculated
    public array $cellsDisplay = [];
    public array $cellsFormula = []; // key: "{rowId}_{colId}" => formula string
    public array $cellsFormat = []; // key: "{rowId}_{colId}" => format options array
    public array $cellsVersions = [];
    public array $cellsStyleCss = [];
    public array $mergedCells = [];
    public array $mergedCoveredCells = [];
    public int $freezeTopRowCount = 0;
    public int $freezeLeftColumnCount = 0;
    public float $defaultRowHeightPx = 32;
    public array $frozenRowTopOffsets = [];
    public array $frozenColumnLeftOffsets = [];
    /** Rows that remain visible below the column headers (used by Analisa). */
    public array $fixedTopRowOffsets = [];

    // Active Selection & Formula Bar
    public string $activeCellCoord = 'A1';
    public ?int $activeRowId = null;
    public ?int $activeColumnId = null;
    public int $activeCellVersion = 0;
    public string $formulaValue = '';
    public bool $isEditingCell = false;

    // Search query & Find-Replace
    public string $searchQuery = '';
    public string $findText = '';
    public string $replaceText = '';
    public bool $showFindReplaceModal = false;

    // Deletion modal state for non-empty columns
    public bool $showDeleteColumnModal = false;
    public ?int $columnPendingDeleteId = null;
    public string $columnPendingDeleteLetter = '';
    public int $columnPendingDeleteCount = 0;

    // Financial calculations
    public float $sumTotal = 0.0;
    public float $averageTotal = 0.0;
    public int $numericCellsCount = 0;
    public int $filledCellsCount = 0;

    // Auto-save badge indicator
    public string $saveStatus = 'Tersimpan';

    // Undo / Redo history stacks
    public array $undoStack = [];
    public array $redoStack = [];
    public array $recentActivities = [];

    protected $listeners = [
        'cellUpdated' => 'updateCell',
        'tsvPasted' => 'pasteTsvData',
        'undoTriggered' => 'undo',
        'redoTriggered' => 'redo',
    ];

    public function mount(SpreadsheetSheet $sheet)
    {
        $this->sheetId = $sheet->id;
        $this->sheet = $sheet;
        $this->authorizeAccess();
        $this->loadSheetData();
    }

    /**
     * Verify user authorization before performing any read or write operation.
     */
    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (!$this->sheet) {
            $this->sheet = SpreadsheetSheet::with('group')->find($this->sheetId);
        }

        if (!$this->sheet || !$this->sheet->group) {
            abort(404, 'Lembar kerja tidak ditemukan.');
        }

        if (!$this->sheet->group->isMember($user->id)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang pada workspace ini.');
        }

        $userRole = $this->sheet->group->getMemberRole($user->id);
        if ($this->sheet->isRestrictedForRole($userRole)) {
            abort(403, 'Akses dibatasi: Hanya Leader yang memiliki izin untuk mengakses lembar kerja ' . $this->sheet->name . '.');
        }

        if ($userRole === 'admin' && !$this->sheet->group->isAdminSheetVerifiedForSession($user->id, $this->sheet->id)) {
            abort(403, 'Lembar kerja ini memerlukan verifikasi password akun admin.');
        }

        if ($userRole !== 'admin' && !$this->sheet->group->isUnlockedForSession()) {
            abort(403, 'Lembar kerja ini harus dibuka dengan password terlebih dahulu.');
        }
    }

    /**
     * Load all columns, rows, and cells into memory.
     */
    public function loadSheetData()
    {
        $this->authorizeAccess();

        $this->sheet = SpreadsheetSheet::with([
            'columns' => fn($q) => $q->orderBy('sort_order'),
            'rows' => fn($q) => $q->orderBy('sort_order'),
        ])->find($this->sheetId);

        if (!$this->sheet) {
            return;
        }

        $this->columns = $this->sheet->columns->toArray();
        $this->rows = $this->sheet->rows->toArray();

        foreach ($this->columns as &$col) {
            $expectedLetter = SpreadsheetFormulaEngine::indexToColumnLetter($col['sort_order']);
            if ($col['letter'] !== $expectedLetter) {
                SpreadsheetColumn::whereKey($col['id'])->update(['letter' => $expectedLetter]);
                $col['letter'] = $expectedLetter;
            }
        }
        unset($col);

        $this->ensureSupportingFirstColumnWidth();
        $this->buildRenderedGrid();
        $defaultHeight = (float) ($this->sheet->metadata['default_row_height'] ?? 24);
        $this->defaultRowHeightPx = $defaultHeight * 1.333333;
        $this->loadFreezePaneOffsets();

        // Fetch all cells in one query
        $rowIds = array_column($this->rows, 'id');
        $cells = SpreadsheetCell::whereIn('row_id', $rowIds)->get();

        $this->cellsData = [];
        $this->cellsDisplay = [];
        $this->cellsFormula = [];
        $this->cellsFormat = [];
        $this->cellsVersions = [];
        $this->cellsStyleCss = [];

        foreach ($cells as $cell) {
            $key = $cell->row_id . '_' . $cell->column_id;
            $this->cellsData[$key] = $cell->value ?? '';
            $this->cellsFormula[$key] = $cell->formula ?? '';
            $this->cellsFormat[$key] = $cell->format ?? [];
            $this->cellsVersions[$key] = (int) ($cell->version ?? 1);
            $this->updateCellPresentation($key, $cell->value ?? '', $cell->format ?? []);
        }

        $this->loadAnalisaFixedRows();

        $this->loadMergedCells();

        // Set initial active cell
        if (!empty($this->rows) && !empty($this->columns)) {
            if (!$this->activeRowId || !$this->activeColumnId) {
                $firstRow = $this->rows[0];
                $firstCol = $this->columns[0];
                $this->activeRowId = $firstRow['id'];
                $this->activeColumnId = $firstCol['id'];
                $this->activeCellCoord = $firstCol['letter'] . ($firstRow['sort_order'] ?? 1);
                $key = $firstRow['id'] . '_' . $firstCol['id'];
                $this->activeCellVersion = $this->cellsVersions[$key] ?? 0;
                $this->formulaValue = !empty($this->cellsFormula[$key]) ? $this->cellsFormula[$key] : ($this->cellsData[$key] ?? '');
            }
        }

        $this->recalculateSummary();
        $this->loadRecentActivities();
    }

    public function switchSheet(int $sheetId): void
    {
        $this->authorizeAccess();

        $targetSheet = SpreadsheetSheet::with('group')
            ->where('group_id', $this->sheet->group_id)
            ->findOrFail($sheetId);

        $this->sheet = $targetSheet;
        $this->sheetId = $targetSheet->id;
        $this->activeCellCoord = 'A1';
        $this->activeRowId = null;
        $this->activeColumnId = null;
        $this->activeCellVersion = 0;
        $this->formulaValue = '';
        $this->isEditingCell = false;
        $this->undoStack = [];
        $this->redoStack = [];
        $this->showDeleteColumnModal = false;
        $this->columnPendingDeleteId = null;
        $this->columnPendingDeleteLetter = '';
        $this->columnPendingDeleteCount = 0;
        $this->gridColumnLimit = 26;
        $this->gridRowLimit = 100;

        $this->authorizeAccess();
        $this->loadSheetData();

        $this->dispatch(
            'worksheet-sheet-switched',
            sheetId: $this->sheetId,
            coord: $this->activeCellCoord,
            rowId: $this->activeRowId,
            colId: $this->activeColumnId,
            rowOrder: $this->rows[0]['sort_order'] ?? 1,
            colOrder: $this->columns[0]['sort_order'] ?? 1,
            formula: $this->formulaValue,
            url: route('group.workspace', [$this->sheet->group_id, $this->sheetId]),
        );
    }

    /**
     * Build an Excel-like canvas independently from persisted cell records.
     * Rows and columns become real database records only when the user uses one.
     */
    private function buildRenderedGrid(): void
    {
        $persistedColumns = collect($this->columns)->keyBy('sort_order');
        $persistedRows = collect($this->rows)->keyBy('sort_order');
        $maxColumn = max(1, (int) (collect($this->columns)->max('sort_order') ?? 0));
        $maxRow = max(1, (int) (collect($this->rows)->max('sort_order') ?? 0));
        $this->gridColumnLimit = max(26, $this->gridColumnLimit, $maxColumn);
        $this->gridRowLimit = max(100, $this->gridRowLimit, $maxRow);
        $this->gridColumns = [];
        $this->gridRows = [];

        for ($order = 1; $order <= $this->gridColumnLimit; $order++) {
            $column = $persistedColumns->get($order);
            $this->gridColumns[] = $column ?: [
                'id' => null,
                'letter' => SpreadsheetFormulaEngine::indexToColumnLetter($order),
                'title' => '',
                'width' => $this->defaultCanvasColumnWidth($order),
                'sort_order' => $order,
            ];
        }

        for ($order = 1; $order <= $this->gridRowLimit; $order++) {
            $row = $persistedRows->get($order);
            $this->gridRows[] = $row ?: [
                'id' => null,
                'sort_order' => $order,
                'height' => null,
            ];
        }
    }

    /** A spacious default grid with content-aware sizing. */
    private function defaultCanvasColumnWidth(int $order): int
    {
        $name = (string) ($this->sheet->name ?? '');
        return \App\Services\OktAnalisaTemplateImporter::calculateReadableWidth($name, $order, -1, 10);
    }

    private function ensureSupportingFirstColumnWidth(): void
    {
        $name = mb_strtolower((string) ($this->sheet->name ?? ''));
        if ($this->columns === [] || $this->columns[0]['sort_order'] !== 1
            || (!str_contains($name, 'pendukung') && !str_contains($name, 'supporting'))
            || (int) $this->columns[0]['width'] >= 140) {
            return;
        }

        SpreadsheetColumn::whereKey($this->columns[0]['id'])->update(['width' => 140]);
        $this->columns[0]['width'] = 140;
    }

    /** Materialize only the row/column needed for an interaction with a virtual cell. */
    public function ensureGridCell(int $rowOrder, int $columnOrder): void
    {
        $this->authorizeAccess();
        if ($rowOrder < 1 || $columnOrder < 1 || $rowOrder > 10000 || $columnOrder > 702) return;

        DB::transaction(function () use ($rowOrder, $columnOrder) {
            $this->ensureRowExists($rowOrder);
            $this->ensureColumnExists($columnOrder);
        });

        $coord = SpreadsheetFormulaEngine::indexToColumnLetter($columnOrder) . $rowOrder;
        $this->loadSheetData();
        $row = collect($this->rows)->firstWhere('sort_order', $rowOrder);
        $column = collect($this->columns)->firstWhere('sort_order', $columnOrder);
        $this->activeRowId = $row['id'] ?? null;
        $this->activeColumnId = $column['id'] ?? null;
        $this->activeCellCoord = $coord;
        $key = ($row['id'] ?? '') . '_' . ($column['id'] ?? '');
        $this->activeCellVersion = $this->cellsVersions[$key] ?? 0;
        $this->formulaValue = $this->cellsFormula[$key] ?? ($this->cellsData[$key] ?? '');
        $this->dispatch('grid-cell-materialized', coord: $coord);
    }

    /** Persist an intentional resize of an empty projected column. */
    public function ensureColumn(int $columnOrder, int $width): void
    {
        $this->authorizeAccess();
        if ($columnOrder < 1 || $columnOrder > 702) return;
        $column = $this->ensureColumnExists($columnOrder);
        $column->update(['width' => max(64, min(900, $width))]);
        $this->loadSheetData();
    }

    /** Persist an intentional resize of an empty projected row. */
    public function ensureRow(int $rowOrder, int $height): void
    {
        $this->authorizeAccess();
        if ($rowOrder < 1 || $rowOrder > 10000) return;
        $row = $this->ensureRowExists($rowOrder);
        $row->update(['height' => max(15, min(300, $height / 1.333333))]);
        $this->loadSheetData();
    }

    /** Grow only the rendered canvas; this never creates data records. */
    public function extendGrid(string $axis): void
    {
        if ($axis === 'row') $this->gridRowLimit = min(10000, $this->gridRowLimit + 25);
        if ($axis === 'column') $this->gridColumnLimit = min(702, $this->gridColumnLimit + 5);
        $this->buildRenderedGrid();
    }

    private function ensureColumnExists(int $order): SpreadsheetColumn
    {
        return SpreadsheetColumn::firstOrCreate(
            ['sheet_id' => $this->sheetId, 'sort_order' => $order],
            [
                'letter' => SpreadsheetFormulaEngine::indexToColumnLetter($order),
                'title' => '',
                'width' => $this->defaultCanvasColumnWidth($order),
            ]
        );
    }

    private function ensureRowExists(int $order): SpreadsheetRow
    {
        return SpreadsheetRow::firstOrCreate(
            ['sheet_id' => $this->sheetId, 'sort_order' => $order],
            ['height' => null]
        );
    }

    public function evaluateAllFormulas()
    {
        $this->authorizeAccess();
        app(\App\Services\SpreadsheetWorkbookCalculator::class)->recalculate($this->sheet->group);

        $rowIds = array_column($this->rows, 'id');
        foreach (SpreadsheetCell::whereIn('row_id', $rowIds)->get() as $cell) {
            $key = $cell->row_id . '_' . $cell->column_id;
            $this->cellsData[$key] = $cell->value ?? '';
            $this->cellsFormula[$key] = $cell->formula ?? '';
            $this->cellsVersions[$key] = (int) ($cell->version ?? 1);
            // Prefer in-memory format (may include recent formatCell changes not yet fetched);
            // fall back to DB format if key was never populated in memory.
            if (!isset($this->cellsFormat[$key])) {
                $this->cellsFormat[$key] = $cell->format ?? [];
            }
            $this->updateCellPresentation($key, $cell->value ?? '', $this->cellsFormat[$key]);
            if ($this->activeRowId === $cell->row_id && $this->activeColumnId === $cell->column_id) {
                $this->activeCellVersion = (int) ($cell->version ?? 1);
                $this->formulaValue = $cell->formula ?: ($cell->value ?? '');
            }
        }
    }


    /**
     * Set the currently active cell.
     */
    public function selectCell(int $rowId, int $colId, string $coord)
    {
        $this->activeRowId = $rowId;
        $this->activeColumnId = $colId;
        $this->activeCellCoord = $coord;
        $this->isEditingCell = false;
        $key = "{$rowId}_{$colId}";
        $this->activeCellVersion = $this->cellsVersions[$key] ?? 0;
        $this->formulaValue = !empty($this->cellsFormula[$key]) ? $this->cellsFormula[$key] : ($this->cellsData[$key] ?? '');
    }

    public function startCellEdit(): void
    {
        $this->isEditingCell = true;
    }

    public function cancelCellEdit(): void
    {
        $this->isEditingCell = false;
    }

    public function finishCellEdit(int $rowId, int $colId, string $value, string $originalValue): void
    {
        $this->isEditingCell = false;
        if ($value !== $originalValue) {
            $this->updateCell($rowId, $colId, $value, $this->cellsVersions["{$rowId}_{$colId}"] ?? 0);
        }
    }

    public function beginFormulaEdit(): void
    {
        $this->isEditingCell = true;
    }

    public function finishFormulaEdit(): void
    {
        $this->isEditingCell = false;
    }

    /**
     * Update active cell from formula bar input.
     */
    public function updatedFormulaValue($value)
    {
        // The formula bar is deliberately a draft field.  Persisting from this
        // lifecycle hook would save one activity (and potentially one formula)
        // per keystroke when it is bound by a client.  The explicit Enter action
        // calls updateCell(), which gives cell editing and the formula bar one
        // commit path and makes Escape safe.
        $this->isEditingCell = true;
    }

    /**
     * Update a specific cell value with formula evaluation, history, and persistent DB save.
     */
    public function updateCell(int $rowId, int $colId, ?string $value, ?int $expectedVersion = null)
    {
        $this->authorizeAccess();
        $this->assertCellBelongsToSheet($rowId, $colId);

        $key = "{$rowId}_{$colId}";
        $raw = $value !== null ? trim($value) : '';

        // Validate formula syntax if raw begins with '='
        if (str_starts_with($raw, '=')) {
            $syntaxError = SpreadsheetFormulaEngine::validateFormulaSyntax($raw);
            if ($syntaxError !== null) {
                $this->dispatch('toast', message: $syntaxError, type: 'error');
                $this->saveStatus = 'Formula tidak valid';
                return;
            }
        }

        $expectedVersion ??= $this->cellsVersions[$key] ?? 0;
        $saveResult = DB::transaction(function () use ($rowId, $colId, $key, $raw, $expectedVersion) {
            $currentCell = SpreadsheetCell::where('row_id', $rowId)
                ->where('column_id', $colId)
                ->lockForUpdate()
                ->first();
            $currentVersion = (int) ($currentCell?->version ?? 0);
            $oldVal = $currentCell?->value ?? ($this->cellsData[$key] ?? '');
            $oldFormula = $currentCell?->formula ?? ($this->cellsFormula[$key] ?? '');
            $unchanged = $oldFormula !== ''
                ? $oldFormula === $raw
                : (string) $oldVal === $raw;

            if ($unchanged) {
                return compact('currentVersion', 'oldVal', 'oldFormula') + [
                    'conflict' => false,
                    'unchanged' => true,
                    'formula' => $oldFormula ?: null,
                    'displayVal' => $oldVal,
                ];
            }

            // A stale editor must not silently replace a value saved by another
            // member.  The caller refreshes its local state and can retry.
            if ($expectedVersion > 0 && $currentVersion !== $expectedVersion) {
                return compact('currentVersion', 'oldVal', 'oldFormula') + [
                    'conflict' => true,
                    'unchanged' => false,
                    'rejected' => true,
                    'formula' => $oldFormula ?: null,
                    'displayVal' => $oldVal,
                ];
            }

            $formula = str_starts_with($raw, '=') ? $raw : null;
            $displayVal = $formula ? $oldVal : $raw;
            $currentVersion++;
            SpreadsheetCell::updateOrCreate(
                ['row_id' => $rowId, 'column_id' => $colId],
                ['value' => $displayVal, 'formula' => $formula, 'version' => $currentVersion]
            );
            $row = SpreadsheetRow::findOrFail($rowId);
            $column = SpreadsheetColumn::findOrFail($colId);
            app(SpreadsheetActivityRecorder::class)->record(
                Auth::user(),
                $this->sheet,
                $formula ? 'formula_changed' : 'cell_changed',
                $column->letter . $row->sort_order,
                $oldFormula ?: $oldVal,
                $raw,
                [
                    'old_formula' => $oldFormula,
                    'new_formula' => $formula,
                    'old_value' => $oldVal,
                    'new_value' => $displayVal,
                ]
            );

            return compact('currentVersion', 'oldVal', 'oldFormula', 'formula', 'displayVal') + [
                'conflict' => $expectedVersion > 0 && (int) ($currentCell?->version ?? 0) !== $expectedVersion,
                'unchanged' => false,
                'rejected' => false,
            ];
        });

        if ($saveResult['unchanged']) {
            $this->isEditingCell = false;
            $this->skipRender();
            return;
        }

        if ($saveResult['rejected'] ?? false) {
            $this->isEditingCell = false;
            $this->syncChanges();
            $this->saveStatus = 'Konflik: data diperbarui';
            $this->dispatch('toast', message: 'Sel sudah diubah pengguna lain. Nilai terbaru dimuat; silakan tinjau lalu ulangi perubahan.', type: 'warning');
            return;
        }

        $currentVersion = $saveResult['currentVersion'];
        $oldVal = $saveResult['oldVal'];
        $oldFormula = $saveResult['oldFormula'];
        $formula = $saveResult['formula'];
        $displayVal = $saveResult['displayVal'];
        $conflict = $saveResult['conflict'];

        if ($oldVal !== $raw && $oldFormula !== $raw) {
            $this->undoStack[] = [
                'row_id' => $rowId,
                'col_id' => $colId,
                'coord' => $this->activeCellCoord,
                'old_val' => $oldVal,
                'old_formula' => $oldFormula,
                'new_val' => $raw,
                'new_formula' => $formula,
            ];
            // Clear redo on new edit
            $this->redoStack = [];
        }

        $this->cellsData[$key] = $displayVal;
        $this->cellsFormula[$key] = $formula ?? '';

        $this->cellsVersions[$key] = $currentVersion;
        $this->isEditingCell = false;

        if ($this->activeRowId === $rowId && $this->activeColumnId === $colId) {
            $this->formulaValue = $formula ?? $displayVal;
            $this->activeCellVersion = $currentVersion;
        }

        $beforeData = $this->cellsData;
        $beforeDisplay = $this->cellsDisplay;

        $this->evaluateAllFormulas();
        $this->recalculateSummary();
        $this->loadRecentActivities();
        $this->dispatch('refreshActivityHistory');

        $changedCells = [];
        $rowMap = collect($this->rows)->pluck('sort_order', 'id');
        $colMap = collect($this->columns)->pluck('letter', 'id');

        foreach ($this->cellsData as $k => $val) {
            if (($beforeData[$k] ?? null) !== $val || ($beforeDisplay[$k] ?? null) !== ($this->cellsDisplay[$k] ?? null)) {
                [$rId, $cId] = explode('_', $k);
                $cCoord = ($colMap[$cId] ?? '') . ($rowMap[$rId] ?? '');
                if ($cCoord) {
                    $changedCells[$cCoord] = [
                        'val' => $val,
                        'formula' => $this->cellsFormula[$k] ?? '',
                        'display' => $this->cellsDisplay[$k] ?? $val,
                        'version' => $this->cellsVersions[$k] ?? 1,
                    ];
                }
            }
        }

        $rowObj = SpreadsheetRow::find($rowId);
        $colObj = SpreadsheetColumn::find($colId);
        $savedCoord = ($colObj?->letter ?? '') . ($rowObj?->sort_order ?? '');
        $saveTime = date('H:i:s');

        $this->saveStatus = $conflict ? 'Konflik: perubahan terakhir diterapkan' : 'Tersimpan ' . $saveTime;

        // Named event parameters are exposed as a stable CustomEvent detail by
        // Livewire. Passing one positional array here made the browser receive
        // a nested payload, so it could not advance its optimistic cell version.
        $this->dispatch(
            'cell-updated-confirmed',
            coord: $savedCoord,
            rowId: $rowId,
            colId: $colId,
            version: $currentVersion,
            val: $this->cellsData[$key] ?? $displayVal,
            formula: $this->cellsFormula[$key] ?? ($formula ?? ''),
            display: $this->cellsDisplay[$key] ?? ($this->cellsData[$key] ?? $displayVal),
            changedCells: $changedCells,
            saveTime: $saveTime,
        );

        $this->dispatch(
            'toast',
            message: $conflict
                ? 'Sel diubah pengguna lain. Strategi konflik last-write-wins diterapkan dan perubahan Anda tersimpan.'
                : 'Sel tersimpan ke database',
            type: $conflict ? 'warning' : 'info'
        );

        $this->skipRender();
    }

    public function syncChanges(): void
    {
        $this->authorizeAccess();
        if ($this->isEditingCell) {
            return;
        }

        $freshColumns = $this->sheet->columns()->orderBy('sort_order')
            ->get(['id', 'letter', 'title', 'width', 'sort_order'])
            ->toArray();
        $currentColumns = array_map(
            fn ($column) => array_intersect_key($column, array_flip(['id', 'letter', 'title', 'width', 'sort_order'])),
            $this->columns
        );
        $freshRows = $this->sheet->rows()->orderBy('sort_order')
            ->get(['id', 'sort_order', 'height'])
            ->toArray();
        $currentRows = array_map(
            fn ($row) => array_intersect_key($row, array_flip(['id', 'sort_order', 'height'])),
            $this->rows
        );
        if ($freshColumns !== $currentColumns || $freshRows !== $currentRows) {
            $this->loadSheetData();
            $this->saveStatus = 'Struktur disinkronkan ' . date('H:i:s');
            return;
        }

        $rowIds = array_column($this->rows, 'id');
        foreach (SpreadsheetCell::whereIn('row_id', $rowIds)->get() as $cell) {
            $key = $cell->row_id . '_' . $cell->column_id;
            if ((int) ($this->cellsVersions[$key] ?? 0) === (int) ($cell->version ?? 1)) {
                continue;
            }

            $this->cellsData[$key] = $cell->value ?? '';
            $this->cellsFormula[$key] = $cell->formula ?? '';
            $this->cellsFormat[$key] = $cell->format ?? [];
            $this->cellsVersions[$key] = (int) ($cell->version ?? 1);
            $this->updateCellPresentation($key, $cell->value ?? '', $cell->format ?? []);

            if ($this->activeRowId === $cell->row_id && $this->activeColumnId === $cell->column_id) {
                $this->formulaValue = $cell->formula ?: ($cell->value ?? '');
                $this->activeCellVersion = (int) ($cell->version ?? 1);
            }
        }

        $this->recalculateSummary();
        $this->loadRecentActivities();
        $this->saveStatus = 'Tersinkron ' . date('H:i:s');
    }

    private function loadRecentActivities(): void
    {
        $this->recentActivities = SpreadsheetActivity::with(['user:id,name', 'sheet:id,name'])
            ->where('group_id', $this->sheet->group_id)->latest('committed_at')->limit(20)->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'actor' => $activity->user?->name ?? 'Pengguna',
                'action' => $activity->action,
                'sheet' => $activity->sheet?->name ?? '-',
                'range' => $activity->cell_range,
                'at' => $activity->committed_at->timezone('Asia/Jakarta')->format('d M H:i:s'),
            ])->all();
    }

    /**
     * Toggle or set formatting on active cell (bold, italic, underline, color, bg, align, numFormat).
     */
    public function formatCell(string $property, mixed $value = null, ?int $rowId = null, ?int $colId = null)
    {
        $this->authorizeAccess();

        if ($rowId && $colId) {
            $this->activeRowId = $rowId;
            $this->activeColumnId = $colId;
        }

        if (!$this->activeRowId || !$this->activeColumnId) {
            return;
        }

        $this->assertCellBelongsToSheet($this->activeRowId, $this->activeColumnId);
        $key = "{$this->activeRowId}_{$this->activeColumnId}";
        $currentFormat = $this->cellsFormat[$key] ?? [];

        if (in_array($property, ['bold', 'italic', 'underline'])) {
            $currentFormat[$property] = empty($currentFormat[$property]);
        } else {
            $currentFormat[$property] = $value;
        }

        $this->cellsFormat[$key] = $currentFormat;

        $version = $this->persistCellWithVersion(
            $this->activeRowId,
            $this->activeColumnId,
            ['format' => $currentFormat]
        );
        $this->cellsVersions[$key] = $version;
        $this->activeCellVersion = $version;
        $this->updateCellPresentation($key, $this->cellsData[$key] ?? '', $currentFormat);
        $row = SpreadsheetRow::findOrFail($this->activeRowId);
        $column = SpreadsheetColumn::findOrFail($this->activeColumnId);
        app(SpreadsheetActivityRecorder::class)->record(Auth::user(), $this->sheet, 'format_changed', $column->letter . $row->sort_order, null, null, ['property' => $property]);

        $this->saveStatus = 'Format Tersimpan ' . date('H:i:s');
        $this->dispatch('toast', message: 'Format sel diterapkan', type: 'success');
    }

    /**
     * Undo last cell edit.
     */
    public function undo()
    {
        $this->authorizeAccess();
        if (empty($this->undoStack)) {
            $this->dispatch('toast', message: 'Tidak ada tindakan untuk di-undo', type: 'info');
            return;
        }

        $lastAction = array_pop($this->undoStack);
        $rowId = $lastAction['row_id'];
        $colId = $lastAction['col_id'];
        $key = "{$rowId}_{$colId}";

        // Save current to redo stack
        $this->redoStack[] = [
            'row_id' => $rowId,
            'col_id' => $colId,
            'coord' => $lastAction['coord'],
            'new_val' => $this->cellsData[$key] ?? '',
            'new_formula' => $this->cellsFormula[$key] ?? '',
            'old_val' => $lastAction['old_val'],
            'old_formula' => $lastAction['old_formula'],
        ];

        $revertVal = $lastAction['old_val'];
        $revertFormula = $lastAction['old_formula'];
        $this->assertCellBelongsToSheet($rowId, $colId);

        $this->cellsData[$key] = $revertVal;
        $this->cellsFormula[$key] = $revertFormula;
        $this->cellsVersions[$key] = $this->persistCellWithVersion($rowId, $colId, [
            'value' => $revertVal,
            'formula' => !empty($revertFormula) ? $revertFormula : null,
        ]);

        $this->evaluateAllFormulas();
        $this->recalculateSummary();
        $this->dispatch('toast', message: 'Perubahan dibatalkan (Undo)', type: 'info');
    }

    /**
     * Redo previously undone action.
     */
    public function redo()
    {
        $this->authorizeAccess();
        if (empty($this->redoStack)) {
            $this->dispatch('toast', message: 'Tidak ada tindakan untuk di-redo', type: 'info');
            return;
        }

        $lastAction = array_pop($this->redoStack);
        $rowId = $lastAction['row_id'];
        $colId = $lastAction['col_id'];
        $key = "{$rowId}_{$colId}";

        $this->undoStack[] = [
            'row_id' => $rowId,
            'col_id' => $colId,
            'coord' => $lastAction['coord'],
            'old_val' => $this->cellsData[$key] ?? '',
            'old_formula' => $this->cellsFormula[$key] ?? '',
            'new_val' => $lastAction['new_val'],
            'new_formula' => $lastAction['new_formula'] ?? null,
        ];

        $applyVal = $lastAction['new_val'];
        $applyFormula = $lastAction['new_formula'] ?? null;
        $this->assertCellBelongsToSheet($rowId, $colId);

        $this->cellsData[$key] = $applyVal;
        $this->cellsFormula[$key] = $applyFormula ?? '';
        $this->cellsVersions[$key] = $this->persistCellWithVersion($rowId, $colId, [
            'value' => $applyVal,
            'formula' => !empty($applyFormula) ? $applyFormula : null,
        ]);

        $this->evaluateAllFormulas();
        $this->recalculateSummary();
        $this->dispatch('toast', message: 'Tindakan diulangi (Redo)', type: 'info');
    }

    /**
     * Clear content of currently active cell.
     */
    public function clearActiveCell(?int $rowId = null, ?int $colId = null)
    {
        $rId = $rowId ?? $this->activeRowId;
        $cId = $colId ?? $this->activeColumnId;
        if ($rId && $cId) {
            $this->updateCell($rId, $cId, '');
        }
    }

    /**
     * Sort sheet rows based on values in a specific column (preserves complete rows!).
     */
    public function sortColumn(int $colId, string $direction = 'asc')
    {
        $this->authorizeAccess();

        $rowsWithVal = [];
        foreach ($this->rows as $row) {
            $key = $row['id'] . '_' . $colId;
            $val = $this->cellsData[$key] ?? '';
            $rowsWithVal[] = [
                'row' => $row,
                'val' => $val,
                'num' => SpreadsheetFormulaEngine::extractNumber($val),
            ];
        }

        usort($rowsWithVal, function ($a, $b) use ($direction) {
            $aNum = $a['num'];
            $bNum = $b['num'];

            if ($aNum !== null && $bNum !== null) {
                return $direction === 'asc' ? ($aNum <=> $bNum) : ($bNum <=> $aNum);
            }

            $cmp = strcasecmp((string)$a['val'], (string)$b['val']);
            return $direction === 'asc' ? $cmp : -$cmp;
        });

        // Persist new sort_order for rows
        DB::transaction(function () use ($rowsWithVal) {
            foreach ($rowsWithVal as $idx => $item) {
                SpreadsheetRow::where('id', $item['row']['id'])->update(['sort_order' => $idx + 1]);
            }
        });

        $this->loadSheetData();
        $this->dispatch('toast', message: "Data diurutkan ({$direction})", type: 'success');
    }

    /**
     * Find and replace occurrences of text in the sheet.
     */
    public function executeFindReplace()
    {
        $this->authorizeAccess();

        $find = trim($this->findText);
        $replace = $this->replaceText;

        if ($find === '') {
            $this->dispatch('toast', message: 'Teks pencarian tidak boleh kosong', type: 'warning');
            return;
        }

        $count = 0;
        foreach ($this->cellsData as $key => $val) {
            $currentText = $this->cellsFormula[$key] ?: (string) $val;
            if (stripos($currentText, $find) !== false) {
                $newVal = str_ireplace($find, $replace, $currentText);
                [$rId, $cId] = explode('_', $key);
                $rowId = (int) $rId;
                $colId = (int) $cId;
                $this->assertCellBelongsToSheet($rowId, $colId);
                $formula = str_starts_with($newVal, '=') ? $newVal : null;
                $this->cellsVersions[$key] = $this->persistCellWithVersion($rowId, $colId, [
                    'value' => $formula ? ($this->cellsData[$key] ?? '') : $newVal,
                    'formula' => $formula,
                ]);
                $this->cellsData[$key] = $formula ? ($this->cellsData[$key] ?? '') : $newVal;
                $this->cellsFormula[$key] = $formula ?? '';
                $count++;
            }
        }

        $this->showFindReplaceModal = false;
        $this->findText = '';
        $this->replaceText = '';
        $this->evaluateAllFormulas();
        $this->recalculateSummary();
        $this->dispatch('toast', message: "Berhasil mengganti {$count} sel yang cocok.", type: 'success');
    }

    /**
     * Add a new column with next letter (A-Z, AA...) and blank title.
     */
    public function addColumn()
    {
        $this->authorizeAccess();

        $nextLetter = $this->sheet->getNextColumnLetter();
        $maxOrder = $this->sheet->columns()->max('sort_order') ?? 0;

        $newCol = $this->sheet->columns()->create([
            'letter' => $nextLetter,
            'title' => '',
            'width' => 160,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->loadSheetData();
        $this->dispatch('toast', message: "Kolom {$nextLetter} berhasil ditambahkan", type: 'success');
        app(SpreadsheetActivityRecorder::class)->record(Auth::user(), $this->sheet, 'column_added', $nextLetter);
    }

    /**
     * Delete column with safety modal.
     */
    public function requestDeleteColumn(int $colId)
    {
        $this->authorizeAccess();

        $col = SpreadsheetColumn::where('sheet_id', $this->sheetId)->find($colId);
        if (!$col) return;

        $populatedCount = $col->populatedCellsCount();

        if ($populatedCount > 0) {
            $this->columnPendingDeleteId = $col->id;
            $this->columnPendingDeleteLetter = $col->letter;
            $this->columnPendingDeleteCount = $populatedCount;
            $this->showDeleteColumnModal = true;
        } else {
            $col->delete();
            $this->loadSheetData();
            $this->dispatch('toast', message: "Kolom {$col->letter} dihapus", type: 'info');
        }
    }

    public function forceDeleteColumn()
    {
        $this->authorizeAccess();

        if ($this->columnPendingDeleteId) {
            $col = SpreadsheetColumn::where('sheet_id', $this->sheetId)->find($this->columnPendingDeleteId);
            if ($col) {
                $letter = $col->letter;
                $col->delete();
                $this->dispatch('toast', message: "Kolom {$letter} dan datanya telah dihapus", type: 'warning');
            }
        }

        $this->showDeleteColumnModal = false;
        $this->columnPendingDeleteId = null;
        $this->loadSheetData();
    }

    public function cancelDeleteColumn()
    {
        $this->showDeleteColumnModal = false;
        $this->columnPendingDeleteId = null;
    }

    /**
     * Change column width (110, 160, 230, 320 px).
     */
    public function setColumnWidth(int $colId, int $width)
    {
        $this->authorizeAccess();

        // Keep resizing a presentation concern: it never touches cell data or formulas.
        $width = max(64, min(900, $width));

        SpreadsheetColumn::where('sheet_id', $this->sheetId)
            ->where('id', $colId)
            ->update(['width' => $width]);

        $this->loadSheetData();
    }

    /** Persist a user-resized row without changing its contents. */
    public function setRowHeight(int $rowId, int $height)
    {
        $this->authorizeAccess();

        SpreadsheetRow::where('sheet_id', $this->sheetId)
            ->where('id', $rowId)
            ->update(['height' => max(15, min(300, $height / 1.333333))]);

        $this->loadSheetData();
    }

    /**
     * Inline update column title.
     */
    public function updateColumnTitle(int $colId, string $title)
    {
        $this->authorizeAccess();

        SpreadsheetColumn::where('sheet_id', $this->sheetId)
            ->where('id', $colId)
            ->update(['title' => trim($title)]);

        $this->loadSheetData();
        $this->dispatch('toast', message: 'Judul kolom disimpan ke database', type: 'success');
    }

    /**
     * Add a row while retaining the visual and calculation pattern of the row above.
     * Formula-only footer rows (for example totals) remain below the new data row.
     */
    public function addRow()
    {
        $this->authorizeAccess();

        $orderedRows = $this->sheet->rows()->with('cells')->orderBy('sort_order')->get();
        $footer = $orderedRows->last(fn (SpreadsheetRow $row) => $row->cells->contains(fn (SpreadsheetCell $cell) => !empty($cell->formula)));
        $isFormulaOnlyFooter = $footer && $footer->cells->contains(fn (SpreadsheetCell $cell) => !empty($cell->formula))
            && $footer->cells->every(fn (SpreadsheetCell $cell) => !empty($cell->formula) || trim((string) $cell->value) === '');
        $insertBeforeFooter = $isFormulaOnlyFooter && $footer->sort_order === $orderedRows->last()?->sort_order;

        $newRow = DB::transaction(function () use ($orderedRows, $footer, $insertBeforeFooter) {
            $sortOrder = ($orderedRows->last()?->sort_order ?? 0) + 1;
            $sourceRow = $orderedRows->last();

            if ($insertBeforeFooter) {
                SpreadsheetRow::where('sheet_id', $this->sheetId)
                    ->where('sort_order', '>=', $footer->sort_order)
                    ->increment('sort_order');
                $sortOrder = $footer->sort_order;
                $sourceRow = $orderedRows->count() > 1 ? $orderedRows[$orderedRows->count() - 2] : null;

                // A trailing SUM/AVERAGE-style range ending at the prior data row
                // must include the inserted row. This is intentionally limited to
                // range endpoints so absolute and unrelated references stay intact.
                foreach ($footer->cells as $footerCell) {
                    if (!$footerCell->formula || !$sourceRow) continue;
                    $expanded = $this->extendFormulaRangesForInsertedRow($footerCell->formula, $sourceRow->sort_order);
                    if ($expanded !== $footerCell->formula) {
                        $footerCell->update(['formula' => $expanded]);
                    }
                }
            }

            $newRow = $this->sheet->rows()->create([
                'sort_order' => $sortOrder,
                'height' => $sourceRow?->height,
            ]);

            if ($sourceRow) {
                foreach ($sourceRow->cells as $sourceCell) {
                    // Copy formats for a coherent expanded table. Values are deliberately not copied.
                    $formula = $sourceCell->formula
                        ? $this->shiftFormulaForNewRow($sourceCell->formula, $newRow->sort_order - $sourceRow->sort_order)
                        : null;
                    SpreadsheetCell::create([
                        'row_id' => $newRow->id,
                        'column_id' => $sourceCell->column_id,
                        'value' => '',
                        'formula' => $formula,
                        'format' => $sourceCell->format,
                    ]);
                }
            }

            return $newRow;
        });

        $this->loadSheetData();
        $this->evaluateAllFormulas();
        app(SpreadsheetActivityRecorder::class)->record(Auth::user(), $this->sheet, 'row_added', 'A' . $newRow->sort_order);
        $this->dispatch('toast', message: "Baris {$newRow->sort_order} ditambahkan", type: 'info');
    }

    /**
     * Delete row.
     */
    public function deleteRow(int $rowId)
    {
        $this->authorizeAccess();

        $row = SpreadsheetRow::where('sheet_id', $this->sheetId)->where('id', $rowId)->firstOrFail();
        $order = $row->sort_order;
        DB::transaction(function () use ($row) {
            $row->delete();
            app(SpreadsheetActivityRecorder::class)->record(Auth::user(), $this->sheet, 'row_deleted', 'A' . $row->sort_order);
        });
        $this->loadSheetData();
        $this->dispatch('toast', message: 'Baris dihapus', type: 'info');
    }

    /**
     * Handle Excel / Google Sheets Multi-Cell TSV Paste into persistent database.
     */
    public function pasteTsvData(int $startRowId, int $startColId, string $tsvContent)
    {
        $this->authorizeAccess();
        $this->assertCellBelongsToSheet($startRowId, $startColId);

        // Do not trim clipboard text: blank cells and spaces are meaningful in a
        // spreadsheet.  Excel and Google Sheets both use CRLF or LF.
        $lines = preg_split('/\r\n|\n|\r/', $tsvContent);
        if (end($lines) === '') {
            array_pop($lines);
        }
        if (empty($lines)) return;

        // Validate the complete paste before opening a write transaction. This
        // keeps a malformed formula from leaving a partially pasted range.
        foreach ($lines as $line) {
            foreach (explode("\t", rtrim($line, "\r")) as $pastedValue) {
                if (!str_starts_with($pastedValue, '=')) {
                    continue;
                }

                $syntaxError = SpreadsheetFormulaEngine::validateFormulaSyntax($pastedValue);
                if ($syntaxError !== null) {
                    $this->saveStatus = 'Formula tidak valid';
                    $this->dispatch('toast', message: $syntaxError, type: 'error');
                    return;
                }
            }
        }

        $startRowIdx = array_search($startRowId, array_column($this->rows, 'id'));
        $startColIdx = array_search($startColId, array_column($this->columns, 'id'));

        if ($startRowIdx === false || $startColIdx === false) {
            abort(404, 'Sel awal tidak ditemukan di lembar kerja ini.');
        }

        $conflicts = 0;
        DB::transaction(function () use ($lines, $startRowIdx, $startColIdx, &$conflicts) {
            foreach ($lines as $rOffset => $line) {
                $targetRowIdx = $startRowIdx + $rOffset;

                if (!isset($this->rows[$targetRowIdx])) {
                    $maxOrder = $this->sheet->rows()->max('sort_order') ?? 0;
                    $createdRow = $this->sheet->rows()->create(['sort_order' => $maxOrder + 1]);
                    $this->rows[] = $createdRow->toArray();
                }

                $currentRowId = $this->rows[$targetRowIdx]['id'];
                $values = explode("\t", rtrim($line, "\r"));

                foreach ($values as $cOffset => $val) {
                    $targetColIdx = $startColIdx + $cOffset;

                    if (!isset($this->columns[$targetColIdx])) {
                        $nextLetter = $this->sheet->getNextColumnLetter();
                        $maxColOrder = $this->sheet->columns()->max('sort_order') ?? 0;
                        $createdCol = $this->sheet->columns()->create([
                            'letter' => $nextLetter,
                            'title' => '',
                            'width' => 160,
                            'sort_order' => $maxColOrder + 1,
                        ]);
                        $this->columns[] = $createdCol->toArray();
                    }

                    $currentColId = $this->columns[$targetColIdx]['id'];
                    $cleanVal = $val;
                    $formula = str_starts_with($cleanVal, '=') ? $cleanVal : null;
                    $key = "{$currentRowId}_{$currentColId}";
                    $existing = SpreadsheetCell::where('row_id', $currentRowId)
                        ->where('column_id', $currentColId)
                        ->lockForUpdate()
                        ->first();
                    $currentVersion = (int) ($existing?->version ?? 0);
                    if ($currentVersion !== (int) ($this->cellsVersions[$key] ?? 0)) {
                        $conflicts++;
                    }
                    $version = $currentVersion + 1;

                    SpreadsheetCell::updateOrCreate(
                        ['row_id' => $currentRowId, 'column_id' => $currentColId],
                        ['value' => $cleanVal, 'formula' => $formula, 'version' => $version]
                    );

                    $this->cellsData[$key] = $cleanVal;
                    $this->cellsFormula[$key] = $formula ?? '';
                    $this->cellsVersions[$key] = $version;
                }
            }
            app(SpreadsheetActivityRecorder::class)->record(Auth::user(), $this->sheet, 'cells_pasted', null, null, null, ['rows' => count($lines)]);
        });

        $this->loadSheetData();
        $this->dispatch(
            'toast',
            message: $conflicts > 0
                ? "Paste selesai, tetapi {$conflicts} sel telah diubah pengguna lain; last-write-wins diterapkan."
                : 'Data tabel Excel berhasil disimpan ke database!',
            type: $conflicts > 0 ? 'warning' : 'success'
        );
    }

    /**
     * Automatically compute SUM and AVERAGE for all actual numeric/currency cells.
     */
    public function recalculateSummary()
    {
        $sum = 0.0;
        $count = 0;
        $filled = 0;

        foreach ($this->cellsData as $val) {
            if ($val !== null && trim((string)$val) !== '') {
                $filled++;

                $num = SpreadsheetFormulaEngine::extractNumber($val);
                if ($num !== null) {
                    $sum += $num;
                    $count++;
                }
            }
        }

        $this->sumTotal = $sum;
        $this->numericCellsCount = $count;
        $this->averageTotal = $count > 0 ? ($sum / $count) : 0.0;
        $this->filledCellsCount = $filled;
    }

    public function render()
    {
        return view('livewire.spreadsheet-worksheet');
    }

    private function assertCellBelongsToSheet(int $rowId, int $columnId): void
    {
        abort_unless(
            SpreadsheetRow::where('sheet_id', $this->sheetId)->whereKey($rowId)->exists()
            && SpreadsheetColumn::where('sheet_id', $this->sheetId)->whereKey($columnId)->exists(),
            404,
            'Sel tidak ditemukan di lembar kerja ini.'
        );
    }

    private function persistCellWithVersion(int $rowId, int $columnId, array $attributes): int
    {
        return DB::transaction(function () use ($rowId, $columnId, $attributes) {
            $cell = SpreadsheetCell::where('row_id', $rowId)
                ->where('column_id', $columnId)
                ->lockForUpdate()
                ->first();
            $version = (int) ($cell?->version ?? 0) + 1;
            SpreadsheetCell::updateOrCreate(
                ['row_id' => $rowId, 'column_id' => $columnId],
                $attributes + ['version' => $version]
            );

            return $version;
        });
    }

    private function updateCellPresentation(string $key, string $value, array $format): void
    {
        $this->cellsDisplay[$key] = $value;
        $style = $format['xlsx'] ?? [];
        $formatCode = match ($format['numFormat'] ?? null) {
            'currency' => '"Rp" #,##0.00',
            'number' => '#,##0.00',
            'percent' => '0.00%',
            default => $style['numberFormat']['formatCode'] ?? null,
        };
        if ($formatCode && is_numeric($value)) {
            $this->cellsDisplay[$key] = NumberFormat::toFormattedString((float) $value, $formatCode);
        }

        $css = [];
        $font = $style['font'] ?? [];
        $alignment = $style['alignment'] ?? [];
        $fill = $style['fill'] ?? [];

        if (array_key_exists('bold', $format)) {
            $css[] = 'font-weight:' . ($format['bold'] ? '700' : '400');
        } elseif (!empty($font['bold'])) {
            $css[] = 'font-weight:700';
        }
        if (array_key_exists('italic', $format)) {
            $css[] = 'font-style:' . ($format['italic'] ? 'italic' : 'normal');
        } elseif (!empty($font['italic'])) {
            $css[] = 'font-style:italic';
        }
        if (array_key_exists('underline', $format)) {
            $css[] = 'text-decoration:' . ($format['underline'] ? 'underline' : 'none');
        } elseif (($font['underline'] ?? 'none') !== 'none') {
            $css[] = 'text-decoration:underline';
        }
        if (isset($font['size']) && is_numeric($font['size'])) $css[] = 'font-size:' . (float) $font['size'] . 'pt';
        if (!empty($font['name'])) {
            $fontName = preg_replace('/[^a-zA-Z0-9 ,_-]/', '', $font['name']);
            if ($fontName !== '') $css[] = 'font-family:' . $fontName;
        }

        $fontColor = $this->xlsxColor($font['color'] ?? []);
        if ($fontColor) $css[] = 'color:' . $fontColor;
        $background = $this->xlsxColor($fill['startColor'] ?? []);
        if ($background) {
            $css[] = 'background-color:' . $background;
        } elseif (!empty($format['bg'])) {
            $css[] = 'background-color:' . $format['bg'];
        }

        if (in_array($alignment['horizontal'] ?? null, ['left', 'center', 'right', 'justify'], true)) {
            $css[] = 'text-align:' . $alignment['horizontal'];
        }
        if (in_array($alignment['vertical'] ?? null, ['top', 'center', 'bottom'], true)) {
            $css[] = 'vertical-align:' . $alignment['vertical'];
        }
        if (!empty($alignment['wrapText'])) $css[] = 'white-space:normal';

        foreach (['left', 'right', 'top', 'bottom'] as $edge) {
            $border = $style['borders'][$edge] ?? [];
            $borderStyle = $border['borderStyle'] ?? 'none';
            $borderWidths = ['hair' => '1px', 'thin' => '1px', 'medium' => '2px', 'thick' => '3px'];
            if (isset($borderWidths[$borderStyle])) {
                $borderColor = $this->xlsxColor($border['color'] ?? []) ?? '#000000';
                $css[] = "border-{$edge}:{$borderWidths[$borderStyle]} solid {$borderColor}";
            }
        }

        $this->cellsStyleCss[$key] = !empty($css) ? implode('; ', $css) . '; ' : '';
    }

    private function xlsxColor(array $color): ?string
    {
        $argb = strtoupper((string) ($color['argb'] ?? ($color['rgb'] ?? '')));
        if (preg_match('/^(?:[0-9A-F]{2})?([0-9A-F]{6})$/', $argb, $matches)) {
            return '#' . $matches[1];
        }

        return null;
    }

    /** Shift relative same-sheet row references when a patterned formula is filled down. */
    private function shiftFormulaForNewRow(string $formula, int $rowOffset): string
    {
        if ($rowOffset === 0) return $formula;

        return preg_replace_callback('/(?<![!A-Z0-9_])(?<column>\$?[A-Z]{1,3})(?<row>\$?\d+)/i',
            static function (array $match) use ($rowOffset): string {
                if (str_starts_with($match['row'], '$')) return $match[0];
                return $match['column'] . ((int) $match['row'] + $rowOffset);
            }, $formula) ?? $formula;
    }

    /** Extend range endpoints that previously stopped at the final data row. */
    private function extendFormulaRangesForInsertedRow(string $formula, int $previousLastRow): string
    {
        return preg_replace_callback(
            '/(?<start>\$?[A-Z]{1,3}\$?\d+):(?<endcolumn>\$?[A-Z]{1,3})(?<endrow>\$?' . $previousLastRow . ')(?!\d)/i',
            static fn (array $match): string => $match['start'] . ':' . $match['endcolumn'] .
                (str_starts_with($match['endrow'], '$') ? '$' : '') . ($previousLastRow + 1),
            $formula
        ) ?? $formula;
    }

    private function loadMergedCells(): void
    {
        $this->mergedCells = [];
        $this->mergedCoveredCells = [];

        foreach ($this->sheet->metadata['merged_cells'] ?? [] as $range) {
            if (!preg_match('/^\$?([A-Z]+)\$?(\d+):\$?([A-Z]+)\$?(\d+)$/i', $range, $matches)) {
                continue;
            }

            $startColumn = SpreadsheetFormulaEngine::columnLetterToIndex($matches[1]);
            $startRow = (int) $matches[2];
            $endColumn = SpreadsheetFormulaEngine::columnLetterToIndex($matches[3]);
            $endRow = (int) $matches[4];
            $start = strtoupper($matches[1]) . $startRow;
            $this->mergedCells[$start] = [
                'colspan' => max(1, $endColumn - $startColumn + 1),
                'rowspan' => max(1, $endRow - $startRow + 1),
            ];

            for ($column = $startColumn; $column <= $endColumn; $column++) {
                for ($row = $startRow; $row <= $endRow; $row++) {
                    if ($column === $startColumn && $row === $startRow) continue;
                    $this->mergedCoveredCells[
                        SpreadsheetFormulaEngine::indexToColumnLetter($column) . $row
                    ] = true;
                }
            }
        }
    }

    private function loadFreezePaneOffsets(): void
    {
        $this->freezeTopRowCount = 0;
        $this->freezeLeftColumnCount = 0;
        $this->frozenRowTopOffsets = [];
        $this->frozenColumnLeftOffsets = [];

        $freezePane = $this->sheet->metadata['freeze_pane'] ?? null;
        $sheetName = mb_strtolower((string) ($this->sheet->name ?? ''));

        // Default freeze pane if not configured or if standard template structure is needed
        if (empty($freezePane)) {
            if (str_contains($sheetName, 'analisa') || str_contains($sheetName, 'analysis')) {
                $freezePane = 'C6';
            } elseif (str_contains($sheetName, 'cost')) {
                $freezePane = 'C8';
            } elseif (str_contains($sheetName, 'margin')) {
                $freezePane = 'C5';
            }
        }

        if (is_string($freezePane) && preg_match('/^([A-Z]+)(\d+)$/i', $freezePane, $matches)) {
            $this->freezeLeftColumnCount = max(
                0,
                SpreadsheetFormulaEngine::columnLetterToIndex($matches[1]) - 1
            );
            $this->freezeTopRowCount = max(0, (int) $matches[2] - 1);
        }

        // Preserve the workbook's horizontal header behavior and enforce each template's row boundary.
        if (str_contains($sheetName, 'analisa') || str_contains($sheetName, 'analysis')) {
            $this->freezeTopRowCount = max($this->freezeTopRowCount, 5);
            $this->freezeLeftColumnCount = max($this->freezeLeftColumnCount, 2);
        } elseif ($this->sheet->isCostWorksheet()) {
            $this->freezeTopRowCount = 7;
            $this->freezeLeftColumnCount = max($this->freezeLeftColumnCount, 2);
        } elseif (str_contains($sheetName, 'margin')) {
            $this->freezeTopRowCount = max($this->freezeTopRowCount, 4);
            $this->freezeLeftColumnCount = max($this->freezeLeftColumnCount, 2);
        }

        $left = 48;
        foreach ($this->gridColumns as $index => $column) {
            if ($index < $this->freezeLeftColumnCount) {
                $this->frozenColumnLeftOffsets[$index] = $left;
            }
            $left += (int) $column['width'];
        }

        // Row offsets use the configured row heights; the view adds the measured
        // column-heading height once.
        $top = 0;
        foreach ($this->gridRows as $index => $row) {
            if ($index < $this->freezeTopRowCount) {
                $this->frozenRowTopOffsets[$index] = $top;
            }
            $top += !empty($row['height']) ? (float) $row['height'] * 1.333333 : $this->defaultRowHeightPx;
        }
    }

    /**
     * Keep the RM / Rendemen Fillet section and its cream summary rows in view
     * on the Analisa and Margin worksheets. Labels may be in merged cells,
     * therefore every cell in a row is inspected instead of relying on a fixed column.
     */
    private function loadAnalisaFixedRows(): void
    {
        $this->fixedTopRowOffsets = [];

        $sheetName = mb_strtolower((string) ($this->sheet->name ?? ''));
        if ($this->sheet->isCostWorksheet()
            || (!str_contains($sheetName, 'analisa') && !str_contains($sheetName, 'analysis')
                && !str_contains($sheetName, 'margin'))) {
            return;
        }

        $fixedIndexes = [];
        foreach ($this->gridRows as $rowIndex => $row) {
            $rowId = $row['id'] ?? null;
            if (!$rowId) {
                continue;
            }

            $rowText = '';
            $hasCreamFill = false;
            foreach ($this->gridColumns as $column) {
                if (!empty($column['id'])) {
                    $cellKey = $rowId . '_' . $column['id'];
                    $rowText .= ' ' . ($this->cellsData[$cellKey] ?? '');
                    $hasCreamFill = $hasCreamFill || $this->isCreamCellFormat($this->cellsFormat[$cellKey] ?? []);
                }
            }
            $rowText = mb_strtolower(trim($rowText));

            if ($hasCreamFill || preg_match('/(^|\\s)rm(\\s|$)/u', $rowText) || str_contains($rowText, 'rendemen fillet')) {
                $fixedIndexes[] = $rowIndex;
            }
        }

        // A row already frozen by the workbook does not need a second sticky rule.
        $fixedIndexes = array_values(array_filter(
            $fixedIndexes,
            fn (int $index): bool => $index >= $this->freezeTopRowCount
        ));

        // The top offset for any additional fixed rows must start directly below the last frozen row
        $runningTop = 0;
        foreach ($this->gridRows as $index => $row) {
            if ($index < $this->freezeTopRowCount) {
                $runningTop += !empty($row['height'])
                    ? (float) $row['height'] * 1.333333
                    : $this->defaultRowHeightPx;
            }
        }

        foreach ($fixedIndexes as $rowIndex) {
            $this->fixedTopRowOffsets[$rowIndex] = $runningTop;
            $row = $this->gridRows[$rowIndex];
            $runningTop += !empty($row['height'])
                ? (float) $row['height'] * 1.333333
                : $this->defaultRowHeightPx;
        }
    }

    /** Identify the light cream / peach fills used as spreadsheet summary rows. */
    private function isCreamCellFormat(array $format): bool
    {
        $color = $format['bg'] ?? null;
        if (!$color) {
            $fill = $format['xlsx']['fill'] ?? [];
            $color = $this->xlsxColor($fill['startColor'] ?? []);
        }

        if (!is_string($color) || !preg_match('/^#?([0-9a-f]{6})$/i', $color, $matches)) {
            return false;
        }

        [$red, $green, $blue] = array_map('hexdec', str_split($matches[1], 2));

        // Detect warm cream, peach, soft orange fills (e.g. #FCD5B6, #FCE4D6, #FFF2CC)
        return $red >= 220 && $green >= 170 && $blue >= 120
            && $red >= $green && $green >= $blue
            && (($red - $blue) >= 15);
    }
}
