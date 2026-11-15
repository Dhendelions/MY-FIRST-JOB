@extends('layouts.app', ['title' => 'Dashboard Ringkasan Operasional — Dadhen Mandiri'])

@section('content')
<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto w-full">

    <!-- 1. WELCOME BANNER (Clean Light Executive Card) -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 sm:p-8 text-[#263445] shadow-xs border border-[#E5EAF0] reveal-on-scroll card-clean">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#142B4A] via-[#526780] to-sky-400"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#F5F7FA] border border-[#E5EAF0] text-xs font-semibold text-[#142B4A]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    @if(Auth::user()->isOwner())
                        Executive Portal &bull; Owner PT Dadhen Mandiri
                    @else
                        Workspace Kolaboratif &bull; Admin
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#263445]">
                    Halo, {{ $user->name }}! 👋
                </h1>
                <p class="text-xs sm:text-sm text-[#7B8794] max-w-2xl leading-relaxed">
                    Selamat datang di dasbor kolaborasi <span class="text-[#142B4A] font-bold">Dadhen Mandiri</span>. Kelola lembar spreadsheet dokumen, formula, anggaran, dan perencanaan proyek bersama seluruh anggota tim Anda.
                </p>
            </div>

            <!-- TOMBOL AKSI CEPAT UTAMA -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
                @if(Auth::user()->isOwner())
                    <a href="{{ route('company.analytics') }}" 
                       class="px-3.5 py-2 rounded-xl btn-secondary-gradient text-xs font-bold flex items-center gap-1.5 shadow-xs">
                        <svg class="w-4 h-4 text-[#315E91]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Analytics</span>
                    </a>
                    <a href="{{ route('company.users.index') }}" 
                       class="px-3.5 py-2 rounded-xl btn-secondary-gradient text-xs font-bold flex items-center gap-1.5 shadow-xs">
                        <svg class="w-4 h-4 text-[#315E91]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Pengguna</span>
                    </a>
                @endif
                <button @click="activeModal = 'modal-friend'" 
                        class="px-4 py-2.5 rounded-xl btn-primary-gradient text-xs sm:text-sm font-bold flex items-center gap-2 shadow-xs">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>Hubungkan Rekan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. KARTU STATISTIK KPI REAL-TIME DARI DATABASE -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- KPI 1: Total Workspace -->
        <div class="group relative bg-white rounded-2xl p-5 border border-[#E5EAF0] shadow-xs hover:border-[#142B4A]/30 hover:shadow-card-hover transition-all duration-200 ease-out hover:-translate-y-1 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#7B8794] uppercase tracking-wider">Workspace Tim</span>
                <div class="w-9 h-9 rounded-xl bg-[#F5F7FA] text-[#142B4A] border border-[#E5EAF0] flex items-center justify-center font-bold group-hover:scale-105 group-hover:bg-[#142B4A] group-hover:text-white transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-[#263445] font-mono">{{ $totalWorkspaces }}</span>
                <span class="text-xs text-[#142B4A] font-semibold">Workspace Aktif</span>
            </div>
            <p class="text-[11px] text-[#7B8794] mt-1">Ruang kerja kolaboratif Anda</p>
        </div>

        <!-- KPI 2: Lembar Spreadsheet Aktif -->
        <div class="group relative bg-white rounded-2xl p-5 border border-[#E5EAF0] shadow-xs hover:border-[#142B4A]/30 hover:shadow-card-hover transition-all duration-200 ease-out hover:-translate-y-1 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#7B8794] uppercase tracking-wider">Lembar Spreadsheet</span>
                <div class="w-9 h-9 rounded-xl bg-[#DCFCE7] text-[#15803d] border border-emerald-200 flex items-center justify-center font-bold group-hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-[#263445] font-mono">{{ $totalSheetsCount }}</span>
                <span class="text-xs text-[#15803d] font-semibold">Lembar Terbuka</span>
            </div>
            <p class="text-[11px] text-[#7B8794] mt-1">Multi-sheet dokumen tim</p>
        </div>

        <!-- KPI 3: Total Sel Data Tercatat -->
        <div class="group relative bg-white rounded-2xl p-5 border border-[#E5EAF0] shadow-xs hover:border-[#142B4A]/30 hover:shadow-card-hover transition-all duration-200 ease-out hover:-translate-y-1 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#7B8794] uppercase tracking-wider">Sel Data Tercatat</span>
                <div class="w-9 h-9 rounded-xl bg-[#FEF3C7] text-[#b45309] border border-amber-200 flex items-center justify-center font-bold group-hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-[#263445] font-mono">{{ number_format($totalRecordedCells, 0, ',', '.') }}</span>
                <span class="text-xs text-[#b45309] font-semibold">Sel Terisi</span>
            </div>
            <p class="text-[11px] text-[#7B8794] mt-1">Data tersimpan di database</p>
        </div>

        <!-- KPI 4: Jumlah Rekan Terhubung -->
        <div class="group relative bg-white rounded-2xl p-5 border border-[#E5EAF0] shadow-xs hover:border-[#142B4A]/30 hover:shadow-card-hover transition-all duration-200 ease-out hover:-translate-y-1 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#7B8794] uppercase tracking-wider">Rekan Terhubung</span>
                <div class="w-9 h-9 rounded-xl bg-[#F5F7FA] text-[#526780] border border-[#E5EAF0] flex items-center justify-center font-bold group-hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-[#263445] font-mono">{{ $totalFriends }}</span>
                <span class="text-xs text-[#526780] font-semibold">Rekan Kolaboratif</span>
            </div>
            <p class="text-[11px] text-[#7B8794] mt-1">Jaringan rekan kerja aktif</p>
        </div>
    </div>

    <!-- 2.5 WORKSPACE PERUSAHAAN TERPUSAT (HIERARKI TAHUN & BULAN) -->
    @if(isset($operationalYears) && $operationalYears->isNotEmpty())
    <div class="bg-white rounded-2xl border border-[#E5EAF0] shadow-xs p-6 space-y-6 reveal-on-scroll"
         x-data="{ showAddYearModal: false }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E5EAF0]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-[#F5F7FA] text-[#142B4A] border border-[#E5EAF0] font-bold text-[11px] flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#142B4A] animate-pulse"></span>
                        Sistem Terpusat
                    </span>
                    <h2 class="text-lg font-bold text-[#263445] tracking-tight">Workspace Perusahaan Dadhen Mandiri</h2>
                </div>
                <p class="text-xs text-[#7B8794] mt-1">
                    @if(Auth::user()->isOwner())
                        Struktur pembukuan terpusat: Tahun &rarr; Bulan &rarr; 5 Lembar Kerja (Analisa, Cost, Margin, Pendukung, Rank)
                    @else
                        Struktur pembukuan terpusat: Tahun &rarr; Bulan &rarr; Lembar Kerja Operasional (Analisa &amp; Cost)
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(Auth::user()->isOwner())
                    <a href="{{ route('company.analytics') }}" class="px-3.5 py-1.5 rounded-xl btn-secondary-gradient text-xs font-bold flex items-center gap-1.5 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-[#315E91]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Analytics</span>
                    </a>
                @endif
                <span class="text-xs font-mono text-[#7B8794] mr-1">Total: <strong class="text-[#263445]">{{ $operationalYears->count() }} Tahun</strong></span>
                @if(Auth::user()->isOwner())
                    <button type="button" @click="showAddYearModal = true" class="px-3.5 py-1.5 rounded-xl btn-primary-gradient font-bold text-xs shadow-xs flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Add Year</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
             x-data="{
                activeUnlockYear: null,
                activePasswordModalYear: null,
                openUnlockModal(year) {
                    this.activeUnlockYear = year;
                },
                openPasswordModal(year) {
                    this.activePasswordModalYear = year;
                }
             }">
            @foreach($operationalYears as $opY)
                @php
                    $isUnlocked = $opY->isUnlockedForUser(Auth::id());
                    $isAdminArchived = $opY->is_archived && Auth::user()->isAdmin();
                @endphp
                <div class="group relative rounded-2xl p-5 sm:p-6 transition-all duration-220 flex flex-col justify-between overflow-hidden select-none {{ $isAdminArchived ? 'card-archived-disabled cursor-not-allowed' : 'card-clean hover:-translate-y-1 cursor-pointer' }}"
                     @if($isAdminArchived)
                         @click.prevent.stop
                     @else
                         @click="@if(Auth::user()->isOwner() || $isUnlocked) window.location.href = '{{ route('company.year', $opY->year) }}' @else openUnlockModal({{ $opY->year }}) @endif"
                     @endif>
                    
                    <div class="relative z-10 space-y-4">
                        <!-- Card Header: Workspace Icon, Workspace Name, Member Count & Status -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-xl {{ $isAdminArchived ? 'bg-slate-200 text-slate-500 border border-slate-300' : 'bg-gradient-to-br from-[#315E91] to-[#1E3E66] text-white shadow-xs group-hover:scale-105' }} flex items-center justify-center font-extrabold text-base transition-all duration-200 shrink-0">
                                    @if($isAdminArchived)
                                        <span class="text-xl">🔒</span>
                                    @else
                                        {{ $opY->year }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-extrabold text-[#263445] text-base {{ $isAdminArchived ? 'text-slate-500' : 'group-hover:text-[#315E91]' }} transition-colors tracking-tight truncate">
                                        Workspace {{ $opY->year }}
                                    </h3>
                                    <p class="text-xs text-[#7B8794] mt-0.5 font-medium flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#526780]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        <span>{{ $totalMembers }} Anggota</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if($opY->is_archived)
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $isAdminArchived ? 'bg-slate-200 text-slate-600 border border-slate-300' : 'bg-[#FEF3C7] text-[#b45309] border border-amber-200' }}">
                                        🔒 Archived
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-[#DCFCE7] text-[#15803d] border border-emerald-200">
                                        Aktif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Footer -->
                    <div class="relative z-10 pt-4 mt-5 border-t border-[#E5EAF0] flex items-center justify-between gap-2" @click.stop>
                        @if(Auth::user()->isOwner())
                            <div class="flex items-center gap-1.5">
                                <button type="button" 
                                        @click="openPasswordModal({{ $opY->year }})"
                                        class="px-2.5 py-1.5 rounded-lg btn-secondary-gradient text-xs font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-[#7B8794]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    <span>Password</span>
                                </button>

                                @if($opY->is_archived)
                                    <form action="{{ route('company.years.restore', $opY->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs border border-emerald-200 transition-all duration-180 hover:-translate-y-0.5 active:translate-y-0">
                                            Pulihkan
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('company.years.archive', $opY->id) }}" method="POST" class="inline" onsubmit="return confirm('Arsipkan tahun {{ $opY->year }}? Semua lembar kerja di tahun ini akan menjadi mode hanya baca (read-only).');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-xs border border-amber-200 transition-all duration-180 hover:-translate-y-0.5 active:translate-y-0">
                                            Arsipkan
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <a href="{{ route('company.year', $opY->year) }}" 
                               class="px-3.5 py-1.5 rounded-xl btn-primary-gradient font-bold text-xs flex items-center gap-1">
                                <span>Buka Workspace</span>
                                <svg class="w-3.5 h-3.5 text-white/90 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @elseif($isAdminArchived)
                            {{-- Admin on Archived Workspace: Absolutely NO "Buka Workspace" button --}}
                            <div class="w-full py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-400 font-semibold text-xs flex items-center justify-center gap-1.5 cursor-not-allowed">
                                <span>🔒</span>
                                <span>Workspace Diarsipkan</span>
                            </div>
                        @else
                            {{-- Admin View for Active Workspace --}}
                            @if($isUnlocked)
                                <a href="{{ route('company.year', $opY->year) }}" 
                                   class="w-full py-2.5 rounded-xl btn-primary-gradient font-bold text-xs text-center flex items-center justify-center gap-2">
                                    <span>Buka Workspace {{ $opY->year }}</span>
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <button type="button" 
                                        @click="openUnlockModal({{ $opY->year }})"
                                        class="w-full py-2.5 rounded-xl btn-secondary-gradient font-bold text-xs text-center flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span>Buka Workspace {{ $opY->year }}</span>
                                    <svg class="w-3.5 h-3.5 text-[#7B8794] group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Modal Unlock Password Workspace (Admin) -->
            <div x-show="activeUnlockYear !== null" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#142B4A]/30 backdrop-blur-xs"
                 @keydown.escape.window="activeUnlockYear = null">
                <div class="bg-white w-full max-w-md rounded-2xl border border-[#E5EAF0] shadow-xl p-6 space-y-5 modal-enter"
                     @click.outside="activeUnlockYear = null">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center font-bold text-sm">
                                🔒
                            </div>
                            <h3 class="text-base font-bold text-[#263445]" x-text="'Masukkan Password Workspace ' + activeUnlockYear"></h3>
                        </div>
                        <button type="button" @click="activeUnlockYear = null" class="text-[#7B8794] hover:text-[#263445] text-lg">&times;</button>
                    </div>

                    <form :action="'/company/year/' + activeUnlockYear + '/unlock'" method="POST" class="space-y-4">
                        @csrf
                        <p class="text-xs text-[#7B8794]">
                            Masukkan password yang telah ditentukan oleh Owner untuk membuka seluruh workspace bulanan pada tahun ini.
                        </p>

                        <div>
                            <label for="dashboard_admin_unlock_password" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                                Password Workspace
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="dashboard_admin_unlock_password" 
                                   required 
                                   placeholder="Password workspace" 
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E5EAF0]">
                            <button type="button" 
                                    @click="activeUnlockYear = null" 
                                    class="px-4 py-2 rounded-xl btn-secondary-gradient font-bold text-xs">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 rounded-xl btn-primary-gradient font-bold text-xs flex items-center gap-1.5">
                                <span>Masuk ke Workspace</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Set Password Workspace (Owner) -->
            <div x-show="activePasswordModalYear !== null" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#142B4A]/30 backdrop-blur-xs"
                 @keydown.escape.window="activePasswordModalYear = null">
                <div class="bg-white w-full max-w-md rounded-2xl border border-[#E5EAF0] shadow-xl p-6 space-y-5 modal-enter"
                     @click.outside="activePasswordModalYear = null">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center font-bold text-sm">
                                🔑
                            </div>
                            <h3 class="text-base font-bold text-[#263445]" x-text="'Atur Password Workspace ' + activePasswordModalYear"></h3>
                        </div>
                        <button type="button" @click="activePasswordModalYear = null" class="text-[#7B8794] hover:text-[#263445] text-lg">&times;</button>
                    </div>

                    <form :action="'/company/year/' + activePasswordModalYear + '/password'" method="POST" class="space-y-4">
                        @csrf
                        <div class="p-3 bg-[#FEF3C7] border border-amber-200 rounded-xl text-xs text-[#b45309] leading-relaxed">
                            <p class="font-bold">Perhatian:</p>
                            <p class="mt-0.5">
                                Mengubah password akan membatalkan sesi Admin yang sedang aktif untuk tahun ini. Kosongkan kolom jika ingin mematikan proteksi password.
                            </p>
                        </div>

                        <div>
                            <label for="dashboard_new_password" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                                Password Baru (Min. 4 Karakter)
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="dashboard_new_password" 
                                   placeholder="Kosongkan untuk menghapus password"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all">
                        </div>

                        <div>
                            <label for="dashboard_new_password_confirmation" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                                Konfirmasi Password Baru
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="dashboard_new_password_confirmation" 
                                   placeholder="Ulangi password baru"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E5EAF0]">
                            <button type="button" 
                                    @click="activePasswordModalYear = null" 
                                    class="px-4 py-2 rounded-xl btn-secondary-gradient font-bold text-xs">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 rounded-xl btn-primary-gradient font-bold text-xs">
                                Simpan Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Year Modal -->
        <div x-show="showAddYearModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#142B4A]/30 backdrop-blur-xs"
             @keydown.escape.window="showAddYearModal = false">
            <div class="bg-white w-full max-w-md rounded-2xl border border-[#E5EAF0] shadow-xl p-6 space-y-5 modal-enter"
                 @click.outside="showAddYearModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-700 border border-sky-200 flex items-center justify-center font-bold text-sm">
                            📅
                        </div>
                        <h3 class="text-base font-bold text-[#263445]">Tambah Tahun Operasional Baru</h3>
                    </div>
                    <button type="button" @click="showAddYearModal = false" class="text-[#7B8794] hover:text-[#263445] text-lg">&times;</button>
                </div>

                <form action="{{ route('company.years.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="year_input" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                            Tahun Operasional (4 Digit)
                        </label>
                        <input type="number" 
                               name="year" 
                               id="year_input" 
                               required 
                               min="1000" 
                               max="9999" 
                               placeholder="Contoh: 2027" 
                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm font-mono text-[#263445] placeholder-[#7B8794] outline-none transition-all">
                        <p class="text-[11px] text-[#7B8794] mt-1.5">
                            Sistem akan otomatis membuat 12 workspace bulanan (Januari - Desember) dan 60 lembar kerja dengan formula standar.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E5EAF0]">
                        <button type="button" 
                                @click="showAddYearModal = false" 
                                class="px-4 py-2 rounded-xl btn-secondary-gradient font-bold text-xs">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 rounded-xl btn-primary-gradient font-bold text-xs flex items-center gap-1.5">
                            <span>Create Workspace</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
