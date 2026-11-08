@extends('layouts.app', ['title' => 'Workspace ' . $operationalYear->year . ' — Dadhen Mandiri'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="{ showPasswordModal: false }">

    <!-- 1. Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" class="hover:text-sky-600 transition-colors font-medium">Dashboard</a>
        <span>/</span>
        <span class="font-bold text-slate-800">Workspace {{ $operationalYear->year }}</span>
    </nav>

    @if(!$isUnlocked)
        <!-- LOCKED STATE: Centered Password Form for Locked Admins -->
        <div class="max-w-md mx-auto my-12 bg-white rounded-2xl border border-slate-200 shadow-xl p-6 sm:p-8 space-y-6 text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <div>
                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] uppercase tracking-wider">
                    Autentikasi Terpusat
                </span>
                <h1 class="text-xl font-extrabold text-slate-900 mt-2">
                    Masukkan Password Workspace {{ $operationalYear->year }}
                </h1>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Workspace tahun operasional {{ $operationalYear->year }} dilindungi oleh password. Masukkan password untuk mengakses 12 lembar kerja bulanan dan spreadsheet.
                </p>
            </div>

            @if(session('error'))
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 text-left font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 text-left space-y-1">
                    @foreach($errors->all() as $err)
                        <p>&bull; {{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('company.year.unlock', $operationalYear->year) }}" method="POST" class="space-y-4 text-left">
                @csrf
                <div>
                    <label for="year_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Password Workspace
                    </label>
                    <input type="password" 
                           name="password" 
                           id="year_password" 
                           required 
                           autofocus
                           autocomplete="current-password"
                           placeholder="Masukkan password workspace" 
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm text-slate-900 outline-none transition-all">
                </div>

                <div class="flex flex-col gap-2 pt-2">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                        <span>Buka Workspace</span>
                    </button>
                    <a href="{{ route('dashboard') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs text-center transition-colors">
                        Kembali ke Dashboard
                    </a>
                </div>
            </form>
        </div>
    @else
        <!-- UNLOCKED / OWNER STATE: Full Year Workspace View -->
        
        <!-- Header Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0A192F] via-[#112240] to-[#1D3557] p-6 sm:p-8 text-white shadow-xl border border-slate-800">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 rounded-full bg-sky-500/20 border border-sky-400/30 text-xs font-bold text-sky-300">
                            Workspace Tahunan
                        </span>
                        @if($operationalYear->is_archived)
                            <span class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/30 text-xs font-bold text-amber-300">
                                Diarsipkan (Read-Only)
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-xs font-bold text-emerald-300">
                                Tahun Aktif
                            </span>
                        @endif

                        @if($operationalYear->hasPassword())
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-800/80 border border-slate-700 text-[11px] font-semibold text-slate-300 flex items-center gap-1">
                                <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Password Protected
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-800/80 border border-slate-700 text-[11px] font-semibold text-slate-300 flex items-center gap-1">
                                <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                Bebas Password
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center font-black text-2xl text-sky-300 shadow-inner">
                            {{ $operationalYear->year }}
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                Workspace {{ $operationalYear->year }}
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-300 mt-0.5">
                                12 Lembar Kerja Bulanan &bull; 60 Dokumen Spreadsheet Terintegrasi
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl bg-slate-800/90 hover:bg-slate-700 border border-slate-700 text-slate-200 text-xs font-bold transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Dashboard</span>
                    </a>

                    @if(Auth::user()->isOwner())
                        <a href="{{ route('company.analytics', ['year' => $operationalYear->year]) }}" class="px-3.5 py-2 rounded-xl bg-sky-600/90 hover:bg-sky-500 text-white text-xs font-bold transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            <span>Analytics</span>
                        </a>

                        <button type="button" 
                                @click="showPasswordModal = true"
                                class="px-3.5 py-2 rounded-xl bg-amber-600/90 hover:bg-amber-500 text-white text-xs font-bold transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            <span>Atur Password</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-medium flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- 12 Months Grid -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Bulan Operasional</h2>
                    <p class="text-xs text-slate-500">Pilih bulan operasional untuk membuka lembar kerja spreadsheet terpusat.</p>
                </div>
                <div class="text-xs font-semibold text-slate-500">
                    @if(Auth::user()->isOwner())
                        <span class="text-sky-600">Hak Akses: Owner (Semua Sheet)</span>
                    @else
                        <span class="text-emerald-600">Hak Akses: Admin (Analisa &amp; Cost)</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pt-2">
                @foreach($operationalYear->monthlyWorkspaces as $mw)
                    <div class="rounded-xl border border-slate-200/90 hover:border-sky-400 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 ease-out bg-white p-4 flex flex-col justify-between space-y-3 group">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 group-hover:bg-sky-50 text-slate-800 group-hover:text-sky-600 font-black text-sm flex items-center justify-center font-mono transition-colors">
                                    {{ sprintf('%02d', $mw->month) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 text-sm group-hover:text-sky-600 transition-colors">
                                        {{ $mw->name }}
                                    </h3>
                                    <p class="text-[11px] text-slate-400 font-mono">{{ $operationalYear->year }}</p>
                                </div>
                            </div>
                            @if($operationalYear->is_archived)
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">🔒 Archived</span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            @endif
                        </div>

                        <!-- Available Sheets list -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex flex-wrap gap-1 text-[10px]">
                                @if(!$operationalYear->is_archived || Auth::user()->isOwner())
                                    <a href="{{ route('company.workspace', [$operationalYear->year, $mw->month, 'Analisa']) }}"
                                       class="px-2 py-0.5 rounded bg-sky-50 hover:bg-sky-100 text-sky-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Analisa
                                    </a>
                                    <a href="{{ route('company.workspace', [$operationalYear->year, $mw->month, 'Cost']) }}"
                                       class="px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Cost
                                    </a>
                                @else
                                    <span class="rounded bg-amber-50 px-2 py-0.5 text-amber-700" aria-disabled="true">🔒 Archived</span>
                                @endif
                                @if(Auth::user()->isOwner())
                                    <a href="{{ route('company.workspace', [$operationalYear->year, $mw->month, 'Margin']) }}" 
                                       class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Margin
                                    </a>
                                    <a href="{{ route('company.workspace', [$operationalYear->year, $mw->month, 'Pendukung']) }}" 
                                       class="px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Pendukung
                                    </a>
                                    <a href="{{ route('company.workspace', [$operationalYear->year, $mw->month, 'Rank']) }}" 
                                       class="px-2 py-0.5 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold transition-all duration-150 hover:-translate-y-0.5">
                                        Rank
                                    </a>
                                @else
                                    <span class="px-1.5 py-0.5 rounded bg-slate-50 text-slate-400 text-[9px] font-medium" title="Dibatasi khusus Owner">
                                        +3 Sheet Owner
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if(!$operationalYear->is_archived || Auth::user()->isOwner())
                            <a href="{{ route('company.workspace', [$operationalYear->year, $mw->month]) }}"
                               class="w-full mt-2 px-3 py-2 rounded-xl bg-slate-50 hover:bg-sky-600 group-hover:bg-sky-600 text-slate-700 hover:text-white group-hover:text-white text-xs font-bold text-center transition-all duration-200 flex items-center justify-center gap-1.5 hover:-translate-y-0.5 active:translate-y-0">
                                <span>Buka Bulan {{ $mw->name }}</span>
                                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @else
                            <span aria-disabled="true"
                                  class="w-full mt-2 px-3 py-2 rounded-xl bg-slate-100 text-slate-500 text-xs font-bold text-center cursor-not-allowed">
                                🔒 Archived — Hanya Owner
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if(Auth::user()->isOwner())
            <!-- Modal Atur Password Workspace (Owner Only) -->
            <div x-show="showPasswordModal" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                 @keydown.escape.window="showPasswordModal = false">
                <div class="bg-white w-full max-w-md rounded-2xl border border-slate-200 shadow-2xl p-6 space-y-5"
                     @click.outside="showPasswordModal = false">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm">
                                🔑
                            </div>
                            <h3 class="text-base font-bold text-slate-900">Pengaturan Password Workspace {{ $operationalYear->year }}</h3>
                        </div>
                        <button type="button" @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                    </div>

                    <form action="{{ route('company.year.password', $operationalYear->year) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 leading-relaxed">
                            <p class="font-bold">Informasi Keamanan:</p>
                            <p class="mt-0.5">
                                Mengatur atau memperbarui password akan segera merevokasi sesi seluruh Admin untuk tahun {{ $operationalYear->year }}. Kosongkan kolom jika ingin menghapus proteksi password.
                            </p>
                        </div>

                        <div>
                            <label for="modal_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Password Baru (Min. 4 Karakter)
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="modal_password" 
                                   placeholder="Kosongkan untuk menghapus password"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm text-slate-900 outline-none transition-all">
                        </div>

                        <div>
                            <label for="modal_password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Konfirmasi Password Baru
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="modal_password_confirmation" 
                                   placeholder="Ulangi password baru"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm text-slate-900 outline-none transition-all">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" 
                                    @click="showPasswordModal = false" 
                                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-xs transition-colors">
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif

</div>
@endsection
