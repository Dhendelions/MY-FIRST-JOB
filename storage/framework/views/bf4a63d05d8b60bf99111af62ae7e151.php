<?php $__env->startSection('content'); ?>
<div class="p-4 sm:p-6 lg:p-8 max-w-2xl mx-auto w-full">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6 sm:p-8 relative overflow-hidden">
        
        <!-- Header Accent -->
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-sky-500 to-emerald-400"></div>

        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 pt-2">
            <img class="w-24 h-24 rounded-2xl object-cover ring-4 ring-sky-100 shadow-md shrink-0" 
                 src="<?php echo e($profileData['avatar_url']); ?>" 
                 alt="<?php echo e($profileData['name']); ?>">
            
            <div class="text-center sm:text-left flex-1 min-w-0">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"><?php echo e($profileData['name']); ?></h1>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($profileData['is_self']): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800">Profil Anda</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <p class="text-xs sm:text-sm text-sky-600 font-semibold font-mono mt-1 capitalize"><?php echo e($profileData['role_title']); ?></p>

                <div class="mt-4 flex flex-wrap items-center justify-center sm:justify-start gap-4 text-xs text-slate-500">
                    <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Bergabung: <strong><?php echo e($profileData['joined_since']); ?></strong></span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span>Workspace: <strong><?php echo e($profileData['total_workspaces']); ?></strong></span>
                    </div>
                </div>

                <!-- Friendship Action -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$profileData['is_self']): ?>
                    <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-center sm:justify-start gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($profileData['friendship_status'] === 'friends'): ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Rekan Tim Terhubung
                            </span>
                        <?php elseif($profileData['friendship_status'] === 'pending_sent'): ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">
                                <svg class="w-4 h-4 text-amber-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Permintaan Dikirim (Menunggu)
                            </span>
                        <?php elseif($profileData['friendship_status'] === 'pending_received'): ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-sky-50 text-sky-700 text-xs font-bold border border-sky-200">
                                Permintaan Masuk untuk Anda
                            </span>
                        <?php else: ?>
                            <form action="<?php echo e(route('friends.store')); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="friend_id" value="<?php echo e($profileData['id']); ?>">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-sm shadow-sky-500/20 flex items-center gap-1.5 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    Tambah Rekan Tim
                                </button>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="mt-8 pt-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400">
            <a href="<?php echo e(route('dashboard')); ?>" class="hover:text-slate-600 font-semibold flex items-center gap-1">
                ← Kembali ke Dasbor
            </a>
            <span>Dadhen Mandiri</span>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Profil Pengguna — ' . $profileData['name']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views\users\profile.blade.php ENDPATH**/ ?>