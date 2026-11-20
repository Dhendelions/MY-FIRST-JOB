<?php $__env->startSection('content'); ?>
<div class="min-h-full w-full flex flex-col lg:flex-row">
    
    <!-- LEFT PANEL: Split-Screen Branding Showcase (Navy #142B4A Identity) -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-[#142B4A] p-12 text-white flex-col justify-between overflow-hidden border-r border-[#1B355A]">
        <!-- Background Ambient Lighting -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-sky-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Brand Header -->
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center shadow-xs text-white font-black text-2xl tracking-wider ring-2 ring-white/10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <span class="text-2xl font-extrabold tracking-tight text-white">Dadhen Mandiri</span>
                <p class="text-xs text-slate-300 font-medium">Aplikasi Spreadsheet Kolaboratif Modern</p>
            </div>
        </div>

        <!-- Middle Content / Value Props -->
        <div class="relative z-10 my-auto py-8">
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-sky-200 border border-white/15 inline-block mb-4">
                Kolaborasi Tim &amp; Spreadsheet Modern
            </span>
            <h1 class="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight">
                Kelola Perencanaan, Operasional &amp; Data Spreadsheet Tim Anda Secara Kolaboratif.
            </h1>
            <p class="mt-4 text-slate-300 text-sm leading-relaxed max-w-lg">
                Platform lembar kerja fleksibel ala Excel dan Google Sheets untuk berbagai kebutuhan bisnis, proyek, organisasi, inventaris, dan tim kerja. Nikmati kemudahan olah data bersama tanpa instalasi aplikasi rumit.
            </p>

            <!-- Feature Badges -->
            <div class="mt-8 grid grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                    <div class="w-8 h-8 rounded-lg bg-sky-400/20 text-sky-300 flex items-center justify-center font-bold mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h2 class="text-xs font-bold text-white">Ruang Kerja Multi-Sheet</h2>
                    <p class="text-[11px] text-slate-300 mt-1">Struktur dokumen terorganisir dengan formula dan kustomisasi kolom.</p>
                </div>
                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                    <div class="w-8 h-8 rounded-lg bg-emerald-400/20 text-emerald-300 flex items-center justify-center font-bold mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <h2 class="text-xs font-bold text-white">Tempel Sel Excel (Multi-Cell)</h2>
                    <p class="text-[11px] text-slate-300 mt-1">Copy-paste langsung tabel dari Microsoft Excel atau Google Sheets.</p>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="relative z-10 flex items-center justify-between text-xs text-slate-300 border-t border-white/10 pt-6">
            <span>&copy; <?php echo e(date('Y')); ?> Dadhen Mandiri. Hak Cipta Dilindungi.</span>
            <span class="flex items-center gap-1.5 text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Kolaborasi Cepat &amp; Aman
            </span>
        </div>
    </div>

    <!-- RIGHT PANEL: Clean Light Register Card -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 bg-[#F5F7FA] overflow-y-auto">
        <div class="w-full max-w-md bg-white border border-[#E5EAF0] rounded-2xl p-8 shadow-sm">
            
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-[#263445] tracking-tight">Daftar Akun Baru</h2>
                <p class="text-xs sm:text-sm text-[#7B8794] mt-1">Lengkapi data untuk bergabung ke ruang kerja Dadhen Mandiri</p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-[#FEE2E2] border border-red-200 text-[#991b1b] text-xs space-y-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span><?php echo e($error); ?></span>
                        </p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <form action="<?php echo e(route('register')); ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-xs font-semibold text-[#263445] mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="<?php echo e(old('name')); ?>" required placeholder="cth: Dheni Pratama"
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-white border border-[#E5EAF0] rounded-xl text-[#263445] placeholder-[#7B8794] focus:outline-none focus:ring-2 focus:ring-[#142B4A]/10 focus:border-[#142B4A] transition-colors">
                </div>

                <!-- Email & No. WhatsApp -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#263445] mb-1.5">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="<?php echo e(old('email')); ?>" required placeholder="nama@email.com"
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-white border border-[#E5EAF0] rounded-xl text-[#263445] placeholder-[#7B8794] focus:outline-none focus:ring-2 focus:ring-[#142B4A]/10 focus:border-[#142B4A] transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#263445] mb-1.5">WhatsApp</label>
                        <input type="text" name="whatsapp" value="<?php echo e(old('whatsapp')); ?>" placeholder="cth: 0853 3100 3356"
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-white border border-[#E5EAF0] rounded-xl text-[#263445] placeholder-[#7B8794] focus:outline-none focus:ring-2 focus:ring-[#142B4A]/10 focus:border-[#142B4A] transition-colors">
                    </div>
                </div>

                <!-- Password & Konfirmasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#263445] mb-1.5">Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required placeholder="••••••••"
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-white border border-[#E5EAF0] rounded-xl text-[#263445] placeholder-[#7B8794] focus:outline-none focus:ring-2 focus:ring-[#142B4A]/10 focus:border-[#142B4A] transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#263445] mb-1.5">Konfirmasi Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••"
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-white border border-[#E5EAF0] rounded-xl text-[#263445] placeholder-[#7B8794] focus:outline-none focus:ring-2 focus:ring-[#142B4A]/10 focus:border-[#142B4A] transition-colors">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-3 px-4 rounded-xl bg-[#142B4A] hover:bg-[#1B355A] text-white font-bold text-xs sm:text-sm shadow-xs transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0">
                        Daftar &amp; Masuk ke Dasbor
                    </button>
                </div>
            </form>

            <!-- Bottom Link -->
            <div class="mt-6 pt-5 border-t border-[#E5EAF0] text-center">
                <p class="text-xs text-[#7B8794]">
                    Sudah memiliki akun? 
                    <a href="<?php echo e(route('login')); ?>" class="font-bold text-[#142B4A] hover:underline ml-1">
                        Masuk di Sini
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', ['title' => 'Daftar Akun Baru — Dadhen Mandiri'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views\auth\register.blade.php ENDPATH**/ ?>