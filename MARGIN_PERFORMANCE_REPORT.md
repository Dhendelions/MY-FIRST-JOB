# Margin Spreadsheet Performance Investigation

## Scope

Margin uses the same `SpreadsheetWorksheet` Livewire component, Blade grid,
and Alpine controller as Analisa, Cost, Pendukung, and Rank. The Margin work
therefore focuses on behavior that becomes especially visible in a large or
heavily used Margin canvas, without changing formulas or accounting data.

## Root causes found

1. A click near the projected grid edge called `extendGrid()` even if the
   clicked cell was already persisted. That created a Livewire request and a
   structural grid render during what should have been a local selection.
2. `ensureGridCell()` wrote the materialized coordinate into Livewire's active
   selection properties. If users clicked virtual cells quickly, an older
   response could repaint its cell as selected after a later click.
3. The client handled every materialization event as authoritative, so it
   could overwrite the name-box/formula-bar selection state with an outdated
   coordinate.

## Changes

- `app/Livewire/SpreadsheetWorksheet.php`
  - `ensureGridCell()` now returns its materialized cell metadata and leaves
    active selection client-owned.
- `public/js/spreadsheet-worksheet.js`
  - Materialization events refresh data but change selection only when their
    coordinate is still the current selection.
  - Autosave uses returned row/column IDs, rather than selection state, when
    saving a newly materialized cell.
  - Added `maybeExtendGridForCell()`: persisted-cell selection is entirely
    local; canvas growth is requested only for a virtual edge cell.
- `resources/views/livewire/spreadsheet-worksheet.blade.php`
  - Cell-click wiring uses the guarded extension path above.

The existing client-owned selection, direct DOM class replacement, passive
requestAnimationFrame-limited scroll extension, debounced serialized autosave,
and dependency-aware formula recalculation remain in use. Selection sends no
Livewire request and does not recalculate formulas.

## Validation

- `SpreadsheetWorkflowTest::test_canvas_projects_a_to_z_and_materializes_only_an_interacted_coordinate`: passed.
- `SpreadsheetFormulaAndInteractionTest`: passed in the focused run.
- PHP and JavaScript syntax checks: passed.
- Existing module-consistency coverage includes Margin's unselected startup,
  edit persistence, and dependent-formula recalculation.

No browser performance runner is connected to this workspace. Therefore
click-to-paint latency, scroll FPS, browser memory, main-thread time, and a
before/after numerical benchmark were not claimed. The implementation removes
the identifiable selection-time requests and stale-response path; manual
browser QA should still exercise 10/50 rapid clicks, rapid virtual-cell clicks,
editing while saves are pending, and horizontal/vertical scroll on a large
Margin workbook.
