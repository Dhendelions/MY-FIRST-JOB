# Cost Performance and Selection-Stability Report

## Phase 1 result

Cost was validated before Rank. It shares the standard worksheet controller
with Analisa, but a global Livewire morph hook was being registered by each
Alpine worksheet instance. Reopening or navigating worksheets could retain
stale component callbacks and multiply structural-grid work over time.

## Source changes

- `public/js/spreadsheet-worksheet.js`
  - Replaced per-instance global `Livewire.hook('morph.updated')` callbacks
    with one stateless page relay.
  - Each active worksheet registers an abortable listener and coalesces morph
    processing to one animation frame.
  - Only a changed row/column count rebuilds the cell map.
  - Existing local selection, stale virtual-cell response protection, guarded
    grid expansion, direct cell save transport, and serialized autosave queue
    remain active.
- `tests/Feature/SpreadsheetModuleConsistencyTest.php`
  - Added an explicit Cost selection/commit/formula regression test.

## Selection and interaction behavior

Persisted Cost cell selection is local state: it changes the old/new DOM
selection classes, name box, and formula bar without a Livewire request or a
formula recalculation. A materialized virtual cell cannot restore an older
selection. This retains exactly one primary selection border in the normal
single-cell path.

## Validation before Rank

- Dedicated Cost test: passed (3 assertions).
- Virtual materialization test: passed (9 assertions).
- PHP and JavaScript syntax checks: passed.

Browser-only stress figures for 10/50/100 real clicks, paint latency, FPS,
memory, and long-duration interaction are unavailable in this workspace and
were not fabricated. The automated tests verify the server-side data,
selection initialization contract, formula results, and structural-cell path.
