# Rank Spreadsheet Performance Report

## Investigation result

Rank shares the same `SpreadsheetWorksheet` Livewire component, Blade grid,
and Alpine controller as Analisa and Pendukung. There is no Rank-specific
selection, scroll listener, formula engine, or autosave transport that could
diverge from the optimized interaction path.

## Root causes and resolution

The shared grid had two rapid-interaction risks:

1. A click near a projected-grid boundary could create a needless Livewire
   extension request even for a persisted Rank cell.
2. A delayed virtual-cell response could replace the user’s newer selection
   with an older coordinate.

The fixed path makes ordinary Rank selection purely local and ignores stale
materialization events. Virtual-cell save operations now use returned row and
column IDs instead of active selection state. This prevents accumulated
selection borders and keeps the name box/formula bar on the latest cell.

## Rendering, scroll, calculation, and save behavior

- No formula calculation or database synchronization occurs on selection.
- Scroll extension work is passive and requestAnimationFrame-limited.
- The grid has an explicit low-to-high sticky layering order: normal cells,
  row headers, column headers, corner. Cell text cannot paint over sticky
  surfaces.
- Cell input remains immediate and local; commits are debounced, serialized,
  version-protected, and patch only affected formula cells without a grid
  morph.
- Existing Rank formulas, values, accounting rules, history, paste,
  keyboard navigation, and export logic were not changed.

## Independent regression validation

`SpreadsheetModuleConsistencyTest` passed Rank's independent case for:

- blank initial selection and common interaction surface;
- edit persistence; and
- dependent-formula recalculation.

The focused validation passed 31 tests / 135 assertions, and the dedicated
virtual-grid selection test passed with 9 assertions. Browser-only measures
such as click-to-paint time, FPS, memory, and main-thread blocking are not
available in this environment, so no before/after numerical figures are
claimed.

## Cost-first Phase 2 validation

Rank work began only after the dedicated Cost regression test passed. Rank
also received the shared morph-listener lifecycle correction: there is one
stateless Livewire morph relay per page, while the active Rank worksheet owns
an abortable, animation-frame-coalesced listener. This prevents prior
worksheet instances from retaining callbacks and repeatedly scanning the
current grid after navigation.

`test_rank_selection_stays_local_and_formula_commits_remain_correct` passed.
The combined focused suite subsequently passed 33 tests / 141 assertions;
the virtual materialization regression passed separately with 9 assertions.
