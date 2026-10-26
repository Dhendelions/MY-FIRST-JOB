<?php $__env->startSection('content'); ?>
<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto w-full">

    <!-- 1. WELCOME BANNER DENGAN STATUS KOLABORASI -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0A192F] via-[#112240] to-[#1D3557] p-6 sm:p-8 text-white shadow-xl border border-slate-800 reveal-on-scroll">
        <!-- Ambient Background Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-0 right-1/4 w-40 h-40 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/10 border border-sky-400/20 text-xs font-semibold text-sky-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Sistem Kolaborasi Spreadsheet Aktif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Halo, <?php echo e($user->name); ?>! 👋
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Selamat datang di dasbor kolaborasi <span class="text-sky-300 font-semibold">Dadhen Mandiri</span>. Kelola lembar spreadsheet dokumen, formula, anggaran, dan perencanaan proyek bersama seluruh anggota tim Anda.
                </p>
            </div>

            <!-- TIGA TOMBOL AKSI CEPAT UTAMA -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
                <button @click="activeModal = 'modal-create-group'" 
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-400 hover:to-sky-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-sky-500/25 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Buat Workspace
                </button>

                <button @click="activeModal = 'modal-join-group'" 
                        class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Gabung Tim
                </button>

                <button @click="activeModal = 'modal-friend'" 
                        class="px-4 py-2.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 border border-slate-700 text-slate-200 text-xs sm:text-sm font-bold flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Hubungkan Rekan
                </button>
            </div>
        </div>
    </div>

    <!-- 2. KARTU STATISTIK KPI REAL-TIME DARI DATABASE -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- KPI 1: Total Workspace -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow reveal-on-scroll">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Workspace Tim</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono"><?php echo e($totalWorkspaces); ?></span>
                <span class="text-xs text-sky-600 font-medium">Workspace Aktif</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Ruang kerja kolaboratif Anda</p>
        </div>

        <!-- KPI 2: Lembar Spreadsheet Aktif -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow reveal-on-scroll">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lembar Spreadsheet</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono"><?php echo e($totalSheetsCount); ?></span>
                <span class="text-xs text-emerald-600 font-medium">Lembar Terbuka</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Multi-sheet dokumen tim</p>
        </div>

        <!-- KPI 3: Total Sel Data Tercatat -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow reveal-on-scroll">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sel Data Tercatat</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono"><?php echo e(number_format($totalRecordedCells, 0, ',', '.')); ?></span>
                <span class="text-xs text-amber-600 font-medium">Sel Terisi</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Data tersimpan di database</p>
        </div>

        <!-- KPI 4: Jumlah Rekan Terhubung -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow reveal-on-scroll">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rekan Terhubung</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono"><?php echo e($totalFriends); ?></span>
                <span class="text-xs text-indigo-600 font-medium">Rekan Kolaboratif</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Jaringan rekan kerja aktif</p>
        </div>
    </div>

    <!-- 3. KATALOG WORKSPACE TIM -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6 reveal-on-scroll"
         x-data="{
            manageableWorkspaces: <?php echo \Illuminate\Support\Js::from($manageableGroups->map(fn ($workspace) => ['id' => (int) $workspace->id, 'name' => $workspace->name])->values())->toHtml() ?>,
            selectedWorkspaceIds: [],
            showBulkDeleteConfirmation: false,
            isDeleting: false,
            get selectedWorkspaceDetails() {
                return this.manageableWorkspaces.filter(workspace => this.selectedWorkspaceIds.includes(workspace.id));
            },
            cleanSelection() {
                const validIds = new Set(this.manageableWorkspaces.map(workspace => workspace.id));
                this.selectedWorkspaceIds = this.selectedWorkspaceIds.filter(id => validIds.has(id));
            },
            toggleWorkspace(workspace, checked) {
                const selectedIds = new Set(this.selectedWorkspaceIds);
                if (checked) {
                    selectedIds.add(workspace.id);
                } else {
                    selectedIds.delete(workspace.id);
                }
                this.selectedWorkspaceIds = [...selectedIds];
                this.cleanSelection();
            },
            toggleAllWorkspaces() {
                this.cleanSelection();
                this.selectedWorkspaceIds = this.selectedWorkspaceIds.length === this.manageableWorkspaces.length
                    ? []
                    : this.manageableWorkspaces.map(workspace => workspace.id);
            },
            submitBulkDelete(event) {
                if (this.isDeleting || this.selectedWorkspaceIds.length === 0) {
                    return;
                }

                this.isDeleting = true;
                event.target.submit();
            }
         }"
         x-init="cleanSelection()">
        
        <!-- Filter & Search Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Katalog Workspace Tim</h2>
                <p class="text-xs text-slate-500">Pilih workspace untuk membuka lembar kerja spreadsheet kolaboratif</p>
            </div>

            <!-- Search and Filter Form -->
            <form action="<?php echo e(route('dashboard')); ?>" method="GET" class="flex flex-wrap items-center gap-2.5">
                <div class="relative min-w-[220px]">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" name="q" value="<?php echo e(request('q')); ?>" placeholder="Cari nama workspace..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none bg-slate-50/50">
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request('q')): ?>
                    <a href="<?php echo e(route('dashboard')); ?>" class="text-xs text-rose-600 hover:underline px-2">Reset</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </form>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($manageableGroups->isNotEmpty()): ?>
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox"
                               :checked="selectedWorkspaceIds.length === manageableWorkspaces.length && manageableWorkspaces.length > 0"
                               x-effect="$el.indeterminate = selectedWorkspaceIds.length > 0 && selectedWorkspaceIds.length < manageableWorkspaces.length"
                               @change="toggleAllWorkspaces()"
                               class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        <span x-text="selectedWorkspaceIds.length === manageableWorkspaces.length && manageableWorkspaces.length > 0 ? 'Batalkan Semua Pilihan' : 'Pilih Semua yang Dapat Dikelola'">Pilih Semua yang Dapat Dikelola</span>
                    </label>
                    <span class="text-xs text-slate-500"><span x-text="selectedWorkspaceIds.length"></span> workspace dipilih</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="selectedWorkspaceIds = []"
                            :disabled="selectedWorkspaceIds.length === 0"
                            class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white disabled:cursor-not-allowed disabled:opacity-40">
                        Batalkan Pilihan
                    </button>
                    <button type="button" @click="showBulkDeleteConfirmation = true"
                            x-show="selectedWorkspaceIds.length > 0"
                            class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700">
                        Hapus Workspace Terpilih
                    </button>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Grid Kartu Workspace -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $myGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="group relative bg-white rounded-2xl border border-slate-200 hover:border-sky-300 shadow-xs hover:shadow-xl transition-all duration-200 flex flex-col justify-between overflow-hidden"
                     :class="selectedWorkspaceIds.includes(<?php echo e($grp->id); ?>) ? 'border-sky-500 ring-2 ring-sky-200 bg-sky-50/30' : ''">
                    
                    <!-- Card Top Accent Header -->
                    <div class="h-2 bg-gradient-to-r from-sky-500 via-emerald-400 to-indigo-500"></div>

                    <div class="p-5 flex-1 space-y-4">
                        
                        <!-- Header & Category Tag -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center font-bold text-sky-700 text-sm shadow-xs shrink-0">
                                    <?php echo e(strtoupper(substr($grp->name, 0, 2))); ?>

                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 group-hover:text-sky-600 transition-colors line-clamp-1">
                                        <?php echo e($grp->name); ?>

                                    </h3>
                                    <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                        <?php echo e($grp->location ?: 'Utama'); ?>

                                    </p>
                                </div>
                            </div>

                            <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold tracking-wide shrink-0 bg-sky-50 text-sky-700 border border-sky-200">
                                <?php echo e($grp->category); ?>

                            </span>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grp->isLeader($user->id)): ?>
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-600" @click.stop>
                                <input type="checkbox"
                                       :checked="selectedWorkspaceIds.includes(<?php echo e($grp->id); ?>)"
                                       @change="toggleWorkspace({ id: <?php echo e($grp->id); ?>, name: <?php echo \Illuminate\Support\Js::from($grp->name)->toHtml() ?> }, $event.target.checked)"
                                       @click.stop
                                       aria-label="Pilih workspace <?php echo e($grp->name); ?>"
                                       class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                Pilih workspace
                            </label>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <!-- Description -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grp->description): ?>
                            <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                <?php echo e($grp->description); ?>

                            </p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <!-- Metrics: Capacity & Sheets count -->
                        <div class="grid grid-cols-2 gap-2 py-2 px-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-semibold">Kapasitas Tim</span>
                                <p class="font-bold text-slate-800 font-mono"><?php echo e($grp->pond_count ?? 5); ?> Anggota</p>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-semibold">Lembar Kerja</span>
                                <p class="font-bold text-slate-800 font-mono"><?php echo e($grp->sheets->count()); ?> Sheet Aktif</p>
                            </div>
                        </div>

                        <!-- Member Avatars (Clickable to view profile) -->
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[11px] text-slate-400 font-medium">Anggota Tim:</span>
                            <div class="flex -space-x-2 overflow-hidden">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_2 = true; $__currentLoopData = $grp->activeMembers->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                    <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white object-cover cursor-pointer hover:ring-sky-400 hover:scale-110 transition-transform" 
                                         src="<?php echo e($m->user->avatar_url); ?>" 
                                         alt="<?php echo e($m->user->name); ?>" 
                                         title="Lihat profil <?php echo e($m->user->name); ?> (<?php echo e($m->role); ?>)"
                                         @click="openUserProfile(<?php echo e($m->user->id); ?>)">
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                    <span class="text-[11px] text-slate-400">1 Anggota</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grp->activeMembers->count() > 4): ?>
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full ring-2 ring-white bg-slate-200 text-slate-700 text-[10px] font-bold">
                                        +<?php echo e($grp->activeMembers->count() - 4); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Open Workspace Button -->
                    <div class="p-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-medium">Spreadsheet Kolaboratif</span>
                        <a href="<?php echo e(route('group.workspace', $grp->id)); ?>" 
                           class="px-3.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-all">
                            <span>Buka Ruang Kerja</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-span-full py-12 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Belum ada workspace ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Mulai dengan membuat workspace tim baru atau bergabung ke workspace yang sudah ada.
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-2">
                        <button @click="activeModal = 'modal-create-group'" class="px-4 py-2 bg-sky-600 text-white rounded-xl text-xs font-semibold hover:bg-sky-500 transition-colors">
                            + Buat Workspace Baru
                        </button>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div x-show="showBulkDeleteConfirmation" x-transition.opacity
             class="fixed inset-0 z-[1100] overflow-y-auto" x-cloak role="dialog" aria-modal="true"
             aria-labelledby="bulk-delete-workspaces-title" @keydown.escape.window="showBulkDeleteConfirmation = false">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="showBulkDeleteConfirmation = false"></div>
                <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white text-left shadow-2xl ring-1 ring-black/5">
                    <div class="h-1.5 bg-gradient-to-r from-rose-500 via-red-500 to-orange-400"></div>
                    <div class="p-6">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-rose-600">Tindakan permanen</p>
                        <h3 id="bulk-delete-workspaces-title" class="mt-1 text-lg font-bold text-slate-900">
                            Hapus <span x-text="selectedWorkspaceIds.length"></span> workspace terpilih?
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Workspace dan data terkaitnya, termasuk sheet, sel, anggota, formula, gambar, serta riwayat aktivitas, akan dihapus sesuai kebijakan aplikasi dan tidak dapat dipulihkan.
                        </p>
                        <ul class="mt-4 max-h-40 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                            <template x-for="workspace in selectedWorkspaceDetails" :key="workspace.id">
                                <li class="flex items-center gap-2"><span class="text-rose-500">•</span><span x-text="workspace.name"></span></li>
                            </template>
                        </ul>
                        <form action="<?php echo e(route('groups.bulk-destroy')); ?>" method="POST" @submit.prevent="submitBulkDelete($event)" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <?php echo csrf_field(); ?>
                            <template x-for="workspaceId in selectedWorkspaceIds" :key="workspaceId">
                                <input type="hidden" name="groups[]" :value="workspaceId">
                            </template>
                            <button type="button" @click="showBulkDeleteConfirmation = false"
                                    class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                Batal
                            </button>
                            <button type="submit" :disabled="isDeleting || selectedWorkspaceIds.length === 0"
                                    class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700">
                                <span x-show="!isDeleting">Hapus Workspace</span>
                                <span x-show="isDeleting">Menghapus...</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Dashboard Ringkasan Operasional — Dadhen Mandiri'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views/dashboard.blade.php ENDPATH**/ ?>