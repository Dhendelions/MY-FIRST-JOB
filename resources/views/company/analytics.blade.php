@extends('layouts.app', ['title' => 'Company Analytics — PT Dadhen Mandiri'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- 1. Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200 font-bold text-xs uppercase tracking-wider">
                    Executive Portal
                </span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500">PT Dadhen Mandiri</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">Company Analytics</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Modul analisis terpusat untuk memantau performa keuangan, operasional, dan komparasi lintas tahun operasional.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Dashboard</span>
            </a>
            @if($selectedYear)
                @php
                    $firstMw = $selectedYear->monthlyWorkspaces->first();
                @endphp
                @if($firstMw)
                    <a href="{{ route('group.workspace', $firstMw->group_id) }}" class="px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Buka Workspace {{ $selectedYear->year }}</span>
                    </a>
                @endif
            @endif
        </div>
    </div>

    <!-- 2. Sub-Module Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-px">
        <a href="{{ route('company.analytics', ['tab' => 'overview', 'year' => $selectedYear?->year]) }}"
           class="px-4 py-2.5 font-bold text-xs rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $activeTab === 'overview' ? 'border-sky-600 text-sky-600 bg-sky-50/50' : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>1. Overview</span>
        </a>

        <a href="{{ route('company.analytics', ['tab' => 'financial', 'year' => $selectedYear?->year]) }}"
           class="px-4 py-2.5 font-bold text-xs rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $activeTab === 'financial' ? 'border-sky-600 text-sky-600 bg-sky-50/50' : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>2. Financial Analytics</span>
        </a>

        <a href="{{ route('company.analytics', ['tab' => 'operational', 'year' => $selectedYear?->year]) }}"
           class="px-4 py-2.5 font-bold text-xs rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $activeTab === 'operational' ? 'border-sky-600 text-sky-600 bg-sky-50/50' : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            <span>3. Operational Analytics</span>
        </a>

        <a href="{{ route('company.analytics', ['tab' => 'comparison', 'year' => $selectedYear?->year]) }}"
           class="px-4 py-2.5 font-bold text-xs rounded-t-xl transition-all flex items-center gap-2 border-b-2 {{ $activeTab === 'comparison' ? 'border-sky-600 text-sky-600 bg-sky-50/50' : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>4. Yearly Comparison</span>
        </a>
    </div>

    <!-- 3. Database-Driven Year Selector Bar (Unless on Comparison Tab) -->
    @if($activeTab !== 'comparison')
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mr-1">Tahun Operasional:</span>
                @foreach($allYears as $y)
                    <a href="{{ route('company.analytics', ['tab' => $activeTab, 'year' => $y->year]) }}"
                       class="px-3.5 py-1.5 rounded-xl font-bold text-xs transition-all flex items-center gap-1.5 {{ ($selectedYear && $selectedYear->id === $y->id) ? 'bg-[#0A192F] text-sky-400 shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        <span>{{ $y->year }}</span>
                        @if($y->is_archived)
                            <span class="text-[9px] px-1 py-0.2 rounded font-normal {{ ($selectedYear && $selectedYear->id === $y->id) ? 'bg-amber-400/20 text-amber-300' : 'bg-amber-100 text-amber-700' }}">Arsip</span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if($selectedYear)
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-400">Status:</span>
                    <span class="px-2.5 py-0.5 rounded-full font-bold uppercase text-[10px] {{ $selectedYear->is_archived ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $selectedYear->is_archived ? 'Diarsipkan (Read-Only)' : 'Aktif' }}
                    </span>
                    <span class="text-slate-300">•</span>
                    <span class="text-slate-500 font-mono">12 Bulan / 60 Sheet</span>
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 1: OVERVIEW -->
    @if($activeTab === 'overview')
        @php
            $fin = $overviewData['financial_summary'];
            $ops = $overviewData['operational_summary'];
            $monthly = $overviewData['monthly_breakdown'];
            $hasData = collect($monthly)->contains('has_data', true);
        @endphp

        <!-- Key Financial Indicators Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Omset / Revenue -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                    <span>Total Omset (Revenue)</span>
                    <span class="text-emerald-500 font-mono">IDR</span>
                </div>
                <div class="text-2xl font-black text-slate-900 font-mono">
                    Rp {{ number_format($fin['total_omset'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">
                    Nilai penjualan bruto dari lembar kerja Rank/Margin
                </div>
            </div>

            <!-- Gross Margin -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                    <span>Gross Margin</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold">
                        {{ $fin['margin_pct'] }}%
                    </span>
                </div>
                <div class="text-2xl font-black text-emerald-600 font-mono">
                    Rp {{ number_format($fin['total_margin'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">
                    Omset dikurangi beban bahan baku utama (RM)
                </div>
            </div>

            <!-- Operational Cost -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                    <span>Total Biaya Operasional</span>
                    <span class="text-amber-500 font-mono">COST</span>
                </div>
                <div class="text-2xl font-black text-amber-600 font-mono">
                    Rp {{ number_format($fin['total_cost'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">
                    Total kemasan, bahan kimia, listrik, & utilitas
                </div>
            </div>

            <!-- Net Profit -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                    <span>Laba Bersih (Nett)</span>
                    <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 text-[10px] font-bold">
                        {{ $fin['net_profit_margin_pct'] }}%
                    </span>
                </div>
                <div class="text-2xl font-black text-sky-600 font-mono">
                    Rp {{ number_format($fin['total_nett'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">
                    Margin dikurangi biaya operasional & gaji tenaga kerja
                </div>
            </div>
        </div>

        <!-- Production Summary Indicators -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Indikator Produksi & Rendemen ({{ $selectedYear?->year }})</h3>
                    <p class="text-xs text-slate-500">Data agregasi dari lembar kerja Analisa seluruh bulan</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-mono text-xs font-semibold">
                    12 Siklus Bulanan
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-slate-400">Total Raw Material</span>
                    <div class="text-xl font-black text-slate-800 font-mono">{{ number_format($ops['total_rm_kg'], 1, ',', '.') }} <span class="text-xs font-normal">Kg</span></div>
                    <p class="text-[10px] text-slate-400">Bahan baku ikan/udang masuk</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-slate-400">Hasil Fillet</span>
                    <div class="text-xl font-black text-slate-800 font-mono">{{ number_format($ops['total_fillet_kg'], 1, ',', '.') }} <span class="text-xs font-normal">Kg</span></div>
                    <p class="text-[10px] text-slate-400">Rendemen fillet: {{ $ops['rendemen_fillet_pct'] }}%</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-slate-400">Finished Goods</span>
                    <div class="text-xl font-black text-slate-800 font-mono">{{ number_format($ops['total_finished_goods_kg'], 1, ',', '.') }} <span class="text-xs font-normal">Kg</span></div>
                    <p class="text-[10px] text-slate-400">Produk siap edar/ekspor</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-slate-400">Total Rendemen</span>
                    <div class="text-xl font-black text-indigo-600 font-mono">{{ $ops['total_rendemen_pct'] }}%</div>
                    <p class="text-[10px] text-slate-400">Rasio output terhadap input</p>
                </div>
            </div>
        </div>

        <!-- Monthly Trends Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Performa Bulanan Tahun {{ $selectedYear?->year }}</h3>
                    <p class="text-xs text-slate-500">Rincian omset, biaya, dan laba per bulan</p>
                </div>
                @if(!$hasData)
                    <span class="text-xs px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-medium">
                        Lembar Kerja Belum Terisi Transaksi
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold text-[11px]">
                        <tr>
                            <th class="px-6 py-3.5">Bulan</th>
                            <th class="px-6 py-3.5 text-right">Omset (Rp)</th>
                            <th class="px-6 py-3.5 text-right">Bahan Baku / RM (Rp)</th>
                            <th class="px-6 py-3.5 text-right">Gross Margin (Rp)</th>
                            <th class="px-6 py-3.5 text-right">Biaya Ops (Rp)</th>
                            <th class="px-6 py-3.5 text-right">Laba Bersih (Rp)</th>
                            <th class="px-6 py-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        @foreach($monthly as $m)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3.5 font-sans font-bold text-slate-800">
                                    {{ $m['name'] }}
                                </td>
                                <td class="px-6 py-3.5 text-right text-slate-700">
                                    {{ $m['financial']['omset'] > 0 ? number_format($m['financial']['omset'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-3.5 text-right text-slate-600">
                                    {{ $m['financial']['rm'] > 0 ? number_format($m['financial']['rm'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-bold text-emerald-600">
                                    {{ $m['financial']['margin'] > 0 ? number_format($m['financial']['margin'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-3.5 text-right text-amber-600">
                                    {{ $m['financial']['cost'] > 0 ? number_format($m['financial']['cost'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-black text-sky-600">
                                    {{ $m['financial']['nett'] > 0 ? number_format($m['financial']['nett'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-3.5 text-center font-sans">
                                    <a href="{{ route('group.workspace', $m['group_id']) }}" class="text-sky-600 hover:text-sky-700 font-bold hover:underline">
                                        Buka Sheet
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 2: FINANCIAL ANALYTICS -->
    @if($activeTab === 'financial')
        @php
            $fin = $financialData['financial_summary'] ?? [];
            $comp = $financialData['cost_composition'] ?? [];
            $supplies = $financialData['utilities_and_supplies'] ?? [];
            $monthly = $financialData['monthly_breakdown'] ?? [];
        @endphp

        <!-- Financial Breakdown Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Komposisi Beban Bahan Baku</span>
                <div class="text-2xl font-black text-slate-800 font-mono">
                    Rp {{ number_format($fin['total_rm'] ?? 0, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500">Biaya pengadaan bahan baku langsung perikanan</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Biaya Operasional (Cost Sheet)</span>
                <div class="text-2xl font-black text-amber-600 font-mono">
                    Rp {{ number_format($fin['total_cost'] ?? 0, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500">Total kemasan, listrik, es, solar, dan sewa</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Beban Payroll / Gaji</span>
                <div class="text-2xl font-black text-indigo-600 font-mono">
                    Rp {{ number_format($fin['total_payroll'] ?? 0, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500">Upah tenaga kerja dan staf produksi</p>
            </div>
        </div>

        <!-- Ledger Table of Financial Metrics -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Buku Besar Finansial Bulanan (Tahun {{ $financialData['year'] }})</h3>
                    <p class="text-xs text-slate-500">Perhitungan bersumber langsung dari lembar kerja Rank dan Margin</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase {{ $financialData['is_archived'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                    {{ $financialData['is_archived'] ? 'Tahun Diarsipkan' : 'Tahun Aktif' }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-sans font-semibold text-[11px]">
                        <tr>
                            <th class="px-5 py-3">Bulan</th>
                            <th class="px-5 py-3 text-right">Omset (Rp)</th>
                            <th class="px-5 py-3 text-right">RM (Rp)</th>
                            <th class="px-5 py-3 text-right">Gross Margin (Rp)</th>
                            <th class="px-5 py-3 text-right">Biaya Ops (Rp)</th>
                            <th class="px-5 py-3 text-right">Payroll (Rp)</th>
                            <th class="px-5 py-3 text-right">Laba Bersih (Rp)</th>
                            <th class="px-5 py-3 text-right">Saldo Kas/Bank (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($monthly as $m)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-5 py-3 font-sans font-bold text-slate-800">{{ $m['name'] }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $m['financial']['omset'] > 0 ? number_format($m['financial']['omset'], 0, ',', '.') : '-' }}</td>
                                <td class="px-5 py-3 text-right text-slate-600">{{ $m['financial']['rm'] > 0 ? number_format($m['financial']['rm'], 0, ',', '.') : '-' }}</td>
                                <td class="px-5 py-3 text-right font-bold text-emerald-600">{{ $m['financial']['margin'] > 0 ? number_format($m['financial']['margin'], 0, ',', '.') : '-' }}</td>
                                <td class="px-5 py-3 text-right text-amber-600">{{ $m['financial']['cost'] > 0 ? number_format($m['financial']['cost'], 0, ',', '.') : '-' }}</td>
                                <td class="px-5 py-3 text-right text-indigo-600">{{ $m['financial']['payroll'] > 0 ? number_format($m['financial']['payroll'], 0, ',', '.') : '-' }}</td>
                                <td class="px-5 py-3 text-right font-black text-sky-600">{{ $m['financial']['nett'] > 0 ? number_format($m['financial']['nett'], 0, ',', '.') : '-' }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $m['financial']['bank'] > 0 ? number_format($m['financial']['bank'], 0, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-100/80 font-bold border-t border-slate-200 text-slate-900">
                        <tr>
                            <td class="px-5 py-3.5 font-sans">TOTAL TAHUNAN</td>
                            <td class="px-5 py-3.5 text-right">{{ number_format($fin['total_omset'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right">{{ number_format($fin['total_rm'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right text-emerald-700">{{ number_format($fin['total_margin'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right text-amber-700">{{ number_format($fin['total_cost'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right text-indigo-700">{{ number_format($fin['total_payroll'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right text-sky-700">{{ number_format($fin['total_nett'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right">{{ number_format($fin['total_bank'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 3: OPERATIONAL ANALYTICS -->
    @if($activeTab === 'operational')
        @php
            $ops = $operationalData['operational_summary'] ?? [];
            $utils = $operationalData['utilities_and_supplies'] ?? [];
            $monthly = $operationalData['monthly_breakdown'] ?? [];
        @endphp

        <!-- Production Volumes Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Input Bahan Baku (Raw Material)</span>
                <div class="text-2xl font-black text-slate-800 font-mono">
                    {{ number_format($ops['total_rm_kg'] ?? 0, 1, ',', '.') }} <span class="text-sm font-normal text-slate-400">Kg</span>
                </div>
                <p class="text-xs text-slate-500">Volume total ikan/udang yang diproses pada tahun ini</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hasil Fillet & Rendemen</span>
                <div class="text-2xl font-black text-emerald-600 font-mono">
                    {{ number_format($ops['total_fillet_kg'] ?? 0, 1, ',', '.') }} <span class="text-sm font-normal text-slate-400">Kg</span>
                </div>
                <p class="text-xs text-slate-500">Rasio fillet terhadap bahan baku: <strong>{{ $ops['rendemen_fillet_pct'] ?? 0 }}%</strong></p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Finished Goods Siap Jual</span>
                <div class="text-2xl font-black text-sky-600 font-mono">
                    {{ number_format($ops['total_finished_goods_kg'] ?? 0, 1, ',', '.') }} <span class="text-sm font-normal text-slate-400">Kg</span>
                </div>
                <p class="text-xs text-slate-500">Total rendemen keseluruhan: <strong>{{ $ops['total_rendemen_pct'] ?? 0 }}%</strong></p>
            </div>
        </div>

        <!-- Itemized Utilities & Supplies Costs -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Rincian Pengeluaran Bahan Pendukung & Utilitas</h3>
                    <p class="text-xs text-slate-500">Diringkas dari lembar kerja Cost dan Pendukung</p>
                </div>
                <span class="text-xs font-mono text-slate-400">Tahun {{ $operationalData['year'] }}</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-xs font-mono">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Master Carton (MC)</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['packaging_mc'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Polybag (PB)</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['packaging_pb'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Chemicals</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['chemicals'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Listrik / PLN</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['listrik'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Air & Es</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format(($utils['air'] ?? 0) + ($utils['es'] ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Solar / BBM</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['solar'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Transportasi</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['transport'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Cold Storage</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['cold_storage'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="text-[10px] font-sans font-bold text-slate-400 uppercase">Sewa / Fasilitas</span>
                    <div class="font-bold text-slate-800">Rp {{ number_format($utils['sewa'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 4: YEARLY COMPARISON -->
    @if($activeTab === 'comparison')
        @php
            $comparisonList = $comparisonData['comparison'] ?? [];
        @endphp

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden space-y-4">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Perbandingan Kinerja Lintas Tahun Operasional</h3>
                    <p class="text-xs text-slate-500">Mencakup seluruh tahun operasional aktif maupun yang telah diarsipkan</p>
                </div>
                <span class="text-xs font-mono text-slate-500">Total: {{ count($comparisonList) }} Tahun Terdaftar</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-sans font-semibold text-[11px]">
                        <tr>
                            <th class="px-5 py-3.5">Tahun</th>
                            <th class="px-5 py-3.5 font-sans">Status</th>
                            <th class="px-5 py-3.5 text-right">Omset (Rp)</th>
                            <th class="px-5 py-3.5 text-right">Bahan Baku (Rp)</th>
                            <th class="px-5 py-3.5 text-right">Gross Margin (Rp)</th>
                            <th class="px-5 py-3.5 text-right">Margin %</th>
                            <th class="px-5 py-3.5 text-right">Biaya Ops (Rp)</th>
                            <th class="px-5 py-3.5 text-right">Laba Bersih (Rp)</th>
                            <th class="px-5 py-3.5 text-right">Input RM (Kg)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($comparisonList as $c)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-5 py-4 font-sans font-black text-slate-900 text-sm">
                                    {{ $c['year'] }}
                                </td>
                                <td class="px-5 py-4 font-sans">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ $c['is_archived'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $c['is_archived'] ? 'Diarsipkan' : 'Aktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-slate-800">
                                    {{ $c['financial']['total_omset'] > 0 ? number_format($c['financial']['total_omset'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-right text-slate-600">
                                    {{ $c['financial']['total_rm'] > 0 ? number_format($c['financial']['total_rm'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-emerald-600">
                                    {{ $c['financial']['total_margin'] > 0 ? number_format($c['financial']['total_margin'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-slate-700">
                                    {{ $c['financial']['margin_pct'] > 0 ? $c['financial']['margin_pct'] . '%' : '-' }}
                                </td>
                                <td class="px-5 py-4 text-right text-amber-600">
                                    {{ $c['financial']['total_cost'] > 0 ? number_format($c['financial']['total_cost'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-right font-black text-sky-600">
                                    {{ $c['financial']['total_nett'] > 0 ? number_format($c['financial']['total_nett'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-right text-slate-700">
                                    {{ $c['operational']['total_rm_kg'] > 0 ? number_format($c['operational']['total_rm_kg'], 1, ',', '.') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-8 text-center text-slate-400 font-sans">
                                    Belum ada tahun operasional yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
