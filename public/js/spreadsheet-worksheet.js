/**
 * Dadhen Mandiri - Spreadsheet Worksheet Alpine.js Component
 */
window.spreadsheetWorksheet = function($wire) {
    return {
        activeR: 0,
        activeC: 0,
        activeCoord: 'A1',
        activeFormula: '',

        init() {
            // Watch active cell and formula synchronization
            this.$watch('$wire.activeCellCoordinate', val => {
                if (val) this.activeCoord = val;
            });
            this.$watch('$wire.formulaBarValue', val => {
                this.activeFormula = val || '';
            });
            this.activeCoord = $wire.activeCellCoordinate || 'A1';
            this.activeFormula = $wire.formulaBarValue || '';

            // Global Keyboard Shortcuts
            window.addEventListener('keydown', (e) => {
                // Ignore if typing inside standard inputs outside the grid
                if (['INPUT', 'TEXTAREA'].includes(e.target.tagName) && !e.target.classList.contains('grid-cell-input')) {
                    return;
                }

                // Ctrl+Z: Undo
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
                    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
                        // Let native undo work if cursor inside input, otherwise trigger Livewire undo
                    } else {
                        e.preventDefault();
                        $wire.undo();
                    }
                }
                // Ctrl+Y: Redo
                else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
                    e.preventDefault();
                    $wire.redo();
                }
                // Ctrl+F: Find & Replace
                else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
                    e.preventDefault();
                    $wire.set('showFindReplaceModal', true);
                }
            });
        },

        focusCell(r, c, coord, rowId, colId, rowOrder, colOrder) {
            this.activeR = r;
            this.activeC = c;
            this.activeCoord = coord;
            if (!rowId || !colId) {
                $wire.ensureGridCell(rowOrder, colOrder);
                return;
            }
            $wire.selectCell(rowId, colId, coord);
        },

        onKeyDown(event, r, c, totalRows, totalCols, rowId, colId, version) {
            if (!rowId || !colId) return;
            // Delete / Backspace when not in text edit mode
            if (event.key === 'Delete') {
                $wire.updateCell(rowId, colId, '', version);
            }
            // Enter or Shift+Enter
            else if (event.key === 'Enter') {
                event.preventDefault();
                if (event.shiftKey) {
                    if (r > 0) this.navigateCell(r - 1, c);
                } else {
                    if (r < totalRows - 1) {
                        this.navigateCell(r + 1, c);
                    } else {
                        $wire.addRow().then(() => {
                            setTimeout(() => { this.navigateCell(r + 1, c); }, 150);
                        });
                    }
                }
            }
            // Tab or Shift+Tab
            else if (event.key === 'Tab') {
                event.preventDefault();
                if (event.shiftKey) {
                    if (c > 0) this.navigateCell(r, c - 1);
                    else if (r > 0) this.navigateCell(r - 1, totalCols - 1);
                } else {
                    if (c < totalCols - 1) this.navigateCell(r, c + 1);
                    else if (r < totalRows - 1) this.navigateCell(r + 1, 0);
                    else {
                        $wire.addRow().then(() => {
                            setTimeout(() => { this.navigateCell(r + 1, 0); }, 150);
                        });
                    }
                }
            }
            // Arrow Keys
            else if (event.key === 'ArrowUp' && r > 0) {
                event.preventDefault();
                this.navigateCell(r - 1, c);
            }
            else if (event.key === 'ArrowDown' && r < totalRows - 1) {
                event.preventDefault();
                this.navigateCell(r + 1, c);
            }
            else if (event.key === 'ArrowLeft' && c > 0 && event.target.selectionStart === 0) {
                event.preventDefault();
                this.navigateCell(r, c - 1);
            }
            else if (event.key === 'ArrowRight' && c < totalCols - 1 && event.target.selectionEnd === event.target.value.length) {
                event.preventDefault();
                this.navigateCell(r, c + 1);
            }
        },

        navigateCell(r, c) {
            let rowEl = document.querySelectorAll('.grid-data-row')[r];
            if (rowEl) {
                let cellInput = rowEl.querySelectorAll('.grid-cell-input')[c];
                if (cellInput) {
                    cellInput.focus();
                }
            }
        },

        handlePaste(event, rowId, colId) {
            let text = (event.clipboardData || window.clipboardData).getData('text');
            if (text && (text.includes('\t') || text.includes('\n'))) {
                event.preventDefault();
                $wire.pasteTsvData(rowId, colId, text);
            }
        },

        resizeColumn(event, columnId, columnOrder, startingWidth) {
            event.preventDefault();
            const startX = event.clientX;
            const onMove = (moveEvent) => {
                const width = Math.max(64, Math.min(900, startingWidth + moveEvent.clientX - startX));
                const colEl = document.querySelector('col[data-col-index="' + columnOrder + '"]');
                if (colEl) {
                    colEl.style.width = width + 'px';
                    colEl.style.minWidth = width + 'px';
                    colEl.style.maxWidth = width + 'px';
                }
                const thEl = document.querySelector('th[data-grid-column="' + columnOrder + '"]');
                if (thEl) {
                    thEl.style.width = width + 'px';
                    thEl.style.minWidth = width + 'px';
                    thEl.style.maxWidth = width + 'px';
                }
            };
            const onUp = (upEvent) => {
                const width = Math.max(64, Math.min(900, Math.round(startingWidth + upEvent.clientX - startX)));
                window.removeEventListener('pointermove', onMove);
                window.removeEventListener('pointerup', onUp);
                if (columnId) $wire.setColumnWidth(columnId, width);
                else $wire.ensureColumn(columnOrder, width);
            };
            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp, { once: true });
        },

        resizeRow(event, rowId, rowOrder, startingHeight) {
            event.preventDefault();
            const startY = event.clientY;
            const rowElement = event.currentTarget.closest('tr');
            const onMove = (moveEvent) => {
                const height = Math.max(20, Math.min(400, Math.round(startingHeight + moveEvent.clientY - startY)));
                rowElement.style.height = height + 'px';
                rowElement.querySelectorAll('th, td, .grid-cell-input').forEach((cell) => { cell.style.height = height + 'px'; });
            };
            const onUp = (upEvent) => {
                const height = Math.max(20, Math.min(400, Math.round(startingHeight + upEvent.clientY - startY)));
                window.removeEventListener('pointermove', onMove);
                window.removeEventListener('pointerup', onUp);
                if (rowId) $wire.setRowHeight(rowId, height);
                else $wire.ensureRow(rowOrder, height);
            };
            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp, { once: true });
        }
    };
};

if (typeof Alpine !== 'undefined') {
    Alpine.data('spreadsheetWorksheet', (wire) => window.spreadsheetWorksheet(wire));
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('spreadsheetWorksheet', (wire) => window.spreadsheetWorksheet(wire));
    });
}
