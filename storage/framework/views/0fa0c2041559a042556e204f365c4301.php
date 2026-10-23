<div class="flex-1 flex flex-col min-h-0 bg-white" 
     wire:poll.10s="syncChanges"
     x-data="spreadsheetWorksheet($wire)">

    <!-- 1. TOOLBAR UTAMA SPREADSHEET (MODERN EXCEL & GOOGLE SHEETS STYLE) -->
    <div class="bg-slate-50 border-b border-slate-200 px-3 py-1.5 flex flex-wrap items-center justify-between gap-2 text-xs toolbar-area shrink-0">
        
        <!-- Left: Undo / Redo & Cell Formatting Controls -->
        <div class="flex items-center flex-wrap gap-1">
            
            <!-- Undo / Redo -->
            <button wire:click="undo" type="button" title="Undo (Ctrl+Z)"
                    class="p-1.5 rounded hover:bg-slate-200 text-slate-700 transition-colors <?php echo e(empty($undoStack) ? 'opacity-40 cursor-not-allowed' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2M3 10l6-6M3 10l6 6"></path></svg>
            </button>
            <button wire:click="redo" type="button" title="Redo (Ctrl+Y)"
                    class="p-1.5 rounded hover:bg-slate-200 text-slate-700 transition-colors <?php echo e(empty($redoStack) ? 'opacity-40 cursor-not-allowed' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a5 5 0 00-5 5v2m15-7l-6-6m6 6l-6 6"></path></svg>
            </button>

            <span class="w-px h-5 bg-slate-300 mx-1"></span>

            <!-- Bold, Italic, Underline -->
            <button wire:click="formatCell('bold')" type="button" title="Tebal (Bold)"
                    class="p-1.5 rounded hover:bg-slate-200 font-bold text-slate-800 transition-colors">
                <span class="w-4 h-4 flex items-center justify-center font-serif text-sm">B</span>
            </button>
            <button wire:click="formatCell('italic')" type="button" title="Miring (Italic)"
                    class="p-1.5 rounded hover:bg-slate-200 italic font-serif text-slate-800 transition-colors">
                <span class="w-4 h-4 flex items-center justify-center text-sm">I</span>
            </button>
            <button wire:click="formatCell('underline')" type="button" title="Garis Bawah (Underline)"
                    class="p-1.5 rounded hover:bg-slate-200 underline text-slate-800 transition-colors">
                <span class="w-4 h-4 flex items-center justify-center text-sm">U</span>
            </button>

            <span class="w-px h-5 bg-slate-300 mx-1"></span>

            <!-- Text Alignment Dropdown -->
            <div class="relative" x-data="{ alignOpen: false }">
                <button @click="alignOpen = !alignOpen" type="button" title="Perataan Teks"
                        class="p-1.5 rounded hover:bg-slate-200 text-slate-700 flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"></path></svg>
                    <svg class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="alignOpen" @click.away="alignOpen = false" 
                     class="absolute left-0 mt-1 w-32 bg-white rounded-lg shadow-xl border border-slate-200 py-1 z-50 text-xs" x-cloak>
                    <button wire:click="formatCell('align', 'left')" @click="alignOpen = false" 
                            class="w-full px-3 py-1.5 hover:bg-slate-100 flex items-center gap-2 text-slate-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"></path></svg>
                        <span>Kiri</span>
                    </button>
                    <button wire:click="formatCell('align', 'center')" @click="alignOpen = false" 
                            class="w-full px-3 py-1.5 hover:bg-slate-100 flex items-center gap-2 text-slate-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16"></path></svg>
                        <span>Tengah</span>
                    </button>
                    <button wire:click="formatCell('align', 'right')" @click="alignOpen = false" 
                            class="w-full px-3 py-1.5 hover:bg-slate-100 flex items-center gap-2 text-slate-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16"></path></svg>
                        <span>Kanan</span>
                    </button>
                </div>
            </div>

            <!-- Number Formatting Dropdown -->
            <div class="relative" x-data="{ numFormatOpen: false }">
                <button @click="numFormatOpen = !numFormatOpen" type="button" title="Format Nilai Sel"
                        class="px-2 py-1 rounded hover:bg-slate-200 text-slate-700 font-mono text-xs flex items-center gap-1 transition-colors border border-slate-300 bg-white">
                    <span>123</span>
                    <svg class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="numFormatOpen" @click.away="numFormatOpen = false" 
                     class="absolute left-0 mt-1 w-44 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-50 text-xs" x-cloak>
                    <div class="px-3 py-1 text-[10px] uppercase font-bold text-slate-400">Format Angka</div>
                    <button wire:click="formatCell('numFormat', 'general')" @click="numFormatOpen = false" 
                            class="w-full px-3 py-1.5 hover:bg-slate-100 text-left text-slate-700">
                        Umum (Teks Otomatis)
                    </button>
                    <button wire:click="formatCell('numFormat', 'currency')" @click="numFormatOpen = false" 
                            class="w-full px-3 py-1.5 hover:bg-slate-100 text-left text-slate-700 flex justify-between">
                        <span>Mata Uang (Rp)</span> <span class="text-slate-400 font-mono">Rp 1.000</span>
                    </button>
                    <button wire:click="formatCell('numFormat', 'number')" @click="numFormatOpen = false" 
                            class="w-full px-3 py-1.5 hover:bg-slate-100 text-left text-slate-700 flex justify-between">
                        <span>Angka Desimal</span> <span class="text-slate-400 font-mono">1.000,00</span>
                    </button>
                    <button wire:click="formatCell('numFormat', 'percent')" @click="numFormatOpen = false" 
                            class="w-full px-3 py-1.5 hover:bg-slate-100 text-left text-slate-700 flex justify-between">
                        <span>Persentase (%)</span> <span class="text-slate-400 font-mono">100%</span>
                    </button>
                </div>
            </div>

            <!-- Text Color Picker Dropdown -->
            <div class="relative" x-data="{ colorOpen: false }">
                <button @click="colorOpen = !colorOpen" type="button" title="Warna Teks"
                        class="p-1.5 rounded hover:bg-slate-200 text-slate-700 flex items-center gap-0.5 transition-colors">
                    <span class="w-4 h-4 font-bold border-b-2 border-sky-600 flex items-center justify-center">A</span>
                    <svg class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="colorOpen" @click.away="colorOpen = false" 
                     class="absolute left-0 mt-1 p-2 bg-white rounded-lg shadow-xl border border-slate-200 z-50 grid grid-cols-5 gap-1.5 w-36" x-cloak>
                    <button wire:click="formatCell('color', '#0f172a')" @click="colorOpen = false" class="w-5 h-5 rounded bg-slate-900 border border-slate-300" title="Hitam Default"></button>
                    <button wire:click="formatCell('color', '#0284c7')" @click="colorOpen = false" class="w-5 h-5 rounded bg-sky-600" title="Biru"></button>
                    <button wire:click="formatCell('color', '#059669')" @click="colorOpen = false" class="w-5 h-5 rounded bg-emerald-600" title="Hijau"></button>
                    <button wire:click="formatCell('color', '#e11d48')" @click="colorOpen = false" class="w-5 h-5 rounded bg-rose-600" title="Merah"></button>
                    <button wire:click="formatCell('color', '#d97706')" @click="colorOpen = false" class="w-5 h-5 rounded bg-amber-600" title="Oranye / Kuning"></button>
                </div>
            </div>

            <!-- Background Color Picker Dropdown -->
            <div class="relative" x-data="{ bgOpen: false }">
                <button @click="bgOpen = !bgOpen" type="button" title="Warna Latar Sel (Fill)"
                        class="p-1.5 rounded hover:bg-slate-200 text-slate-700 flex items-center gap-0.5 transition-colors">
                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4.5 4.5 0 013.9-4.48 3.5 3.5 0 016.2 0A4.5 4.5 0 0117 17a4 4 0 01-4 4H7z"></path></svg>
                    <svg class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="bgOpen" @click.away="bgOpen = false" 
                     class="absolute left-0 mt-1 p-2 bg-white rounded-lg shadow-xl border border-slate-200 z-50 grid grid-cols-5 gap-1.5 w-36" x-cloak>
                    <button wire:click="formatCell('bg', '#ffffff')" @click="bgOpen = false" class="w-5 h-5 rounded bg-white border border-slate-300" title="Putih"></button>
                    <button wire:click="formatCell('bg', '#fef9c3')" @click="bgOpen = false" class="w-5 h-5 rounded bg-yellow-100 border border-yellow-200" title="Kuning Muda"></button>
                    <button wire:click="formatCell('bg', '#dcfce7')" @click="bgOpen = false" class="w-5 h-5 rounded bg-emerald-100 border border-emerald-200" title="Hijau Muda"></button>
                    <button wire:click="formatCell('bg', '#e0f2fe')" @click="bgOpen = false" class="w-5 h-5 rounded bg-sky-100 border border-sky-200" title="Biru Muda"></button>
                    <button wire:click="formatCell('bg', '#ffe4e6')" @click="bgOpen = false" class="w-5 h-5 rounded bg-rose-100 border border-rose-200" title="Merah Muda"></button>
                </div>
            </div>

            <span class="w-px h-5 bg-slate-300 mx-1"></span>

            <!-- Core Operations: Row/Col addition -->
            <button wire:click="addRow" type="button" 
                    class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-300 font-semibold text-slate-700 flex items-center gap-1.5 shadow-2xs hover:border-slate-400 transition-colors">
                <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span class="hidden sm:inline">+ Baris</span>
            </button>

            <button wire:click="addColumn" type="button" 
                    class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-300 font-semibold text-slate-700 flex items-center gap-1.5 shadow-2xs hover:border-slate-400 transition-colors">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span class="hidden sm:inline">+ Kolom</span>
            </button>

            <!-- Find & Replace Trigger -->
            <button @click="$wire.set('showFindReplaceModal', true)" type="button" title="Cari & Ganti (Ctrl+F)"
                    class="p-1.5 rounded hover:bg-slate-200 text-slate-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </button>
        </div>

        <!-- Right: Export CSV & Search -->
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('spreadsheet.export', $sheetId)); ?>" 
               class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-emerald-50 border border-slate-300 font-semibold text-emerald-700 flex items-center gap-1.5 shadow-2xs hover:border-emerald-400 transition-colors">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span class="hidden sm:inline">Ekspor CSV</span>
            </a>

            <!-- Quick Search Input -->
            <div class="relative w-36 sm:w-48">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari di sel..." 
                       class="w-full pl-8 pr-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-sky-500">
            </div>
        </div>
    </div>

    <!-- 2. FORMULA BAR (COORDINATE + FX INPUT) -->
    <div class="h-10 bg-white border-b border-slate-200 px-3 flex items-center gap-2 text-xs shrink-0 shadow-2xs">
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
        <div class="flex-1 flex items-center">
            <input type="text" 
                   x-model="activeFormula" 
                   @focus="$wire.beginFormulaEdit()"
                   @blur="$wire.finishFormulaEdit()"
                   @input.debounce.300ms="$wire.updatedFormulaValue(activeFormula)"
                   placeholder="Ketik nilai, formula (contoh: =SUM(A1:A5), =A1+B1), atau tempel data..."
                   class="w-full h-7 px-2.5 text-xs text-slate-800 font-sans focus:outline-none focus:bg-sky-50/30 rounded border border-transparent focus:border-sky-300 transition-colors">
        </div>

        <!-- Auto-save Status Badge -->
        <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60 shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span wire:loading wire:target="updateCell,finishCellEdit,updatedFormulaValue,pasteTsvData,formatCell">Menyimpan</span>
            <span wire:loading.remove wire:target="updateCell,finishCellEdit,updatedFormulaValue,pasteTsvData,formatCell" x-text="$wire.saveStatus">Tersimpan</span>
        </div>
    </div>

    <!-- 3. GRID TABEL SPREADSHEET (MICROSOFT EXCEL LOOK & FEEL) -->
    <div class="flex-1 overflow-auto bg-slate-200 select-text relative spreadsheet-scroll" aria-label="Area kerja spreadsheet">
        <table class="spreadsheet-table bg-white w-max table-fixed text-xs border-separate border-spacing-0">
            <colgroup>
                <col style="width: 48px; min-width: 48px; max-width: 48px;">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $gridColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cIdx => $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <col data-col-index="<?php echo e($col['sort_order']); ?>" style="width: <?php echo e($col['width']); ?>px; min-width: <?php echo e($col['width']); ?>px; max-width: <?php echo e($col['width']); ?>px;">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </colgroup>
            
            <!-- STICKY COLUMN HEADERS (A, B, C...) -->
            <thead class="bg-slate-100 select-none shadow-xs">
                <tr>
                    <!-- Top-Left Corner (Row Header intersection) -->
                    <th class="sticky top-0 left-0 z-30 w-12 min-w-[48px] bg-slate-200 border-r border-b border-slate-300 p-0 text-center text-slate-400 text-[10px] font-mono">
                        #
                    </th>

                    <!-- Column Headers -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $gridColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cIdx => $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th style="position:sticky;top:0;width: <?php echo e($col['width']); ?>px; min-width: <?php echo e($col['width']); ?>px; max-width: <?php echo e($col['width']); ?>px; <?php echo e($cIdx < $freezeLeftColumnCount ? 'left:' . $frozenColumnLeftOffsets[$cIdx] . 'px;z-index:25;' : 'z-index:20;'); ?>" 
                            data-grid-column="<?php echo e($col['sort_order']); ?>" wire:key="grid-header-<?php echo e($col['sort_order']); ?>"
                            class="border-r border-b border-slate-300 bg-slate-100 font-normal p-0 relative group">
                            
                            <div class="flex items-center justify-between px-2 py-1.5 text-slate-700 min-h-9">
                                
                                <!-- Column Letter & Editable Title -->
                                <div class="flex-1 flex items-center gap-1.5 min-w-0" 
                                     @dblclick="if (<?php echo e($col['id'] ?? 0); ?>) { inlineEditColId = <?php echo e($col['id'] ?? 0); ?>; inlineEditColTitle = '<?php echo e(addslashes($col['title'] ?? '')); ?>'; $nextTick(() => $refs['colInput<?php echo e($col['id'] ?? 0); ?>'].focus()) }">
                                    
                                    <!-- Excel Letter (A, B, C...) -->
                                    <span class="font-bold text-slate-900 font-mono text-xs"><?php echo e($col['letter']); ?></span>

                                    <!-- Column Title Display or Inline Edit -->
                                    <template x-if="inlineEditColId === <?php echo e($col['id'] ?? 0); ?>">
                                        <input x-ref="colInput<?php echo e($col['id'] ?? 0); ?>" 
                                               type="text" 
                                               x-model="inlineEditColTitle" 
                                               @keydown.enter="inlineEditColId = null; $wire.updateColumnTitle(<?php echo e($col['id'] ?? 0); ?>, inlineEditColTitle)"
                                               @blur="inlineEditColId = null; $wire.updateColumnTitle(<?php echo e($col['id'] ?? 0); ?>, inlineEditColTitle)"
                                               class="w-full text-xs px-1 py-0.5 border border-sky-400 rounded bg-white font-sans focus:outline-none">
                                    </template>

                                    <template x-if="inlineEditColId !== <?php echo e($col['id'] ?? 0); ?>">
                                        <span class="truncate text-[11px] font-medium text-slate-600 cursor-pointer hover:text-sky-700" title="Klik ganda untuk mengubah judul kolom">
                                             <?php echo e($col['title'] ?: '(Kosong)'); ?>

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
                                        <button wire:click="sortColumn(<?php echo e($col['id'] ?? 0); ?>, 'asc')" @click="openMenu = false"
                                                class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                                            <span>Urutkan A &rarr; Z / Terkecil</span>
                                        </button>
                                        <button wire:click="sortColumn(<?php echo e($col['id'] ?? 0); ?>, 'desc')" @click="openMenu = false"
                                                class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>
                                            <span>Urutkan Z &rarr; A / Terbesar</span>
                                        </button>

                                        <div class="my-1 border-t border-slate-100"></div>

                                        <!-- Width settings -->
                                        <div class="px-3 py-1 text-[10px] uppercase font-bold text-slate-400">Lebar Kolom</div>
                                        <button wire:click="<?php echo e(!empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 110)' : 'ensureColumn(' . $col['sort_order'] . ', 110)'); ?>" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between <?php echo e($col['width'] == 110 ? 'font-bold text-sky-600' : 'text-slate-700'); ?>">
                                            <span>Sempit</span> <span class="font-mono text-[10px]">110px</span>
                                        </button>
                                        <button wire:click="<?php echo e(!empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 160)' : 'ensureColumn(' . $col['sort_order'] . ', 160)'); ?>" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between <?php echo e($col['width'] == 160 ? 'font-bold text-sky-600' : 'text-slate-700'); ?>">
                                            <span>Standar</span> <span class="font-mono text-[10px]">160px</span>
                                        </button>
                                        <button wire:click="<?php echo e(!empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 230)' : 'ensureColumn(' . $col['sort_order'] . ', 230)'); ?>" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between <?php echo e($col['width'] == 230 ? 'font-bold text-sky-600' : 'text-slate-700'); ?>">
                                            <span>Lebar</span> <span class="font-mono text-[10px]">230px</span>
                                        </button>
                                        <button wire:click="<?php echo e(!empty($col['id']) ? 'setColumnWidth(' . $col['id'] . ', 320)' : 'ensureColumn(' . $col['sort_order'] . ', 320)'); ?>" @click="openMenu = false" 
                                                class="w-full px-3 py-1 hover:bg-slate-100 text-left flex items-center justify-between <?php echo e($col['width'] == 320 ? 'font-bold text-sky-600' : 'text-slate-700'); ?>">
                                            <span>Ekstra Lebar</span> <span class="font-mono text-[10px]">320px</span>
                                        </button>

                                        <div class="my-1 border-t border-slate-100"></div>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($col['id'])): ?>
                                        <button wire:click="requestDeleteColumn(<?php echo e($col['id']); ?>)" @click="openMenu = false" 
                                                class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 text-left flex items-center gap-1.5 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            Hapus Kolom <?php echo e($col['letter']); ?>

                                        </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <button type="button" @pointerdown="resizeColumn($event, <?php echo e($col['id'] ?? 'null'); ?>, <?php echo e($col['sort_order']); ?>, <?php echo e($col['width']); ?>)"
                                    class="absolute -right-1 top-0 z-30 h-full w-2 cursor-col-resize opacity-0 group-hover:opacity-100 hover:bg-sky-500/50 focus:opacity-100"
                                    aria-label="Ubah lebar kolom <?php echo e($col['letter']); ?>"></button>
                        </th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tr>
            </thead>

            <!-- DATA ROWS & CELLS -->
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $gridRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rIdx => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr wire:key="grid-row-<?php echo e($row['sort_order']); ?>" data-grid-row="<?php echo e($row['sort_order']); ?>" class="grid-data-row group/row hover:bg-slate-50/50"
                        style="height:<?php echo e(!empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx); ?>px;">
                        
                        <!-- STICKY ROW NUMBER (1, 2, 3...) -->
                        <th style="<?php echo e($rIdx < $freezeTopRowCount ? 'position:sticky;top:calc(36px + ' . $frozenRowTopOffsets[$rIdx] . 'px);z-index:15;' : (isset($fixedTopRowOffsets[$rIdx]) ? 'position:sticky;top:calc(36px + ' . $fixedTopRowOffsets[$rIdx] . 'px);z-index:18;' : '')); ?>"
                            class="sticky left-0 z-10 w-12 min-w-[48px] bg-slate-100 border-r border-b border-slate-300 p-0 text-center font-mono text-[11px] text-slate-500 font-semibold select-none group/num">
                            <div class="flex items-center justify-center relative" style="height:<?php echo e(!empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx); ?>px;">
                                <span class="group-hover/row:hidden"><?php echo e($row['sort_order']); ?></span>
                                
                                <!-- Hover Action: Delete row -->
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($row['id'])): ?>
                                <button wire:click="deleteRow(<?php echo e($row['id']); ?>)" 
                                        type="button" 
                                        title="Hapus baris <?php echo e($row['sort_order']); ?>"
                                        class="hidden group-hover/row:flex items-center justify-center w-5 h-5 rounded hover:bg-rose-100 text-rose-500 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <button type="button" @pointerdown="resizeRow($event, <?php echo e($row['id'] ?? 'null'); ?>, <?php echo e($row['sort_order']); ?>, <?php echo e(!empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx); ?>)"
                                    class="absolute bottom-0 left-0 z-30 h-2 w-full cursor-row-resize opacity-0 group-hover/row:opacity-100 hover:bg-sky-500/50"
                                    aria-label="Ubah tinggi baris <?php echo e($row['sort_order']); ?>"></button>
                        </th>

                        <!-- Cells in this Row -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $gridColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cIdx => $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $cellKey = ($row['id'] ?? ('virtual-row-' . $row['sort_order'])) . '_' . ($col['id'] ?? ('virtual-column-' . $col['sort_order']));
                                $val = $cellsData[$cellKey] ?? '';
                                $formula = $cellsFormula[$cellKey] ?? '';
                                $format = $cellsFormat[$cellKey] ?? [];
                                $coord = $col['letter'] . ($row['sort_order'] ?? ($rIdx + 1));
                                $isFixedTopRow = $rIdx < $freezeTopRowCount || isset($fixedTopRowOffsets[$rIdx]);
                                $fixedTopOffset = $rIdx < $freezeTopRowCount
                                    ? ($frozenRowTopOffsets[$rIdx] ?? 0)
                                    : ($fixedTopRowOffsets[$rIdx] ?? 0);

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
                            ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($mergedCoveredCells[$coord])): ?>
                                <?php continue; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <td <?php if(isset($mergedCells[$coord])): ?> colspan="<?php echo e($mergedCells[$coord]['colspan']); ?>" rowspan="<?php echo e($mergedCells[$coord]['rowspan']); ?>" <?php endif; ?>
                                data-grid-column="<?php echo e($col['sort_order']); ?>" wire:key="grid-cell-<?php echo e($coord); ?>"
                                style="height:<?php echo e(!empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx); ?>px; <?php echo e($bgColor ? "background-color: {$bgColor};" : ''); ?> <?php echo e($cellsStyleCss[$cellKey] ?? ''); ?> <?php echo e($cIdx < $freezeLeftColumnCount ? 'position:sticky;left:' . $frozenColumnLeftOffsets[$cIdx] . 'px;z-index:5;' : ''); ?> <?php echo e($isFixedTopRow ? 'position:sticky;top:calc(36px + ' . $fixedTopOffset . 'px);z-index:' . ($rIdx < $freezeTopRowCount ? '6' : '17') . ';' : ''); ?>" 
                                class="p-0 relative transition-colors focus-within:ring-2 focus-within:ring-sky-500 focus-within:z-30 bg-white overflow-hidden">
                                
                                <input id="cell-<?php echo e($coord); ?>" 
                                       type="text" 
                                       value="<?php echo e($cellsDisplay[$cellKey] ?? $val); ?>"
                                       title="<?php echo e(!empty($format['external_reference']) ? 'Formula merujuk workbook eksternal; nilai tersimpan tidak dapat dihitung ulang tanpa sumber eksternal.' : ''); ?>"
                                       @focus="originalCellValue = $el.value; focusCell(<?php echo e($rIdx); ?>, <?php echo e($cIdx); ?>, '<?php echo e($coord); ?>', <?php echo e($row['id'] ?? 'null'); ?>, <?php echo e($col['id'] ?? 'null'); ?>, <?php echo e($row['sort_order']); ?>, <?php echo e($col['sort_order']); ?>); if (<?php echo e($rIdx); ?> >= <?php echo e(count($gridRows) - 5); ?>) $wire.extendGrid('row'); if (<?php echo e($cIdx); ?> >= <?php echo e(count($gridColumns) - 3); ?>) $wire.extendGrid('column');"
                                       @blur="if (<?php echo e(!empty($row['id']) && !empty($col['id']) ? 'true' : 'false'); ?>) $wire.finishCellEdit(<?php echo e($row['id'] ?? 0); ?>, <?php echo e($col['id'] ?? 0); ?>, $el.value, originalCellValue)"
                                       @keydown="onKeyDown($event, <?php echo e($rIdx); ?>, <?php echo e($cIdx); ?>, <?php echo e(count($gridRows)); ?>, <?php echo e(count($gridColumns)); ?>, <?php echo e($row['id'] ?? 0); ?>, <?php echo e($col['id'] ?? 0); ?>, <?php echo e($cellsVersions[$cellKey] ?? 0); ?>)"
                                       @paste="if (<?php echo e(!empty($row['id']) && !empty($col['id']) ? 'true' : 'false'); ?>) handlePaste($event, <?php echo e($row['id'] ?? 0); ?>, <?php echo e($col['id'] ?? 0); ?>)"
                                       style="<?php echo e($textColor ? "color: {$textColor};" : ''); ?> text-align: <?php echo e($textAlign); ?>; height:<?php echo e(!empty($row['height']) ? (float) $row['height'] * 1.333333 : $defaultRowHeightPx); ?>px;"
                                       :class="{
                                           'bg-amber-100/60': searchQuery && ('<?php echo e(addslashes($val)); ?>'.toLowerCase().includes(searchQuery.toLowerCase())),
                                           'font-bold': <?php echo e($isBold ? 'true' : 'false'); ?>,
                                           'italic': <?php echo e($isItalic ? 'true' : 'false'); ?>,
                                           'underline': <?php echo e($isUnderline ? 'true' : 'false'); ?>,
                                       }"
                                       class="grid-cell-input <?php echo e($isWrapped ? 'is-wrapped' : ''); ?> w-full h-full min-h-8 px-2 py-1 text-xs border-none focus:outline-none bg-transparent">
                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 4. TAB LEMBAR KERJA DI BAGIAN BAWAH (GOOGLE SHEETS STYLE) -->
    <div class="h-10 bg-slate-100 border-t border-slate-200 px-4 flex items-center justify-between text-xs bottom-tabs shrink-0 select-none">
        
        <div class="flex items-center space-x-1 overflow-x-auto py-1">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sheet && $sheet->group): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $sheet->group->sheets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $isCurrent = $sh->id === $sheetId;
                    ?>
                    <div class="relative group/tab flex items-center">
                        <a wire:navigate href="<?php echo e(route('group.workspace', [$sheet->group_id, $sh->id])); ?>" <?php if($sheet->group->hasPassword() && $sh->id !== $sheetId): ?> @click.prevent="$dispatch('sheet-switch', { sheetId: <?php echo e($sh->id); ?> })" <?php endif; ?>
                           class="px-3 py-1 rounded-t-lg font-medium transition-all flex items-center gap-1.5 <?php echo e($isCurrent 
                               ? 'bg-white text-sky-800 border-t-2 border-sky-600 shadow-2xs font-bold' 
                               : 'bg-slate-200/80 text-slate-600 hover:bg-slate-200'); ?>">
                            <span><?php echo e($sh->name); ?></span>
                        </a>

                        <!-- Dropdown actions for tab -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isCurrent): ?>
                            <div class="relative" x-data="{ tabMenuOpen: false }">
                                <button @click="tabMenuOpen = !tabMenuOpen" 
                                        class="p-1 hover:bg-slate-200 text-slate-500 rounded-r">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="tabMenuOpen" @click.away="tabMenuOpen = false" 
                                     class="absolute bottom-full left-0 mb-1 w-40 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-50 text-xs" x-cloak>
                                    <button @click="$dispatch('open-rename-modal', { id: <?php echo e($sh->id); ?>, name: '<?php echo e(addslashes($sh->name)); ?>' }); tabMenuOpen = false;"
                                            class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Ganti Nama</span>
                                    </button>
                                    
                                    <form action="<?php echo e(route('sheets.duplicate', $sh->id)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="w-full px-3 py-1.5 hover:bg-slate-100 text-left flex items-center gap-2 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            <span>Duplikasi</span>
                                        </button>
                                    </form>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sheet->group->sheets->count() > 1): ?>
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <form action="<?php echo e(route('sheets.delete', $sh->id)); ?>" method="POST" onsubmit="return confirm('Hapus lembar kerja ini? Data di dalamnya akan hilang.')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="w-full px-3 py-1.5 hover:bg-rose-50 text-left flex items-center gap-2 text-rose-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span>Hapus Lembar</span>
                                            </button>
                                        </form>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

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

    <!-- 5. BILAH RINGKASAN BAWAH (SUM, AVERAGE, STATUS) -->
    <div class="h-11 bg-slate-900 text-white px-4 flex items-center justify-between text-xs shrink-0 border-t border-slate-800">
        
        <!-- Left: Summary Calculations (SUM, AVERAGE, COUNT) -->
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">TOTAL (SUM):</span>
                <span class="font-mono font-bold text-emerald-400 text-sm">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($numericCellsCount > 0): ?>
                        <?php echo e(number_format($sumTotal, 2, ',', '.')); ?>

                    <?php else: ?>
                        0
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </span>
            </div>

            <div class="hidden sm:flex items-center gap-2 border-l border-slate-700 pl-6">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">RATA-RATA (AVERAGE):</span>
                <span class="font-mono font-bold text-sky-400 text-sm">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($numericCellsCount > 0): ?>
                        <?php echo e(number_format($averageTotal, 2, ',', '.')); ?>

                    <?php else: ?>
                        —
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </span>
            </div>

            <div class="hidden md:flex items-center gap-2 border-l border-slate-700 pl-6">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">SEL TERISI:</span>
                <span class="font-mono font-bold text-slate-300">
                    <?php echo e($filledCellsCount); ?>

                </span>
            </div>
        </div>

        <!-- Right: Status Badge -->
        <div class="flex items-center gap-2">
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-300 font-semibold text-slate-700">Aktivitas</button>
                <aside x-show="open" @click.away="open = false" x-cloak class="absolute right-0 bottom-full mb-2 w-80 max-h-80 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-xl p-3 z-40" aria-label="Riwayat aktivitas spreadsheet">
                    <h3 class="text-xs font-bold text-slate-800 mb-2">Riwayat Aktivitas</h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="py-2 border-b border-slate-100 last:border-0 text-[11px]">
                            <span class="font-semibold text-slate-800"><?php echo e($activity['actor']); ?></span>
                            <span class="text-slate-600"><?php echo e(str_replace('_', ' ', $activity['action'])); ?></span>
                            <span class="text-sky-700"><?php echo e($activity['sheet']); ?><?php echo e($activity['range'] ? ' · ' . $activity['range'] : ''); ?></span>
                            <time class="block text-slate-400 mt-0.5"><?php echo e($activity['at']); ?></time>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="py-5 text-center text-xs text-slate-400">Belum ada aktivitas tersimpan.</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </aside>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-950/80 border border-emerald-500/40 text-emerald-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Tersimpan Otomatis
            </span>
        </div>
    </div>

    <!-- 6. MODAL CARI & GANTI (FIND AND REPLACE) -->
    <div x-show="$wire.showFindReplaceModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
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
    <div x-show="$wire.showDeleteColumnModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-xs transition-opacity" wire:click="cancelDeleteColumn"></div>

            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Peringatan: Kolom Berisi Data!</h3>
                        <p class="text-xs text-slate-500">Kolom <?php echo e($columnPendingDeleteLetter); ?> memiliki data</p>
                    </div>
                </div>

                <div class="py-4 text-xs text-slate-600 space-y-2">
                    <p>
                        Kolom <strong class="text-slate-900 font-mono"><?php echo e($columnPendingDeleteLetter); ?></strong> saat ini memiliki <strong class="text-rose-600 font-bold font-mono"><?php echo e($columnPendingDeleteCount); ?> sel</strong> yang sudah terisi data.
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
<?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views\livewire\spreadsheet-worksheet.blade.php ENDPATH**/ ?>