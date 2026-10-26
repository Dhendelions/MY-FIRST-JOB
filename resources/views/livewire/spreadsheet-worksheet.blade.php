{{-- Do not poll the worksheet root: a Livewire morph replaces the interactive
     grid and caused cells to disappear after the first 10-second refresh. --}}
<div class="relative flex-1 flex flex-col min-h-0 min-w-0 bg-white"
     x-data="spreadsheetWorksheet($wire)"
     data-sheet-id="{{ $sheetId }}"
     style="--sheet-accent: {{ $sheet->identityColor()['active'] }};">

    <style>
        @keyframes worksheet-sheet-enter {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes worksheet-sheet-exit {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-3px); }
        }
        .sheet-transition-enter {
            animation: worksheet-sheet-enter 180ms ease-out both;
        }
        .sheet-transition-exit {
            animation: worksheet-sheet-exit 150ms ease-in both;
        }
        @media (prefers-reduced-motion: reduce) {
            .sheet-transition-enter,
            .sheet-transition-exit {
                animation: none;
                transform: none;
                opacity: 1;
            }
            .sheet-switch-overlay,
            .sheet-switch-spinner {
                transition: none !important;
                animation: none !important;
            }
        }
        .grid-cell-td.is-selected {
            box-shadow: inset 0 0 0 2px var(--sheet-accent) !important;
            z-index: 25;
        }
        .grid-cell-td.is-editing {
            box-shadow: inset 0 0 0 2px #0284c7 !important;
            background-color: #ffffff !important;
            z-index: 30;
        }
        .grid-cell-td.is-search-match {
            background-color: rgba(254, 240, 138, 0.6) !important;
        }
        .formula-bar-container {
            border-top: 2px solid var(--sheet-accent);
        }
    </style>

    <!-- FORMULA BAR (COORDINATE + FX INPUT) -->
    <div class="h-10 bg-white border-b border-slate-200 px-3 flex items-center gap-2 text-xs shrink-0 shadow-2xs formula-bar-container">
        <!-- Coordinate Box (e.g. A1, B4) -->
        <div class="w-16 h-7 bg-slate-100 border border-slate-300 rounded flex items-center justify-center font-mono font-bold text-slate-800 shadow-inner">
            <span x-text="activeCoord">A1</span>
        </div>

        <!-- Fx Indicator -->
        <div class="px-2 font-mono font-bold italic text-slate-400 select-none">
            fx
        </div>

        <span class="w-px h-5 bg-slate-200"></span>

        <!-- Synced Formula Bar Text Input -->
        <div class="flex-1 flex items-center min-w-0">
            <input type="text" 
                   x-ref="formulaBarInput"
                   x-model="activeFormula" 
                   @focus="isFormulaBarFocused = true; originalFormulaValue = activeFormula;"
                   @blur="isFormulaBarFocused = false"
                   @keydown.enter.prevent="commitFormulaBar()"
                   @keydown.escape.prevent="cancelFormulaBar()"
                   placeholder="Ketik nilai, formula (contoh: =SUM(A1:A5), =A1+B1), atau tempel data..."
                   class="formula-bar-input w-full h-7 px-2.5 text-xs text-slate-800 font-sans focus:outline-none focus:bg-sky-50/30 rounded border border-transparent focus:border-sky-300 transition-colors">
        </div>

        <!-- Auto-save Status Badge -->
        <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60 shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span wire:loading wire:target="updateCell,finishCellEdit,pasteTsvData,formatCell">Menyimpan</span>
            <span wire:loading.remove wire:target="updateCell,finishCellEdit,pasteTsvData,formatCell" x-text="saveStatus">Tersimpan</span>
        </div>
    </div>

    <!-- GRID TABEL SPREADSHEET (MICROSOFT EXCEL LOOK & FEEL) -->
    <div class="flex-1 overflow-auto min-h-0 min-w-0 bg-slate-200 select-text relative spreadsheet-scroll" aria-label="Area kerja spreadsheet">
        <table class="spreadsheet-table bg-white w-max table-fixed text-xs border-separate border-spacing-0">
            <colgroup>
                <col style="width: 48px; min-width: 48px; max-width: 48px;">
                @foreach($gridColumns as $cIdx => $col)
                    <col data-col-index="{{ $col['sort_order'] }}" style="width: {{ $col['width'] }}px; min-width: {{ $col['width'] }}px; max-width: {{ $col['width'] }}px;">
                @endforeach
            </colgroup>
            
            <!-- STICKY COLUMN HEADERS (A, B, C...) -->
            <thead class="bg-slate-100 select-none shadow-xs">
                <tr>
                    <!-- Top-Left Corner (Row Header intersection) -->
                    <th class="sticky top-0 left-0 z-40 w-12 min-w-[48px] bg-slate-200 border-r border-b border-slate-300 p-0 text-center text-slate-400 text-[10px] font-mono">
                        #
                    </th>

                    <!-- Column Headers -->
                    @foreach($gridColumns as $cIdx => $col)
                        <th style="position:sticky;top:0;width: {{ $col['width'] }}px; min-width: {{ $col['width'] }}px; max-width: {{ $col['width'] }}px; {{ $cIdx < $freezeLeftColumnCount ? 'left:' . $frozenColumnLeftOffsets[$cIdx] . 'px;z-index:35;' : 'z-index:30;' }}" 
                            data-grid-column="{{ $col['sort_order'] }}" wire:key="grid-header-{{ $col['sort_order'] }}"
                            class="border-r border-b border-slate-300 bg-slate-100 font-normal p-0 relative group">
                            
                            <div class="flex items-center justify-between px-2 py-1.5 text-slate-700 min-h-9">
                                
                                <!-- Column Letter & Editable Title -->
                                <div class="flex-1 flex items-center gap-1.5 min-w-0" 
                                     @dblclick="if ({{ $col['id'] ?? 0 }}) { inlineEditColId = {{ $col['id'] ?? 0 }}; inlineEditColTitle = '{{ addslashes($col['title'] ?? '') }}'; $nextTick(() => $refs['colInput{{ $col['id'] ?? 0 }}'].focus()) }">
                                    
                                    <!-- Excel Letter (A, B, C...) -->
                                    <span class="font-bold text-slate-900 font-mono text-xs">{{ $col['letter'] }}</span>

                                    <!-- Column Title Display or Inline Edit -->
                                    <template x-if="inlineEditColId === {{ $col['id'] ?? 0 }}">
                                        <input x-ref="colInput{{ $col['id'] ?? 0 }}" 
                                               type="text" 
                                               x-model="inlineEditColTitle" 
                                               @keydown.enter="inlineEditColId = null; $wire.updateColumnTitle({{ $col['id'] ?? 0 }}, inlineEditColTitle)"
                                               @blur="inlineEditColId = null; $wire.updateColumnTitle({{ $col['id'] ?? 0 }}, inlineEditColTitle)"
                                               class="w-full text-xs px-1 py-0.5 border border-sky-400 rounded bg-white font-sans focus:outline-none">
                                    </template>

                                    <template x-if="inlineEditColId !== {{ $col['id'] ?? 0 }}">
                                        <span class="truncate text-[11px] font-medium text-slate-600 cursor-pointer hover:text-sky-700" title="Klik ganda untuk mengubah judul kolom">
                                             {{ $col['title'] ?: '(Kosong)' }}
                                        </span>
                                    </template>
                                </div>

                                <!-- Column Options Dropdown (Width, Sort & Delete) -->
                                <div class="relative" x-data="{ openMenu: false }">
                                    <button @click="openMenu = !openMenu" 
                                            class="opacity-0 group-hover:opacity-100 p-1 hover:bg-slate-200 rounded text-slate-500 transition-opacity">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>

                                    <div x-show="openMenu" 
                                         @click.away="openMenu = false"
                                         class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-40 text-left text-xs" x-cloak>
                                        
                                        <!-- Sort actions -->
                                        <div class="px-3 py-1 text-[10px] uppercase font-bold text-slate-400">Urutkan Data</div>
                                        <button wire:click="sortColumn({{ $col['id'] ?? 0 }}, 'asc')" @click="openMenu = false"
                                                class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                                            <span>Urutkan A &rarr; Z / Terkecil</span>
                                        </button>
                                        <button wire:click="sortColumn({{ $col['id'] ?? 0 }}, 'desc')" @click="openMenu = false"
                                                class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>
                                            <span>Urutkan Z &rarr; A / Terbesar</span>
                                        </button>

                                        <div class="my-1 border-t border-slate-100"></div>

                                        <!-- Width settings -->
                                        <div class="px-3 py-1 text-[10px] uppercase font-bold text-slate-400">Lebar Kolom</div>
                                        <button wire:click="{{ !empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 110)' : 'ensureColumn(' . $col['sort_order'] . ', 110)' }}" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between {{ $col['width'] == 110 ? 'font-bold text-sky-600' : 'text-slate-700' }}">
                                            <span>Sempit</span> <span class="font-mono text-[10px]">110px</span>
                                        </button>
                                        <button wire:click="{{ !empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 160)' : 'ensureColumn(' . $col['sort_order'] . ', 160)' }}" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between {{ $col['width'] == 160 ? 'font-bold text-sky-600' : 'text-slate-700' }}">
                                            <span>Standar</span> <span class="font-mono text-[10px]">160px</span>
                                        </button>
                                        <button wire:click="{{ !empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 230)' : 'ensureColumn(' . $col['sort_order'] . ', 230)' }}" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between {{ $col['width'] == 230 ? 'font-bold text-sky-600' : 'text-slate-700' }}">
                                            <span>Lebar</span> <span class="font-mono text-[10px]">230px</span>
                                        </button>
                                        <button wire:click="{{ !empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 320)' : 'ensureColumn(' . $col['sort_order'] . ', 320)' }}" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between {{ $col['width'] == 320 ? 'font-bold text-sky-600' : 'text-slate-700' }}">
                                            <span>Ekstra Lebar</span> <span class="font-mono text-[10px]">320px</span>
                                        </button>

                                        <div class="my-1 border-t border-slate-100"></div>

                                        @if(!empty($col['id']))
                                        <button wire:click="requestDeleteColumn({{ $col['id'] }})" @click="openMenu = false" 
                                                class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 text-left flex items-center gap-1.5 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            Hapus Kolom {{ $col['letter'] }}
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <button type="button" @pointerdown="resizeColumn($event, {{ $col['id'] ?? 'null' }}, {{ $col['sort_order'] }}, {{ $col['width'] }})"
                                    class="absolute -right-1 top-0 z-30 h-full w-2 cursor-col-resize opacity-0 group-hover:opacity-100 hover:bg-sky-500/50 focus:opacity-100"
                                    aria-label="Ubah lebar kolom {{ $col['letter'] }}"></button>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <!-- DATA ROWS & CELLS -->
            <tbody>
                @foreach($gridRows as $rIdx => $row)
                    <tr wire:key="grid-row-{{ $row['sort_order'] }}" data-grid-row="{{ $row['sort_order'] }}" class="grid-data-row group/row hover:bg-slate-50/50"
                        style="height:{{ !empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx }}px;">
                        
                        <!-- STICKY ROW NUMBER (1, 2, 3...) -->
                        @php
                            $isFixedRowHeader = $rIdx < $freezeTopRowCount || isset($fixedTopRowOffsets[$rIdx]);
                            $rowHeaderTopOffset = $rIdx < $freezeTopRowCount
                                ? ($frozenRowTopOffsets[$rIdx] ?? 0)
                                : ($fixedTopRowOffsets[$rIdx] ?? 0);
                        @endphp
                        <th style="{{ $isFixedRowHeader ? 'position:sticky;top:calc(var(--spreadsheet-column-header-height, 36px) + ' . $rowHeaderTopOffset . 'px);z-index:28;' : 'position:sticky;z-index:16;' }}left:0;"
                            class="sticky left-0 w-12 min-w-[48px] bg-slate-100 border-r border-b border-slate-300 p-0 text-center font-mono text-[11px] text-slate-500 font-semibold select-none group/num">
                            <div class="flex items-center justify-center relative" style="height:{{ !empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx }}px;">
                                <span class="group-hover/row:hidden">{{ $row['sort_order'] }}</span>
                                
                                <!-- Hover Action: Delete row -->
                                @if(!empty($row['id']))
                                <button wire:click="deleteRow({{ $row['id'] }})" 
                                        type="button" 
                                        title="Hapus baris {{ $row['sort_order'] }}"
                                        class="hidden group-hover/row:flex items-center justify-center w-5 h-5 rounded hover:bg-rose-100 text-rose-500 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                @endif
                            </div>
                            <button type="button" @pointerdown="resizeRow($event, {{ $row['id'] ?? 'null' }}, {{ $row['sort_order'] }}, {{ !empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx }})"
                                    class="absolute bottom-0 left-0 z-30 h-2 w-full cursor-row-resize opacity-0 group-hover/row:opacity-100 hover:bg-sky-500/50"
                                    aria-label="Ubah tinggi baris {{ $row['sort_order'] }}"></button>
                        </th>

                        <!-- Cells in this Row -->
                        @foreach($gridColumns as $cIdx => $col)
                            @php
                                $cellKey = ($row['id'] ?? ('virtual-row-' . $row['sort_order'])) . '_' . ($col['id'] ?? ('virtual-column-' . $col['sort_order']));
                                $val = $cellsData[$cellKey] ?? '';
                                $formula = $cellsFormula[$cellKey] ?? '';
                                $format = $cellsFormat[$cellKey] ?? [];
                                $coord = $col['letter'] . ($row['sort_order'] ?? ($rIdx + 1));
                                $isFixedTopRow = $rIdx < $freezeTopRowCount || isset($fixedTopRowOffsets[$rIdx]);
                                $fixedTopOffset = $rIdx < $freezeTopRowCount
                                    ? ($frozenRowTopOffsets[$rIdx] ?? 0)
                                    : ($fixedTopRowOffsets[$rIdx] ?? 0);
                                $isFrozenCol = $cIdx < $freezeLeftColumnCount;
                                $frozenLeftOffset = $frozenColumnLeftOffsets[$cIdx] ?? 0;

                                // Format styling rules
                                $isBold = !empty($format['bold']);
                                $isItalic = !empty($format['italic']);
                                $isUnderline = !empty($format['underline']);
                                $textColor = $format['color'] ?? '';
                                $bgColor = $format['bg'] ?? '';
                                $textAlign = $format['align'] ?? ($format['xlsx']['alignment']['horizontal'] ?? '');
                                $isWrapped = !empty($format['xlsx']['alignment']['wrapText']);

                                // Auto align numeric/currency if not explicitly set
                                if (empty($textAlign)) {
                                    $clean = preg_replace('/[^\d\.\,\-]/', '', $val);
                                    $isNum = ($val !== '') && is_numeric(str_replace(',', '.', $clean));
                                    $textAlign = $isNum ? 'right' : 'left';
                                }

                                $stickyStyle = '';
                                if ($isFixedTopRow && $isFrozenCol) {
                                    $stickyStyle = 'position:sticky;top:calc(var(--spreadsheet-column-header-height, 36px) + ' . $fixedTopOffset . 'px);left:' . $frozenLeftOffset . 'px;z-index:26;';
                                } elseif ($isFixedTopRow) {
                                    $stickyStyle = 'position:sticky;top:calc(var(--spreadsheet-column-header-height, 36px) + ' . $fixedTopOffset . 'px);z-index:18;';
                                } elseif ($isFrozenCol) {
                                    $stickyStyle = 'position:sticky;left:' . $frozenLeftOffset . 'px;z-index:22;';
                                }
                            @endphp

                            @if(isset($mergedCoveredCells[$coord]))
                                @continue
                            @endif
                            @php
                                $cellFormula = $cellsFormula[$cellKey] ?? null;
                                $hasFormula = !empty($cellFormula);
                            @endphp
                            <td @if(isset($mergedCells[$coord])) colspan="{{ $mergedCells[$coord]['colspan'] }}" rowspan="{{ $mergedCells[$coord]['rowspan'] }}" @endif
                                id="td-{{ $coord }}"
                                data-grid-column="{{ $col['sort_order'] }}" 
                                data-coord="{{ $coord }}"
                                wire:key="grid-cell-{{ $coord }}"
                                style="height:{{ !empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx }}px; {{ $bgColor ? "background-color: {$bgColor}; " : '' }}{{ $cellsStyleCss[$cellKey] ?? '' }}{{ $stickyStyle }}" 
                                class="grid-cell-td p-0 relative bg-white select-none outline-none border-b border-r border-slate-200/60 {{ $coord === ($activeCellCoord ?? 'A1') ? 'is-selected' : '' }}"
                                @click="selectCell({{ \Illuminate\Support\Js::from($coord) }}, {{ $row['id'] ?? 'null' }}, {{ $col['id'] ?? 'null' }}, {{ $rIdx }}, {{ $cIdx }}, {{ $row['sort_order'] }}, {{ $col['sort_order'] }}, {{ \Illuminate\Support\Js::from((string) $formula) }}, {{ \Illuminate\Support\Js::from((string) $val) }}, {{ \Illuminate\Support\Js::from((string) ($cellsDisplay[$cellKey] ?? $val)) }}); if ({{ $rIdx }} >= {{ count($gridRows) - 5 }}) $wire.extendGrid('row'); if ({{ $cIdx }} >= {{ count($gridColumns) - 3 }}) $wire.extendGrid('column');"
                                @dblclick="startEditCell({{ \Illuminate\Support\Js::from($coord) }}, {{ $row['id'] ?? 'null' }}, {{ $col['id'] ?? 'null' }}, {{ $row['sort_order'] }}, {{ $col['sort_order'] }}, {{ \Illuminate\Support\Js::from((string) ($formula ?: $val)) }})"
                                @paste="if ({{ !empty($row['id']) && !empty($col['id']) ? 'true' : 'false' }}) handlePaste($event, {{ $row['id'] ?? 0 }}, {{ $col['id'] ?? 0 }})"
                                title="{{ $hasFormula ? 'Formula: ' . $cellFormula : (!empty($format['external_reference']) ? 'Formula merujuk workbook eksternal; nilai tersimpan tidak dapat dihitung ulang tanpa sumber eksternal.' : '') }}">
                                
                                @if($hasFormula)
                                    <div class="formula-marker absolute top-0 right-0 w-1.5 h-1.5 border-t-2 border-r-2 border-sky-500/70 pointer-events-none z-10" title="Formula: {{ $cellFormula }}"></div>
                                @endif

                                {{-- Keep a real input in every cell. This preserves the table grid even
                                     before Alpine initializes, while readonly prevents single-click edits. --}}
                                <input id="cell-{{ $coord }}"
                                       type="text"
                                       value="{{ $cellsDisplay[$cellKey] ?? $val }}"
                                       data-coord="{{ $coord }}"
                                       data-val="{{ $val }}"
                                       data-formula="{{ $formula }}"
                                       data-version="{{ $cellsVersions[$cellKey] ?? 0 }}"
                                       data-row-id="{{ $row['id'] ?? '' }}"
                                       data-col-id="{{ $col['id'] ?? '' }}"
                                       readonly
                                       @input="handleCellInput($event, {{ \Illuminate\Support\Js::from($coord) }})"
                                       @keydown.enter.prevent="commitCellEdit({{ \Illuminate\Support\Js::from($coord) }}, {{ $row['id'] ?? 'null' }}, {{ $col['id'] ?? 'null' }}, {{ $row['sort_order'] }}, {{ $col['sort_order'] }}, true)"
                                       @keydown.escape.prevent="cancelCellEdit({{ \Illuminate\Support\Js::from($coord) }})"
                                       @keydown.tab.prevent="commitCellEdit({{ \Illuminate\Support\Js::from($coord) }}, {{ $row['id'] ?? 'null' }}, {{ $col['id'] ?? 'null' }}, {{ $row['sort_order'] }}, {{ $col['sort_order'] }}, false, $event.shiftKey ? 'left' : 'right')"
                                       @blur="commitCellEdit({{ \Illuminate\Support\Js::from($coord) }}, {{ $row['id'] ?? 'null' }}, {{ $col['id'] ?? 'null' }}, {{ $row['sort_order'] }}, {{ $col['sort_order'] }}, false)"
                                       style="{{ $textColor ? "color: {$textColor};" : '' }} text-align: {{ $textAlign }}; height:{{ !empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx }}px;"
                                       class="grid-cell-input {{ $isWrapped ? 'is-wrapped' : '' }} w-full h-full min-h-8 px-2 py-1 text-xs border-none focus:outline-none bg-transparent {{ $isBold ? 'font-bold' : '' }} {{ $isItalic ? 'italic' : '' }} {{ $isUnderline ? 'underline' : '' }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div x-show="isSheetSwitching || sheetSwitchError"
         x-cloak
         class="sheet-switch-overlay absolute inset-x-0 top-0 bottom-10 z-50 flex items-center justify-center bg-white/85 backdrop-blur-[1px]"
         x-transition:enter="transition-opacity ease-out duration-100 delay-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-75"
         role="status"
         aria-live="polite">
        <div class="flex max-w-sm items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600 shadow-lg">
            <template x-if="isSheetSwitching">
                <span class="sheet-switch-spinner h-4 w-4 shrink-0 rounded-full border-2 border-sky-200 border-t-sky-600 animate-spin" aria-hidden="true"></span>
            </template>
            <span x-text="sheetSwitchError || 'Memuat lembar kerja…'"></span>
            <button x-show="sheetSwitchError"
                    type="button"
                    @click="retrySheetSwitch()"
                    class="shrink-0 rounded-lg bg-sky-600 px-2.5 py-1.5 font-semibold text-white hover:bg-sky-500">
                Coba lagi
            </button>
        </div>
    </div>

    <!-- 4. TAB LEMBAR KERJA DI BAGIAN BAWAH (GOOGLE SHEETS STYLE) -->
    <div class="relative z-[60] h-10 bg-slate-100 border-t border-slate-200 px-4 flex items-center justify-between text-xs bottom-tabs shrink-0 select-none">
        
        <div class="flex items-center gap-1 min-w-0 flex-1">
            @if($sheet && $sheet->group)
                @php
                    $memberRole = $sheet->group->getMemberRole(Auth::id());
                    $isLeader = $memberRole === 'leader';
                @endphp
                <div class="flex items-center space-x-1 overflow-x-auto py-1 min-w-0 flex-1">
                @foreach($sheet->group->sheets as $sh)
                    @php
                        $isCurrent = $sh->id === $sheetId;
                        $shRestricted = $sh->isRestrictedForRole($memberRole);
                        $tabPalette = $sh->identityColor();
                        $tabStyle = $isCurrent
                            ? "background-color: {$tabPalette['active']}; color: #ffffff; border-color: {$tabPalette['active']};"
                            : ($shRestricted
                                ? 'background-color: #e2e8f0; color: #94a3b8; border-color: #cbd5e1;'
                                : "background-color: {$tabPalette['idle']}; color: {$tabPalette['text']}; border-color: {$tabPalette['active']};");
                    @endphp
                    <div class="relative group/tab flex items-center">
                        <a wire:navigate href="{{ route('group.workspace', [$sheet->group_id, $sh->id]) }}"
                           @if(($memberRole === 'admin' || (!$isLeader && $sheet->group->hasPassword())) && $sh->id !== $sheetId && !$shRestricted)
                               @click.prevent="$dispatch('sheet-switch', { sheetId: {{ $sh->id }} })"
                           @elseif(!$shRestricted)
                               @click.prevent="requestSheetSwitch({{ $sh->id }}, $el.href)"
                           @endif
                           style="{{ $tabStyle }}"
                           class="px-3 py-1 rounded-t-lg border font-medium transition-all flex items-center gap-1.5 {{ $isCurrent ? 'shadow-2xs font-bold' : '' }}">
                            <span>{{ $sh->name }}</span>
                            @if($shRestricted)
                                <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20" title="Akses Dibatasi (Leader Only)"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg>
                            @endif
                        </a>

                        {{--
                        <!-- Dropdown actions moved outside the horizontally scrolling tab strip. -->
                        @if($isCurrent)
                            <div class="relative" x-data="{ tabMenuOpen: false }">
                                <button @click="tabMenuOpen = !tabMenuOpen" 
                                        class="p-1 hover:bg-slate-200 text-slate-500 rounded-r">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="tabMenuOpen" @click.away="tabMenuOpen = false" 
                                     class="absolute bottom-full left-0 mb-1 w-40 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-50 text-xs" x-cloak>
                                    <button @click="$dispatch('open-rename-modal', { id: {{ $sh->id }}, name: '{{ addslashes($sh->name) }}' }); tabMenuOpen = false;"
                                            class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Ganti Nama</span>
                                    </button>
                                    
                                    <form action="{{ route('sheets.duplicate', $sh->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            <span>Duplikasi</span>
                                        </button>
                                    </form>

                                    @if($sheet->group->sheets->count() > 1)
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <form action="{{ route('sheets.delete', $sh->id) }}" method="POST" onsubmit="return confirm('Hapus lembar kerja ini? Data di dalamnya akan hilang.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full px-3 py-1.5 hover:bg-rose-50 text-left flex items-center gap-2 text-rose-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span>Hapus Lembar</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif
                        --}}
                    </div>
                @endforeach
                </div>

                <div class="relative shrink-0" x-data="{ tabMenuOpen: false }">
                    <button @click="tabMenuOpen = !tabMenuOpen"
                            class="p-1 hover:bg-slate-200 text-slate-500 rounded"
                            title="Opsi lembar aktif">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="tabMenuOpen" @click.away="tabMenuOpen = false"
                         class="absolute bottom-full left-0 mb-1 w-40 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-50 text-xs" x-cloak>
                        <button @click="$dispatch('open-rename-modal', { id: {{ $sheet->id }}, name: '{{ addslashes($sheet->name) }}' }); tabMenuOpen = false;"
                                class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            <span>Ganti Nama</span>
                        </button>
                        <form action="{{ route('sheets.duplicate', $sheet->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <span>Duplikasi</span>
                            </button>
                        </form>
                        @if($sheet->group->sheets->count() > 1)
                            <div class="my-1 border-t border-slate-100"></div>
                            <form action="{{ route('sheets.delete', $sheet->id) }}" method="POST" onsubmit="return confirm('Hapus lembar kerja ini? Data di dalamnya akan hilang.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-1.5 hover:bg-rose-50 text-left flex items-center gap-2 text-rose-600">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    <span>Hapus Lembar</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <button type="button" @click="$dispatch('open-add-sheet-modal')" 
                    class="p-1 rounded-md text-slate-500 hover:text-sky-600 hover:bg-slate-200 transition-colors"
                    title="Tambah Lembar Baru">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>
        </div>

        <div class="text-[11px] text-slate-400 hidden sm:block">
            Navigasi: <kbd class="px-1 py-0.5 bg-slate-200 rounded text-slate-700 font-mono">Tab</kbd> / <kbd class="px-1 py-0.5 bg-slate-200 rounded text-slate-700 font-mono">Enter</kbd> / <kbd class="px-1 py-0.5 bg-slate-200 rounded text-slate-700 font-mono">&uarr;&darr;&larr;&rarr;</kbd>
        </div>
    </div>

    <!-- 6. MODAL CARI & GANTI (FIND AND REPLACE) -->
    <template x-teleport="body">
    <div x-show="$wire.showFindReplaceModal" class="fixed inset-0 z-[1000] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="$wire.set('showFindReplaceModal', false)"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Cari & Ganti Sel</h3>
                            <p class="text-xs text-slate-500">Temukan nilai dan ganti teks dalam lembar ini</p>
                        </div>
                    </div>
                    <button @click="$wire.set('showFindReplaceModal', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cari Kata / Nilai</label>
                        <input type="text" wire:model="findText" placeholder="Teks yang ingin dicari..." 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti dengan</label>
                        <input type="text" wire:model="replaceText" placeholder="Teks pengganti..." 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5 mt-4">
                    <button type="button" @click="$wire.set('showFindReplaceModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                        Batal
                    </button>
                    <button type="button" wire:click="executeFindReplace" class="px-5 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-500/20 transition-all">
                        Ganti Semua
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 7. MODAL KONFIRMASI HAPUS KOLOM BERISI DATA -->
    <div x-show="$wire.showDeleteColumnModal" class="fixed inset-0 z-[1000] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-xs transition-opacity" wire:click="cancelDeleteColumn"></div>

            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Peringatan: Kolom Berisi Data!</h3>
                        <p class="text-xs text-slate-500">Kolom {{ $columnPendingDeleteLetter }} memiliki data</p>
                    </div>
                </div>

                <div class="py-4 text-xs text-slate-600 space-y-2">
                    <p>
                        Kolom <strong class="text-slate-900 font-mono">{{ $columnPendingDeleteLetter }}</strong> saat ini memiliki <strong class="text-rose-600 font-bold font-mono">{{ $columnPendingDeleteCount }} sel</strong> yang sudah terisi data.
                    </p>
                    <p class="text-slate-500">
                        Menghapus kolom ini akan menghapus seluruh data di dalamnya secara permanen. Apakah Anda yakin ingin melanjutkan?
                    </p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" wire:click="cancelDeleteColumn" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                        Batal
                    </button>
                    <button type="button" wire:click="forceDeleteColumn" class="px-5 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-500/20 transition-all">
                        Ya, Hapus Kolom & Seluruh Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>
