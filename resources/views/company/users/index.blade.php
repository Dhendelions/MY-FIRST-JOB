@extends('layouts.app', ['title' => 'Manajemen Pengguna — PT Dadhen Mandiri'])

@section('content')
<div class="flex-1 flex flex-col min-h-0 min-w-0 bg-slate-50"
     x-data="{
        activeUserModal: null,
        selectedUser: null,
        userForm: {
            id: null,
            name: '',
            email: '',
            role: 'admin',
            whatsapp: '',
            password: '',
            password_confirmation: ''
        },
        openAddUser() {
            this.userForm = { id: null, name: '', email: '', role: 'admin', whatsapp: '', password: '', password_confirmation: '' };
            this.activeUserModal = 'modal-add-user';
        },
        openEditUser(user) {
            this.selectedUser = user;
            this.userForm = { id: user.id, name: user.name, email: user.email, whatsapp: user.whatsapp || '' };
            this.activeUserModal = 'modal-edit-user';
        },
        openChangeRole(user) {
            this.selectedUser = user;
            this.userForm.role = user.role;
            this.activeUserModal = 'modal-change-role';
        },
        openResetPassword(user) {
            this.selectedUser = user;
            this.userForm.password = '';
            this.userForm.password_confirmation = '';
            this.activeUserModal = 'modal-reset-password';
        }
     }">

    <!-- HEADER SECTION -->
    <div class="bg-white border-b border-slate-200 px-4 sm:px-6 py-5 shrink-0 shadow-2xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-sky-600 mb-1">
                    <span>PT Dadhen Mandiri</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Manajemen Perusahaan</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Manajemen Pengguna
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola akun seluruh personil, hak akses Owner dan Admin, status keaktifan, dan kredensial keamanan.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <button @click="openAddUser()"
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white text-xs sm:text-sm font-bold shadow-md shadow-sky-600/20 flex items-center gap-2 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    + Tambah Pengguna
                </button>
            </div>
        </div>

        <!-- KPI SUMMARY STATS -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-5">
            <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Akun</p>
                <p class="text-lg font-black text-slate-900 mt-0.5">{{ $stats['total'] }}</p>
            </div>
            <div class="p-3.5 bg-amber-50/60 border border-amber-200/70 rounded-xl">
                <p class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider">Owner</p>
                <p class="text-lg font-black text-amber-900 mt-0.5">{{ $stats['owners'] }}</p>
            </div>
            <div class="p-3.5 bg-sky-50/60 border border-sky-200/70 rounded-xl">
                <p class="text-[11px] font-semibold text-sky-700 uppercase tracking-wider">Admin</p>
                <p class="text-lg font-black text-sky-900 mt-0.5">{{ $stats['admins'] }}</p>
            </div>
            <div class="p-3.5 bg-emerald-50/60 border border-emerald-200/70 rounded-xl">
                <p class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Aktif</p>
                <p class="text-lg font-black text-emerald-900 mt-0.5">{{ $stats['active'] }}</p>
            </div>
            <div class="p-3.5 bg-rose-50/60 border border-rose-200/70 rounded-xl">
                <p class="text-[11px] font-semibold text-rose-700 uppercase tracking-wider">Nonaktif</p>
                <p class="text-lg font-black text-rose-900 mt-0.5">{{ $stats['inactive'] }}</p>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">

        <!-- FILTER & SEARCH BAR -->
        <form action="{{ route('company.users.index') }}" method="GET" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, whatsapp..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>
            </div>

            <div class="w-36">
                <select name="role" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    <option value="">Semua Peran</option>
                    <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="w-36">
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'role', 'status']))
                <a href="{{ route('company.users.index') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 transition-colors">
                    Reset
                </a>
            @endif
        </form>

        <!-- USERS TABLE -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold text-[11px] uppercase tracking-wider">
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Kontak</th>
                            <th class="py-3 px-4">Peran</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Terdaftar</th>
                            <th class="py-3 px-4">Terakhir Aktif</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $u)
                            @php
                                $isOwner = $u->isOwner();
                                $isActive = $u->isActive();
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl {{ $isOwner ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-sky-100 text-sky-800 border border-sky-200' }} flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 leading-tight">{{ $u->name }}</p>
                                            <p class="text-[11px] text-slate-400">ID: #{{ $u->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <p class="font-mono text-xs">{{ $u->email }}</p>
                                    @if($u->whatsapp)
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $u->whatsapp }}</p>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($isOwner)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            OWNER
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
                                            ADMIN
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($isActive)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-xs">
                                    {{ $u->created_at ? $u->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-xs">
                                    {{ $u->last_active_at ? $u->last_active_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') : 'Belum pernah' }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEditUser({{ json_encode($u) }})"
                                                title="Edit Informasi"
                                                class="p-1.5 text-slate-400 hover:text-sky-600 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>

                                        <button @click="openChangeRole({{ json_encode($u) }})"
                                                title="Ubah Peran"
                                                class="p-1.5 text-slate-400 hover:text-amber-600 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        </button>

                                        <button @click="openResetPassword({{ json_encode($u) }})"
                                                title="Reset Password"
                                                class="p-1.5 text-slate-400 hover:text-purple-600 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                        </button>

                                        @if(Auth::id() !== $u->id)
                                            <form action="{{ route('company.users.toggle-status', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ $isActive ? 'Nonaktifkan akun pengguna ini?' : 'Aktifkan kembali akun pengguna ini?' }}')">
                                                @csrf
                                                <button type="submit"
                                                        title="{{ $isActive ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}"
                                                        class="p-1.5 text-slate-400 {{ $isActive ? 'hover:text-rose-600' : 'hover:text-emerald-600' }} rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                                                    @if($isActive)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400 text-xs">
                                    Tidak ada pengguna ditemukan dengan kriteria pencarian ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($users->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL 1: TAMBAH PENGGUNA -->
    <div x-show="activeUserModal === 'modal-add-user'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeUserModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">+ Tambah Pengguna Baru</h3>
                    <button @click="activeUserModal = null" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form action="{{ route('company.users.store') }}" method="POST" class="mt-4 space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="userForm.name" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" x-model="userForm.email" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Akses <span class="text-rose-500">*</span></label>
                        <select name="role" x-model="userForm.role" class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white">
                            <option value="admin">Admin (Akses Analisa & Cost)</option>
                            <option value="owner">Owner (Akses Penuh Seluruh Modul & Keuangan)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp (Opsional)</label>
                        <input type="text" name="whatsapp" x-model="userForm.whatsapp" placeholder="08..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Password Sementara <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="activeUserModal = null" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white rounded-xl shadow-md shadow-sky-600/20">Buat Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: EDIT PENGGUNA -->
    <div x-show="activeUserModal === 'modal-edit-user'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeUserModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Edit Informasi Pengguna</h3>
                    <button @click="activeUserModal = null" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form :action="'/company/users/' + (selectedUser ? selectedUser.id : '')" method="POST" class="mt-4 space-y-3.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="userForm.name" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" x-model="userForm.email" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp</label>
                        <input type="text" name="whatsapp" x-model="userForm.whatsapp" class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="activeUserModal = null" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white rounded-xl shadow-md">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: UBAH PERAN -->
    <div x-show="activeUserModal === 'modal-change-role'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeUserModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Ubah Peran Akses</h3>
                    <button @click="activeUserModal = null" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form :action="'/company/users/' + (selectedUser ? selectedUser.id : '') + '/role'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <p class="text-xs text-slate-600">Ubah peran untuk: <strong class="text-slate-900" x-text="selectedUser ? selectedUser.name : ''"></strong></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Peran Baru <span class="text-rose-500">*</span></label>
                        <select name="role" x-model="userForm.role" class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none bg-white">
                            <option value="admin">Admin (Akses Analisa & Cost)</option>
                            <option value="owner">Owner (Akses Penuh Seluruh Modul & Keuangan)</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Perhatian: Perusahaan wajib memiliki minimal satu akun Owner yang aktif.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="activeUserModal = null" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-white rounded-xl shadow-md">Perbarui Peran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 4: RESET PASSWORD -->
    <div x-show="activeUserModal === 'modal-reset-password'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeUserModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Reset Password Pengguna</h3>
                    <button @click="activeUserModal = null" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form :action="'/company/users/' + (selectedUser ? selectedUser.id : '') + '/reset-password'" method="POST" class="mt-4 space-y-3.5">
                    @csrf
                    <div>
                        <p class="text-xs text-slate-600">Reset password untuk: <strong class="text-slate-900" x-text="selectedUser ? selectedUser.name : ''"></strong></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Baru <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation" required minlength="8" placeholder="Ulangi password baru..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="activeUserModal = null" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold bg-purple-600 hover:bg-purple-500 text-white rounded-xl shadow-md">Simpan Password Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

