# Pendukung Spreadsheet Performance Report

## Investigation result

Pendukung is rendered by the same `SpreadsheetWorksheet` Livewire component,
Blade worksheet, and Alpine controller as Analisa. It has no separate click,
scroll, selection, formula, or autosave implementation. Its wider first
column is handled as layout metadata only; it does not introduce an additional
event or render path.

## Root causes and resolution

The shared interaction path had two sources of work that could be especially
visible in a large worksheet:

1. Edge-cell clicks could request a grid extension despite selecting an
   already persisted cell.
2. An older virtual-cell materialization response could write an outdated
   server-side active cell after a newer click.

The shared fix used by Pendukung now keeps persisted-cell selection local,
grows the projected canvas only for a virtual edge cell, and accepts a
materialization response only if it still matches the current client
selection. This preserves one primary active border, name box, and formula
bar during rapid clicking.

## Rendering, scroll, calculation, and save behavior

- Click selection does not call Livewire or calculate formulas.
- Scroll handling is passive and extension checks are capped at one animation
  frame; it does not update the DOM unless a grid extension is necessary.
- The isolated stacking context keeps ordinary cells below row headers,
  column headers, and the corner; text is clipped within cells.
- Typing is local. A commit is debounced and serialized, sends only the
  changed cell, and uses optimistic versions to reject stale saves.
- Formula recalculation runs only for formula commits or affected dependency
  paths; no Pendukung accounting formula was changed.

## Independent regression validation

`SpreadsheetModuleConsistencyTest` passed Pendukung's independent case for:

- blank initial selection and the shared interaction surface;
- saving an edit; and
- recalculating its dependent formula.

The full focused run passed 31 tests / 135 assertions; the virtual-grid
selection regression passed separately with 9 assertions. Browser profiler
metrics (paint latency, scroll FPS, memory, main-thread time) cannot be
measured in this workspace and are intentionally not fabricated.
