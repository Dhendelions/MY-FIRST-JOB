<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="{ showPasswordModal: false }" <?php if(!$isUnlocked): ?> x-init="$nextTick(() => $dispatch('open-workspace-unlock', { year: <?php echo e($operationalYear->year); ?>, title: 'Ruang Kerja <?php echo e($operationalYear->year); ?>' }))" <?php endif; ?>>

    <!-- 1. Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?php echo e(route('dashboard')); ?>" class="hover:text-sky-600 transition-colors font-medium">Dasbor</a>
        <span>/</span>
        <span class="font-bold text-slate-800" aria-label="Workspace <?php echo e($operationalYear->year); ?>">Ruang Kerja <?php echo e($operationalYear->year); ?></span>
    </nav>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isUnlocked && !$requiresPasswordConfiguration): ?>
        <!-- LOCKED STATE: Centered Password Form for Locked Admins -->
        <div class="max-w-md mx-auto my-12 bg-white rounded-2xl border border-[#E5EAF0] shadow-sm p-6 sm:p-8 space-y-6 text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-[#FEF3C7] border border-amber-200 text-[#b45309] flex items-center justify-center shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <div>
                <span class="px-2.5 py-0.5 rounded-full bg-[#F5F7FA] text-[#142B4A] border border-[#E5EAF0] font-bold text-[11px] uppercase tracking-wider">
                    Autentikasi Terpusat
                </span>
                <h1 class="text-xl font-extrabold text-[#263445] mt-2" aria-label="Masukkan Password Workspace <?php echo e($operationalYear->year); ?>">
                    Masukkan Password Ruang Kerja <?php echo e($operationalYear->year); ?>

                </h1>
                <p class="text-xs text-[#7B8794] mt-1.5 leading-relaxed">
                    Ruang kerja tahun operasional <?php echo e($operationalYear->year); ?> dilindungi oleh password. Masukkan password untuk mengakses 12 lembar kerja bulanan dan spreadsheet.
                </p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                <div class="p-3 bg-[#FEE2E2] border border-red-200 rounded-xl text-xs text-[#991b1b] text-left font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?php echo e(session('error')); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                <div class="p-3 bg-[#FEE2E2] border border-red-200 rounded-xl text-xs text-[#991b1b] text-left space-y-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p>&bull; <?php echo e($err); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <form action="<?php echo e(route('company.year.unlock', $operationalYear->year)); ?>" method="POST" class="space-y-4 text-left">
                <?php echo csrf_field(); ?>
                <div>
                    <label for="year_password" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                        Password Ruang Kerja
                    </label>
                    <input type="password" 
                           name="password" 
                           id="year_password" 
                           required 
                           autofocus
                           autocomplete="current-password"
                           placeholder="Masukkan password ruang kerja" 
                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all">
                </div>

                <div class="flex flex-col gap-2 pt-2">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl btn-primary-gradient font-bold text-xs flex items-center justify-center gap-1.5" aria-label="Buka Workspace">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                        <span>Buka Ruang Kerja</span>
                    </button>
                    <a href="<?php echo e(route('dashboard')); ?>" class="w-full px-4 py-2.5 rounded-xl btn-secondary-gradient font-bold text-xs text-center">
                        Kembali ke Dasbor
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($requiresPasswordConfiguration): ?>
            <div class="min-h-[55vh] flex items-center justify-center">
                <div class="w-full max-w-md rounded-2xl border border-amber-200 bg-white p-6 text-center shadow-xl">
                    <div class="mx-auto mb-4 flex h-11 w-11 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-xl">🔒</div>
                    <h1 class="text-lg font-extrabold text-[#263445]">Password Workspace Belum Diatur</h1>
                    <p class="mt-2 text-sm leading-relaxed text-[#7B8794]">Owner perlu mengatur password untuk Ruang Kerja <?php echo e($operationalYear->year); ?> sebelum Admin dapat mengakses data atau periode apa pun.</p>
                    <a href="<?php echo e(route('dashboard')); ?>" class="mt-5 inline-flex rounded-xl btn-secondary-gradient px-4 py-2.5 text-xs font-bold">Kembali ke Dasbor</a>
                </div>
            </div>
        <?php else: ?>
        <!-- UNLOCKED / OWNER STATE: Full Year Workspace View -->
        
        <!-- Header Card (Clean Light SaaS Card) -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-6 sm:p-8 text-[#263445] shadow-xs border border-[#E5EAF0] card-clean">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#142B4A] via-[#526780] to-sky-400"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 rounded-full bg-[#F5F7FA] border border-[#E5EAF0] text-xs font-bold text-[#142B4A]">
                            Ruang Kerja Tahunan
                        </span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($operationalYear->is_archived): ?>
                            <span class="px-3 py-1 rounded-full bg-[#FEF3C7] border border-amber-200 text-xs font-bold text-[#b45309]">
                                Diarsipkan (Hanya Baca)
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full bg-[#DCFCE7] border border-emerald-200 text-xs font-bold text-[#15803d]">
                                Tahun Aktif
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($operationalYear->hasPassword()): ?>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#FEF3C7] border border-amber-200 text-[11px] font-semibold text-[#b45309] flex items-center gap-1">
                                <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Dilindungi Password
                            </span>
                        <?php else: ?>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#F5F7FA] border border-[#E5EAF0] text-[11px] font-semibold text-[#7B8794] flex items-center gap-1">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                Bebas Password
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-[#F5F7FA] border border-[#E5EAF0] flex items-center justify-center font-black text-2xl text-[#142B4A] shadow-xs">
                            <?php echo e($operationalYear->year); ?>

                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#263445] tracking-tight" aria-label="Workspace <?php echo e($operationalYear->year); ?>">
                                Ruang Kerja <?php echo e($operationalYear->year); ?>

                            </h1>
                            <p class="text-xs sm:text-sm text-[#7B8794] mt-0.5">
                                12 Lembar Kerja Bulanan &bull; 60 Dokumen Spreadsheet Terintegrasi
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a href="<?php echo e(route('dashboard')); ?>" class="px-3.5 py-2 rounded-xl btn-secondary-gradient text-xs font-bold flex items-center gap-1.5 shadow-xs">
                        <svg class="w-4 h-4 text-[#7B8794]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Dasbor</span>
                    </a>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->isOwner()): ?>
                        <a href="<?php echo e(route('company.analytics', ['year' => $operationalYear->year])); ?>" class="px-3.5 py-2 rounded-xl btn-secondary-gradient text-xs font-bold flex items-center gap-1.5 shadow-xs">
                            <svg class="w-4 h-4 text-[#315E91]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            <span>Analitik</span>
                        </a>

                        <button type="button" 
                                @click="showPasswordModal = true"
                                class="px-3.5 py-2 rounded-xl btn-primary-gradient text-xs font-bold flex items-center gap-1.5 shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            <span>Atur Password</span>
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
            <div class="p-4 bg-[#DCFCE7] border border-emerald-200 rounded-xl text-xs text-[#15803d] font-medium flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span><?php echo e(session('success')); ?></span>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- 12 Months Grid -->
        <div class="bg-white rounded-2xl border border-[#E5EAF0] shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                <div>
                    <h2 class="text-base font-bold text-[#263445]">Daftar Bulan Operasional</h2>
                    <p class="text-xs text-[#7B8794]">Pilih bulan operasional untuk membuka lembar kerja spreadsheet terpusat.</p>
                </div>
                <div class="text-xs font-semibold text-[#7B8794]">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->isOwner()): ?>
                        <span class="text-[#142B4A] font-bold">Hak Akses: Owner (Semua Sheet)</span>
                    <?php else: ?>
                        <span class="text-[#15803d] font-bold">Hak Akses: Admin (Analisa &amp; Cost)</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pt-2">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $operationalYear->monthlyWorkspaces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mw): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded-xl border border-[#E5EAF0] hover:border-[#142B4A]/30 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200 ease-out bg-white p-4 flex flex-col justify-between space-y-3 group">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-[#F5F7FA] group-hover:bg-[#142B4A] text-[#142B4A] group-hover:text-white font-black text-sm flex items-center justify-center font-mono transition-colors">
                                    <?php echo e(sprintf('%02d', $mw->month)); ?>

                                </div>
                                <div>
                                    <h3 class="font-bold text-[#263445] text-sm group-hover:text-[#142B4A] transition-colors">
                                        <?php echo e($mw->name); ?>

                                    </h3>
                                    <p class="text-[11px] text-[#7B8794] font-mono"><?php echo e($operationalYear->year); ?></p>
                                </div>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($operationalYear->is_archived): ?>
                                <span class="rounded-full bg-[#FEF3C7] px-2 py-0.5 text-[10px] font-bold text-[#b45309]">🔒 Diarsipkan</span>
                            <?php else: ?>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <!-- Available Sheets list -->
                        <div class="pt-2 border-t border-[#E5EAF0]">
                            <div class="flex flex-wrap gap-1 text-[10px]">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$operationalYear->is_archived || Auth::user()->isOwner()): ?>
                                    <a href="<?php echo e(route('company.workspace', [$operationalYear->year, $mw->month, 'Analisa'])); ?>"
                                       class="px-2 py-0.5 rounded bg-sky-50 hover:bg-sky-100 text-sky-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Analisa
                                    </a>
                                    <a href="<?php echo e(route('company.workspace', [$operationalYear->year, $mw->month, 'Cost'])); ?>"
                                       class="px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Cost
                                    </a>
                                <?php else: ?>
                                    <span class="rounded bg-[#FEF3C7] px-2 py-0.5 text-[#b45309]" aria-disabled="true">🔒 Diarsipkan</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->isOwner()): ?>
                                    <a href="<?php echo e(route('company.workspace', [$operationalYear->year, $mw->month, 'Margin'])); ?>" 
                                       class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Margin
                                    </a>
                                    <a href="<?php echo e(route('company.workspace', [$operationalYear->year, $mw->month, 'Pendukung'])); ?>" 
                                       class="px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Pendukung
                                    </a>
                                    <a href="<?php echo e(route('company.workspace', [$operationalYear->year, $mw->month, 'Rank'])); ?>" 
                                       class="px-2 py-0.5 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Rank
                                    </a>
                                <?php else: ?>
                                    <span class="px-1.5 py-0.5 rounded bg-[#F5F7FA] text-[#7B8794] text-[9px] font-medium" title="Dibatasi khusus Owner">
                                        +3 Sheet Owner
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$operationalYear->is_archived || Auth::user()->isOwner()): ?>
                            <a href="<?php echo e(route('company.workspace', [$operationalYear->year, $mw->month])); ?>"
                               class="w-full mt-2 px-3 py-2 rounded-xl bg-[#F5F7FA] hover:bg-[#142B4A] group-hover:bg-[#142B4A] text-[#263445] hover:text-white group-hover:text-white text-xs font-bold text-center transition-all duration-200 flex items-center justify-center gap-1.5 hover:-translate-y-0.5 active:translate-y-0">
                                <span>Buka Bulan <?php echo e($mw->name); ?></span>
                                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        <?php else: ?>
                            <span aria-disabled="true"
                                  class="w-full mt-2 px-3 py-2 rounded-xl bg-slate-100 text-slate-500 text-xs font-bold text-center cursor-not-allowed">
                                🔒 Diarsipkan — Hanya Owner
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->isOwner()): ?>
            <!-- Modal Atur Password Workspace (Owner Only) -->
            <div x-show="showPasswordModal" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#142B4A]/30 backdrop-blur-xs"
                 @keydown.escape.window="showPasswordModal = false">
                <div class="bg-white w-full max-w-md rounded-2xl border border-[#E5EAF0] shadow-xl p-6 space-y-5 modal-enter"
                     @click.outside="showPasswordModal = false">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center font-bold text-sm">
                                🔑
                            </div>
                            <h3 class="text-base font-bold text-[#263445]">Pengaturan Password Ruang Kerja <?php echo e($operationalYear->year); ?></h3>
                        </div>
                        <button type="button" @click="showPasswordModal = false" class="text-[#7B8794] hover:text-[#263445] text-lg">&times;</button>
                    </div>

                    <form action="<?php echo e(route('company.year.password', $operationalYear->year)); ?>" method="POST" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <div class="p-3 bg-[#FEF3C7] border border-amber-200 rounded-xl text-xs text-[#b45309] leading-relaxed">
                            <p class="font-bold">Informasi Keamanan:</p>
                            <p class="mt-0.5">
                                Mengatur atau memperbarui password akan segera merevokasi sesi seluruh Admin untuk tahun <?php echo e($operationalYear->year); ?>. Kosongkan kolom jika ingin menghapus proteksi password.
                            </p>
                        </div>

                        <div>
                            <label for="modal_password" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                                Password Baru (Min. 4 Karakter)
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="modal_password" 
                                   placeholder="Kosongkan untuk menghapus password"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all">
                        </div>

                        <div>
                            <label for="modal_password_confirmation" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                                Konfirmasi Password Baru
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="modal_password_confirmation" 
                                   placeholder="Ulangi password baru"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E5EAF0]">
                            <button type="button" 
                                    @click="showPasswordModal = false" 
                                    class="px-4 py-2 rounded-xl btn-secondary-gradient font-bold text-xs">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 rounded-xl btn-primary-gradient font-bold text-xs">
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Ruang Kerja ' . $operationalYear->year . ' — Dadhen Mandiri'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views/company/year.blade.php ENDPATH**/ ?>