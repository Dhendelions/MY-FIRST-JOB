/**
 * Dadhen Mandiri - High-Performance Spreadsheet Worksheet Alpine.js Component
 * Optimized for instant zero-latency cell editing, optimistic local formula calculations,
 * and flicker-free background server persistence.
 */

// Helper functions for formula parsing and range expansion
function colLetterToIndex(letter) {
    letter = letter.toUpperCase();
    let index = 0;
    for (let i = 0; i < letter.length; i++) {
        index = index * 26 + (letter.charCodeAt(i) - 64);
    }
    return index;
}

function indexToColLetter(index) {
    let letter = '';
    while (index > 0) {
        let remainder = (index - 1) % 26;
        letter = String.fromCharCode(65 + remainder) + letter;
        index = Math.floor((index - 1) / 26);
    }
    return letter;
}

function expandRange(start, end) {
    const mStart = start.toUpperCase().match(/^([A-Z]+)([0-9]+)$/);
    const mEnd = end.toUpperCase().match(/^([A-Z]+)([0-9]+)$/);
    if (!mStart || !mEnd) return [];

    const colStart = colLetterToIndex(mStart[1]);
    const rowStart = parseInt(mStart[2], 10);
    const colEnd = colLetterToIndex(mEnd[1]);
    const rowEnd = parseInt(mEnd[2], 10);

    const minCol = Math.min(colStart, colEnd);
    const maxCol = Math.max(colStart, colEnd);
    const minRow = Math.min(rowStart, rowEnd);
    const maxRow = Math.max(rowStart, rowEnd);

    const coords = [];
    for (let c = minCol; c <= maxCol; c++) {
        const colLetter = indexToColLetter(c);
        for (let r = minRow; r <= maxRow; r++) {
            coords.push(colLetter + r);
        }
    }
    return coords;
}

function parseNumber(val) {
    if (val === null || val === undefined || val === '') return 0;
    if (typeof val === 'number') return isNaN(val) ? 0 : val;
    let str = String(val).trim();
    if (str === '-' || str === '') return 0;
    let isPercent = false;
    if (str.endsWith('%')) {
        isPercent = true;
        str = str.slice(0, -1).trim();
    }
    str = str.replace(/^Rp\.?\s*/i, '').trim();
    if (str.includes('.') && str.includes(',')) {
        str = str.replace(/\./g, '').replace(',', '.');
    } else if (str.includes(',') && !str.includes('.')) {
        str = str.replace(',', '.');
    }
    str = str.replace(/[^\d.-]/g, '');
    const num = parseFloat(str);
    if (isNaN(num)) return 0;
    return isPercent ? num / 100 : num;
}

/**
 * Evaluate a formula expression string.
 * Supports: SUM, AVERAGE, AVG, MIN, MAX, COUNT, IF (basic), cell references,
 * ranges (A1:B5), arithmetic operators (+,-,*,/,%), parentheses, and string literals.
 *
 * @param {string} expr      - Expression text (without leading '=')
 * @param {function} getCellVal - Function(coord:string) => raw string value of a cell
 * @returns {string} Computed result as string, or an error code.
 */
function evaluateArithmeticString(expr, getCellVal) {
    if (!expr || expr.trim() === '') return '';

    // --- helpers ---
    function resolveCell(coord) {
        const raw = getCellVal(coord.toUpperCase().replace(/\$/g, ''));
        return parseNumber(raw);
    }

    function expandRangeCoords(start, end) {
        return expandRange(start, end);
    }

    function resolveArgs(argsStr) {
        // Split by top-level commas (respecting nested parens)
        const args = [];
        let depth = 0, cur = '';
        for (let ch of argsStr) {
            if (ch === '(' ) depth++;
            else if (ch === ')') depth--;
            if (ch === ',' && depth === 0) { args.push(cur.trim()); cur = ''; }
            else cur += ch;
        }
        if (cur.trim() !== '') args.push(cur.trim());

        const nums = [];
        for (const arg of args) {
            const rangeMatch = arg.match(/^\$?([A-Z]+)\$?([0-9]+):\$?([A-Z]+)\$?([0-9]+)$/i);
            if (rangeMatch) {
                const coords = expandRangeCoords(rangeMatch[1] + rangeMatch[2], rangeMatch[3] + rangeMatch[4]);
                for (const c of coords) {
                    const n = resolveCell(c);
                    if (!isNaN(n)) nums.push(n);
                }
            } else if (/^\$?[A-Z]+\$?[0-9]+$/i.test(arg)) {
                const n = resolveCell(arg);
                if (!isNaN(n)) nums.push(n);
            } else if (!isNaN(parseFloat(arg.replace(/,/g, '.')))) {
                nums.push(parseFloat(arg.replace(/,/g, '.')));
            } else {
                // Could be a nested expression — evaluate recursively
                try {
                    const nested = evaluateArithmeticString(arg, getCellVal);
                    const n = parseFloat(nested);
                    if (!isNaN(n)) nums.push(n);
                } catch (_) { /* skip */ }
            }
        }
        return nums;
    }

    // --- resolve embedded functions before tokenizing ---
    function resolveEmbeddedFunctions(str) {
        let iterations = 0;
        while (iterations++ < 20) {
            // Match outermost function call
            const m = str.match(/([A-Z_]+)\s*\(([^()]*)\)/i);
            if (!m) break;
            const fn = m[1].toUpperCase();
            const argsStr = m[2];
            let result = '0';

            if (fn === 'SUM') {
                const nums = resolveArgs(argsStr);
                result = String(nums.reduce((a, b) => a + b, 0));
            } else if (fn === 'AVERAGE' || fn === 'AVG') {
                const nums = resolveArgs(argsStr);
                result = nums.length > 0 ? String(nums.reduce((a, b) => a + b, 0) / nums.length) : '0';
            } else if (fn === 'MIN') {
                const nums = resolveArgs(argsStr);
                result = nums.length > 0 ? String(Math.min(...nums)) : '0';
            } else if (fn === 'MAX') {
                const nums = resolveArgs(argsStr);
                result = nums.length > 0 ? String(Math.max(...nums)) : '0';
            } else if (fn === 'COUNT') {
                const nums = resolveArgs(argsStr);
                result = String(nums.length);
            } else if (fn === 'ABS') {
                const nums = resolveArgs(argsStr);
                result = nums.length > 0 ? String(Math.abs(nums[0])) : '0';
            } else if (fn === 'ROUND') {
                const nums = resolveArgs(argsStr);
                const places = nums.length > 1 ? nums[1] : 0;
                result = nums.length > 0 ? String(Math.round(nums[0] * Math.pow(10, places)) / Math.pow(10, places)) : '0';
            } else if (fn === 'IF') {
                // IF(condition, trueVal, falseVal) – only handle simple numeric conditions
                const parts = [];
                let depth2 = 0, cur = '';
                for (const ch of argsStr) {
                    if (ch === '(') depth2++;
                    else if (ch === ')') depth2--;
                    if (ch === ',' && depth2 === 0) { parts.push(cur.trim()); cur = ''; }
                    else cur += ch;
                }
                if (cur.trim()) parts.push(cur.trim());
                if (parts.length >= 2) {
                    let cond;
                    try { cond = parseFloat(evaluateArithmeticString(parts[0], getCellVal)); } catch (_) { cond = 0; }
                    result = cond ? (parts[1] || '0') : (parts[2] || '0');
                    // result may itself be an expression — resolve it
                    try { result = evaluateArithmeticString(result, getCellVal); } catch (_) { /* keep as is */ }
                }
            } else {
                // Unknown function — replace with 0 to avoid infinite loop
                result = '0';
            }

            str = str.slice(0, m.index) + result + str.slice(m.index + m[0].length);
        }
        return str;
    }

    // Step 1: Resolve all spreadsheet functions
    let processed;
    try {
        processed = resolveEmbeddedFunctions(expr.trim());
    } catch (e) {
        return '#VALUE!';
    }

    // Step 2: Replace remaining cell references with numeric values
    processed = processed.replace(/\$?([A-Z]+)\$?([0-9]+)/gi, (_, col, row) => {
        const n = resolveCell(col + row);
        return isNaN(n) ? '0' : String(n);
    });

    // Step 3: Handle percentage operator (e.g. 15% -> 0.15)
    processed = processed.replace(/([0-9.]+)%/g, (_, num) => String(parseFloat(num) / 100));

    // Step 4: Safe shunting-yard arithmetic evaluation (no eval())
    const tokens = [];
    let i = 0;
    while (i < processed.length) {
        const c = processed[i];
        if (/\s/.test(c)) { i++; continue; }
        if (/[0-9.]/.test(c)) {
            let numStr = '';
            while (i < processed.length && /[0-9.]/.test(processed[i])) numStr += processed[i++];
            tokens.push({ type: 'num', val: parseFloat(numStr) });
        } else if ('+-*/'.includes(c)) {
            tokens.push({ type: 'op', val: c });
            i++;
        } else if (c === '(') {
            tokens.push({ type: 'lparen' });
            i++;
        } else if (c === ')') {
            tokens.push({ type: 'rparen' });
            i++;
        } else {
            i++; // skip unknown chars
        }
    }

    const precedence = { '+': 1, '-': 1, '*': 2, '/': 2 };
    const outputQueue = [];
    const opStack = [];

    let prevType = null;
    for (const tok of tokens) {
        if (tok.type === 'num') {
            outputQueue.push(tok.val);
            prevType = 'num';
        } else if (tok.type === 'op') {
            let op = tok.val;
            // Unary minus
            if (op === '-' && (prevType === null || prevType === 'op' || prevType === 'lparen')) {
                op = 'u-';
            }
            const prec = op === 'u-' ? 3 : precedence[op];
            while (opStack.length) {
                const top = opStack[opStack.length - 1];
                if (top === '(' ) break;
                const topPrec = top === 'u-' ? 3 : (precedence[top] ?? 0);
                if (topPrec >= prec) outputQueue.push(opStack.pop());
                else break;
            }
            opStack.push(op);
            prevType = 'op';
        } else if (tok.type === 'lparen') {
            opStack.push('(');
            prevType = 'lparen';
        } else if (tok.type === 'rparen') {
            while (opStack.length && opStack[opStack.length - 1] !== '(') {
                outputQueue.push(opStack.pop());
            }
            if (opStack.length) opStack.pop(); // pop '('
            prevType = 'rparen';
        }
    }
    while (opStack.length) outputQueue.push(opStack.pop());

    // RPN evaluation
    const evalStack = [];
    for (const tok of outputQueue) {
        if (typeof tok === 'number') {
            evalStack.push(tok);
        } else if (tok === 'u-') {
            const a = evalStack.pop() ?? 0;
            evalStack.push(-a);
        } else if (['+', '-', '*', '/'].includes(tok)) {
            const b = evalStack.pop() ?? 0;
            const a = evalStack.pop() ?? 0;
            if (tok === '+') evalStack.push(a + b);
            else if (tok === '-') evalStack.push(a - b);
            else if (tok === '*') evalStack.push(a * b);
            else if (tok === '/') evalStack.push(b === 0 ? 0 : a / b);
        }
    }

    if (evalStack.length === 0) return '0';
    const result = evalStack[evalStack.length - 1];
    if (isNaN(result) || !isFinite(result)) return '#VALUE!';
    // Format: no unnecessary decimal
    if (Number.isInteger(result)) return String(result);
    return String(Math.round(result * 1e8) / 1e8);
}

window.spreadsheetWorksheet = function($wire) {
    return {
        // Selection and active cell state
        selectedCell: 'A1',
        selectedRowId: null,
        selectedColId: null,
        selectedRowOrder: 1,
        selectedColOrder: 1,
        selectedR: 0,
        selectedC: 0,

        // Inline cell edit mode state
        editingCell: null,
        editValue: '',
        originalEditValue: '',

        // Formula bar state
        activeCoord: 'A1',
        activeFormula: '',
        originalFormulaValue: '',
        isFormulaBarFocused: false,

        // Auto-save badge state
        saveStatus: 'Tersimpan',

        // Search and column editing state
        searchQuery: '',
        inlineEditColId: null,
        inlineEditColTitle: '',

        // Fast in-memory cell map for zero-latency lookups & calculations
        cellsMap: {},
        // Only one request per coordinate may be in flight. A newer local
        // value replaces a queued older value, preventing late responses from
        // writing over the user's most recent edit.
        cellSaveQueues: {},
        failedCellSaves: {},
        listenerController: null,
        headerResizeObserver: null,
        currentSheetId: null,
        latestSheetSwitch: null,
        sheetSwitchPromise: null,
        sheetSwitchError: '',
        lastSheetSwitchRequest: null,
        sheetScrollPositions: {},
        isSheetSwitching: false,
        gridExtensionPending: { row: false, column: false },

        init() {
            this.listenerController = new AbortController();
            this.currentSheetId = Number(this.$el.dataset.sheetId);
            // Populate in-memory cells map from rendered DOM inputs
            this.initCellsMap();

            const initialCoord = $wire.activeCellCoord || 'A1';
            this.selectedCell = initialCoord;
            this.activeCoord = initialCoord;
            this.activeFormula = $wire.formulaValue !== null && $wire.formulaValue !== undefined ? String($wire.formulaValue) : '';
            this.originalFormulaValue = this.activeFormula;

            // Apply initial selection highlight
            this.$nextTick(() => {
                const initialTd = document.getElementById('td-' + initialCoord);
                if (initialTd) initialTd.classList.add('is-selected');

                const headerRow = this.$el.querySelector('.spreadsheet-table thead tr');
                if (headerRow && typeof ResizeObserver !== 'undefined') {
                    const updateHeaderHeight = () => {
                        this.$el.style.setProperty(
                            '--spreadsheet-column-header-height',
                            headerRow.getBoundingClientRect().height + 'px'
                        );
                    };
                    this.headerResizeObserver = new ResizeObserver(updateHeaderHeight);
                    this.headerResizeObserver.observe(headerRow);
                    updateHeaderHeight();
                }
            });

            // Watch searchQuery to highlight matching cells without overhead on normal editing
            this.$watch('searchQuery', (query) => {
                this.highlightSearchMatches(query);
            });

            // Handle materialized virtual cell event
            window.addEventListener('grid-cell-materialized', (e) => {
                if (e.detail) {
                    if (e.detail.coord) {
                        this.selectedCell = e.detail.coord;
                        this.activeCoord = e.detail.coord;
                        const td = document.getElementById('td-' + e.detail.coord);
                        if (td) td.classList.add('is-selected');
                    }
                    if (e.detail.rowId) this.selectedRowId = e.detail.rowId;
                    if (e.detail.colId) this.selectedColId = e.detail.colId;
                    this.initCellsMap();
                }
            }, { signal: this.listenerController.signal });

            // Handle server confirmation of saved cell
            window.addEventListener('cell-updated-confirmed', (e) => {
                const detail = e.detail;
                if (!detail) return;
                if (detail.saveTime) {
                    this.saveStatus = 'Tersimpan ' + detail.saveTime;
                }

                // Update version of the primary saved cell
                if (detail.coord && this.cellsMap[detail.coord]) {
                    this.cellsMap[detail.coord].version = detail.version;
                    if (detail.formula !== undefined) {
                        this.cellsMap[detail.coord].formula = detail.formula || '';
                    }
                    const savedInput = document.getElementById('cell-' + detail.coord);
                    if (savedInput) savedInput.dataset.version = detail.version;
                }

                // Propagate changed formula dependents back to DOM (without re-render)
                if (detail.changedCells) {
                    for (const [coord, data] of Object.entries(detail.changedCells)) {
                        // Ensure cellsMap entry exists (for cells not yet tracked locally)
                        if (!this.cellsMap[coord]) {
                            const input = document.getElementById('cell-' + coord);
                            if (input) {
                                this.cellsMap[coord] = {
                                    coord: coord,
                                    val: data.val,
                                    formula: data.formula || '',
                                    display: data.display,
                                    rowId: input.dataset.rowId ? parseInt(input.dataset.rowId, 10) : null,
                                    colId: input.dataset.colId ? parseInt(input.dataset.colId, 10) : null,
                                    version: data.version
                                };
                            }
                        }
                        if (this.cellsMap[coord]) {
                            this.cellsMap[coord].version = data.version;
                            // If a newer edit is waiting for this coordinate,
                            // retain its optimistic value. The confirmation is
                            // still used above to advance the version required
                            // by the queued request.
                            const hasNewerQueuedEdit = !!this.cellSaveQueues[coord]?.pending;
                            if (this.editingCell !== coord && !hasNewerQueuedEdit) {
                                this.cellsMap[coord].val = data.val;
                                this.cellsMap[coord].formula = data.formula || '';
                                this.cellsMap[coord].display = data.display;

                                const input = document.getElementById('cell-' + coord);
                                if (input) {
                                    input.value = data.display;
                                    input.dataset.val = data.val;
                                    input.dataset.formula = data.formula || '';
                                    input.dataset.version = data.version;
                                }

                                // Update formula marker triangle on <td>
                                const td = document.getElementById('td-' + coord);
                                if (td) {
                                    let marker = td.querySelector('.formula-marker');
                                    if (data.formula) {
                                        if (!marker) {
                                            marker = document.createElement('div');
                                            marker.className = 'formula-marker absolute top-0 right-0 w-1.5 h-1.5 border-t-2 border-r-2 border-sky-500/70 pointer-events-none z-10';
                                            td.appendChild(marker);
                                        }
                                        marker.title = 'Formula: ' + data.formula;
                                    } else if (marker) {
                                        marker.remove();
                                    }
                                }

                                // Sync formula bar if this is the currently active cell
                                if (coord === this.selectedCell && !this.editingCell) {
                                    this.activeFormula = data.formula || data.display || '';
                                }
                            }
                        }
                    }
                }
            }, { signal: this.listenerController.signal });

            window.addEventListener('worksheet-sheet-switched', (e) => {
                const detail = e.detail || {};
                this.currentSheetId = Number(detail.sheetId);
                this.selectedCell = detail.coord || 'A1';
                this.activeCoord = this.selectedCell;
                this.selectedRowId = detail.rowId || null;
                this.selectedColId = detail.colId || null;
                this.selectedRowOrder = detail.rowOrder || 1;
                this.selectedColOrder = detail.colOrder || 1;
                this.selectedR = 0;
                this.selectedC = 0;
                this.editingCell = null;
                this.activeFormula = detail.formula || '';
                this.originalFormulaValue = this.activeFormula;
                this.isFormulaBarFocused = false;
                this.cellsMap = {};
                this.$nextTick(() => {
                    this.initCellsMap();
                    const selected = document.getElementById('td-' + this.selectedCell);
                    if (selected) selected.classList.add('is-selected');
                });
            }, { signal: this.listenerController.signal });

            // Listen for Livewire morph updates to refresh cellsMap after structural changes (addRow, etc.)
            // We only reinitialize on *structural* DOM changes (rows/columns added or removed).
            // Simple cell value morphs handled via cell-updated-confirmed event should NOT
            // trigger a full DOM scan, which is expensive on large sheets.
            if (typeof Livewire !== 'undefined') {
                let _prevRowCount = document.querySelectorAll('.grid-data-row').length;
                let _prevColCount = document.querySelectorAll('col[data-col-index]').length;
                Livewire.hook('morph.updated', () => {
                    this.$nextTick(() => {
                        const newRowCount = document.querySelectorAll('.grid-data-row').length;
                        const newColCount = document.querySelectorAll('col[data-col-index]').length;
                        if (newRowCount !== _prevRowCount || newColCount !== _prevColCount) {
                            _prevRowCount = newRowCount;
                            _prevColCount = newColCount;
                            this.initCellsMap();
                        }
                        const activeTd = document.getElementById('td-' + this.selectedCell);
                        if (activeTd) activeTd.classList.add('is-selected');
                    });
                });
            }

            // Global Keyboard Shortcuts
            window.addEventListener('keydown', (e) => {
                if (this.isSheetSwitching) return;

                const isGridCell = e.target && e.target.classList && e.target.classList.contains('grid-cell-input');
                const isFormulaBar = e.target && e.target.classList && e.target.classList.contains('formula-bar-input');
                const isStandardInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);

                // Ignore keys typed in modal or external inputs
                if (isStandardInput && !isGridCell && !isFormulaBar) {
                    return;
                }

                // Ctrl+Z: Undo
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
                    if (!this.editingCell && !this.isFormulaBarFocused) {
                        e.preventDefault();
                        $wire.undo().then(() => this.initCellsMap());
                    }
                }
                // Ctrl+Y: Redo
                else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
                    if (!this.editingCell && !this.isFormulaBarFocused) {
                        e.preventDefault();
                        $wire.redo().then(() => this.initCellsMap());
                    }
                }
                // Ctrl+F: Find & Replace
                else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
                    e.preventDefault();
                    $wire.set('showFindReplaceModal', true);
                }
                // Escape key
                else if (e.key === 'Escape') {
                    if (this.editingCell) {
                        e.preventDefault();
                        this.cancelCellEdit(this.editingCell);
                    } else if (this.isFormulaBarFocused) {
                        e.preventDefault();
                        this.cancelFormulaBar();
                    }
                }
                // Grid navigation when a cell is selected and NOT editing
                else if (!this.editingCell && !this.isFormulaBarFocused && !isStandardInput) {
                    this.handleGridNavigation(e);
                }
            }, { signal: this.listenerController.signal });
        },

        destroy() {
            this.listenerController?.abort();
            this.headerResizeObserver?.disconnect();
        },

        initCellsMap() {
            this.cellsMap = {};
            const inputs = document.querySelectorAll('.grid-cell-input');
            inputs.forEach(input => {
                const coord = input.dataset.coord;
                if (!coord) return;
                const val = input.dataset.val !== undefined ? input.dataset.val : input.value;
                const formula = input.dataset.formula || '';
                const rowId = input.dataset.rowId ? parseInt(input.dataset.rowId, 10) : null;
                const colId = input.dataset.colId ? parseInt(input.dataset.colId, 10) : null;
                this.cellsMap[coord] = {
                    coord: coord,
                    val: val,
                    formula: formula,
                    display: input.value,
                    rowId: rowId,
                    colId: colId,
                    version: input.dataset.version ? parseInt(input.dataset.version, 10) : 0
                };
            });
        },

        getCellVal(coord) {
            const cell = this.cellsMap[coord.toUpperCase()];
            if (cell) {
                return cell.val !== undefined && cell.val !== null ? cell.val : '';
            }
            return '';
        },

        formatCurrentTime() {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            return `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        },

        highlightSearchMatches(query) {
            document.querySelectorAll('.is-search-match').forEach(el => el.classList.remove('is-search-match'));
            const q = query ? query.trim().toLowerCase() : '';
            if (!q) return;

            Object.values(this.cellsMap).forEach(cell => {
                const text = (cell.formula || cell.display || cell.val || '').toString().toLowerCase();
                if (text.includes(q)) {
                    const td = document.getElementById('td-' + cell.coord);
                    if (td) td.classList.add('is-search-match');
                }
            });
        },

        /**
         * 1. Single click on a cell: SELECT ONLY (0ms latency, zero server roundtrips)
         */
        selectCell(coord, rowId, colId, r, c, rowOrder, colOrder, formula, val, displayVal) {
            if (this.editingCell && this.editingCell !== coord) {
                this.commitCellEdit(this.editingCell, this.selectedRowId, this.selectedColId, this.selectedRowOrder, this.selectedColOrder, false);
            }

            if (this.selectedCell && this.selectedCell !== coord) {
                const prevTd = document.getElementById('td-' + this.selectedCell);
                if (prevTd) {
                    prevTd.classList.remove('is-selected');
                    prevTd.classList.remove('is-editing');
                }
            }

            this.selectedCell = coord;
            this.activeCoord = coord;
            this.selectedRowId = rowId;
            this.selectedColId = colId;
            this.selectedR = r;
            this.selectedC = c;
            this.selectedRowOrder = rowOrder;
            this.selectedColOrder = colOrder;
            this.editingCell = null;

            const newTd = document.getElementById('td-' + coord);
            if (newTd) newTd.classList.add('is-selected');

            const cellData = this.cellsMap[coord];
            const rawFormula = cellData ? cellData.formula : (formula || '');
            const rawVal = cellData ? cellData.val : (val !== null && val !== undefined ? String(val) : '');
            const raw = rawFormula ? rawFormula : rawVal;

            this.activeFormula = raw;
            this.originalFormulaValue = raw;

            // Materialize virtual canvas cell only when needed
            if (!rowId || !colId) {
                $wire.ensureGridCell(rowOrder, colOrder);
            }
        },

        /**
         * 2. Double click or F2 on a cell: ENTER EDIT MODE
         */
        startEditCell(coord, rowId, colId, rowOrder, colOrder, rawValue) {
            this.selectCell(coord, rowId, colId, this.selectedR, this.selectedC, rowOrder, colOrder);

            this.editingCell = coord;
            const cellData = this.cellsMap[coord];
            // The previous value is always from cellsMap (formula string or raw value).
            // rawValue may be a single key character (typed by user) or undefined/null (F2/dblclick).
            const prevVal = cellData ? (cellData.formula || cellData.val) : '';

            const isDirectKey = rawValue !== undefined && rawValue !== null
                && String(rawValue) !== prevVal
                && String(rawValue).length === 1
                && !String(rawValue).startsWith('=');

            const initVal = (rawValue !== undefined && rawValue !== null) ? String(rawValue) : prevVal;

            // originalEditValue tracks the PREVIOUS cell value so we can detect actual changes
            this.originalEditValue = prevVal;
            this.editValue = initVal;
            this.activeFormula = initVal;

            const td = document.getElementById('td-' + coord);
            if (td) {
                td.classList.remove('is-selected');
                td.classList.add('is-editing');
            }

            const input = document.getElementById('cell-' + coord);
            if (input) {
                input.readOnly = false;
                input.value = initVal;
                input.focus();
                if (isDirectKey) {
                    // User typed a character: place cursor at end so they can keep typing
                    const len = input.value.length;
                    input.setSelectionRange(len, len);
                } else {
                    // F2 or dblclick: select all so user can replace or inspect
                    input.select();
                }
            }
        },

        handleCellInput(event, coord) {
            this.editValue = event.target.value;
            this.activeFormula = event.target.value;
        },

        /**
         * Commit changes from the cell inline editor with immediate optimistic UI & local formulas
         */
        commitCellEdit(coord, rowId, colId, rowOrder, colOrder, navigateDown = false, navigateDirection = null) {
            if (this.editingCell !== coord) {
                return;
            }

            const input = document.getElementById('cell-' + coord);
            const newValue = input ? String(input.value).trim() : (this.editValue !== null ? String(this.editValue).trim() : '');
            const changed = newValue !== this.originalEditValue;

            this.editingCell = null;
            if (input) input.readOnly = true;

            const td = document.getElementById('td-' + coord);
            if (td) {
                td.classList.remove('is-editing');
                td.classList.add('is-selected');
            }

            if (changed) {
                if (newValue.startsWith('=')) {
                    const syntaxErr = this.checkFormulaSyntax(newValue);
                    if (syntaxErr) {
                        this.notifyError(syntaxErr);
                        if (input) input.value = this.originalEditValue;
                        this.editValue = this.originalEditValue;
                        this.activeFormula = this.originalEditValue;
                        return;
                    }
                }

                // 1. Optimistic immediate local UI update and formula calculation
                this.updateCellLocally(coord, newValue, rowId, colId);

                // 2. Background persistence to server
                this.saveCellToServer(coord, rowId, colId, rowOrder, colOrder, newValue);
            }

            if (navigateDown) {
                this.navigateCell(this.selectedR + 1, this.selectedC);
            } else if (navigateDirection === 'right') {
                this.navigateCell(this.selectedR, this.selectedC + 1);
            } else if (navigateDirection === 'left') {
                this.navigateCell(this.selectedR, Math.max(0, this.selectedC - 1));
            }
        },

        updateCellLocally(coord, newValue, rowId, colId) {
            const isFormula = newValue.startsWith('=');
            const formula = isFormula ? newValue : '';
            let displayVal = newValue;

            if (isFormula) {
                displayVal = this.evaluateFormulaLocally(newValue);
            }

            if (!this.cellsMap[coord]) {
                this.cellsMap[coord] = { coord: coord, rowId: rowId, colId: colId, version: 0 };
            }
            const cell = this.cellsMap[coord];
            cell.formula = formula;
            cell.val = isFormula ? displayVal : newValue;
            cell.display = displayVal;

            const input = document.getElementById('cell-' + coord);
            if (input) {
                input.value = displayVal;
                input.dataset.val = cell.val;
                input.dataset.formula = formula;
            }

            const td = document.getElementById('td-' + coord);
            if (td) {
                let marker = td.querySelector('.formula-marker');
                if (isFormula) {
                    if (!marker) {
                        marker = document.createElement('div');
                        marker.className = 'formula-marker absolute top-0 right-0 w-1.5 h-1.5 border-t-2 border-r-2 border-sky-500/70 pointer-events-none z-10';
                        td.appendChild(marker);
                    }
                    marker.title = 'Formula: ' + formula;
                } else if (marker) {
                    marker.remove();
                }
            }

            this.recalculateDependents(coord);
        },

        evaluateFormulaLocally(formula) {
            const raw = formula.trim();
            if (!raw.startsWith('=')) return raw;
            const expr = raw.substring(1).trim();
            if (expr === '') return '';

            try {
                return evaluateArithmeticString(expr, (c) => this.getCellVal(c));
            } catch (e) {
                return '#VALUE!';
            }
        },

        formulaReferencesCoord(formula, targetCoord) {
            if (!formula || !formula.startsWith('=')) return false;
            const clean = formula.substring(1).toUpperCase();
            const target = targetCoord.toUpperCase();

            const regex = new RegExp('\\b' + target + '\\b', 'i');
            if (regex.test(clean)) return true;

            const rangeRegex = /([A-Z]+[0-9]+):([A-Z]+[0-9]+)/gi;
            let match;
            while ((match = rangeRegex.exec(clean)) !== null) {
                const coords = expandRange(match[1], match[2]);
                if (coords.includes(target)) return true;
            }
            return false;
        },

        recalculateDependents(changedCoord) {
            const visited = new Set();
            const queue = [changedCoord];

            while (queue.length > 0) {
                const currentCoord = queue.shift();
                for (const [coord, cell] of Object.entries(this.cellsMap)) {
                    if (cell.formula && !visited.has(coord)) {
                        if (this.formulaReferencesCoord(cell.formula, currentCoord)) {
                            visited.add(coord);
                            queue.push(coord);
                            const newCalculated = this.evaluateFormulaLocally(cell.formula);
                            cell.val = newCalculated;
                            cell.display = newCalculated;
                            const depInput = document.getElementById('cell-' + coord);
                            if (depInput) {
                                depInput.value = newCalculated;
                                depInput.dataset.val = newCalculated;
                            }
                        }
                    }
                }
            }
        },

        saveCellToServer(coord, rowId, colId, rowOrder, colOrder, newValue) {
            this.saveStatus = 'Menyimpan...';
            delete this.failedCellSaves[coord];
            const queue = this.cellSaveQueues[coord] || { running: false, pending: null, waiters: [] };
            // Coalesce rapid commits to the same cell. This mirrors normal
            // spreadsheet behavior: the final committed value is authoritative.
            queue.pending = { rowId, colId, rowOrder, colOrder, newValue };
            this.cellSaveQueues[coord] = queue;
            this.drainCellSaveQueue(coord);
        },

        drainCellSaveQueue(coord) {
            const queue = this.cellSaveQueues[coord];
            if (!queue || queue.running || !queue.pending) return;

            const request = queue.pending;
            queue.pending = null;
            queue.running = true;

            const persist = (rowId, colId) => {
                const cell = this.cellsMap[coord];
                // Read the version immediately before dispatching, after any
                // preceding request confirmation has updated the local map.
                const version = cell ? cell.version : 0;
                return $wire.updateCell(rowId, colId, request.newValue, version);
            };

            const save = (!request.rowId || !request.colId)
                ? $wire.ensureGridCell(request.rowOrder, request.colOrder).then(() => {
                    const freshRowId = $wire.activeRowId;
                    const freshColId = $wire.activeColumnId;
                    const cell = this.cellsMap[coord];
                    if (cell) {
                        cell.rowId = freshRowId;
                        cell.colId = freshColId;
                    }
                    return persist(freshRowId, freshColId);
                })
                : persist(request.rowId, request.colId);

            Promise.resolve(save)
                .then(() => {
                    this.saveStatus = 'Tersimpan ' + this.formatCurrentTime();
                    delete this.failedCellSaves[coord];
                })
                .catch((err) => {
                    console.error('Save error', err);
                    this.saveStatus = 'Gagal menyimpan';
                    this.failedCellSaves[coord] = request;
                })
                .finally(() => {
                    queue.running = false;
                    if (queue.pending) {
                        this.drainCellSaveQueue(coord);
                    } else {
                        const waiters = queue.waiters.splice(0);
                        delete this.cellSaveQueues[coord];
                        waiters.forEach(resolve => resolve());
                    }
                });
        },

        async waitForPendingCellSaves() {
            const pendingSaves = Object.entries(this.cellSaveQueues).map(([, queue]) => new Promise(resolve => {
                queue.waiters.push(resolve);
            }));

            await Promise.all(pendingSaves);

            if (Object.keys(this.failedCellSaves).length > 0) {
                throw new Error('Ada perubahan yang belum berhasil disimpan.');
            }
        },

        requestSheetSwitch(sheetId, url, historyMode = 'push') {
            if (this.activeFormula !== this.originalFormulaValue && !this.commitFormulaBar()) {
                return Promise.resolve(false);
            }

            if (!this.sheetSwitchPromise && Number(sheetId) === this.currentSheetId) {
                return Promise.resolve(true);
            }

            const request = { sheetId: Number(sheetId), url, historyMode };
            this.latestSheetSwitch = request;
            this.sheetSwitchError = '';
            this.lastSheetSwitchRequest = request;

            if (this.sheetSwitchPromise) return this.sheetSwitchPromise;

            this.isSheetSwitching = true;
            this.sheetSwitchPromise = (async () => {
                while (this.latestSheetSwitch) {
                    const next = this.latestSheetSwitch;
                    this.latestSheetSwitch = null;

                    if (next.sheetId === this.currentSheetId) continue;

                    try {
                        await this.waitForPendingCellSaves();

                        const scrollArea = this.$el.querySelector('.spreadsheet-scroll');
                        if (scrollArea) {
                            this.sheetScrollPositions[this.currentSheetId] = {
                                top: scrollArea.scrollTop,
                                left: scrollArea.scrollLeft,
                            };
                            scrollArea.classList.remove('sheet-transition-enter');
                            scrollArea.classList.add('sheet-transition-exit');
                        }

                        await $wire.switchSheet(next.sheetId);

                        if (this.latestSheetSwitch) continue;

                        if (next.historyMode === 'push') {
                            history.pushState(
                                { ...(history.state || {}), worksheetSheetId: next.sheetId },
                                '',
                                next.url
                            );
                        }

                        this.currentSheetId = next.sheetId;
                        this.lastSheetSwitchRequest = null;
                        this.sheetSwitchError = '';

                        if (scrollArea) {
                            scrollArea.classList.remove('sheet-transition-exit');
                            scrollArea.classList.add('sheet-transition-enter');
                            const position = this.sheetScrollPositions[next.sheetId] || { top: 0, left: 0 };
                            scrollArea.scrollTop = position.top;
                            scrollArea.scrollLeft = position.left;
                        }
                    } catch (error) {
                        const scrollArea = this.$el.querySelector('.spreadsheet-scroll');
                        if (scrollArea) scrollArea.classList.remove('sheet-transition-exit');

                        if (this.latestSheetSwitch) continue;

                        this.sheetSwitchError = Object.keys(this.failedCellSaves).length > 0
                            ? 'Perubahan belum tersimpan. Coba simpan lagi sebelum berpindah lembar.'
                            : 'Lembar tidak dapat dimuat. Silakan coba lagi.';
                        this.lastSheetSwitchRequest = next;
                        console.error('Sheet switch error', error);
                    }
                }
            })().finally(() => {
                this.isSheetSwitching = false;
                this.sheetSwitchPromise = null;
            });

            return this.sheetSwitchPromise;
        },

        retrySheetSwitch() {
            const request = this.lastSheetSwitchRequest;
            if (!request) return;

            Object.entries(this.failedCellSaves).forEach(([coord, save]) => {
                delete this.failedCellSaves[coord];
                this.saveCellToServer(coord, save.rowId, save.colId, save.rowOrder, save.colOrder, save.newValue);
            });

            this.requestSheetSwitch(request.sheetId, request.url, request.historyMode);
        },

        extendGrid(axis) {
            const limit = axis === 'row' ? 10000 : 702;
            const property = axis === 'row' ? 'gridRowLimit' : 'gridColumnLimit';
            if (this.gridExtensionPending[axis] || Number($wire[property]) >= limit) return;

            this.gridExtensionPending[axis] = true;
            $wire.extendGrid(axis).finally(() => {
                this.gridExtensionPending[axis] = false;
            });
        },

        maybeExtendGrid(event) {
            const scrollArea = event.currentTarget;
            if (!scrollArea || scrollArea.scrollHeight - scrollArea.scrollTop - scrollArea.clientHeight > 160) return;
            this.extendGrid('row');
        },

        startSheetTransition(direction) {
            const scrollArea = this.$el.querySelector('.spreadsheet-scroll');
            if (!scrollArea) return;
            scrollArea.classList.remove('sheet-transition-enter', 'sheet-transition-exit');
            scrollArea.classList.add('sheet-transition-' + direction);
        },

        /**
         * Cancel editing and restore original value (Escape)
         */
        cancelCellEdit(coord) {
            this.editValue = this.originalEditValue;
            this.activeFormula = this.originalEditValue;
            const input = document.getElementById('cell-' + coord);
            if (input) {
                input.value = this.originalEditValue;
                input.readOnly = true;
            }
            const td = document.getElementById('td-' + coord);
            if (td) {
                td.classList.remove('is-editing');
                td.classList.add('is-selected');
            }
            this.editingCell = null;
        },

        /**
         * Formula Bar: Commit on Enter
         */
        commitFormulaBar() {
            const raw = this.activeFormula !== null ? String(this.activeFormula).trim() : '';
            const changed = raw !== this.originalFormulaValue;

            if (!changed) {
                return true;
            }

            if (raw.startsWith('=')) {
                const syntaxErr = this.checkFormulaSyntax(raw);
                if (syntaxErr) {
                    this.notifyError(syntaxErr);
                    return false;
                }
            }

            this.updateCellLocally(this.selectedCell, raw, this.selectedRowId, this.selectedColId);
            this.saveCellToServer(this.selectedCell, this.selectedRowId, this.selectedColId, this.selectedRowOrder, this.selectedColOrder, raw);
            this.originalFormulaValue = raw;
            return true;
        },

        /**
         * Formula Bar: Cancel on Escape
         */
        cancelFormulaBar() {
            this.activeFormula = this.originalFormulaValue;
            const input = this.$refs.formulaBarInput;
            if (input) input.blur();
        },

        formatCell(property, value = null) {
            if (!this.selectedRowId || !this.selectedColId) return;
            $wire.formatCell(property, value, this.selectedRowId, this.selectedColId);
        },

        checkFormulaSyntax(formula) {
            if (!formula.startsWith('=')) return null;
            const expr = formula.substring(1).trim();
            if (expr === '') return 'Formula tidak boleh kosong setelah tanda sama dengan (=).';

            const openParens = (formula.match(/\(/g) || []).length;
            const closeParens = (formula.match(/\)/g) || []).length;
            if (openParens !== closeParens) {
                return 'Jumlah tanda kurung buka "(" dan tutup ")" tidak seimbang.';
            }

            if (/[+\-*\/]$/.test(expr)) {
                return 'Formula berakhir dengan operator yang tidak lengkap.';
            }

            return null;
        },

        notifyError(message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: message, type: 'error' } }));
        },

        handleGridNavigation(e) {
            if (this.editingCell || this.isFormulaBarFocused) return;

            const totalRows = document.querySelectorAll('.grid-data-row').length;
            const totalCols = document.querySelectorAll('col[data-col-index]').length;

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (this.selectedR > 0) this.navigateCell(this.selectedR - 1, this.selectedC);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (this.selectedR < totalRows - 1) this.navigateCell(this.selectedR + 1, this.selectedC);
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                if (this.selectedC > 0) this.navigateCell(this.selectedR, this.selectedC - 1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                if (this.selectedC < totalCols - 1) this.navigateCell(this.selectedR, this.selectedC + 1);
            } else if (e.key === 'Tab') {
                e.preventDefault();
                if (e.shiftKey) {
                    if (this.selectedC > 0) this.navigateCell(this.selectedR, this.selectedC - 1);
                    else if (this.selectedR > 0) this.navigateCell(this.selectedR - 1, totalCols - 1);
                } else {
                    if (this.selectedC < totalCols - 1) this.navigateCell(this.selectedR, this.selectedC + 1);
                    else if (this.selectedR < totalRows - 1) this.navigateCell(this.selectedR + 1, 0);
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (e.shiftKey) {
                    if (this.selectedR > 0) this.navigateCell(this.selectedR - 1, this.selectedC);
                } else {
                    if (this.selectedR < totalRows - 1) {
                        this.navigateCell(this.selectedR + 1, this.selectedC);
                    } else {
                        $wire.addRow().then(() => {
                            setTimeout(() => { this.navigateCell(this.selectedR + 1, this.selectedC); }, 150);
                        });
                    }
                }
            } else if (e.key === 'F2') {
                e.preventDefault();
                this.startEditCell(this.selectedCell, this.selectedRowId, this.selectedColId, this.selectedRowOrder, this.selectedColOrder, this.activeFormula);
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                if (this.selectedRowId && this.selectedColId) {
                    e.preventDefault();
                    this.updateCellLocally(this.selectedCell, '', this.selectedRowId, this.selectedColId);
                    this.saveCellToServer(this.selectedCell, this.selectedRowId, this.selectedColId, this.selectedRowOrder, this.selectedColOrder, '');
                    this.activeFormula = '';
                }
            } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                this.startEditCell(this.selectedCell, this.selectedRowId, this.selectedColId, this.selectedRowOrder, this.selectedColOrder, e.key);
            }
        },

        navigateCell(r, c) {
            // Use cached references when possible to avoid repeated DOM scans.
            const rows = document.querySelectorAll('.grid-data-row');
            if (rows && rows[r]) {
                const cells = rows[r].querySelectorAll('td[data-coord]');
                if (cells && cells[c]) {
                    cells[c].click();
                    cells[c].scrollIntoView({ block: 'nearest', inline: 'nearest' });
                }
            }
        },

        handlePaste(event, rowId, colId) {
            let text = (event.clipboardData || window.clipboardData).getData('text');
            if (text && (text.includes('\t') || text.includes('\n'))) {
                event.preventDefault();
                this.saveStatus = 'Menyimpan...';
                $wire.pasteTsvData(rowId, colId, text).then(() => {
                    this.saveStatus = 'Tersimpan ' + this.formatCurrentTime();
                    this.$nextTick(() => {
                        this.initCellsMap();
                    });
                });
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
