<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dadhen Mandiri' }} — Aplikasi Spreadsheet Kolaboratif Modern</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Livewire already ships and starts Alpine. Loading a second CDN instance
         breaks x-data/@click bindings in the worksheet after hydration. --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            950: '#060e1b',
                            900: '#0A192F',
                            800: '#112240',
                            700: '#1D3557',
                        },
                        aquablue: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>


    <!-- Custom CSS for Page Transitions, Scroll Reveal & Spreadsheet -->
    <style>
        [x-cloak] { display: none !important; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
        
        /* Smooth Page Transition (180ms ease-out) */
        @keyframes pageFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .page-transition {
            animation: pageFadeIn 180ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Scroll Reveal Effect for Cards & Content */
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 250ms cubic-bezier(0.16, 1, 0.3, 1), transform 250ms cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }
        .reveal-on-scroll.is-revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* Respect prefers-reduced-motion */
        @media (prefers-reduced-motion: reduce) {
            .page-transition, .reveal-on-scroll {
                animation: none !important;
                transition: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }

        /* Custom scrollbars for spreadsheet */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Spreadsheet borders must not use border-collapse: collapsed borders are
           painted inconsistently by browsers when table cells are sticky. */
        .spreadsheet-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .spreadsheet-table th,
        .spreadsheet-table td {
            box-sizing: border-box;
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .spreadsheet-table tr > :first-child {
            border-left: 1px solid #cbd5e1;
        }
        .spreadsheet-table thead tr:first-child > * {
            border-top: 1px solid #cbd5e1;
        }
        .spreadsheet-table .grid-cell-input {
            display: block;
            min-width: 0;
            min-height: 0;
            max-height: 100%;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
        .spreadsheet-table .grid-cell-input:not(.is-wrapped) {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .spreadsheet-table .grid-cell-input.is-wrapped {
            white-space: normal;
        }
        .workspace-modal-layer > .fixed.inset-0 {
            z-index: 1000 !important;
        }

        /* Print styles */
        @media print {
            aside, header, .no-print, .toolbar-area, .bottom-tabs {
                display: none !important;
            }
            main {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .spreadsheet-table {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }
        }
    </style>

    <script>
        // Reinitialize reveal effects after Livewire navigates between sheets.
        const initializeRevealElements = () => {
            if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-revealed');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -20px 0px' });

                document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
            } else {
                document.querySelectorAll('.reveal-on-scroll').forEach(el => el.classList.add('is-revealed'));
            }
        };
        document.addEventListener('DOMContentLoaded', initializeRevealElements);
        document.addEventListener('livewire:navigated', initializeRevealElements);
    </script>

    @livewireStyles
</head>
<body class="h-full flex text-slate-800 antialiased font-sans" x-data="{ 
    mobileSidebarOpen: false,
    activeModal: @js(session('workspace_exit_target') ? 'modal-exit-verification' : (request()->query('open') === 'friends' ? 'modal-friend' : null)),
    workspaceExitGroupId: @js(
        isset($group, $userRole)
            && in_array($userRole, ['admin', 'karyawan'], true)
            && (int) session('admin_workspace_context.user_id', session('workspace_exit_context.user_id')) === (int) Auth::id()
            && (int) session('admin_workspace_context.group_id', session('workspace_exit_context.group_id')) === (int) $group->id
                ? $group->id
                : null
    ),
    get adminWorkspaceGroupId() {
        return this.workspaceExitGroupId;
    },
    workspaceExitTarget: @js(session('workspace_exit_target', '')),
    get adminExitTarget() {
        return this.workspaceExitTarget;
    },
    set adminExitTarget(val) {
        this.workspaceExitTarget = val;
    },
    exitPasswordInput: '',
    showExitPassword: false,
    isSubmittingExit: false,
    exitErrorMessage: '',
    workspacePasswordError: '',
    toastMessage: '',
    toastType: 'info',
    showToast: false,
    guardWorkspaceNavigation(event) {
        if (!this.workspaceExitGroupId) return;
        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || event.defaultPrevented) return;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin) return;

        const workspacePath = `/groups/${this.workspaceExitGroupId}/workspace`;
        if (destination.pathname === workspacePath || destination.pathname.startsWith(workspacePath + '/')) return;

        event.preventDefault();
        this.requestWorkspaceExit(destination.pathname + destination.search + destination.hash);
    },
    requestWorkspaceExit(destination) {
        if (!this.workspaceExitGroupId) {
            if (destination.includes('open=friends')) {
                this.activeModal = 'modal-friend';
            }
            return;
        }
        this.workspaceExitTarget = destination;
        this.exitPasswordInput = '';
        this.showExitPassword = false;
        this.isSubmittingExit = false;
        this.exitErrorMessage = '';
        this.activeModal = 'modal-exit-verification';
    },
    requestAdminExit(destination) {
        this.requestWorkspaceExit(destination);
    },
    cancelExit() {
        this.activeModal = null;
        this.workspaceExitTarget = '';
        this.exitPasswordInput = '';
        this.showExitPassword = false;
        this.isSubmittingExit = false;
        this.exitErrorMessage = '';
    },
    submitExitVerification(event) {
        if (this.isSubmittingExit) return;
        this.isSubmittingExit = true;
        this.exitErrorMessage = '';

        const form = event.target;
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
            }
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errorMsg = data.message || Object.values(data.errors || {}).flat()[0] || 'Password akun salah. Silakan coba lagi.';
                throw new Error(errorMsg);
            }
            return data;
        })
        .then(data => {
            this.isSubmittingExit = false;
            this.exitPasswordInput = '';
            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (this.workspaceExitTarget && this.workspaceExitTarget !== 'logout') {
                window.location.href = this.workspaceExitTarget;
            } else {
                window.location.href = '/dashboard';
            }
        })
        .catch(err => {
            this.isSubmittingExit = false;
            this.exitPasswordInput = '';
            this.exitErrorMessage = err.message || 'Verifikasi password gagal. Silakan coba lagi.';
        });
    },
    initWorkspaceHistory() {
        if (this.workspaceExitGroupId && window.history && window.history.pushState) {
            window.history.pushState({ inWorkspace: true, groupId: this.workspaceExitGroupId }, '');
            window.addEventListener('popstate', (e) => {
                if (this.workspaceExitGroupId) {
                    window.history.pushState({ inWorkspace: true, groupId: this.workspaceExitGroupId }, '');
                    this.requestWorkspaceExit('{{ route('dashboard') }}');
                }
            });
        }
    },
    unlockWorkspace(event) {
        const form = event.target;
        const password = form.elements.password;
        this.workspacePasswordError = '';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async response => {
            const result = await response.json();
            if (!response.ok) {
                const validationError = Object.values(result.errors || {}).flat()[0];
                throw new Error(result.message || validationError || 'Verifikasi password gagal.');
            }
            return result;
        })
        .then(result => {
            password.value = '';
            window.Livewire.navigate(result.redirect);
        })
        .catch(error => {
            password.value = '';
            this.workspacePasswordError = error.message || 'Verifikasi password gagal.';
        });
    },
    viewProfileUserId: null,
    viewProfileData: null,
    viewProfileLoading: false,
    viewProfileError: '',
    triggerToast(msg, type = 'info') {
        this.toastMessage = msg;
        this.toastType = type;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 4000);
    },
    openUserProfile(userId) {
        if (!userId) return;
        this.viewProfileUserId = userId;
        this.viewProfileData = null;
        this.viewProfileError = '';
        this.viewProfileLoading = true;
        this.activeModal = 'modal-view-profile';
        
        fetch('/users/' + userId + '/profile', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Profil pengguna tidak ditemukan.');
            return res.json();
        })
        .then(data => {
            this.viewProfileLoading = false;
            this.viewProfileData = data.user;
        })
        .catch(err => {
            this.viewProfileLoading = false;
            this.viewProfileError = err.message || 'Gagal memuat profil.';
        });
    }
}" @toast.window="triggerToast($event.detail.message, $event.detail.type || 'info')"
   @if(isset($group, $userRole) && in_array($userRole, ['admin', 'karyawan'], true)) @click.capture="guardWorkspaceNavigation($event)" @endif
   @open-user-profile.window="openUserProfile($event.detail.userId)"
   x-init="
       @if(session('error')) exitErrorMessage = @js(session('error')); @endif
       initWorkspaceHistory();
   ">

    <!-- Flash message triggers -->
    @if(session('success'))
        <div x-init="triggerToast('{{ session('success') }}', 'success')"></div>
    @endif
    @if(session('error'))
        <div x-init="triggerToast('{{ session('error') }}', 'error')"></div>
    @endif
    @if(session('info'))
        <div x-init="triggerToast('{{ session('info') }}', 'info')"></div>
    @endif

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/80 z-40 lg:hidden"
         @click="mobileSidebarOpen = false" x-cloak></div>

    <!-- SIDEBAR KIRI (Dark Navy #0A192F) -->
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0A192F] text-slate-200 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col border-r border-slate-800 shadow-2xl">
        
        <!-- Header Brand (Tersusun horizontal, bebas tulisan Sheets) -->
        <div class="h-20 flex items-center px-6 gap-3.5 border-b border-slate-800/80 bg-[#060e1b]/40">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-sky-500 to-emerald-400 flex items-center justify-center shadow-lg shadow-sky-500/20 text-white font-black text-xl tracking-wider ring-2 ring-white/10 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-lg font-bold text-white tracking-tight block whitespace-nowrap overflow-hidden text-ellipsis">
                    Dadhen Mandiri
                </span>
                <p class="text-xs text-slate-400 font-medium whitespace-nowrap">Spreadsheet Kolaboratif</p>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Navigation Links -->
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Navigasi Utama</p>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-sky-500/20 text-sky-400 border border-sky-500/30' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-sky-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Beranda / Ringkasan
                    </a>

                    <button @click="@if(isset($group, $userRole) && in_array($userRole, ['admin', 'karyawan'], true)) requestWorkspaceExit('{{ route('dashboard', ['open' => 'friends']) }}') @else activeModal = 'modal-friend' @endif"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800/60 hover:text-white transition-all text-left">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Jaringan Rekan Tim
                    </button>
                </nav>
            </div>

            <!-- Switcher Workspace Tim -->
            <div>
                <div class="px-3 mb-2">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Workspace Saya</p>
                </div>

                <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                    @foreach(Auth::user()->groups as $g)
                        <a href="{{ route('group.workspace', $g->id) }}" 
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition-all {{ isset($group) && $group->id === $g->id ? 'bg-slate-800 text-sky-300 ring-1 ring-sky-500/30' : 'text-slate-300 hover:bg-slate-800/40' }}">
                            <div class="w-8 h-8 rounded-lg bg-sky-600/30 border border-sky-500/40 flex items-center justify-center font-bold text-sky-300 text-xs shrink-0 overflow-hidden">
                                @if($g->avatar_url)
                                    <img src="{{ $g->avatar_url }}" alt="{{ $g->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($g->name, 0, 2)) }}
                                @endif
                            </div>
                            <div class="truncate flex-1">
                                <p class="truncate font-semibold text-white">{{ $g->name }}</p>
                                <p class="text-[10px] text-slate-400 truncate">{{ $g->category }} • {{ $g->sheets()->count() }} Lembar</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-3 px-2 flex gap-2">
                    <button @click="activeModal = 'modal-create-group'" 
                            class="flex-1 py-2 px-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white text-xs font-medium flex items-center justify-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Buat Baru
                    </button>
                    <button @click="activeModal = 'modal-join-group'" 
                            class="flex-1 py-2 px-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white text-xs font-medium flex items-center justify-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        Gabung
                    </button>
                </div>
            </div>
        </div>

        <!-- Profil Ringkas & Logout di Bagian Bawah -->
        <div class="p-4 border-t border-slate-800 bg-[#060e1b]/60 flex items-center justify-between">
            <div class="flex items-center gap-3 overflow-hidden cursor-pointer hover:opacity-85 transition-opacity" 
                 @click="openUserProfile({{ Auth::id() }})" 
                 title="Lihat profil saya">
                <img class="w-9 h-9 rounded-full object-cover ring-2 ring-sky-500/40" src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}">
                <div class="truncate">
                    <p class="text-xs font-semibold text-white truncate hover:underline">{{ Auth::user()->name }}</p>
                    <p class="text-[11px] text-sky-400 truncate capitalize font-mono">{{ Auth::user()->role_title ?? 'User' }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" title="Keluar / Logout"
                        @click="if (workspaceExitGroupId) { $event.preventDefault(); requestWorkspaceExit('logout'); }"
                        class="p-2 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden bg-slate-50 page-transition">
        
        <!-- TOPBAR -->
        <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between shadow-xs sticky top-0 z-[100] shrink-0">
            <div class="flex items-center gap-3">
                <!-- Mobile sidebar toggle -->
                <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 rounded-md text-slate-600 hover:bg-slate-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <!-- Breadcrumb / Active Workspace Indicator -->
                <div class="flex items-center gap-2 text-xs sm:text-sm">
                    <span class="text-slate-400 font-medium hidden sm:inline">Workspace:</span>
                    @if(isset($group))
                        <div class="flex items-center gap-2 bg-sky-50 text-sky-800 border border-sky-200 px-2.5 py-1 rounded-full font-semibold">
                            <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                            <span>{{ $group->name }}</span>
                        </div>
                    @else
                        <span class="font-semibold text-slate-700">Dashboard Dokumen & Spreadsheet</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 sm:gap-4" x-data="{ notifDropdownOpen: false }">
                <!-- Status Badge: Kolaborasi Aktif -->
                <div class="hidden md:flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Kolaborasi Aktif
                </div>

                <!-- Notification Dropdown -->
                <div class="relative">
                    @php
                        $unread = Auth::user()->unreadNotifications()->count();
                        $notifs = Auth::user()->notifications()->take(5)->get();
                    @endphp
                    <button @click="notifDropdownOpen = !notifDropdownOpen" 
                            class="relative p-2 rounded-full text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        @if($unread > 0)
                            <span class="absolute top-1.5 right-1.5 w-4 h-4 bg-rose-500 text-white rounded-full text-[10px] font-bold flex items-center justify-center ring-2 ring-white">
                                {{ $unread }}
                            </span>
                        @endif
                    </button>

                    <!-- Dropdown List -->
                    <div x-show="notifDropdownOpen" 
                         @click.away="notifDropdownOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50" x-cloak>
                        
                        <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800">Notifikasi</span>
                            <form action="{{ route('notifications.read-all') }}" method="POST">
                                @csrf
                                <button type="submit" class="text-[11px] text-sky-600 hover:text-sky-800 font-medium">Tandai Dibaca</button>
                            </form>
                        </div>

                        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                            @forelse($notifs as $n)
                                <div class="p-3 text-xs hover:bg-slate-50 transition-colors {{ !$n->is_read ? 'bg-sky-50/50' : '' }}">
                                    <p class="font-semibold text-slate-800">{{ $n->title }}</p>
                                    <p class="text-slate-600 text-[11px] mt-0.5 line-clamp-2">{{ $n->description }}</p>
                                    <span class="text-[9px] text-slate-400 mt-1 inline-block">{{ $n->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <div class="p-4 text-center text-xs text-slate-400">Tidak ada notifikasi baru</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- User Avatar & Role Badge (Clickable to view Profile) -->
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200 cursor-pointer hover:opacity-85 transition-opacity" 
                     @click="openUserProfile({{ Auth::id() }})" 
                     title="Lihat profil saya">
                    <img class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-300" src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}">
                    <div class="hidden sm:block text-left">
                        <span class="text-xs font-semibold text-slate-700 block leading-tight hover:text-sky-600 underline decoration-slate-300">{{ Auth::user()->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono capitalize">{{ Auth::user()->role_title ?? 'User' }}</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN VIEW AREA -->
        <main class="flex-1 min-h-0 min-w-0 overflow-y-auto flex flex-col">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    @if(isset($group, $userRole) && in_array($userRole, ['admin', 'karyawan'], true))
        <div x-show="activeModal === 'modal-exit-verification' || activeModal === 'modal-admin-exit'" x-transition.opacity
             class="fixed inset-0 z-[1100] overflow-y-auto" x-cloak role="dialog" aria-modal="true"
             aria-labelledby="exit-verification-title" @keydown.escape.window="cancelExit()">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="cancelExit()"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 text-left shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <div>
                                <h2 id="exit-verification-title" class="text-base font-bold text-slate-900">Verifikasi Password</h2>
                                <p class="text-xs text-slate-500">Otentikasi keamanan keluar workspace</p>
                            </div>
                        </div>
                        <button type="button" @click="cancelExit()" class="text-slate-400 hover:text-slate-600 p-1" title="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Masukkan password akun Anda untuk melanjutkan navigasi keluar dari workspace <strong>{{ $group->name }}</strong>.
                    </p>

                    <form action="{{ route('groups.leave', $group->id) }}" method="POST" @submit.prevent="submitExitVerification($event)" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="destination" x-model="workspaceExitTarget">
                        
                        <!-- Alert Pesan Kesalahan -->
                        <div x-show="exitErrorMessage" x-cloak class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-start gap-2">
                            <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span x-text="exitErrorMessage"></span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Password Akun
                            </label>
                            <div class="relative">
                                <input :type="showExitPassword ? 'text' : 'password'" 
                                       name="password" 
                                       x-model="exitPasswordInput"
                                       required 
                                       autocomplete="off" 
                                       autofocus
                                       placeholder="Masukkan password akun Anda..."
                                       :disabled="isSubmittingExit"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:bg-slate-50 disabled:text-slate-400">
                                <button type="button" 
                                        @click="showExitPassword = !showExitPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                        title="Tampilkan / Sembunyikan Password"
                                        tabindex="-1">
                                    <svg x-show="!showExitPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg x-show="showExitPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end pt-2">
                            <button type="button" @click="cancelExit()"
                                    :disabled="isSubmittingExit"
                                    class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-50">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="isSubmittingExit"
                                    class="rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-sky-700 disabled:bg-sky-400 flex items-center justify-center gap-2">
                                <svg x-show="isSubmittingExit" class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSubmittingExit ? 'Memverifikasi...' : 'Verifikasi dan Lanjutkan'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TOAST NOTIFICATION COMPONENT -->
    <div x-show="showToast" 
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-5 right-5 z-[9999] max-w-sm w-full bg-slate-900 text-white rounded-xl shadow-2xl p-4 flex items-center gap-3 border border-slate-700" x-cloak>
        <div :class="{
            'bg-emerald-500': toastType === 'success',
            'bg-sky-500': toastType === 'info',
            'bg-rose-500': toastType === 'error',
            'bg-amber-500': toastType === 'warning'
        }" class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div class="flex-1 text-xs">
            <p class="font-semibold text-slate-100" x-text="toastType.toUpperCase()"></p>
            <p class="text-slate-300 mt-0.5" x-text="toastMessage"></p>
        </div>
        <button @click="showToast = false" class="text-slate-400 hover:text-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <!-- GLOBAL MODAL DIALOGS -->

    <!-- 1. Modal Buat Workspace Baru (Sederhana: Nama & Password Saja) -->
    <div x-show="activeModal === 'modal-create-group'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Buat Workspace Tim Baru</h3>
                        <p class="text-xs text-slate-500">Mulai kolaborasi dokumen dan spreadsheet bersama tim</p>
                    </div>
                    <button @click="activeModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="{{ route('groups.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Workspace <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="cth: Keuangan PT Maju Berkah / Proyek Alpha" 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Password Workspace <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="4" placeholder="Masukkan password untuk keamanan workspace..." 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Anggota yang ingin bergabung wajib memasukkan password ini.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Workspace</label>
                        <select name="workspace_type" class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white">
                            <option value="manual">Manual (lembar kosong)</option>
                            <option value="template">Template</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="activeModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-500/20 transition-all">
                            Buat Workspace
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Modal Gabung Workspace (Dengan Verifikasi Password) -->
    <div x-show="activeModal === 'modal-join-group'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Gabung Workspace Tim</h3>
                            <p class="text-xs text-slate-500">Masukkan nama dan password workspace</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="{{ route('groups.join') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Workspace <span class="text-rose-500">*</span></label>
                        <input type="text" name="workspace_name" required placeholder="Masukkan nama workspace..." 
                               class="w-full text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Password Workspace <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required placeholder="Masukkan password workspace..." 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Dapatkan password dari pembuat workspace.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="activeModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/20 transition-all">
                            Gabung Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. Modal Hubungkan Rekan Tim (Cari Berdasarkan Nama Teman & Database Nyata) -->
    <div x-show="activeModal === 'modal-friend'" 
         x-data="{
             friendTab: 'search',
             searchQuery: '',
             isSearching: false,
             searchResults: [],
             searchMessage: '',
             connectedFriends: [],
             pendingRequests: [],
             isLoadingList: false,
             fetchFriendsList() {
                 this.isLoadingList = true;
                 fetch('{{ route('friends.list') }}', {
                     headers: { 'Accept': 'application/json' }
                 })
                 .then(r => r.json())
                 .then(data => {
                     this.connectedFriends = data.friends || [];
                     this.pendingRequests = data.pending || [];
                     this.isLoadingList = false;
                 })
                 .catch(() => { this.isLoadingList = false; });
             },
             doSearch() {
                 let q = this.searchQuery.trim();
                 if (!q) {
                     this.searchResults = [];
                     this.searchMessage = 'Ketik nama teman untuk mencari di database.';
                     return;
                 }
                 this.isSearching = true;
                 this.searchMessage = '';
                 fetch('{{ route('friends.search') }}?name=' + encodeURIComponent(q), {
                     headers: { 'Accept': 'application/json' }
                 })
                 .then(r => r.json())
                 .then(data => {
                     this.isSearching = false;
                     this.searchResults = data.users || [];
                     if (this.searchResults.length === 0) {
                         this.searchMessage = 'Pengguna dengan nama \'' + q + '\' tidak ditemukan dalam database.';
                     }
                 })
                 .catch(() => {
                     this.isSearching = false;
                     this.searchMessage = 'Terjadi kesalahan saat mencari pengguna.';
                 });
             },
             sendFriendRequest(targetId) {
                 fetch('{{ route('friends.store') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
                     },
                     body: JSON.stringify({ friend_id: targetId })
                 })
                 .then(r => r.json())
                 .then(data => {
                     if (data.success) {
                         $dispatch('toast', { message: data.message, type: 'success' });
                         this.doSearch();
                         this.fetchFriendsList();
                     } else {
                         $dispatch('toast', { message: data.message, type: 'error' });
                     }
                 })
                 .catch(() => {
                     $dispatch('toast', { message: 'Gagal mengirim permintaan pertemanan.', type: 'error' });
                 });
             },
             acceptRequest(friendshipId) {
                 fetch('/friends/' + friendshipId + '/accept', {
                     method: 'POST',
                     headers: {
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
                     }
                 })
                 .then(r => r.json())
                 .then(data => {
                     if (data.success) {
                         $dispatch('toast', { message: data.message, type: 'success' });
                         this.fetchFriendsList();
                     }
                 });
             }
         }" 
         x-init="fetchFriendsList(); $watch('activeModal', value => { if (value === 'modal-friend') { fetchFriendsList(); } })"
         class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 text-left shadow-2xl border border-slate-100 my-8">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Tambah Teman</h3>
                            <p class="text-xs text-slate-500">Cari dan tambahkan rekan kerja</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4 max-h-[65vh] overflow-y-auto pr-1">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Teman <span class="text-rose-500">*</span></label>
                        <div class="flex gap-2">
                            <input type="text" x-model="searchQuery" @keydown.enter.prevent="doSearch()" placeholder="Masukkan nama teman..." 
                                   class="flex-1 text-xs sm:text-sm px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <button type="button" @click="doSearch()" 
                                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors shrink-0">
                                <span x-show="!isSearching">Cari Teman</span>
                                <span x-show="isSearching" x-cloak>Mencari...</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Cukup masukkan nama rekan kerja untuk mencari akun di database.</p>
                    </div>

                    <!-- Hasil Pencarian -->
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        <template x-if="searchMessage">
                            <div class="p-4 text-center text-xs text-slate-500 bg-slate-50 rounded-xl border border-dashed border-slate-200" x-text="searchMessage"></div>
                        </template>

                        <template x-for="user in searchResults" :key="user.id">
                            <div class="p-3 bg-slate-50 hover:bg-indigo-50/40 rounded-xl border border-slate-100 flex items-center justify-between gap-3 transition-colors">
                                <!-- Info Pengguna: Nama dapat diklik untuk membuka profil -->
                                <div class="flex items-center gap-3 min-w-0 cursor-pointer" @click="openUserProfile(user.id)" title="Klik untuk melihat profil">
                                    <img :src="user.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-xs shrink-0" :alt="user.name">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-bold text-slate-800 hover:text-indigo-600 truncate underline decoration-indigo-300 underline-offset-2" x-text="user.name"></p>
                                            <span class="text-[10px] text-indigo-500 font-semibold" title="Buka Profil">↗</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 truncate capitalize" x-text="user.role_title"></p>
                                    </div>
                                </div>

                                <!-- Action Button / Status -->
                                <div class="shrink-0">
                                    <template x-if="user.status === 'friends'">
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[11px] font-bold">✓ Berteman</span>
                                    </template>
                                    <template x-if="user.status === 'pending_sent'">
                                        <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 text-[11px] font-semibold">Menunggu</span>
                                    </template>
                                    <template x-if="user.status === 'pending_received'">
                                        <button type="button" @click="sendFriendRequest(user.id)" 
                                                class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold shadow-xs transition-colors">
                                            Terima
                                        </button>
                                    </template>
                                    <template x-if="user.status === 'none'">
                                        <button type="button" @click="sendFriendRequest(user.id)" 
                                                class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold shadow-xs transition-colors">
                                            + Tambah
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 space-y-4">
                    <!-- Permintaan Masuk -->
                    <template x-if="pendingRequests.length > 0">
                        <div class="space-y-2">
                            <p class="text-xs font-bold text-slate-700">Permintaan Masuk:</p>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <template x-for="p in pendingRequests" :key="p.friendship_id">
                                    <div class="p-2.5 bg-sky-50/70 border border-sky-100 rounded-xl flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2.5 min-w-0 cursor-pointer" @click="openUserProfile(p.id)">
                                            <img :src="p.avatar_url" class="w-8 h-8 rounded-full object-cover shrink-0">
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-slate-800 truncate underline" x-text="p.name"></p>
                                                <p class="text-[10px] text-slate-500" x-text="p.created_at"></p>
                                            </div>
                                        </div>
                                        <button type="button" @click="acceptRequest(p.friendship_id)" 
                                                class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold shrink-0 transition-colors">
                                            Terima
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Rekan yang Terhubung -->
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-700">Daftar Rekan Terhubung:</p>
                        <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                            <template x-if="connectedFriends.length === 0">
                                <div class="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                    Belum ada rekan terhubung. Gunakan tab 'Cari & Tambah Teman' untuk menambahkan rekan kerja.
                                </div>
                            </template>

                            <template x-for="friend in connectedFriends" :key="friend.id">
                                <div class="p-2.5 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3 cursor-pointer transition-colors"
                                     @click="openUserProfile(friend.id)" title="Klik untuk membuka profil">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img :src="friend.avatar_url" class="w-9 h-9 rounded-full object-cover ring-1 ring-slate-200 shrink-0">
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-slate-800 truncate hover:text-indigo-600 underline decoration-indigo-200" x-text="friend.name"></p>
                                            <p class="text-[10px] text-slate-500 truncate" x-text="friend.role_title"></p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-indigo-500 font-semibold shrink-0">Lihat Profil →</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="activeModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Global Modal Profil Pengguna (Dapat Dilihat Publik, Aman Tanpa Membocorkan Data Sensitif) -->
    <div x-show="activeModal === 'modal-view-profile'" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="activeModal = null"></div>
            <div class="relative bg-white rounded-2xl max-w-sm w-full p-6 text-left shadow-2xl border border-slate-100 my-8 overflow-hidden">
                
                <!-- Ambient Accent Bar -->
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-sky-500 via-indigo-500 to-emerald-400"></div>

                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400">Profil Pengguna</h3>
                    <button @click="activeModal = null" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Loading State -->
                <div x-show="viewProfileLoading" class="py-12 text-center text-xs text-slate-500">
                    <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
                    <p>Memuat data profil pengguna...</p>
                </div>

                <!-- Error State -->
                <div x-show="!viewProfileLoading && viewProfileError" class="py-8 text-center text-xs text-rose-500 space-y-2">
                    <p class="font-bold text-sm">Profil Tidak Ditemukan</p>
                    <p x-text="viewProfileError"></p>
                </div>

                <!-- Content State -->
                <div x-show="!viewProfileLoading && viewProfileData" class="mt-4 space-y-5">
                    <div class="flex items-center gap-4">
                        <img :src="viewProfileData ? viewProfileData.avatar_url : ''" 
                             class="w-16 h-16 rounded-2xl object-cover ring-2 ring-indigo-100 shadow-xs shrink-0" 
                             :alt="viewProfileData ? viewProfileData.name : ''">
                        <div class="min-w-0">
                            <h4 class="text-base font-extrabold text-slate-900 truncate" x-text="viewProfileData ? viewProfileData.name : ''"></h4>
                            <p class="text-xs text-indigo-600 font-semibold capitalize font-mono mt-0.5" x-text="viewProfileData ? viewProfileData.role_title : ''"></p>
                            <template x-if="viewProfileData && viewProfileData.is_self">
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800">Profil Anda</span>
                            </template>
                        </div>
                    </div>

                    <!-- Statistics / Info Cards -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[10px] uppercase font-semibold text-slate-400 block">Bergabung</span>
                            <span class="font-bold text-slate-800" x-text="viewProfileData ? viewProfileData.joined_since : '-'"></span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[10px] uppercase font-semibold text-slate-400 block">Workspace</span>
                            <span class="font-bold text-slate-800"><span x-text="viewProfileData ? viewProfileData.total_workspaces : 0"></span> Aktif</span>
                        </div>
                    </div>

                    <!-- Friendship Status & Action -->
                    <template x-if="viewProfileData && !viewProfileData.is_self">
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs text-slate-500">Status Relasi:</span>
                            <div>
                                <template x-if="viewProfileData.friendship_status === 'friends'">
                                    <span class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                                        ✓ Rekan Tim
                                    </span>
                                </template>
                                <template x-if="viewProfileData.friendship_status === 'pending_sent'">
                                    <span class="px-3 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                                        Menunggu Persetujuan
                                    </span>
                                </template>
                                <template x-if="viewProfileData.friendship_status === 'pending_received'">
                                    <form action="{{ route('friends.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="friend_id" :value="viewProfileData.id">
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors">
                                            Terima Pertemanan
                                        </button>
                                    </form>
                                </template>
                                <template x-if="viewProfileData.friendship_status === 'none'">
                                    <form action="{{ route('friends.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="friend_id" :value="viewProfileData.id">
                                        <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition-colors">
                                            + Tambah Rekan
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 pt-3 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="activeModal = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
    <script src="{{ asset('js/spreadsheet-worksheet.js') }}?v={{ filemtime(public_path('js/spreadsheet-worksheet.js')) }}"></script>
</body>
</html>
