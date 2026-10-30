<div wire:poll.30s.visible="refreshActivities" class="flex flex-col h-full max-h-[75vh]">
    <!-- Filter & Search Bar -->
    <div class="px-5 py-3 border-b border-slate-100 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Real-Time
            </span>
            <span class="text-xs text-slate-500">Auto-update setiap 30 detik</span>
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="filterAction" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <option value="all">Semua Aktivitas</option>
                <option value="cells">Data Sel & Formula</option>
                <option value="structure">Baris & Kolom</option>
                <option value="members">Anggota & Peran</option>
                <option value="workspace">Workspace & Lembar</option>
            </select>

            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama/sel..." 
                       class="text-xs pl-7 pr-3 py-1.5 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none w-36 sm:w-44">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Timeline List -->
    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3.5 divide-y divide-slate-100">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $act): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $roleAtTime = strtolower(trim($act->details['user_role'] ?? $group->getMemberRole($act->user_id) ?? 'admin'));
                $userName = $act->user?->name ?? ($act->details['user_name'] ?? 'Pengguna');
                $userAvatar = $act->user?->avatar_url ?? null;
                $sheetName = $act->sheet?->name ?? ($act->details['sheet_name'] ?? null);
                $jakartaTime = $act->committed_at->timezone('Asia/Jakarta');

                $actionBadge = match($act->action) {
                    'cell_changed' => ['label' => 'Edit Sel', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                    'formula_changed' => ['label' => 'Edit Formula', 'class' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    'format_changed' => ['label' => 'Format Sel', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                    'column_added' => ['label' => 'Tambah Kolom', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    'column_deleted' => ['label' => 'Hapus Kolom', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                    'row_added' => ['label' => 'Tambah Baris', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'M12 4v16m8-8H4'],
                    'row_deleted' => ['label' => 'Hapus Baris', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'M20 12H4'],
                    'cells_pasted' => ['label' => 'Paste Data', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    'member_added' => ['label' => 'Tambah Anggota', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                    'member_removed' => ['label' => 'Keluarkan Anggota', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6'],
                    'role_changed' => ['label' => 'Ganti Peran', 'class' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                    'workspace_updated' => ['label' => 'Update Workspace', 'class' => 'bg-slate-100 text-slate-800 border-slate-200', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                    'csv_imported' => ['label' => 'Impor CSV/Data', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
                    default => ['label' => ucfirst(str_replace('_', ' ', $act->action)), 'class' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                };
            ?>

            <div class="pt-3 first:pt-0 flex items-start justify-between gap-3 group">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <!-- Avatar / Initial -->
                    <div class="w-8 h-8 rounded-full ring-1 ring-slate-200 overflow-hidden shrink-0 flex items-center justify-center bg-slate-100 font-bold text-slate-700 text-xs">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userAvatar): ?>
                            <img src="<?php echo e($userAvatar); ?>" alt="<?php echo e($userName); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo e(strtoupper(substr($userName, 0, 2))); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Details -->
                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="font-bold text-slate-900"><?php echo e($userName); ?></span>
                            <span class="text-[10px] text-slate-400 font-mono">(ID: #<?php echo e($act->user_id); ?>)</span>
                            <span class="text-[10px] uppercase font-mono font-bold px-1.5 py-0.5 rounded <?php echo e($roleAtTime === 'leader' ? 'bg-amber-100 text-amber-800' : ($roleAtTime === 'admin' ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700')); ?>">
                                <?php echo e($roleAtTime); ?>

                            </span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border <?php echo e($actionBadge['class']); ?>">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($actionBadge['icon']); ?>"></path></svg>
                                <?php echo e($actionBadge['label']); ?>

                            </span>
                        </div>

                        <!-- Location & Values -->
                        <div class="text-xs text-slate-600 flex flex-wrap items-center gap-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sheetName || $act->cell_range): ?>
                                <span class="font-mono bg-slate-100 text-slate-800 px-1.5 py-0.5 rounded text-[11px]">
                                    <?php echo e($sheetName ?? 'Workspace'); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($act->cell_range): ?> • <strong class="text-sky-700 font-bold"><?php echo e($act->cell_range); ?></strong><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($act->old_value !== null || $act->new_value !== null): ?>
                                <div class="flex items-center gap-1 font-mono text-[11px] truncate max-w-full">
                                    <span class="text-rose-600 line-through bg-rose-50 px-1.5 py-0.2 rounded truncate max-w-[120px]" title="<?php echo e($act->old_value); ?>">
                                        <?php echo e($act->old_value !== null && $act->old_value !== '' ? $act->old_value : '(kosong)'); ?>

                                    </span>
                                    <span class="text-slate-400 font-sans">➔</span>
                                    <span class="text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.2 rounded truncate max-w-[140px]" title="<?php echo e($act->new_value); ?>">
                                        <?php echo e($act->new_value !== null && $act->new_value !== '' ? $act->new_value : '(kosong)'); ?>

                                    </span>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Timestamp (Asia/Jakarta) -->
                <div class="text-right shrink-0">
                    <p class="text-[11px] font-medium text-slate-700 whitespace-nowrap"><?php echo e($jakartaTime->format('H:i:s')); ?> WIB</p>
                    <p class="text-[10px] text-slate-400 whitespace-nowrap"><?php echo e($jakartaTime->translatedFormat('d M Y')); ?></p>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="py-12 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-xs font-medium">Belum ada riwayat aktivitas yang tercatat.</p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views/livewire/workspace-activity-history.blade.php ENDPATH**/ ?>