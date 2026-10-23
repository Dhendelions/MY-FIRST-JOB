<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SpreadsheetSheet;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetCell;
use App\Models\GroupMember;
use App\Services\SpreadsheetFormulaEngine;
use App\Services\SpreadsheetActivityRecorder;

class SpreadsheetController extends Controller
{
    /**
     * Export spreadsheet sheet to clean, Excel-compatible CSV with UTF-8 BOM.
     */
    public function exportCsv(SpreadsheetSheet $sheet): StreamedResponse
    {
        $this->authorizeSheetAccess($sheet);

        $sheet->load(['columns', 'rows.cells', 'group']);

        $groupName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($sheet->group->name ?? 'workspace'));
        $sheetName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($sheet->name));
        $filename = "dadhen_mandiri_{$groupName}_{$sheetName}_" . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($sheet) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // 1. Column headers: If user defined title, use "Letter: Title", otherwise just Letter
            $headerRow = [];
            foreach ($sheet->columns as $col) {
                $title = trim($col->title ?? '');
                $headerRow[] = $title !== '' ? "{$col->letter}: {$title}" : $col->letter;
            }
            fputcsv($handle, $headerRow);

            // 2. Data rows
            foreach ($sheet->rows as $row) {
                $cellsByCol = $row->cells->keyBy('column_id');
                $rowValues = [];
                foreach ($sheet->columns as $col) {
                    $cell = $cellsByCol->get($col->id);
                    $rowValues[] = $cell ? $cell->value : '';
                }
                fputcsv($handle, $rowValues);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import CSV file into sheet with automatic delimiter detection and validation.
     */
    public function importCsv(Request $request, SpreadsheetSheet $sheet)
    {
        $this->authorizeSheetAccess($sheet);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // Max 5MB
        ], [
            'csv_file.required' => 'Silakan pilih file CSV untuk diimpor.',
            'csv_file.mimes' => 'File harus berekstensi .csv atau .txt format tabular.',
            'csv_file.max' => 'Ukuran file CSV maksimal 5MB.',
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();

        // Read sample line to detect delimiter (, or ; or \t)
        $firstLine = fgets(fopen($filePath, 'r'));
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return back()->with('error', 'Gagal membaca file CSV.');
        }

        $importedRows = 0;
        $sheet->load(['columns', 'rows']);
        $columns = $sheet->columns->values();
        $rows = $sheet->rows->values();

        DB::transaction(function () use ($handle, $delimiter, $sheet, &$columns, &$rows, &$importedRows) {
            $rowIndex = 0;
            while (($row = fgetcsv($handle, 10000, $delimiter)) !== false) {
                // Ignore UTF-8 BOM on first field if present
                if ($rowIndex === 0 && isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                }

                // Ensure target row exists
                if (!isset($rows[$rowIndex])) {
                    $maxOrder = $sheet->rows()->max('sort_order') ?? 0;
                    $newRow = $sheet->rows()->create(['sort_order' => $maxOrder + 1]);
                    $rows->push($newRow);
                }
                $currentRow = $rows[$rowIndex];

                // Ensure target columns exist
                foreach ($row as $colIndex => $val) {
                    if (!isset($columns[$colIndex])) {
                        $nextLetter = $sheet->getNextColumnLetter();
                        $maxColOrder = $sheet->columns()->max('sort_order') ?? 0;
                        $newCol = $sheet->columns()->create([
                            'letter' => $nextLetter,
                            'title' => '',
                            'width' => 160,
                            'sort_order' => $maxColOrder + 1,
                        ]);
                        $columns->push($newCol);
                    }
                    $currentCol = $columns[$colIndex];

                    // CSV quoting deliberately preserves leading/trailing spaces;
                    // fgetcsv has already handled delimiters and embedded newlines.
                    $cleanVal = rtrim($val, "\r");
                    $formula = null;
                    if (str_starts_with($cleanVal, '=')) {
                        $formula = $cleanVal;
                    }
                    $existingCell = SpreadsheetCell::where('row_id', $currentRow->id)
                        ->where('column_id', $currentCol->id)
                        ->lockForUpdate()
                        ->first();
                    $version = (int) ($existingCell?->version ?? 0) + 1;

                    SpreadsheetCell::updateOrCreate(
                        ['row_id' => $currentRow->id, 'column_id' => $currentCol->id],
                        ['value' => $cleanVal, 'formula' => $formula, 'version' => $version]
                    );
                }

                $rowIndex++;
                $importedRows++;
            }
            app(SpreadsheetActivityRecorder::class)->record(Auth::user(), $sheet, 'csv_imported', null, null, null, ['rows' => $importedRows]);
        });

        fclose($handle);

        app(\App\Services\SpreadsheetWorkbookCalculator::class)->recalculate($sheet->group);

        return back()->with('success', "Berhasil mengimpor {$importedRows} baris data dari CSV ke lembar kerja.");
    }

    /**
     * Save single cell value and optional formula / formatting.
     */
    public function saveCell(Request $request)
    {
        $validated = $request->validate([
            'row_id' => ['required', 'exists:spreadsheet_rows,id'],
            'column_id' => ['required', 'exists:spreadsheet_columns,id'],
            'value' => ['nullable', 'string', 'max:65535'],
            'formula' => ['nullable', 'string', 'max:500'],
            'format' => ['nullable', 'array'],
            'expected_version' => ['nullable', 'integer', 'min:0'],
        ]);

        $row = SpreadsheetRow::with('sheet.group')->findOrFail($validated['row_id']);
        $column = SpreadsheetColumn::findOrFail($validated['column_id']);

        if ($row->sheet_id !== $column->sheet_id) {
            abort(422, 'Inkonsistensi data: Baris dan kolom berasal dari lembar kerja yang berbeda.');
        }

        $this->authorizeSheetAccess($row->sheet);

        $value = $validated['value'] ?? '';
        $formula = $validated['formula'] ?? null;

        // If value starts with '=', it's a formula
        if (str_starts_with(trim($value), '=')) {
            $formula = trim($value);
        }

        if ($formula !== null && !str_starts_with(trim($formula), '=')) {
            abort(422, 'Formula harus diawali dengan tanda sama dengan (=).');
        }

        $updateData = [
            'value' => $value,
            'formula' => $formula,
        ];

        if (isset($validated['format'])) {
            $updateData['format'] = $validated['format'];
        }

        [$cell, $conflict] = DB::transaction(function () use ($validated, $updateData, $row) {
            $currentCell = SpreadsheetCell::where('row_id', $validated['row_id'])
                ->where('column_id', $validated['column_id'])
                ->lockForUpdate()
                ->first();
            $currentVersion = (int) ($currentCell?->version ?? 0);
            $expectedVersion = $validated['expected_version'] ?? $currentVersion;
            $conflict = $currentVersion !== $expectedVersion;
            $unchanged = $currentCell
                && $currentCell->value === $updateData['value']
                && $currentCell->formula === $updateData['formula']
                && (!array_key_exists('format', $updateData) || $currentCell->format === $updateData['format']);
            if ($unchanged) {
                return [$currentCell, false];
            }
            $cell = SpreadsheetCell::updateOrCreate(
                [
                    'row_id' => $validated['row_id'],
                    'column_id' => $validated['column_id'],
                ],
                $updateData + ['version' => $currentVersion + 1]
            );
            $column = SpreadsheetColumn::findOrFail($validated['column_id']);
            app(SpreadsheetActivityRecorder::class)->record(Auth::user(), $row->sheet, $updateData['formula'] ? 'formula_changed' : 'cell_changed', $column->letter . $row->sort_order, $currentCell?->formula ?: $currentCell?->value, $updateData['formula'] ?: $updateData['value']);

            return [$cell, $conflict];
        });
        app(\App\Services\SpreadsheetWorkbookCalculator::class)->recalculate($row->sheet->group);
        $cell->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Sel berhasil disimpan',
            'cell' => $cell,
            'conflict' => $conflict,
            'is_numeric' => $cell->isNumericValue(),
            'numeric_value' => $cell->getNumericValue(),
        ]);
    }

    /**
     * Add column with next Excel letter and blank title.
     */
    public function addColumn(Request $request, SpreadsheetSheet $sheet)
    {
        $this->authorizeSheetAccess($sheet);

        $nextLetter = $sheet->getNextColumnLetter();
        $maxOrder = $sheet->columns()->max('sort_order') ?? 0;

        $column = $sheet->columns()->create([
            'letter' => $nextLetter,
            'title' => '',
            'width' => 160,
            'sort_order' => $maxOrder + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'column' => $column]);
        }

        return back()->with('success', "Kolom {$nextLetter} berhasil ditambahkan.");
    }

    /**
     * Delete column (with safety check on populated cells).
     */
    public function deleteColumn(Request $request, SpreadsheetColumn $column)
    {
        $this->authorizeSheetAccess($column->sheet);

        $populatedCount = $column->populatedCellsCount();

        if ($populatedCount > 0 && !$request->boolean('confirmed')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => "Kolom {$column->letter} memiliki {$populatedCount} sel terisi data.",
                    'populated_count' => $populatedCount,
                ], 422);
            }

            return back()->with('error', "Kolom {$column->letter} memiliki {$populatedCount} sel berisi data. Konfirmasi penghapusan diperlukan.");
        }

        $letter = $column->letter;
        $column->delete();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'deleted_letter' => $letter]);
        }

        return back()->with('success', "Kolom {$letter} berhasil dihapus.");
    }

    /**
     * Update column title or width.
     */
    public function updateColumn(Request $request, SpreadsheetColumn $column)
    {
        $this->authorizeSheetAccess($column->sheet);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'width' => ['nullable', 'integer', 'in:110,160,230,320'],
        ]);

        $column->update(array_filter($validated, fn($val) => $val !== null));

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'column' => $column]);
        }

        return back()->with('success', "Pengaturan kolom {$column->letter} berhasil disimpan.");
    }

    /**
     * Add empty row to sheet.
     */
    public function addRow(Request $request, SpreadsheetSheet $sheet)
    {
        $this->authorizeSheetAccess($sheet);

        $maxOrder = $sheet->rows()->max('sort_order') ?? 0;

        $row = $sheet->rows()->create([
            'sort_order' => $maxOrder + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'row' => $row]);
        }

        return back()->with('success', "Baris {$row->sort_order} ditambahkan.");
    }

    /**
     * Delete row from sheet.
     */
    public function deleteRow(Request $request, SpreadsheetRow $row)
    {
        $this->authorizeSheetAccess($row->sheet);

        $rowOrder = $row->sort_order;
        $row->delete();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back()->with('success', "Baris {$rowOrder} dihapus.");
    }

    /**
     * Verify that the current user has access to the sheet's workspace.
     */
    protected function authorizeSheetAccess(SpreadsheetSheet $sheet): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $group = $sheet->group;
        if (!$group) {
            abort(404, 'Workspace untuk lembar kerja ini tidak ditemukan.');
        }

        if (!$group->isMember($user->id)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk membaca atau mengubah lembar kerja ini.');
        }
        if ($group->hasPassword() && !request()->session()->get('sheet_unlocked_' . $sheet->id, false)) {
            abort(403, 'Lembar kerja ini belum diotorisasi.');
        }
    }
}
