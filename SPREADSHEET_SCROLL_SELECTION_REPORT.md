# Spreadsheet Scroll, Layering, and Selection Report

## Root causes

1. The selected and edited cell CSS promoted ordinary body cells to z-index 25
   and 30. This placed selected content above frozen left cells (22) and at the
   same level as normal column headers (30), causing the observed overlap.
2. The worksheet began with `A1` in Livewire, Blade, and Alpine state. It was
   therefore selected both in the rendered HTML and again during Alpine init.
3. The scroll listener was passive and already limited to grid extension, but
   it performed its bottom-of-grid measurement for every native scroll event.
   It is now coalesced into one `requestAnimationFrame` per frame.

## Changes

### Layer hierarchy

The worksheet scroll area is now an isolated stacking context. The deliberate
levels are:

| Surface | z-index |
| --- | ---: |
| Ordinary scrolling cells | 0 |
| Frozen-left cells | 10 |
| Frozen-top cells | 15 |
| Frozen top/left intersection | 18 |
| Row headers / fixed row headers | 20 / 24 |
| Column headers / frozen column headers | 30 / 35 |
| Top-left corner | 40 |

Selection and editing retain their inset outlines but no longer change z-index.
Cell and input overflow is clipped and non-wrapped text uses ellipsis, so text
cannot paint into adjacent or sticky cells.

### Selection

- A new sheet starts with no `activeCellCoord`, row id, column id, formula
  value, or selected-cell CSS class.
- Click selection stays client-owned and changes only the previous/current cell
  classes; it does not make a backend request.
- Sheet switching resets selection. Keyboard navigation does nothing until the
  user selects a cell, avoiding accidental A1 activation.
- Formula-bar commit is safely ignored until a persisted cell is selected.

### Scroll work

- The scroll handler remains passive and now schedules at most one extension
  check per animation frame.
- The check does not mutate the DOM unless the user is near the bottom and an
  extension is actually needed.
- Header-height measurement remains `ResizeObserver`-based, not scroll-based.
- The scroll surface uses `isolation: isolate` and `overscroll-behavior:
  contain`; no transforms or promoted body-cell z-indexes are used.

## Files changed

- `resources/views/livewire/spreadsheet-worksheet.blade.php`
- `public/js/spreadsheet-worksheet.js`
- `app/Livewire/SpreadsheetWorksheet.php`
- `tests/Feature/SpreadsheetFormulaAndInteractionTest.php`
- `tests/Feature/SpreadsheetWorkflowTest.php`

## Validation

Passed locally:

- JavaScript parse check and Livewire component PHP syntax check.
- `SpreadsheetFormulaAndInteractionTest`: 8 tests passed, including the new
  no-initial-selection assertion.
- `SpreadsheetPerformanceOptimizationTest`: 13 tests passed.
- Sticky-header template test: passed.
- Total focused results: 21 tests, 103 assertions.
- `SpreadsheetModuleConsistencyTest`: all five standard worksheets passed
  independently for blank initial selection, the common interaction surface,
  editing, and dependent-formula recalculation.
- `SpreadsheetWorkflowTest::test_csv_export_remains_available_for_the_current_sheet`:
  passed after aligning passwordless export authorization with the user's
  workspace membership role.

This environment has no connected browser/visual test runner, so physical
mouse-wheel/scrollbar-drag rendering and compositor timing could not be
observed here. Those scenarios remain the required final manual browser QA;
they have not been represented as completed visual measurements.
