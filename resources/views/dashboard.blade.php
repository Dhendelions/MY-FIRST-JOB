@extends('layouts.app', ['title' => 'Dashboard Ringkasan Operasional — Dadhen Mandiri'])

@section('content')
<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto w-full">

    <!-- 1. WELCOME BANNER DENGAN STATUS KOLABORASI -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0A192F] via-[#112240] to-[#1D3557] p-6 sm:p-8 text-white shadow-xl border border-slate-800 reveal-on-scroll">
        <!-- Ambient Background Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-0 right-1/4 w-40 h-40 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/15 border border-sky-400/30 text-xs font-semibold text-sky-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    @if(Auth::user()->isOwner())
                        Executive Portal &bull; Owner PT Dadhen Mandiri
                    @else
                        Workspace Kolaboratif &bull; Admin
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Halo, {{ $user->name }}! 👋
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Selamat datang di dasbor kolaborasi <span class="text-sky-300 font-semibold">Dadhen Mandiri</span>. Kelola lembar spreadsheet dokumen, formula, anggaran, dan perencanaan proyek bersama seluruh anggota tim Anda.
                </p>
            </div>

            <!-- TOMBOL AKSI CEPAT UTAMA -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
                @if(Auth::user()->isOwner())
                    <a href="{{ route('company.analytics') }}" 
                       class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold flex items-center gap-1.5 transition-all duration-200 hover:-translate-y-0.5 shadow-xs">
                        <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Analytics</span>
                    </a>
                    <a href="{{ route('company.users.index') }}" 
                       class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold flex items-center gap-1.5 transition-all duration-200 hover:-translate-y-0.5 shadow-xs">
                        <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Pengguna</span>
                    </a>
                @endif
                <button @click="activeModal = 'modal-friend'" 
                        class="px-4 py-2.5 rounded-xl bg-sky-600/90 hover:bg-sky-500 border border-sky-400/30 text-white text-xs sm:text-sm font-bold flex items-center gap-2 transition-all duration-200 hover:-translate-y-0.5 shadow-xs">
                    <svg class="w-4 h-4 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>Hubungkan Rekan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. KARTU STATISTIK KPI REAL-TIME DARI DATABASE -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- KPI 1: Total Workspace -->
        <div class="group relative bg-white hover:bg-gradient-to-br hover:from-white hover:to-sky-50/30 rounded-2xl p-5 border border-slate-200/80 hover:border-sky-300/80 shadow-xs hover:shadow-lg hover:shadow-sky-500/5 transition-all duration-200 ease-out hover:-translate-y-0.5 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Workspace Tim</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold group-hover:scale-110 group-hover:bg-sky-500 group-hover:text-white transition-all duration-200 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono">{{ $totalWorkspaces }}</span>
                <span class="text-xs text-sky-600 font-medium">Workspace Aktif</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Ruang kerja kolaboratif Anda</p>
            <div class="absolute bottom-0 inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-sky-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        <!-- KPI 2: Lembar Spreadsheet Aktif -->
        <div class="group relative bg-white hover:bg-gradient-to-br hover:from-white hover:to-emerald-50/30 rounded-2xl p-5 border border-slate-200/80 hover:border-emerald-300/80 shadow-xs hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-200 ease-out hover:-translate-y-0.5 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lembar Spreadsheet</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-200 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono">{{ $totalSheetsCount }}</span>
                <span class="text-xs text-emerald-600 font-medium">Lembar Terbuka</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Multi-sheet dokumen tim</p>
            <div class="absolute bottom-0 inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        <!-- KPI 3: Total Sel Data Tercatat -->
        <div class="group relative bg-white hover:bg-gradient-to-br hover:from-white hover:to-amber-50/30 rounded-2xl p-5 border border-slate-200/80 hover:border-amber-300/80 shadow-xs hover:shadow-lg hover:shadow-amber-500/5 transition-all duration-200 ease-out hover:-translate-y-0.5 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sel Data Tercatat</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all duration-200 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono">{{ number_format($totalRecordedCells, 0, ',', '.') }}</span>
                <span class="text-xs text-amber-600 font-medium">Sel Terisi</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Data tersimpan di database</p>
            <div class="absolute bottom-0 inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        <!-- KPI 4: Jumlah Rekan Terhubung -->
        <div class="group relative bg-white hover:bg-gradient-to-br hover:from-white hover:to-indigo-50/30 rounded-2xl p-5 border border-slate-200/80 hover:border-indigo-300/80 shadow-xs hover:shadow-lg hover:shadow-indigo-500/5 transition-all duration-200 ease-out hover:-translate-y-0.5 reveal-on-scroll overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rekan Terhubung</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold group-hover:scale-110 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-200 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 font-mono">{{ $totalFriends }}</span>
                <span class="text-xs text-indigo-600 font-medium">Rekan Kolaboratif</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Jaringan rekan kerja aktif</p>
            <div class="absolute bottom-0 inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-indigo-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>
    </div>

    <!-- 2.5 WORKSPACE PERUSAHAAN TERPUSAT (HIERARKI TAHUN & BULAN) -->
    @if(isset($operationalYears) && $operationalYears->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6 reveal-on-scroll"
         x-data="{ showAddYearModal: false }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200 font-semibold text-[11px] flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                        Sistem Terpusat
                    </span>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">Workspace Perusahaan Dadhen Mandiri</h2>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    @if(Auth::user()->isOwner())
                        Struktur pembukuan terpusat: Tahun &rarr; Bulan &rarr; 5 Lembar Kerja (Analisa, Cost, Margin, Pendukung, Rank)
                    @else
                        Struktur pembukuan terpusat: Tahun &rarr; Bulan &rarr; Lembar Kerja Operasional (Analisa &amp; Cost)
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(Auth::user()->isOwner())
                    <a href="{{ route('company.analytics') }}" class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 text-slate-700 text-xs font-bold transition-all duration-200 flex items-center gap-1.5 shadow-xs hover:-translate-y-0.5">
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Analytics</span>
                    </a>
                @endif
                <span class="text-xs font-mono text-slate-500 mr-1">Total: <strong>{{ $operationalYears->count() }} Tahun</strong></span>
                @if(Auth::user()->isOwner())
                    <button type="button" @click="showAddYearModal = true" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-sky-600 to-sky-700 hover:from-sky-500 hover:to-sky-600 text-white font-bold text-xs shadow-xs hover:shadow-md hover:shadow-sky-500/20 transition-all duration-200 flex items-center gap-1.5 hover:-translate-y-0.5 active:translate-y-0">
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
                @endphp
                <div class="group relative rounded-2xl bg-gradient-to-br from-[#0A192F] via-[#0D2038] to-[#132A4A] text-white p-5 sm:p-6 border border-slate-800/90 hover:border-sky-500/50 shadow-md hover:shadow-xl hover:shadow-sky-950/40 transition-all duration-200 ease-out {{ $opY->is_archived && Auth::user()->isAdmin() ? 'opacity-60 cursor-not-allowed' : 'hover:-translate-y-1 cursor-pointer' }} flex flex-col justify-between overflow-hidden select-none"
                     @click="@if(Auth::user()->isOwner() || (!$opY->is_archived && $isUnlocked)) window.location.href = '{{ route('company.year', $opY->year) }}' @elseif(!$opY->is_archived) openUnlockModal({{ $opY->year }}) @endif">
                    
                    <!-- Top-right Ambient Cyan Glow on Hover -->
                    <div class="absolute -top-16 -right-16 w-40 h-40 bg-gradient-to-bl from-sky-400/10 via-cyan-400/5 to-transparent rounded-full blur-2xl group-hover:from-sky-400/25 group-hover:via-cyan-300/10 transition-all duration-300 pointer-events-none"></div>

                    <!-- Bottom Gradient Accent Border on Hover -->
                    <div class="absolute bottom-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-sky-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

                    <div class="relative z-10 space-y-4">
                        <!-- Card Header: Year Emblem, Title, Status & Arrow Indicator -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center font-extrabold text-lg text-sky-300 group-hover:bg-sky-500/20 group-hover:border-sky-400/40 group-hover:text-white transition-all duration-200 shadow-inner shrink-0">
                                    {{ $opY->year }}
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-white text-base group-hover:text-sky-300 transition-colors tracking-tight">
                                        Workspace {{ $opY->year }}
                                    </h3>
                                    <p class="text-xs text-slate-300/90 mt-0.5">
                                        12 Bulan Operasional &bull; 60 Dokumen
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($opY->is_archived)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        🔒 Diarsipkan
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        Aktif
                                    </span>
                                @endif

                                <!-- Directional Indicator Arrow -->
                                <div class="w-7 h-7 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 group-hover:text-sky-300 group-hover:bg-sky-500/20 group-hover:border-sky-400/30 transition-all duration-200">
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Security Badges -->
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            @if($opY->hasPassword())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800/90 border border-slate-700/80 text-[11px] font-medium text-amber-300">
                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span>Protected</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800/90 border border-slate-700/80 text-[11px] font-medium text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                    <span>Terbuka</span>
                                </span>
                            @endif

                            @if(Auth::user()->isOwner())
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-950/70 border border-sky-800/70 text-[11px] font-semibold text-sky-300">
                                    <span>Owner Access</span>
                                </span>
                            @elseif($isUnlocked)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-950/70 border border-emerald-800/70 text-[11px] font-semibold text-emerald-300">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Terverifikasi</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-950/70 border border-rose-800/70 text-[11px] font-semibold text-rose-300">
                                    <span>Terkunci</span>
                                </span>
                            @endif
                        </div>

                        @if(!Auth::user()->isOwner() && $isUnlocked)
                            <!-- Accessible Spreadsheet Shortcuts for Admin (Analisa & Cost) -->
                            <div class="pt-2 border-t border-white/10 flex items-center gap-2" @click.stop>
                                <span class="text-[10px] text-slate-400 font-medium">Buka Cepat:</span>
                                <a href="{{ route('company.workspace', [$opY->year, 1, 'Analisa']) }}" 
                                   class="px-2.5 py-1 rounded-md bg-white/10 hover:bg-sky-500 hover:text-white text-[10px] font-semibold text-sky-200 border border-white/10 transition-all duration-180 hover:-translate-y-0.5 flex items-center gap-1">
                                    <span>Analisa</span>
                                </a>
                                <a href="{{ route('company.workspace', [$opY->year, 1, 'Cost']) }}" 
                                   class="px-2.5 py-1 rounded-md bg-white/10 hover:bg-emerald-500 hover:text-white text-[10px] font-semibold text-emerald-200 border border-white/10 transition-all duration-180 hover:-translate-y-0.5 flex items-center gap-1">
                                    <span>Cost</span>
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Bottom Action Footer -->
                    <div class="relative z-10 pt-4 mt-4 border-t border-white/10 flex items-center justify-between gap-2" @click.stop>
                        @if(Auth::user()->isOwner())
                            <div class="flex items-center gap-1.5">
                                <button type="button" 
                                        @click="openPasswordModal({{ $opY->year }})"
                                        class="px-2.5 py-1.5 rounded-lg border border-white/15 bg-white/5 hover:bg-white/15 text-slate-200 hover:text-white text-xs font-semibold transition-all duration-180 flex items-center gap-1 hover:-translate-y-0.5 active:translate-y-0">
                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-amber-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    <span>Password</span>
                                </button>

                                @if($opY->is_archived)
                                    <form action="{{ route('company.years.restore', $opY->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-semibold text-xs border border-emerald-500/30 transition-all duration-180 hover:-translate-y-0.5 active:translate-y-0">
                                            Pulihkan
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('company.years.archive', $opY->id) }}" method="POST" class="inline" onsubmit="return confirm('Arsipkan tahun {{ $opY->year }}? Semua lembar kerja di tahun ini akan menjadi mode hanya baca (read-only).');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-semibold text-xs border border-amber-500/30 transition-all duration-180 hover:-translate-y-0.5 active:translate-y-0">
                                            Arsipkan
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <a href="{{ route('company.year', $opY->year) }}" 
                               class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 hover:from-sky-400 hover:to-cyan-400 text-white font-bold text-xs shadow-xs hover:shadow-md hover:shadow-sky-500/25 transition-all duration-200 flex items-center gap-1 hover:-translate-y-0.5 active:translate-y-0">
                                <span>Buka Workspace</span>
                                <svg class="w-3.5 h-3.5 text-white/90 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @else
                            {{-- Admin View --}}
                            @if($isUnlocked)
                                <a href="{{ route('company.year', $opY->year) }}" 
                                   class="w-full py-2.5 rounded-xl bg-gradient-to-r from-sky-600 via-sky-500 to-cyan-600 hover:from-sky-500 hover:via-sky-400 hover:to-cyan-500 text-white font-bold text-xs shadow-xs hover:shadow-md hover:shadow-sky-500/20 text-center transition-all duration-200 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
                                    <span>Buka Workspace {{ $opY->year }}</span>
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <button type="button" 
                                        @click="openUnlockModal({{ $opY->year }})"
                                        class="w-full py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 hover:border-sky-400/40 text-white font-bold text-xs shadow-xs text-center transition-all duration-200 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
                                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span>Buka Workspace {{ $opY->year }}</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-1 group-hover:text-sky-300 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Modal Unlock Password Workspace (Admin) -->
            <div x-show="activeUnlockYear !== null" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                 @keydown.escape.window="activeUnlockYear = null">
                <div class="bg-white w-full max-w-md rounded-2xl border border-slate-200 shadow-2xl p-6 space-y-5"
                     @click.outside="activeUnlockYear = null">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm">
                                🔒
                            </div>
                            <h3 class="text-base font-bold text-slate-900" x-text="'Masukkan Password Workspace ' + activeUnlockYear"></h3>
                        </div>
                        <button type="button" @click="activeUnlockYear = null" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                    </div>

                    <form :action="'/company/year/' + activeUnlockYear + '/unlock'" method="POST" class="space-y-4">
                        @csrf
                        <p class="text-xs text-slate-500">
                            Masukkan password yang telah ditentukan oleh Owner untuk membuka seluruh workspace bulanan pada tahun ini.
                        </p>

                        <div>
                            <label for="dashboard_admin_unlock_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Password Workspace
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="dashboard_admin_unlock_password" 
                                   required 
                                   placeholder="Password workspace" 
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm text-slate-900 outline-none transition-all">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" 
                                    @click="activeUnlockYear = null" 
                                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                                <span>Masuk ke Workspace</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Set Password Workspace (Owner) -->
            <div x-show="activePasswordModalYear !== null" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                 @keydown.escape.window="activePasswordModalYear = null">
                <div class="bg-white w-full max-w-md rounded-2xl border border-slate-200 shadow-2xl p-6 space-y-5"
                     @click.outside="activePasswordModalYear = null">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm">
                                🔑
                            </div>
                            <h3 class="text-base font-bold text-slate-900" x-text="'Atur Password Workspace ' + activePasswordModalYear"></h3>
                        </div>
                        <button type="button" @click="activePasswordModalYear = null" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                    </div>

                    <form :action="'/company/year/' + activePasswordModalYear + '/password'" method="POST" class="space-y-4">
                        @csrf
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 leading-relaxed">
                            <p class="font-bold">Perhatian:</p>
                            <p class="mt-0.5">
                                Mengubah password akan membatalkan sesi Admin yang sedang aktif untuk tahun ini. Kosongkan kolom jika ingin mematikan proteksi password.
                            </p>
                        </div>

                        <div>
                            <label for="dashboard_new_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Password Baru (Min. 4 Karakter)
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="dashboard_new_password" 
                                   placeholder="Kosongkan untuk menghapus password"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm text-slate-900 outline-none transition-all">
                        </div>

                        <div>
                            <label for="dashboard_new_password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Konfirmasi Password Baru
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="dashboard_new_password_confirmation" 
                                   placeholder="Ulangi password baru"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm text-slate-900 outline-none transition-all">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" 
                                    @click="activePasswordModalYear = null" 
                                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-xs transition-colors">
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
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             @keydown.escape.window="showAddYearModal = false">
            <div class="bg-white w-full max-w-md rounded-2xl border border-slate-200 shadow-2xl p-6 space-y-5"
                 @click.outside="showAddYearModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center font-bold text-sm">
                            📅
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Tambah Tahun Operasional Baru</h3>
                    </div>
                    <button type="button" @click="showAddYearModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('company.years.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="year_input" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tahun Operasional (4 Digit)
                        </label>
                        <input type="number" 
                               name="year" 
                               id="year_input" 
                               required 
                               min="1000" 
                               max="9999" 
                               placeholder="Contoh: 2027" 
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm font-mono text-slate-900 placeholder:text-slate-400 outline-none transition-all">
                        <p class="text-[11px] text-slate-500 mt-1.5">
                            Sistem akan otomatis membuat 12 workspace bulanan (Januari - Desember) dan 60 lembar kerja dengan formula standar.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" 
                                @click="showAddYearModal = false" 
                                class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
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
