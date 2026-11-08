<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-xs uppercase tracking-wider">
                    Komparasi Lintas Tahun
                </span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500">PT Dadhen Mandiri</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">Yearly Comparison</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Perbandingan kinerja keuangan dan operasional lintas seluruh tahun operasional terdaftar (termasuk tahun yang diarsipkan).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('company.analytics')); ?>" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-all shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                <span>Company Analytics</span>
            </a>
            <a href="<?php echo e(route('dashboard')); ?>" class="px-4 py-2 bg-navy-900 hover:bg-navy-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Tabel Komparasi Seluruh Tahun Operasional</h3>
                <p class="text-xs text-slate-500">Sumber data: Agregasi langsung lembar kerja Rank, Margin, Analisa, dan Cost</p>
            </div>
            <span class="text-xs font-mono text-slate-500 font-semibold">Total: <?php echo e(count($comparisonList)); ?> Tahun Terdaftar</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-sans font-semibold text-[11px]">
                    <tr>
                        <th class="px-5 py-4">Tahun</th>
                        <th class="px-5 py-4 font-sans">Status</th>
                        <th class="px-5 py-4 text-right">Omset (Rp)</th>
                        <th class="px-5 py-4 text-right">Bahan Baku (Rp)</th>
                        <th class="px-5 py-4 text-right">Gross Margin (Rp)</th>
                        <th class="px-5 py-4 text-right">Margin %</th>
                        <th class="px-5 py-4 text-right">Biaya Ops (Rp)</th>
                        <th class="px-5 py-4 text-right">Laba Bersih (Rp)</th>
                        <th class="px-5 py-4 text-right">Input RM (Kg)</th>
                        <th class="px-5 py-4 text-center font-sans">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $comparisonList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-5 py-4 font-sans font-black text-slate-900 text-sm">
                                <?php echo e($item['year']); ?>

                            </td>
                            <td class="px-5 py-4 font-sans">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?php echo e($item['is_archived'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'); ?>">
                                    <?php echo e($item['is_archived'] ? 'Diarsipkan' : 'Aktif'); ?>

                                </span>
                            </td>
                            <td class="px-5 py-4 text-right text-slate-800 font-bold">
                                <?php echo e(isset($item['financial']['total_omset']) && $item['financial']['total_omset'] > 0 ? number_format($item['financial']['total_omset'], 0, ',', '.') : '-'); ?>

                            </td>
                            <td class="px-5 py-4 text-right text-slate-600">
                                <?php echo e(isset($item['financial']['total_rm']) && $item['financial']['total_rm'] > 0 ? number_format($item['financial']['total_rm'], 0, ',', '.') : '-'); ?>

                            </td>
                            <td class="px-5 py-4 text-right text-emerald-600 font-bold">
                                <?php echo e(isset($item['financial']['total_margin']) && $item['financial']['total_margin'] > 0 ? number_format($item['financial']['total_margin'], 0, ',', '.') : '-'); ?>

                            </td>
                            <td class="px-5 py-4 text-right text-slate-700">
                                <?php echo e(isset($item['financial']['margin_pct']) && $item['financial']['margin_pct'] > 0 ? $item['financial']['margin_pct'] . '%' : '-'); ?>

                            </td>
                            <td class="px-5 py-4 text-right text-amber-600">
                                <?php echo e(isset($item['financial']['total_cost']) && $item['financial']['total_cost'] > 0 ? number_format($item['financial']['total_cost'], 0, ',', '.') : '-'); ?>

                            </td>
                            <td class="px-5 py-4 text-right font-black text-sky-600">
                                <?php echo e(isset($item['financial']['total_nett']) && $item['financial']['total_nett'] > 0 ? number_format($item['financial']['total_nett'], 0, ',', '.') : '-'); ?>

                            </td>
                            <td class="px-5 py-4 text-right text-slate-700">
                                <?php echo e(isset($item['operational']['total_rm_kg']) && $item['operational']['total_rm_kg'] > 0 ? number_format($item['operational']['total_rm_kg'], 1, ',', '.') : '-'); ?>

                            </td>
                            <td class="px-5 py-4 text-center font-sans">
                                <a href="<?php echo e(route('company.analytics', ['year' => $item['year']])); ?>" class="px-3 py-1 bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-lg font-bold text-xs transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="10" class="px-6 py-8 text-center text-slate-400 font-sans">
                                Belum ada tahun operasional terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Yearly Comparison — PT Dadhen Mandiri'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views/company/comparison.blade.php ENDPATH**/ ?>