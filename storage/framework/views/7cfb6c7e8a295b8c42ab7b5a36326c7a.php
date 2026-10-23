<?php $__env->startSection('content'); ?>
<div class="flex-1 flex flex-col min-h-0 bg-slate-100" 
     x-data="{ 
        workspaceModal: null,
        editSheetModal: false,
        renameSheetId: null,
        renameSheetName: '',
        unlockSheetId: <?php echo e($activeSheet?->id ?? 'null'); ?>

     }" 
     @open-add-sheet-modal.window="workspaceModal = 'modal-add-sheet'"
     @open-rename-modal.window="renameSheetId = $event.detail.id; renameSheetName = $event.detail.name; workspaceModal = 'modal-rename-sheet'"
     @sheet-switch.window="unlockSheetId = $event.detail.sheetId; workspaceModal = 'modal-password-unlock'; $nextTick(() => $refs.unlockPassword.focus())">

    <!-- 1. HEADER IDENTITAS WORKSPACE -->
    <div class="bg-white border-b border-slate-200 px-4 sm:px-6 py-4 shadow-2xs reveal-on-scroll">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Left: Avatar & Nama -->
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-sky-600 to-cyan-500 p-0.5 shadow-md shadow-sky-600/20 shrink-0">
                    <div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center font-black text-sky-700 text-lg">
                        <?php echo e(strtoupper(substr($group->name, 0, 2))); ?>

                    </div>
                </div>

                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"><?php echo e($group->name); ?></h1>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                        <span>Peran Anda: <strong class="text-slate-800 uppercase font-mono"><?php echo e($userRole); ?></strong></span>
                        <span>•</span>
                        <span><?php echo e($members->count()); ?> Anggota</span>
                    </div>
                </div>
            </div>

            <!-- Right: Members & Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">

                <!-- Active Members Avatars & Member Management Button -->
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-2 overflow-hidden">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $members->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white object-cover cursor-pointer hover:ring-sky-400 hover:scale-110 transition-transform" 
                                 src="<?php echo e($m->user->avatar_url); ?>" 
                                 alt="<?php echo e($m->user->name); ?>" 
                                 title="Lihat profil <?php echo e($m->user->name); ?> (<?php echo e($m->role); ?>)"
                                 @click="openUserProfile(<?php echo e($m->user->id); ?>)">
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($userRole, ['leader', 'admin'])): ?>
                        <button @click="workspaceModal = 'modal-add-member'" 
                                class="p-2 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 font-semibold text-xs flex items-center gap-1 transition-colors"
                                title="Tambah Anggota Tim">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            <span class="hidden sm:inline">+ Anggota</span>
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <button @click="workspaceModal = 'modal-manage-members'" 
                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center gap-1 transition-colors"
                            title="Kelola Anggota & Peran">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </button>
                </div>

                <!-- Settings Button (Leader & Admin) -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'leader'): ?>
                    <button @click="workspaceModal = 'modal-group-settings'" 
                            class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                            title="Pengaturan Workspace">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sheet tabs are now only at the bottom via the Livewire component -->

    <!-- 3. LEMBAR KERJA SPREADSHEET (LIVEWIRE COMPONENT) -->
    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeSheet && !$requiresPasswordUnlock && !$requiresPasswordSetup): ?>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('spreadsheet-worksheet', ['sheet' => $activeSheet]);

$__key = $activeSheet->id;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-148324805-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        <?php else: ?>
            <div class="flex-1 flex items-center justify-center p-8 text-center">
                <p class="text-slate-400 text-sm">Pilih lembar kerja di atas untuk mulai mencatat data spreadsheet.</p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- MODAL 1: Tambah Anggota Baru -->
    <div x-show="workspaceModal === 'modal-add-member'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Tambah Anggota Tim</h3>
                            <p class="text-xs text-slate-500">Undang anggota ke workspace <?php echo e($group->name); ?></p>
                        </div>
                    </div>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="<?php echo e(route('groups.members.add', $group->id)); ?>" method="POST" class="mt-4 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email atau No. Kontak Terdaftar <span class="text-rose-500">*</span></label>
                        <input type="text" name="email_or_phone" required placeholder="cth: nama@email.com" 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran dalam Workspace <span class="text-rose-500">*</span></label>
                        <select name="role" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white">
                            <option value="karyawan">Karyawan (Akses & Edit Lembar Kerja)</option>
                            <option value="admin">Admin (Kelola Anggota & Dokumen)</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'leader'): ?>
                                <option value="leader">Leader (Pemilik / Pemimpin Tim)</option>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="workspaceModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-500/20 transition-all">
                            Tambahkan Anggota
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Kelola Anggota & Peran (Leader & Admin) -->
    <div x-show="workspaceModal === 'modal-manage-members'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-2xl w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Daftar Anggota Workspace</h3>
                            <p class="text-xs text-slate-500">Kelola izin dan peran anggota tim</p>
                        </div>
                    </div>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mt-4 max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $normalizedRole = strtolower(trim($mem->role));
                            if ($normalizedRole === 'ketua tambak') $normalizedRole = 'leader';
                            elseif (in_array($normalizedRole, ['teknisi kolam', 'staff keuangan'])) $normalizedRole = 'admin';
                            elseif ($normalizedRole === 'anggota') $normalizedRole = 'karyawan';
                        ?>
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 cursor-pointer hover:opacity-85 transition-opacity" 
                                 @click="openUserProfile(<?php echo e($mem->user->id); ?>)" 
                                 title="Klik untuk membuka profil">
                                <img class="w-9 h-9 rounded-full object-cover ring-1 ring-slate-200" src="<?php echo e($mem->user->avatar_url); ?>" alt="<?php echo e($mem->user->name); ?>">
                                <div>
                                    <p class="text-xs font-bold text-slate-800 hover:text-sky-600 underline decoration-slate-300"><?php echo e($mem->user->name); ?></p>
                                    <p class="text-[11px] text-slate-500"><?php echo e($mem->user->role_title ?? 'Anggota Tim'); ?></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($userRole, ['leader', 'admin']) && $mem->user_id !== Auth::id()): ?>
                                    <form action="<?php echo e(route('groups.members.update', [$group->id, $mem->id])); ?>" method="POST" class="flex items-center gap-1">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <select name="role" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white text-slate-700">
                                            <option value="karyawan" <?php echo e($normalizedRole === 'karyawan' ? 'selected' : ''); ?>>Karyawan</option>
                                            <option value="admin" <?php echo e($normalizedRole === 'admin' ? 'selected' : ''); ?>>Admin</option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'leader'): ?>
                                                <option value="leader" <?php echo e($normalizedRole === 'leader' ? 'selected' : ''); ?>>Leader</option>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </select>
                                    </form>

                                    <form action="<?php echo e(route('groups.members.remove', [$group->id, $mem->id])); ?>" method="POST" onsubmit="return confirm('Keluarkan anggota ini dari workspace?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="p-1 rounded text-rose-500 hover:bg-rose-50" title="Keluarkan Anggota">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded uppercase bg-slate-100 text-slate-700">
                                        <?php echo e($normalizedRole); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Pengaturan Workspace (Leader & Admin) -->
    <div x-show="workspaceModal === 'modal-group-settings'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Pengaturan Workspace</h3>
                            <p class="text-xs text-slate-500">Perbarui nama dan password workspace</p>
                        </div>
                    </div>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="<?php echo e(route('groups.update', $group->id)); ?>" method="POST" class="mt-4 space-y-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Workspace <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="<?php echo e($group->name); ?>" required 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'leader'): ?>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti Password Workspace</label>
                        <input type="password" name="password" minlength="4" placeholder="Kosongkan jika tidak ingin mengubah password..." 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <input type="password" name="current_password" placeholder="Password saat ini (wajib untuk mengganti)" 
                               class="w-full mt-2 text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Status saat ini: <strong class="text-emerald-600"><?php echo e($group->hasPassword() ? 'Password Terpasang ✓' : 'Password Belum Diatur'); ?></strong></p>
                        <p class="text-[11px] text-slate-400">Hanya leader yang dapat melihat dan mengubah password.</p>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'leader'): ?>
                            <button type="button" 
                                    @click="workspaceModal = 'modal-delete-workspace'"
                                    class="px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                                Hapus Workspace
                            </button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="workspaceModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white shadow-xs">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'leader'): ?>
    <!-- MODAL: Konfirmasi hapus workspace -->
    <div x-show="workspaceModal === 'modal-delete-workspace'" x-transition.opacity class="fixed inset-0 z-[60] overflow-y-auto" x-cloak role="dialog" aria-modal="true" aria-labelledby="delete-workspace-title" @keydown.escape.window="workspaceModal = null">
        <div class="flex min-h-screen items-center justify-center px-4 py-8">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="workspaceModal = null"></div>
            <div x-transition.scale.origin.center class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white text-left shadow-2xl ring-1 ring-black/5">
                <div class="h-1.5 bg-gradient-to-r from-rose-500 via-red-500 to-orange-400"></div>
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest text-rose-600">Tindakan permanen</p>
                            <h3 id="delete-workspace-title" class="mt-1 text-lg font-bold text-slate-900">Hapus workspace ini?</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Semua lembar kerja, data sel, dan riwayat yang ada di <strong class="font-semibold text-slate-900"><?php echo e($group->name); ?></strong> akan dihapus dan tidak dapat dipulihkan.</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs leading-5 text-rose-800">
                        Pastikan seluruh anggota sudah menyimpan data yang masih diperlukan sebelum melanjutkan.
                    </div>

                    <form action="<?php echo e(route('groups.destroy', $group->id)); ?>" method="POST" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="button" @click="workspaceModal = 'modal-group-settings'" class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm shadow-rose-600/30 transition hover:bg-rose-700">Ya, hapus workspace</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- MODAL 4: Tambah Lembar Baru -->
    <div x-show="workspaceModal === 'modal-add-sheet'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-sm w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-800">Tambah Lembar Kerja Baru</h3>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="<?php echo e(route('groups.sheets.add', $group->id)); ?>" method="POST" class="mt-4 space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lembar Baru</label>
                        <input type="text" name="name" required placeholder="cth: Proyek Q4, Data Logistik" 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="workspaceModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white">
                            Buat Lembar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 5: Ganti Nama Lembar (Rename Sheet) -->
    <div x-show="workspaceModal === 'modal-rename-sheet'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-sm w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-800">Ganti Nama Lembar</h3>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form :action="'<?php echo e(url('sheets')); ?>/' + renameSheetId + '/rename'" method="POST" class="mt-4 space-y-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lembar</label>
                        <input type="text" name="name" x-model="renameSheetName" required 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="workspaceModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white">
                            Simpan Nama
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($requiresPasswordSetup): ?>
        <!-- Password workspace lama harus disetel oleh leader sebelum digunakan. -->
        <div x-init="workspaceModal = 'modal-password-required'"></div>
        <div x-show="workspaceModal === 'modal-password-required'" x-transition.opacity class="fixed inset-0 z-[60] overflow-y-auto" x-cloak role="dialog" aria-modal="true" aria-labelledby="password-setup-title" @keydown.escape.prevent>
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-hidden="true"></div>
                <div x-transition.scale.origin.center x-ref="passwordSetupDialog" class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100">
                    <h3 id="password-setup-title" class="text-base font-bold text-slate-900">Tambahkan Password Workspace</h3>
                    <p class="text-xs text-slate-500 mt-1">Workspace ini belum memiliki password. Buat password untuk melanjutkan.</p>
                    <form action="<?php echo e(route('groups.update', $group->id)); ?>" method="POST" class="mt-5 space-y-4">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <input type="hidden" name="name" value="<?php echo e($group->name); ?>">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                            <input x-init="$nextTick(() => $el.focus())" type="password" name="password" required minlength="4" autofocus placeholder="Minimal 4 karakter"
                                class="w-full text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        </div>
                        <button type="submit" class="w-full px-5 py-2.5 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white">Simpan Password</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->hasPassword()): ?>
        <div x-init="if (<?php echo e($requiresPasswordUnlock ? 'true' : 'false'); ?>) workspaceModal = 'modal-password-unlock'"></div>
        <div x-show="workspaceModal === 'modal-password-unlock'" x-transition.opacity class="fixed inset-0 z-[60] overflow-y-auto" x-cloak role="dialog" aria-modal="true" aria-labelledby="password-unlock-title" @keydown.escape.prevent @wheel.prevent @touchmove.prevent>
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-hidden="true"></div>
                <div x-transition.scale.origin.center x-ref="passwordUnlockDialog" class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100">
                    <h3 id="password-unlock-title" class="text-base font-bold text-slate-900">Masukkan Password Workspace</h3>
                    <p class="text-xs text-slate-500 mt-1">Password diperlukan setiap kali Anda masuk kembali ke workspace.</p>
                    <form action="<?php echo e(route('groups.unlock', $group->id)); ?>" method="POST" class="mt-5 space-y-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="sheet_id" :value="unlockSheetId">
                        <input x-ref="unlockPassword" x-init="if (<?php echo e($requiresPasswordUnlock ? 'true' : 'false'); ?>) $nextTick(() => $el.focus())" type="password" name="password" required autofocus placeholder="Password workspace"
                            class="w-full text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <button type="submit" class="w-full px-5 py-2.5 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white">Masuk ke Workspace</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $group->name . ' — Ruang Kerja Spreadsheet Dadhen Mandiri'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views\groups\workspace.blade.php ENDPATH**/ ?>