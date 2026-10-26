<?php $__env->startSection('content'); ?>
<div class="min-h-full w-full flex flex-col lg:flex-row">
    
    <!-- LEFT PANEL: Split-Screen Branding Showcase -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-[#060e1b] via-[#0A192F] to-[#112240] p-12 text-white flex-col justify-between overflow-hidden border-r border-slate-800">
        <!-- Ambient Glow -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Brand Header -->
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-500 to-emerald-400 flex items-center justify-center shadow-lg shadow-sky-500/20 text-white font-black text-2xl tracking-wider ring-4 ring-white/10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <span class="text-2xl font-extrabold tracking-tight text-white">Dadhen Mandiri</span>
                <p class="text-xs text-sky-400 font-medium">Aplikasi Spreadsheet Kolaboratif Modern</p>
            </div>
        </div>

        <!-- Middle Quote / Feature Highlight -->
        <div class="relative z-10 my-auto py-8">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-800/80 border border-slate-700/80 text-xs font-semibold text-emerald-400 mb-6">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Spreadsheet Andal, Formula Cepat & Kolaborasi Real-Time
            </div>
            <h1 class="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight">
                "Kelola spreadsheet dokumen, anggaran, dan proyek tim Anda dalam satu ruang kerja fleksibel."
            </h1>
            <p class="mt-4 text-slate-300 text-sm leading-relaxed max-w-lg">
                Masuk ke workspace tim Anda. Atur dokumen lembar kerja multi-sheet lengkap dengan formula otomatis, format sel, dan kolaborasi terpadu bersama tim Anda.
            </p>

            <!-- Real Features Ticker -->
            <div class="mt-8 grid grid-cols-3 gap-4 border-t border-slate-800 pt-6">
                <div>
                    <span class="text-xl font-bold text-sky-400 font-mono">100%</span>
                    <p class="text-[11px] text-slate-400">Ringan & Cepat</p>
                </div>
                <div>
                    <span class="text-xl font-bold text-emerald-400 font-mono">Multi-Tab</span>
                    <p class="text-[11px] text-slate-400">Sheet Dokumen Tim</p>
                </div>
                <div>
                    <span class="text-xl font-bold text-amber-400 font-mono">Formula</span>
                    <p class="text-[11px] text-slate-400">SUM, AVG, MIN, MAX</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 flex items-center justify-between text-xs text-slate-400 border-t border-slate-800/80 pt-6">
            <span>&copy; <?php echo e(date('Y')); ?> Dadhen Mandiri. Hak Cipta Dilindungi.</span>
            <span>Aplikasi Spreadsheet Kolaboratif</span>
        </div>
    </div>

    <!-- RIGHT PANEL: Authentic Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 bg-slate-900 overflow-y-auto">
        <div class="w-full max-w-md bg-slate-800/80 border border-slate-700/80 rounded-2xl p-8 shadow-2xl backdrop-blur-md">
            
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-white tracking-tight">Selamat Datang Kembali</h2>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Masuk dengan email dan password akun Anda</p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('info')): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-sky-500/10 border border-sky-500/30 text-sky-300 text-xs">
                    <?php echo e(session('info')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?php echo e($errors->first()); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Authentic Login Form -->
            <form action="<?php echo e(route('login')); ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email Akun</label>
                    <input type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus placeholder="nama@email.com"
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-sky-500 focus:ring-sky-500">
                        <span>Ingat sesi masuk</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-400 hover:to-sky-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-sky-500/25 transition-all">
                        Masuk ke Akun
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-700/60 text-center">
                <p class="text-xs text-slate-400">
                    Belum memiliki akun? 
                    <a href="<?php echo e(route('register')); ?>" class="font-semibold text-sky-400 hover:text-sky-300 transition-colors ml-1">
                        Daftar Akun Baru
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', ['title' => 'Masuk — Dadhen Mandiri'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views/auth/login.blade.php ENDPATH**/ ?>