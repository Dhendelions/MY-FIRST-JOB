<?php $__env->startSection('content'); ?>
<div class="flex-1 flex flex-col min-h-0 min-w-0 bg-slate-50">

    <!-- HEADER SECTION -->
    <div class="bg-white border-b border-slate-200 px-4 sm:px-6 py-5 shrink-0 shadow-2xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-sky-600 mb-1">
                    <span>PT Dadhen Mandiri</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Audit & Keamanan</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Log Aktivitas Perusahaan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Rekam jejak audit keamanan komprehensif seluruh aktivitas login, perubahan data user, manipulasi tahun operasional, dan mutasi sistem.
                </p>
            </div>
        </div>

        <!-- KPI SUMMARY STATS -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
            <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Aktivitas</p>
                <p class="text-lg font-black text-slate-900 mt-0.5"><?php echo e($stats['total_logs']); ?></p>
            </div>
            <div class="p-3.5 bg-sky-50/60 border border-sky-200/70 rounded-xl">
                <p class="text-[11px] font-semibold text-sky-700 uppercase tracking-wider">Aktivitas Hari Ini</p>
                <p class="text-lg font-black text-sky-900 mt-0.5"><?php echo e($stats['today_logs']); ?></p>
            </div>
            <div class="p-3.5 bg-indigo-50/60 border border-indigo-200/70 rounded-xl">
                <p class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider">Aksi Pengguna</p>
                <p class="text-lg font-black text-indigo-900 mt-0.5"><?php echo e($stats['user_actions']); ?></p>
            </div>
            <div class="p-3.5 bg-amber-50/60 border border-amber-200/70 rounded-xl">
                <p class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider">Aksi Tahun/Sistem</p>
                <p class="text-lg font-black text-amber-900 mt-0.5"><?php echo e($stats['system_actions']); ?></p>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">

        <!-- FILTER & SEARCH BAR -->
        <form action="<?php echo e(route('company.activity-logs.index')); ?>" method="GET" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Cari deskripsi, aktor, modul..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>
            </div>

            <div class="w-40">
                <select name="action" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    <option value="">Semua Tindakan</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $act): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($act); ?>" <?php echo e(request('action') === $act ? 'selected' : ''); ?>><?php echo e($act); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>

            <div class="w-44">
                <select name="user_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    <option value="">Semua Personil</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($usr->id); ?>" <?php echo e((string) request('user_id') === (string) $usr->id ? 'selected' : ''); ?>><?php echo e($usr->name); ?> (<?php echo e(strtoupper($usr->role ?? 'user')); ?>)</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>

            <div class="flex items-center gap-1.5">
                <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="px-2.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <span class="text-slate-400 text-xs">s/d</span>
                <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" class="px-2.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                Filter
            </button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->anyFilled(['search', 'action', 'user_id', 'date_from', 'date_to'])): ?>
                <a href="<?php echo e(route('company.activity-logs.index')); ?>" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 transition-colors">
                    Reset
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </form>

        <!-- LOGS TABLE -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold text-[11px] uppercase tracking-wider">
                            <th class="py-3 px-4">Waktu (WIB)</th>
                            <th class="py-3 px-4">Aktor</th>
                            <th class="py-3 px-4">Tindakan</th>
                            <th class="py-3 px-4">Resource</th>
                            <th class="py-3 px-4">Deskripsi Aktivitas</th>
                            <th class="py-3 px-4 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" x-data="{ expandedRow: null }">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $badgeStyle = match($log->action) {
                                    'login' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'logout' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'user_created' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'user_updated' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'role_changed' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'user_activated' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    'user_deactivated' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'password_reset' => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                                    'year_created' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'year_archived' => 'bg-orange-50 text-orange-700 border-orange-200',
                                    'year_restored' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-500 whitespace-nowrap">
                                    <?php echo e($log->created_at ? $log->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s') : '-'); ?>

                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-[11px] shrink-0 border border-slate-200">
                                            <?php echo e(strtoupper(substr($log->user_name ?? 'SYS', 0, 2))); ?>

                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900 text-xs leading-tight"><?php echo e($log->user_name ?? 'Sistem'); ?></p>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->actor): ?>
                                                <p class="text-[10px] text-slate-400 uppercase font-mono"><?php echo e($log->actor->role); ?></p>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border <?php echo e($badgeStyle); ?>">
                                        <?php echo e($log->action); ?>

                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->resource_type): ?>
                                        <span class="font-semibold text-slate-800"><?php echo e($log->resource_type); ?></span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->resource_id): ?>
                                            <span class="text-slate-400 font-mono">#<?php echo e($log->resource_id); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-700">
                                    <?php echo e($log->description); ?>

                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($log->properties)): ?>
                                        <button @click="expandedRow = (expandedRow === <?php echo e($log->id); ?> ? null : <?php echo e($log->id); ?>)"
                                                class="text-xs font-semibold text-sky-600 hover:text-sky-800 transition-colors cursor-pointer">
                                            <span x-text="expandedRow === <?php echo e($log->id); ?> ? 'Tutup' : 'Lihat Data'"></span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-slate-300 text-xs">-</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($log->properties)): ?>
                                <tr x-show="expandedRow === <?php echo e($log->id); ?>" x-cloak class="bg-slate-50/80">
                                    <td colspan="6" class="p-4 border-t border-slate-100">
                                        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Metadata Aktivitas (Sanitized / No Secrets)</p>
                                            <pre class="text-xs font-mono text-slate-800 bg-slate-50 p-3 rounded-lg overflow-x-auto whitespace-pre-wrap"><?php echo e(json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400 text-xs">
                                    Belum ada catatan aktivitas log yang sesuai dengan filter ini.
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logs->hasPages()): ?>
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    <?php echo e($logs->links()); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', ['title' => 'Log Aktivitas Perusahaan — PT Dadhen Mandiri'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views/company/activity_logs/index.blade.php ENDPATH**/ ?>