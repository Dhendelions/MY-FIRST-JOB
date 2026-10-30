@extends('layouts.app', ['title' => $group->name . ' — Ruang Kerja Spreadsheet Dadhen Mandiri'])

@section('content')
<div class="flex-1 flex flex-col min-h-0 min-w-0 bg-slate-100" 
     x-data="{ 
        workspaceModal: {{ $isSheetRestricted ? "'modal-access-restricted'" : 'null' }},
        editSheetModal: false,
        renameSheetId: null,
        renameSheetName: '',
        unlockSheetId: {{ $activeSheet?->id ?? 'null' }},
        workspacePasswordError: ''
     }" 
     @open-add-sheet-modal.window="workspaceModal = 'modal-add-sheet'"
     @open-rename-modal.window="renameSheetId = $event.detail.id; renameSheetName = $event.detail.name; workspaceModal = 'modal-rename-sheet'"
     @if($userRole !== 'leader')
     @sheet-switch.window="unlockSheetId = $event.detail.sheetId; workspaceModal = 'modal-password-unlock'; $nextTick(() => $refs.unlockPassword.focus())"
     @endif>

    @persist('workspace-header-' . $group->id)
    <!-- 1. HEADER IDENTITAS WORKSPACE -->
    <div class="sticky top-0 z-30 bg-white border-b border-slate-200 px-4 sm:px-6 py-4 shadow-2xs shrink-0">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Left: Avatar & Nama -->
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-sky-600 to-cyan-500 p-0.5 shadow-md shadow-sky-600/20 shrink-0 overflow-hidden">
                    @if($group->avatar_url)
                        <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}" class="w-full h-full object-cover rounded-[14px]">
                    @else
                        <div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center font-black text-sky-700 text-lg">
                            {{ strtoupper(substr($group->name, 0, 2)) }}
                        </div>
                    @endif
                </div>

                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $group->name }}</h1>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                        <span>Peran Anda: <strong class="text-slate-800 uppercase font-mono">{{ $userRole }}</strong></span>
                        <span>•</span>
                        <span>{{ $members->count() }} Anggota</span>
                    </div>
                </div>
            </div>

            <!-- Right: Members & Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">

                <!-- Active Members Avatars & Member Management Button -->
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-2 overflow-hidden">
                        @foreach($members->take(5) as $m)
                            <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white object-cover cursor-pointer hover:ring-sky-400 hover:scale-110 transition-transform" 
                                 src="{{ $m->user->avatar_url }}" 
                                 alt="{{ $m->user->name }}" 
                                 title="Lihat profil {{ $m->user->name }} ({{ $m->role }})"
                                 @click="openUserProfile({{ $m->user->id }})">
                        @endforeach
                    </div>

                    @if(in_array($userRole, ['leader', 'admin']))
                        <button @click="workspaceModal = 'modal-add-member'" 
                                class="p-2 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 font-semibold text-xs flex items-center gap-1 transition-colors"
                                title="Tambah Anggota Tim">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            <span class="hidden sm:inline">+ Anggota</span>
                        </button>
                    @endif

                    <button @click="workspaceModal = 'modal-manage-members'" 
                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center gap-1 transition-colors"
                            title="Kelola Anggota & Peran">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </button>

                    <!-- Tombol Riwayat Aktivitas Workspace -->
                    <button @click="workspaceModal = 'modal-activity-history'" 
                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center gap-1.5 transition-colors"
                            title="Riwayat Aktivitas Workspace">
                        <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="hidden sm:inline">Riwayat Aktivitas</span>
                    </button>

                    @if(in_array($userRole, ['leader', 'admin']))
                    <!-- Tombol Backup Seluruh Spreadsheet -->
                    <button @click="workspaceModal = 'modal-backup-workspace'" 
                            class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs flex items-center gap-1.5 transition-colors"
                            title="Backup Seluruh Spreadsheet (.xlsx)">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span class="hidden sm:inline">Backup Spreadsheet</span>
                    </button>
                    @endif
                </div>

                <!-- Settings Button (Leader & Admin) -->
                @if($userRole === 'leader')
                    <button @click="workspaceModal = 'modal-group-settings'" 
                            class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                            title="Pengaturan Workspace">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endpersist

    <!-- Sheet tabs are now only at the bottom via the Livewire component -->

    <!-- 3. LEMBAR KERJA SPREADSHEET (LIVEWIRE COMPONENT / RESTRICTION) -->
    <div class="flex-1 flex flex-col min-h-0 min-w-0 overflow-hidden">
        @if($isSheetRestricted)
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center bg-slate-50">
                <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4 shadow-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">Akses Lembar Kerja Dibatasi</h2>
                <p class="text-xs sm:text-sm text-slate-500 max-w-md mt-1 mb-5">
                    Lembar <strong>{{ $activeSheet?->name }}</strong> hanya dapat diakses oleh Leader tim. Peran Anda saat ini adalah <strong>{{ strtoupper($userRole) }}</strong>.
                </p>
                <div class="flex items-center gap-3">
                    @php
                        $accessibleSheet = $sheets->firstWhere(fn ($s) => !$s->isRestrictedForRole($userRole));
                    @endphp
                    @if($accessibleSheet)
                        <a href="{{ route('group.workspace', [$group->id, $accessibleSheet->id]) }}" 
                           class="px-4 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white shadow-sm transition-colors">
                            Buka Lembar {{ $accessibleSheet->name }}
                        </a>
                    @endif
                    <button @click="workspaceModal = 'modal-access-restricted'" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-700 transition-colors">
                        Pelajari Hak Akses
                    </button>
                </div>
            </div>

            <!-- Tab Lembar Kerja di Bawah untuk Navigasi jika Lembar Dibatasi -->
            <div class="h-10 bg-slate-100 border-t border-slate-200 px-4 flex items-center justify-between text-xs bottom-tabs shrink-0 select-none">
                <div class="flex items-center space-x-1 overflow-x-auto py-1">
                    @foreach($sheets as $sh)
                        @php
                            $isCurrent = $sh->id === $activeSheet?->id;
                            $shRestricted = $sh->isRestrictedForRole($userRole);
                            $sheetLabel = mb_strtolower($sh->name . ' ' . ($sh->category ?? ''));
                            $tabPalette = match (true) {
                                str_contains($sheetLabel, 'analisa') => ['active' => '#0369a1', 'idle' => '#e0f2fe', 'text' => '#075985'],
                                str_contains($sheetLabel, 'cost') => ['active' => '#c2410c', 'idle' => '#ffedd5', 'text' => '#9a3412'],
                                str_contains($sheetLabel, 'margin') => ['active' => '#6d28d9', 'idle' => '#ede9fe', 'text' => '#5b21b6'],
                                str_contains($sheetLabel, 'pendukung') || str_contains($sheetLabel, 'supporting') => ['active' => '#047857', 'idle' => '#d1fae5', 'text' => '#065f46'],
                                str_contains($sheetLabel, 'rank') => ['active' => '#be123c', 'idle' => '#ffe4e6', 'text' => '#9f1239'],
                                default => ['active' => '#334155', 'idle' => '#e2e8f0', 'text' => '#334155'],
                            };
                            $tabStyle = $isCurrent
                                ? "background-color: {$tabPalette['active']}; color: #ffffff; border-color: {$tabPalette['active']};"
                                : ($shRestricted
                                    ? 'background-color: #e2e8f0; color: #94a3b8; border-color: #cbd5e1;'
                                    : "background-color: {$tabPalette['idle']}; color: {$tabPalette['text']}; border-color: {$tabPalette['active']};");
                        @endphp
                        <div class="relative group/tab flex items-center">
                            <a href="{{ route('group.workspace', [$group->id, $sh->id]) }}"
                               style="{{ $tabStyle }}"
                               class="px-3 py-1 rounded-t-lg border font-medium transition-all flex items-center gap-1.5 {{ $isCurrent ? 'shadow-2xs font-bold' : '' }}">
                                <span>{{ $sh->name }}</span>
                                @if($shRestricted)
                                    <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20" title="Akses Dibatasi (Leader Only)"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($activeSheet && !$requiresPasswordUnlock && !$requiresPasswordSetup)
            @livewire('spreadsheet-worksheet', ['sheet' => $activeSheet], key($activeSheet->id))
        @else
            <div class="flex-1 flex items-center justify-center p-8 text-center">
                <p class="text-slate-400 text-sm">Pilih lembar kerja di atas untuk mulai mencatat data spreadsheet.</p>
            </div>
        @endif
    </div>

    <template x-teleport="body">
    <div class="workspace-modal-layer">
    <!-- MODAL 1: Tambah Anggota Baru -->
    <div x-show="workspaceModal === 'modal-add-member'" 
         x-data="{
            searchQuery: '',
            selectedUserId: null,
            selectedUserName: '',
            searchResults: [],
            isSearching: false,
            searchUsers() {
                if (this.searchQuery.trim().length < 1) {
                    this.searchResults = [];
                    return;
                }
                this.isSearching = true;
                fetch('{{ route('groups.users.search', $group->id) }}?name=' + encodeURIComponent(this.searchQuery))
                    .then(res => res.json())
                    .then(data => {
                        this.searchResults = data.users || [];
                        this.isSearching = false;
                    })
                    .catch(() => { this.isSearching = false; });
            },
            selectUser(user) {
                if (user.is_member) return;
                this.selectedUserId = user.id;
                this.selectedUserName = user.name;
                this.searchQuery = user.name;
                this.searchResults = [];
            },
            resetSelection() {
                this.selectedUserId = null;
                this.selectedUserName = '';
                this.searchQuery = '';
                this.searchResults = [];
            }
         }"
         class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
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
                            <p class="text-xs text-slate-500">Pilih pengguna terdaftar ke workspace {{ $group->name }}</p>
                        </div>
                    </div>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="{{ route('groups.members.add', $group->id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="user_id" :value="selectedUserId" required>

                    <!-- Pencarian Berdasarkan Nama Pengguna -->
                    <div class="relative">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pengguna Terdaftar <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="text" 
                                   x-model="searchQuery" 
                                   @input.debounce.300ms="searchUsers()" 
                                   placeholder="Ketik nama anggota..." 
                                   autocomplete="off"
                                   required 
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 pl-9 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <template x-if="selectedUserId">
                                <button type="button" @click="resetSelection()" class="absolute right-3 top-2.5 text-xs text-slate-400 hover:text-rose-600 font-bold">✕</button>
                            </template>
                        </div>

                        <!-- Autocomplete dropdown list -->
                        <div x-show="searchResults.length > 0" 
                             @click.away="searchResults = []"
                             class="absolute left-0 right-0 mt-1 max-h-52 overflow-y-auto bg-white rounded-xl shadow-xl border border-slate-200 z-50 divide-y divide-slate-100" x-cloak>
                            <template x-for="u in searchResults" :key="u.id">
                                <div @click="selectUser(u)" 
                                     :class="u.is_member ? 'opacity-50 cursor-not-allowed bg-slate-50' : 'cursor-pointer hover:bg-sky-50'"
                                     class="p-2.5 flex items-center justify-between gap-2.5 transition-colors">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-7 h-7 rounded-full bg-slate-100 overflow-hidden shrink-0 flex items-center justify-center font-bold text-slate-600 text-xs">
                                            <template x-if="u.avatar_url">
                                                <img :src="u.avatar_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!u.avatar_url">
                                                <span x-text="u.name.substring(0, 2).toUpperCase()"></span>
                                            </template>
                                        </div>
                                        <div class="truncate">
                                            <p class="text-xs font-bold text-slate-800 truncate" x-text="u.name"></p>
                                            <p class="text-[10px] text-slate-400 truncate" x-text="'ID: #' + u.id + ' • ' + u.role_title"></p>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <template x-if="u.is_member">
                                            <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Sudah Terdaftar</span>
                                        </template>
                                        <template x-if="!u.is_member">
                                            <span class="text-[10px] font-semibold text-sky-600 bg-sky-100 px-2 py-0.5 rounded">Pilih</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <template x-if="selectedUserId">
                            <p class="text-[11px] text-emerald-600 font-medium mt-1">✓ Akun dipilih: <strong x-text="selectedUserName"></strong> (ID: #<span x-text="selectedUserId"></span>)</p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran dalam Workspace <span class="text-rose-500">*</span></label>
                        <select name="role" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white">
                            <option value="admin">Admin (Kelola Anggota & Dokumen)</option>
                            @if($userRole === 'leader')
                                <option value="leader">Leader (Pemimpin Tim & Pengelola Anggota)</option>
                            @endif
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="workspaceModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="!selectedUserId"
                                :class="!selectedUserId ? 'opacity-50 cursor-not-allowed bg-slate-300 text-slate-500' : 'bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-500/20'"
                                class="px-5 py-2 rounded-xl text-xs font-semibold transition-all">
                            Tambahkan Anggota
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Kelola Anggota & Peran (Leader Only) -->
    <div x-show="workspaceModal === 'modal-manage-members'"
         x-data="workspaceMembersManager({
            members: @js($managedMembers),
            currentUserId: {{ (int) Auth::id() }},
            currentUserRole: @js($userRole),
            groupId: {{ (int) $group->id }}
         })"
         class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-2xl w-full text-left shadow-2xl border border-slate-100 my-8 overflow-hidden flex flex-col">

                <!-- Header -->
                <div class="p-6 pb-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Kelola Anggota & Peran</h3>
                            <p class="text-xs text-slate-500">
                                @if($userRole === 'leader')
                                    Anda dapat mengubah peran semua anggota
                                @else
                                    Hanya Leader yang dapat mengubah peran
                                @endif
                            </p>
                        </div>
                    </div>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Toast notification -->
                <div x-show="toast.show"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'"
                     class="mx-6 mt-4 p-3 rounded-xl border text-xs font-medium flex items-center gap-2" x-cloak>
                    <svg x-show="toast.type === 'success'" class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="toast.type === 'error'" class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"></path></svg>
                    <span x-text="toast.message"></span>
                    <button @click="toast.show = false" class="ml-auto text-current opacity-60 hover:opacity-100">✕</button>
                </div>

                <!-- Processing overlay -->
                <div x-show="isProcessing" class="absolute inset-0 bg-white/70 z-10 flex items-center justify-center rounded-2xl" x-cloak>
                    <div class="flex items-center gap-2 text-sky-700 text-sm font-semibold">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Memproses...
                    </div>
                </div>

                <!-- Member list -->
                <div class="overflow-y-auto divide-y divide-slate-100 max-h-96 px-6 py-2">
                    <template x-if="members.length === 0">
                        <p class="py-8 text-center text-xs text-slate-400">Tidak ada anggota aktif dalam workspace ini.</p>
                    </template>

                    <template x-for="mem in members" :key="mem.id">
                        <div class="py-3 flex items-center justify-between gap-3">
                            <!-- Avatar & Info -->
                            <div class="flex items-center gap-3 min-w-0 flex-1 cursor-pointer hover:opacity-80 transition-opacity"
                                 @click="openUserProfile(mem.user_id)" :title="'Lihat profil ' + mem.user_name">
                                <div class="relative shrink-0">
                                    <img class="w-9 h-9 rounded-full object-cover ring-2 ring-white shadow-sm"
                                         :src="mem.user_avatar" :alt="mem.user_name">
                                    <!-- Role indicator dot -->
                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white text-[8px] flex items-center justify-center"
                                          :class="mem.role === 'leader' ? 'bg-amber-400' : 'bg-sky-400'"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <p class="text-xs font-bold text-slate-800 truncate hover:text-sky-600 underline decoration-slate-200" x-text="mem.user_name"></p>
                                        <template x-if="mem.is_founder">
                                            <span class="inline-flex items-center gap-0.5 text-[9px] font-extrabold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 shadow-xs">👑 Founder</span>
                                        </template>
                                    </div>
                                    <p class="text-[10px] text-slate-400 truncate" x-text="mem.user_title"></p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <!-- 1. Founder: permanently Leader, no role change dropdown, no remove button -->
                                <template x-if="mem.is_founder">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-200">👑 LEADER</span>
                                        <template x-if="mem.user_id === currentUserId">
                                            <span class="text-[10px] text-slate-400 font-medium bg-slate-50 px-2 py-0.5 rounded border border-slate-200">Anda</span>
                                        </template>
                                    </div>
                                </template>

                                <!-- 2. Non-founder member (view-only for non-leaders or when looking at oneself) -->
                                <template x-if="!mem.is_founder && (currentUserRole !== 'leader' || mem.user_id === currentUserId)">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide"
                                              :class="getRoleBadgeClass(mem.role)"
                                              x-text="mem.role"></span>
                                        <template x-if="mem.user_id === currentUserId">
                                            <span class="text-[10px] text-slate-400 font-medium bg-slate-50 px-2 py-0.5 rounded border border-slate-200">Anda</span>
                                        </template>
                                    </div>
                                </template>

                                <!-- 3. Non-founder member when current user IS leader and target is NOT oneself -->
                                <template x-if="!mem.is_founder && currentUserRole === 'leader' && mem.user_id !== currentUserId">
                                    <div class="flex items-center gap-1.5">
                                        <div class="relative">
                                            <select :value="mem.pending_role"
                                                    @change="onRoleSelectChange(mem.id, $event.target.value); $event.target.value = mem.pending_role"
                                                    :disabled="isProcessing"
                                                    class="text-xs border border-slate-200 rounded-lg pl-2.5 pr-7 py-1.5 bg-white text-slate-700 font-medium appearance-none cursor-pointer hover:border-sky-400 focus:ring-2 focus:ring-sky-400 focus:border-sky-400 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                                    :class="mem.role === 'leader' ? 'border-amber-300 bg-amber-50' : 'border-sky-300 bg-sky-50'">
                                                <option value="admin" :selected="mem.role === 'admin'">🛡️ Admin</option>
                                                <option value="leader" :selected="mem.role === 'leader'">👑 Leader</option>
                                            </select>
                                            <svg class="w-3 h-3 text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>

                                        <!-- Remove member button (non-founder) -->
                                        <button @click="onRemoveMember(mem.id)"
                                                :disabled="isProcessing"
                                                title="Keluarkan Anggota"
                                                class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors disabled:opacity-40">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer info -->
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between shrink-0">
                    <p class="text-[10px] text-slate-400">
                        <template x-if="currentUserRole === 'leader'">
                            <span>💡 Klik dropdown untuk ubah peran · Ikon 🗑️ keluarkan anggota</span>
                        </template>
                        <template x-if="currentUserRole !== 'leader'">
                            <span>Hanya Leader yang dapat mengubah peran anggota.</span>
                        </template>
                    </p>
                    <span class="text-[10px] font-semibold text-slate-500" x-text="members.length + ' anggota aktif'"></span>
                </div>
            </div>
        </div>

        <!-- Inline Confirm Modal -->
        <div x-show="confirmModal.show"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-[60] flex items-center justify-center px-4" x-cloak>
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="closeConfirm()"></div>
            <div x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden ring-1 ring-black/5">
                <div class="h-1" :class="confirmModal.isDanger ? 'bg-gradient-to-r from-rose-500 to-orange-400' : 'bg-gradient-to-r from-sky-500 to-cyan-400'"></div>
                <div class="p-5">
                    <h4 class="text-sm font-bold text-slate-900" x-text="confirmModal.title"></h4>
                    <p class="mt-2 text-xs leading-5 text-slate-600" x-html="confirmModal.body"></p>

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button @click="closeConfirm()"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                            Batal
                        </button>
                        <button @click="runConfirmedAction()"
                                :class="confirmModal.isDanger
                                    ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/30'
                                    : 'bg-sky-600 hover:bg-sky-500 shadow-sky-600/20'"
                                class="px-5 py-2 rounded-xl text-xs font-semibold text-white shadow-md transition-all">
                            Ya, Lanjutkan
                        </button>
                    </div>
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

                <form action="{{ route('groups.update', $group->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Workspace <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ $group->name }}" required 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>

                    <!-- Foto Profil Workspace -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Profil Workspace</label>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                                @if($group->avatar_url)
                                    <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="font-bold text-sky-600 text-xs">{{ strtoupper(substr($group->name, 0, 2)) }}</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" name="avatar" accept="image/png,image/jpeg,image/jpg,image/webp" 
                                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer">
                                <p class="text-[10px] text-slate-400 mt-1">Format JPG, PNG, WEBP. Maksimal 2MB.</p>
                            </div>
                        </div>
                    </div>

                    @if($userRole === 'leader')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti Password Workspace</label>
                        <input type="password" name="password" minlength="4" placeholder="Kosongkan jika tidak ingin mengubah password..." 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <input type="password" name="current_password" placeholder="Password saat ini (wajib untuk mengganti)" 
                               class="w-full mt-2 text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Status saat ini: <strong class="text-emerald-600">{{ $group->hasPassword() ? 'Password Terpasang ✓' : 'Password Belum Diatur' }}</strong></p>
                        <p class="text-[11px] text-slate-400">Hanya leader yang dapat melihat dan mengubah password.</p>
                    </div>
                    @endif

                    @if(in_array($userRole, ['leader', 'admin']))
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Backup Data Spreadsheet</label>
                            <p class="text-[11px] text-slate-400">Unduh seluruh lembar kerja dalam format file Excel (.xlsx)</p>
                        </div>
                        <button type="button" 
                                @click="workspaceModal = 'modal-backup-workspace'"
                                class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors flex items-center gap-1.5 shadow-2xs">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Backup Seluruh Spreadsheet
                        </button>
                    </div>
                    @endif

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        @if($userRole === 'leader')
                            <button type="button" 
                                    @click="workspaceModal = 'modal-delete-workspace'"
                                    class="px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                                Hapus Workspace
                            </button>
                        @else
                            <div></div>
                        @endif
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

    @if($userRole === 'leader')
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
                            <p class="mt-2 text-sm leading-6 text-slate-600">Semua lembar kerja, data sel, dan riwayat yang ada di <strong class="font-semibold text-slate-900">{{ $group->name }}</strong> akan dihapus dan tidak dapat dipulihkan.</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs leading-5 text-rose-800">
                        Pastikan seluruh anggota sudah menyimpan data yang masih diperlukan sebelum melanjutkan.
                    </div>

                    <form action="{{ route('groups.destroy', $group->id) }}" method="POST" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="workspaceModal = 'modal-group-settings'" class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm shadow-rose-600/30 transition hover:bg-rose-700">Ya, hapus workspace</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

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

                <form action="{{ route('groups.sheets.add', $group->id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
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

                <form :action="'{{ url('sheets') }}/' + renameSheetId + '/rename'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
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

    @if($requiresPasswordSetup)
        <!-- Password workspace lama harus disetel oleh leader sebelum digunakan. -->
        <div x-init="workspaceModal = 'modal-password-required'"></div>
        <div x-show="workspaceModal === 'modal-password-required'" x-transition.opacity class="fixed inset-0 z-[60] overflow-y-auto" x-cloak role="dialog" aria-modal="true" aria-labelledby="password-setup-title" @keydown.escape.prevent>
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-hidden="true"></div>
                <div x-transition.scale.origin.center x-ref="passwordSetupDialog" class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100">
                    <h3 id="password-setup-title" class="text-base font-bold text-slate-900">Tambahkan Password Workspace</h3>
                    <p class="text-xs text-slate-500 mt-1">Workspace ini belum memiliki password. Buat password untuk melanjutkan.</p>
                    <form action="{{ route('groups.update', $group->id) }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $group->name }}">
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
    @endif

    @if($requiresPasswordUnlock)
        <div x-init="workspaceModal = 'modal-password-unlock'"></div>
        <div x-show="workspaceModal === 'modal-password-unlock'" x-transition.opacity class="fixed inset-0 z-[60] overflow-y-auto" x-cloak role="dialog" aria-modal="true" aria-labelledby="password-unlock-title" @keydown.escape.prevent @wheel.prevent @touchmove.prevent>
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-hidden="true"></div>
                <div x-transition.scale.origin.center x-ref="passwordUnlockDialog" class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100">
                    <h3 id="password-unlock-title" class="text-base font-bold text-slate-900">{{ $userRole === 'admin' ? 'Verifikasi Password Akun' : 'Masukkan Password Workspace' }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $userRole === 'admin' ? 'Admin wajib memasukkan password akun untuk membuka setiap lembar kerja.' : 'Password diperlukan setiap kali Anda masuk kembali ke workspace.' }}</p>
                    <form action="{{ route('groups.unlock', $group->id) }}" method="POST" @submit.prevent="unlockWorkspace($event)" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="sheet_id" :value="unlockSheetId">
                        <input x-ref="unlockPassword" x-init="if ({{ $requiresPasswordUnlock ? 'true' : 'false' }}) $nextTick(() => $el.focus())" type="password" name="password" required autocomplete="off" autofocus placeholder="{{ $userRole === 'admin' ? 'Password akun Anda' : 'Password workspace' }}"
                            class="w-full text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <p x-show="workspacePasswordError" x-text="workspacePasswordError" class="text-xs text-rose-600" role="alert"></p>
                        <button type="submit" class="w-full px-5 py-2.5 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white">Masuk ke Workspace</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 6: Akses Dibatasi Berdasarkan Peran -->
    <div x-show="workspaceModal === 'modal-access-restricted'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Akses Lembar Dibatasi</h3>
                        <p class="text-xs text-slate-600 mt-1">
                            Lembar <strong>{{ $activeSheet?->name }}</strong> dilindungi kebijakan hak akses dan hanya dapat diakses serta diedit oleh <strong>Leader</strong>.
                        </p>
                    </div>
                </div>

                <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 text-xs space-y-2 text-slate-600">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Peran Anda saat ini:</span>
                        <strong class="font-mono text-slate-800 uppercase px-2 py-0.5 rounded bg-slate-200/60">{{ $userRole }}</strong>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Lembar yang dapat diakses:</span>
                        <span class="font-semibold text-emerald-600">Analisa, Cost</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Lembar dibatasi (Leader):</span>
                        <span class="font-semibold text-amber-600">Margin, Pendukung, Rank</span>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2.5">
                    <button type="button" @click="workspaceModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                        Tutup
                    </button>
                    @php
                        $fallbackSheet = $sheets->firstWhere(fn ($s) => !$s->isRestrictedForRole($userRole));
                    @endphp
                    @if($fallbackSheet)
                        <a href="{{ route('group.workspace', [$group->id, $fallbackSheet->id]) }}" 
                           class="px-4 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white shadow-xs transition-colors">
                            Buka Lembar {{ $fallbackSheet->name }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 7: Riwayat Aktivitas Workspace -->
    <div x-show="workspaceModal === 'modal-activity-history'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="workspaceModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-2xl w-full text-left shadow-2xl border border-slate-100 my-8 overflow-hidden flex flex-col">
                <div class="p-5 pb-3 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Riwayat Aktivitas Workspace</h3>
                            <p class="text-xs text-slate-500">Catatan pengeditan sel, formula, anggota, dan perubahan workspace {{ $group->name }}</p>
                        </div>
                    </div>
                    <button @click="workspaceModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-0 flex-1 min-h-0">
                    @livewire('workspace-activity-history', ['groupId' => $group->id], key('history-' . $group->id))
                </div>
            </div>
        </div>
    </div>

    @if(in_array($userRole, ['leader', 'admin']))
    <!-- MODAL 8: Backup Seluruh Spreadsheet ke XLSX -->
    <div x-show="workspaceModal === 'modal-backup-workspace'" 
         x-data="{
            isExporting: false,
            startBackup() {
                this.isExporting = true;
                const link = document.createElement('a');
                link.href = '{{ route('groups.backup', $group->id) }}';
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                setTimeout(() => {
                    this.isExporting = false;
                    workspaceModal = null;
                }, 2000);
            }
         }"
         x-transition.opacity 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         x-cloak 
         role="dialog" 
         aria-modal="true"
         aria-labelledby="backup-workspace-title"
         @keydown.escape.window="!isExporting && (workspaceModal = null)">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="!isExporting && (workspaceModal = null)"></div>
            <div x-transition.scale.origin.center class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h3 id="backup-workspace-title" class="text-base font-bold text-slate-800">Backup Seluruh Spreadsheet</h3>
                            <p class="text-xs text-slate-500">Ekspor workbook lengkap ke file Microsoft Excel (.xlsx)</p>
                        </div>
                    </div>
                    <button @click="!isExporting && (workspaceModal = null)" :disabled="isExporting" class="text-slate-400 hover:text-slate-600 disabled:opacity-40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3.5 text-xs text-slate-600">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Nama Workspace:</span>
                            <strong class="text-slate-900 font-semibold">{{ $group->name }}</strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Jumlah Worksheet:</span>
                            <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                {{ $sheets->count() }} Lembar Kerja
                            </span>
                        </div>
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-slate-500 shrink-0">Daftar Lembar:</span>
                            <span class="text-right text-slate-700 font-medium truncate" title="{{ $sheets->pluck('name')->join(', ') }}">
                                {{ $sheets->pluck('name')->join(', ') }}
                            </span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        File backup akan berisi <strong>seluruh worksheet</strong> dalam satu workbook Excel (.xlsx) dengan struktur tabel, formula, warna header/sel, dan format data yang tetap dipertahankan.
                    </p>
                </div>

                <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="workspaceModal = null" :disabled="isExporting" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-40 transition-colors">
                        Batal
                    </button>
                    <button type="button" 
                            @click="startBackup()" 
                            :disabled="isExporting"
                            class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!isExporting">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Unduh Backup (.xlsx)
                            </span>
                        </template>
                        <template x-if="isExporting">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Menyiapkan File...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    </div>
    </template>
</div>

<script>
function workspaceMembersManager(config) {
    return {
        members: config.members || [],
        currentUserId: config.currentUserId,
        currentUserRole: config.currentUserRole,
        groupId: config.groupId,
        isProcessing: false,
        toast: { show: false, message: '', type: 'success' },
        confirmModal: { show: false, title: '', body: '', action: null, isDestructive: false, isDanger: false },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 5000);
        },

        openConfirm(title, body, action, isDestructive = false) {
            this.confirmModal = { show: true, title, body, action, isDestructive, isDanger: isDestructive };
        },

        closeConfirm() {
            this.confirmModal = { show: false, title: '', body: '', action: null, isDestructive: false, isDanger: false };
        },

        runConfirmedAction() {
            if (this.confirmModal.action) this.confirmModal.action();
            this.closeConfirm();
        },

        getMemberById(memberId) {
            return this.members.find(m => m.id === memberId);
        },

        getRoleBadgeClass(role) {
            if (role === 'leader') return 'bg-amber-100 text-amber-800 border border-amber-200';
            return 'bg-sky-100 text-sky-800 border border-sky-200';
        },

        getRoleLabel(role) {
            if (role === 'leader') return '👑 Leader';
            return '🛡️ Admin';
        },

        onRoleSelectChange(memberId, newRole) {
            const member = this.getMemberById(memberId);
            if (!member || member.role === newRole) return;

            const memberName = member.user_name;
            const oldRoleLabel = this.getRoleLabel(member.role);
            const newRoleLabel = this.getRoleLabel(newRole);
            const isPromotion  = newRole === 'leader';

            let title = isPromotion ? '👑 Angkat Menjadi Leader' : '🛡️ Ubah Menjadi Admin';
            let body  = isPromotion
                ? `Angkat <strong>${memberName}</strong> menjadi <strong>Leader</strong>?<br><br>` +
                  `Anggota akan memiliki hak pengelolaan anggota workspace dan hak administratif penuh.`
                : `Ubah peran <strong>${memberName}</strong> dari <strong>${oldRoleLabel}</strong> menjadi <strong>${newRoleLabel}</strong>?<br><br>` +
                  `Anggota tidak akan lagi dapat mengelola peran anggota workspace.`;

            this.openConfirm(title, body, () => {
                this.submitRoleChange(memberId, newRole);
            }, false);
        },

        async submitRoleChange(memberId, newRole) {
            const member = this.getMemberById(memberId);
            if (!member) return;
            this.isProcessing = true;

            try {
                const url    = `/groups/${this.groupId}/members/${memberId}`;
                const csrfEl = document.querySelector('meta[name="csrf-token"]');
                const csrf   = csrfEl ? csrfEl.content : '';

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-HTTP-Method-Override': 'PUT',
                    },
                    body: JSON.stringify({ role: newRole }),
                });

                const data = await res.json();

                if (res.ok) {
                    member.role = newRole;
                    member.pending_role = newRole;
                    this.showToast(data.message || 'Peran berhasil diperbarui.', 'success');
                } else {
                    member.pending_role = member.role;
                    this.showToast(data.message || 'Gagal mengubah peran.', 'error');
                }
            } catch (err) {
                member.pending_role = member.role;
                this.showToast('Terjadi kesalahan koneksi. Coba lagi.', 'error');
            } finally {
                this.isProcessing = false;
            }
        },

        onRemoveMember(memberId) {
            const member = this.getMemberById(memberId);
            if (!member) return;

            this.openConfirm(
                '⛔ Keluarkan Anggota',
                `Keluarkan <strong>${member.user_name}</strong> dari workspace ini secara permanen?`,
                () => { this.submitRemoveMember(memberId); },
                true
            );
        },

        async submitRemoveMember(memberId) {
            const member = this.getMemberById(memberId);
            if (!member) return;
            this.isProcessing = true;

            try {
                const url    = `/groups/${this.groupId}/members/${memberId}`;
                const csrfEl = document.querySelector('meta[name="csrf-token"]');
                const csrf   = csrfEl ? csrfEl.content : '';

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-HTTP-Method-Override': 'DELETE',
                    },
                    body: JSON.stringify({}),
                });

                const data = await res.json();

                if (res.ok) {
                    this.members = this.members.filter(m => m.id !== memberId);
                    this.showToast(data.message || 'Anggota berhasil dikeluarkan.', 'success');
                } else {
                    this.showToast(data.message || 'Gagal mengeluarkan anggota.', 'error');
                }
            } catch (err) {
                this.showToast('Terjadi kesalahan koneksi. Coba lagi.', 'error');
            } finally {
                this.isProcessing = false;
            }
        }
    };
}
</script>
@endsection
