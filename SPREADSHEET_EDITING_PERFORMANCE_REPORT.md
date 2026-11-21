# Spreadsheet Editing Performance Report

## Scope and evidence

The worksheet is rendered by `SpreadsheetWorksheet` (Livewire) with a real
input for every rendered cell. Its Alpine controller owns `editingCell`,
`editValue`, `cellsMap`, and the local formula dependency map.

Audit findings:

- Cell input is client-side (`@input="handleCellInput(...)"`), not a
  `wire:model.live` binding. A value is committed on blur, Enter, or Tab.
- The previous commit transport was `$wire.updateCell(...)`. Although the
  server calls `skipRender()`, Livewire still has to hydrate/dehydrate the
  component snapshot, which contains the worksheet's public grid arrays.
  Thus commit payload/CPU scaled with the sheet, rather than the changed cell.
- The component uses stable row and cell keys (`grid-row-{sort_order}` and
  `grid-cell-{coordinate}`), and normal committed cells already avoid a Blade
  render with `skipRender()`.
- Browser formulas already had a reverse dependency map and update direct DOM
  inputs. Server calculation skips the expensive workbook calculation for an
  edit that no formula references; dependent/unknown formulas deliberately
  use PhpSpreadsheet's authoritative path to retain accounting correctness.
- The old JSON endpoint recalculated the entire workbook after every API save,
  even for cells unrelated to formulas.

## Changes made

- `public/js/spreadsheet-worksheet.js`
  - Normal persisted-cell commits now use the authenticated
    `/spreadsheet/cell` JSON endpoint and send only row id, column id, value,
    and optimistic version. They no longer invoke Livewire's full worksheet
    component snapshot.
  - Keystrokes remain fully local. A 120 ms per-cell debounce coalesces rapid
    revisits; a per-cell latest-value queue and a serialized transport prevent
    overlapping/out-of-order saves.
  - Sheet changes flush the debounce window through the existing pending-save
    wait path.
  - Server formula responses patch only affected input elements; there is no
    table morph or reinitialization of the cell map for a normal cell commit.

- `app/Http/Controllers/SpreadsheetController.php`
  - Replaced unconditional full-workbook recalculation on API cell save with
    `recalculateForCellChange()`.
  - Returns only formula cells whose stored values changed, so the browser can
    patch them directly.
  - Removed a redundant `SpreadsheetColumn::findOrFail()` inside the save
    transaction. Authorization, archive protection, optimistic locking,
    activity history, and format support are retained.

## Formula behavior

Local UI calculation updates visible dependent cells immediately. The server
uses dependency detection to avoid a workbook calculation for unrelated
edits. When a formula is affected, cross-sheet/complex formula handling still
uses PhpSpreadsheet rather than a shortcut, preserving the authoritative
result. The returned changed cells reconcile the local optimistic values.

## Measurements and validation

No browser profiler is available in this environment, so input-to-paint,
main-thread time, DOM mutation count, browser memory, request duration, and a
before/after browser comparison were not fabricated.

Measured test run after the change:

| Check | Result |
| --- | --- |
| `SpreadsheetPerformanceOptimizationTest` | 13 passed, 42 assertions, 5.53 s |
| JavaScript parse check | passed (`node --check`) |
| Controller PHP syntax check | passed |
| Ten rapid server-side edits test | passed |
| Direct/dependent formula, cross-sheet formula, SUM range tests | passed |
| stale-version overwrite prevention | passed |
| Individual module consistency (`Analisa`, `Cost`, `Margin`, `Pendukung`, `Rank`) | passed: each starts unselected and persists an edit with a dependent-formula recalculation |
| CSV export access for a workspace leader | passed |
| TSV paste, selection lifecycle, format-cell tests | passed |

The transport change gives an inspectable production benchmark boundary:
normal cell commits contain only four business fields plus HTTP/CSRF headers,
instead of a Livewire worksheet snapshot. Browser performance profiling should
be run against a representative large workbook to record the remaining
user-facing metrics above.

The individual module coverage is implemented in
`SpreadsheetModuleConsistencyTest`; it exercises every standard worksheet by
name rather than assuming that an Analisa pass applies to the other modules.

## Known validation note

`DynamicYearManagementTest` has one unrelated failure asserting dashboard CSS
text (`opacity-60 cursor-not-allowed`). It is outside the spreadsheet route
and was not changed as part of this work.
