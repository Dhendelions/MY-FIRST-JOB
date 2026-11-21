<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F5F7FA]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Dadhen Mandiri'); ?> — Aplikasi Spreadsheet Kolaboratif Modern</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#315E91',
                        'primary-hover': '#254B75',
                        secondary: '#638FC2',
                        'secondary-hover': '#4E78A8',
                        'soft-blue': '#EAF2FC',
                        'page-bg': '#F4F7FB',
                        surface: '#FFFFFF',
                        'border-soft': '#E2EAF3',
                        'text-main': '#263445',
                        'text-muted': '#7B8794',
                        'badge-success': '#DCFCE7',
                        'badge-warning': '#FEF3C7',
                        'badge-danger': '#FEE2E2',
                        navy: {
                            950: '#0B1728',
                            900: '#142B4A',
                            800: '#1A3459',
                            700: '#20406C',
                            600: '#315E91',
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
                    },
                    boxShadow: {
                        'card': '0 1px 3px rgba(49, 94, 145, 0.04), 0 1px 2px rgba(49, 94, 145, 0.02)',
                        'card-hover': '0 8px 20px rgba(49, 94, 145, 0.09), 0 2px 6px rgba(49, 94, 145, 0.04)',
                    }
                }
            }
        }
    </script>

    <!-- Custom CSS for Design System, Gradients, Cards & Micro-interactions -->
    <style>
        [x-cloak] { display: none !important; }
        .tabular-nums { font-variant-numeric: tabular-nums; }

        :root {
            --dm-navy: #183B63;
            --dm-deep-blue: #142E50;
            --dm-blue: #28588A;
            --dm-blue-light: #EAF3FC;
            --dm-ice: #F2F7FD;
            --dm-cyan: #E6F6FA;
            --dm-indigo: #EEF0FF;
            --dm-teal: #DDF5F1;
            --dm-green: #DCFCE7;
            --dm-amber: #FFF3D6;
            --dm-gray: #EDF2F7;
            --dm-border: #CFE0F1;
            --dm-shadow: 0 8px 24px rgba(24, 59, 99, .08);
            --dm-transition: 200ms ease-in-out;
        }
        
        body {
            background: linear-gradient(135deg, var(--dm-ice) 0%, #F7FAFE 48%, var(--dm-cyan) 100%);
            background-attachment: fixed;
            color: #263445;
            min-height: 100vh;
        }

        /* Sidebar: Navy blue with gentle gradient */
        .sidebar-navy-gradient {
            background: linear-gradient(180deg, var(--dm-deep-blue) 0%, #173A63 44%, var(--dm-navy) 100%);
        }

        /* Navbar: White with subtle blue tint */
        .navbar-glass {
            background: linear-gradient(135deg, rgba(255, 255, 255, .98) 0%, var(--dm-ice) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #E2EAF3;
        }

        /* Clean SaaS Card with soft gradient nuance */
        .card-clean {
            background: linear-gradient(135deg, #FFFFFF 0%, var(--dm-ice) 100%);
            border: 1px solid var(--dm-border);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(49, 94, 145, 0.04), 0 1px 2px rgba(49, 94, 145, 0.02);
            transition: transform 220ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 220ms cubic-bezier(0.16, 1, 0.3, 1), border-color 220ms ease;
        }

        /* Reusable corporate surfaces: only opt-in semantic classes use these accents. */
        .dashboard-kpi-card, .company-workspace-card, .monthly-workspace-card, .workspace-overview-panel {
            border-color: var(--dm-border) !important;
            box-shadow: var(--dm-shadow);
            transition: transform var(--dm-transition), box-shadow var(--dm-transition), border-color var(--dm-transition), background var(--dm-transition);
        }
        .dashboard-kpi-card { background: linear-gradient(135deg, #fff, var(--dm-blue-light)) !important; }
        .dashboard-kpi-card:nth-child(2) { background: linear-gradient(135deg, #fff, var(--dm-green)) !important; }
        .dashboard-kpi-card:nth-child(3) { background: linear-gradient(135deg, #fff, var(--dm-amber)) !important; }
        .dashboard-kpi-card:nth-child(4) { background: linear-gradient(135deg, #fff, var(--dm-indigo)) !important; }
        .company-workspace-card { background: linear-gradient(135deg, var(--dm-blue-light), var(--dm-ice) 60%, var(--dm-cyan)) !important; }
        .company-workspace-card:nth-child(3n + 2) { background: linear-gradient(135deg, var(--dm-cyan), var(--dm-ice)) !important; }
        .company-workspace-card:nth-child(3n) { background: linear-gradient(135deg, var(--dm-indigo), var(--dm-ice)) !important; }
        .workspace-overview-panel { background: linear-gradient(135deg, rgba(255,255,255,.92), var(--dm-blue-light)) !important; }
        .monthly-workspace-card { background: linear-gradient(135deg, var(--dm-ice), var(--dm-blue-light)) !important; }
        .monthly-workspace-card:nth-child(3n + 2) { background: linear-gradient(135deg, var(--dm-cyan), var(--dm-ice)) !important; }
        .monthly-workspace-card:nth-child(3n) { background: linear-gradient(135deg, var(--dm-indigo), var(--dm-ice)) !important; }
        .company-workspace-card:hover, .monthly-workspace-card:hover, .dashboard-kpi-card:hover { border-color: #8FB4D8 !important; box-shadow: 0 12px 28px rgba(24,59,99,.13); transform: translateY(-3px); }
        .monthly-workspace-number { background: linear-gradient(135deg, #D9ECFC, #F6FBFF); box-shadow: 0 3px 10px rgba(40,88,138,.12); }
        .monthly-open-button { background: linear-gradient(135deg, #DCEEFF, #EAF3FC) !important; border: 1px solid #BCD7F0; box-shadow: 0 2px 6px rgba(40,88,138,.09); }
        .monthly-open-button:hover { background: linear-gradient(135deg, var(--dm-navy), var(--dm-blue)) !important; border-color: var(--dm-navy); color: #fff !important; box-shadow: 0 7px 16px rgba(24,59,99,.22); }
        .monthly-open-button:active { transform: scale(.98); }
        .company-year-page .grid.grid-cols-1 > div {
            background: linear-gradient(135deg, var(--dm-ice), var(--dm-blue-light)) !important;
            border-color: var(--dm-border) !important;
            box-shadow: 0 5px 16px rgba(24,59,99,.07);
        }
        .company-year-page .grid.grid-cols-1 > div:nth-child(3n + 2) { background: linear-gradient(135deg, var(--dm-cyan), var(--dm-ice)) !important; }
        .company-year-page .grid.grid-cols-1 > div:nth-child(3n) { background: linear-gradient(135deg, var(--dm-indigo), var(--dm-ice)) !important; }
        .company-year-page .grid.grid-cols-1 > div:hover { border-color: #8FB4D8 !important; box-shadow: 0 11px 24px rgba(24,59,99,.13); transform: translateY(-2px); }
        .company-year-page .grid.grid-cols-1 > div > div:first-child .w-10.h-10 { background: linear-gradient(135deg, #D9ECFC, #F7FBFF) !important; box-shadow: 0 3px 10px rgba(40,88,138,.12); }
        .company-year-page .grid.grid-cols-1 > div > a.w-full { background: linear-gradient(135deg, #DCEEFF, #EAF3FC) !important; border: 1px solid #BCD7F0; box-shadow: 0 2px 6px rgba(40,88,138,.09); }
        .company-year-page .grid.grid-cols-1 > div > a.w-full:hover { background: linear-gradient(135deg, var(--dm-navy), var(--dm-blue)) !important; border-color: var(--dm-navy); color: #fff !important; box-shadow: 0 7px 16px rgba(24,59,99,.22); }

        .workspace-app-header { background: linear-gradient(135deg, #fff, var(--dm-ice)) !important; border-color: var(--dm-border) !important; }
        .spreadsheet-surface { background: linear-gradient(180deg, var(--dm-ice), #DDE9F4) !important; }
        .formula-bar-container { background: linear-gradient(135deg, #fff, var(--dm-ice)) !important; border-bottom-color: var(--dm-border) !important; }
        .bottom-tabs { background: linear-gradient(135deg, var(--dm-ice), var(--dm-blue-light)) !important; border-color: var(--dm-border) !important; }
        .spreadsheet-table thead { background: #E4F0FB !important; }
        .spreadsheet-table thead th { background: #E4F0FB !important; color: var(--dm-deep-blue); border-color: #BFD7EC !important; }
        .spreadsheet-table .grid-cell-td { border-color: #D8E5F0 !important; }
        .spreadsheet-table .grid-cell-td:hover { background: #F4FAFF !important; }
        .card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(49, 94, 145, 0.09), 0 2px 6px rgba(49, 94, 145, 0.04);
            border-color: rgba(49, 94, 145, 0.3);
        }

        /* Archived Card Disabled State */
        .card-archived-disabled {
            cursor: not-allowed !important;
            user-select: none;
            opacity: 0.72;
            background: #F4F7FB !important;
            border-color: #E2EAF3 !important;
        }
        .card-archived-disabled:hover {
            transform: none !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
            border-color: #E2EAF3 !important;
        }

        /* BUTTON GRADIENT INTERACTION SYSTEM (200-300ms) */
        .btn-interactive, .btn-primary-gradient {
            background: linear-gradient(135deg, #315E91 0%, #264D7A 100%);
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 2px 4px rgba(49, 94, 145, 0.18);
            transition: all 250ms cubic-bezier(0.16, 1, 0.3, 1);
            font-weight: 600;
        }
        .btn-interactive:hover, .btn-primary-gradient:hover {
            background: linear-gradient(135deg, #3B6EA8 0%, #315E91 50%, #21436B 100%);
            box-shadow: 0 6px 16px rgba(49, 94, 145, 0.32);
            transform: translateY(-1.5px);
            color: #FFFFFF;
        }
        .btn-interactive:active, .btn-primary-gradient:active {
            background: linear-gradient(135deg, #264D7A 0%, #1D3B5C 100%);
            transform: scale(0.98);
            box-shadow: 0 1px 3px rgba(49, 94, 145, 0.2);
        }
        .btn-interactive:focus-visible, .btn-primary-gradient:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(49, 94, 145, 0.35);
        }

        .btn-secondary-gradient {
            background: linear-gradient(135deg, #FFFFFF 0%, #F4F7FB 100%);
            color: #263445;
            border: 1px solid #E2EAF3;
            box-shadow: 0 1px 2px rgba(49, 94, 145, 0.04);
            transition: all 250ms cubic-bezier(0.16, 1, 0.3, 1);
            font-weight: 600;
        }
        .btn-secondary-gradient:hover {
            background: linear-gradient(135deg, #EAF2FC 0%, #DBEAFE 100%);
            border-color: #638FC2;
            color: #315E91;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(49, 94, 145, 0.12);
        }
        .btn-secondary-gradient:active {
            transform: scale(0.98);
            background: #EAF2FC;
            box-shadow: 0 1px 2px rgba(49, 94, 145, 0.08);
        }
        .btn-secondary-gradient:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 143, 194, 0.35);
        }

        .btn-danger {
            background: #DC2626;
            color: #FFFFFF;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 1px 2px rgba(220, 38, 38, 0.2);
            transition: all 200ms ease;
            font-weight: 600;
        }
        .btn-danger:hover {
            background: #B91C1C;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);
        }
        .btn-danger:active {
            transform: scale(0.98);
            background: #991B1B;
        }

        /* Smooth Page Transition (Fade-in with subtle slide-up 200ms) */
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
            animation: pageFadeIn 200ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Modal Scale-in & Fade-in */
        @keyframes modalScaleIn {
            from {
                opacity: 0;
                transform: scale(0.96) translateY(4px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        .modal-enter {
            animation: modalScaleIn 180ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Scroll Reveal Effect for Cards & Content */
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 220ms cubic-bezier(0.16, 1, 0.3, 1), transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
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

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    <?php
        $appOperationalYears = \App\Models\OperationalYear::with('monthlyWorkspaces')->orderBy('year', 'desc')->get();
        $currentGroupYear = isset($group) ? $group->operationalYearModel() : null;
    ?>
</head>
<body class="h-full flex text-slate-800 antialiased font-sans" x-data="{ 
    mobileSidebarOpen: false,
    activeModal: <?php echo \Illuminate\Support\Js::from(session('workspace_exit_target') ? 'modal-exit-verification' : (request()->query('open') === 'friends' ? 'modal-friend' : null))->toHtml() ?>,
    workspaceExitGroupId: <?php echo \Illuminate\Support\Js::from(
        isset($group, $userRole) && $userRole === 'admin'
            ? $group->id
            : null
    )->toHtml() ?>,
    currentWorkspaceYear: <?php echo \Illuminate\Support\Js::from($currentGroupYear ? (int)$currentGroupYear->year : null)->toHtml() ?>,
    groupYearMap: {
        <?php $__currentLoopData = $appOperationalYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $__currentLoopData = $sy->monthlyWorkspaces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $smw): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo e((int)$smw->group_id); ?>: <?php echo e((int)$sy->year); ?>,
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    },
    yearHasPasswordMap: {
        <?php $__currentLoopData = $appOperationalYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo e((int)$sy->year); ?>: <?php echo e($sy->hasPassword() ? 'true' : 'false'); ?>,
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    },
    get adminWorkspaceGroupId() {
        return this.workspaceExitGroupId;
    },
    workspaceExitTarget: <?php echo \Illuminate\Support\Js::from(session('workspace_exit_target', ''))->toHtml() ?>,
    workspaceExitDestinationLabel: 'Dasbor',
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
        const link = event.target.closest('a[href], button[data-exit-target], form button[type=\'submit\']');
        if (!link || link.target === '_blank' || event.defaultPrevented) return;

        // Check if clicking a logout form
        const logoutForm = link.closest('form[action*=\'logout\']');
        if (logoutForm) {
            event.preventDefault();
            this.requestWorkspaceExit('logout', 'Keluar Akun');
            return;
        }

        const href = link.getAttribute('href') || link.getAttribute('data-href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        const destination = new URL(href, window.location.href);
        if (destination.origin !== window.location.origin) return;

        // 1. Moving within sheet tabs of the same monthly workspace
        const currentWsPath = `/groups/${this.workspaceExitGroupId}/workspace`;
        if (destination.pathname === currentWsPath || destination.pathname.startsWith(currentWsPath + '/')) {
            return;
        }

        // 2. Identify target operational year
        let targetYear = null;
        const groupMatch = destination.pathname.match(/^\/groups\/(\d+)\/workspace/);
        if (groupMatch) {
            const targetGroupId = parseInt(groupMatch[1]);
            targetYear = this.groupYearMap[targetGroupId] || null;
        } else {
            const companyWsMatch = destination.pathname.match(/^\/company\/workspace\/(\d+)/);
            if (companyWsMatch) {
                targetYear = parseInt(companyWsMatch[1]);
            } else {
                const companyYearMatch = destination.pathname.match(/^\/company\/year\/(\d+)/);
                if (companyYearMatch) {
                    targetYear = parseInt(companyYearMatch[1]);
                }
            }
        }

        // Case A: Moving between months within the SAME operational year
        // -> NO password required! Let navigation proceed seamlessly.
        if (targetYear && this.currentWorkspaceYear && targetYear === this.currentWorkspaceYear) {
            return;
        }

        // Case B: Switching to a DIFFERENT operational year
        // -> Requires target workspace password (Workspace Password, NOT account password!)
        if (targetYear && (!this.currentWorkspaceYear || targetYear !== this.currentWorkspaceYear)) {
            event.preventDefault();
            // Check if Owner has configured a password for target year
            if (this.yearHasPasswordMap[targetYear] === false) {
                this.openGlobalNoPasswordModal({
                    year: targetYear,
                    title: `Ruang Kerja ${targetYear}`
                });
                return;
            }

            this.openGlobalUnlockModal({
                year: targetYear,
                title: `Ruang Kerja ${targetYear}`,
                actionUrl: `/company/year/${targetYear}/unlock`,
                redirectTo: destination.href
            });
            return;
        }

        // Case C: Leaving workspace to outside pages (Dashboard, Friends, Profile, etc.)
        // -> Requires Admin ACCOUNT Password!
        event.preventDefault();
        let destLabel = 'Dasbor';
        if (destination.pathname.includes('friends') || destination.search.includes('friends')) {
            destLabel = 'Jaringan Rekan Tim';
        } else if (destination.pathname.includes('profile')) {
            destLabel = 'Profil Pengguna';
        }
        this.requestWorkspaceExit(destination.pathname + destination.search + destination.hash, destLabel);
    },
    requestWorkspaceExit(destination, label = 'halaman luar') {
        if (!this.workspaceExitGroupId) {
            if (destination.includes('open=friends') || destination === 'friends') {
                this.activeModal = 'modal-friend';
            }
            return;
        }
        this.workspaceExitTarget = destination;
        this.workspaceExitDestinationLabel = label;
        this.exitPasswordInput = '';
        this.showExitPassword = false;
        this.isSubmittingExit = false;
        this.exitErrorMessage = '';
        this.activeModal = 'modal-exit-verification';
        this.$nextTick(() => {
            const input = document.getElementById('admin_account_exit_password');
            if (input) input.focus();
        });
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
                const errorMsg = data.message || Object.values(data.errors || {}).flat()[0] || 'Password akun Admin salah. Silakan coba lagi.';
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
            this.exitErrorMessage = err.message || 'Verifikasi password akun Admin gagal. Silakan coba lagi.';
        });
    },
    initWorkspaceHistory() {
        if (this.workspaceExitGroupId && window.history && window.history.pushState) {
            window.history.pushState({ inWorkspace: true, groupId: this.workspaceExitGroupId }, '');
            window.addEventListener('popstate', (e) => {
                if (this.workspaceExitGroupId) {
                    window.history.pushState({ inWorkspace: true, groupId: this.workspaceExitGroupId }, '');
                    this.requestWorkspaceExit('<?php echo e(route('dashboard')); ?>', 'Dasbor');
                }
            });
        }
    },
    unlockWorkspace(event) {
        const form = event.target;
        const password = form.elements.password;
        const sheetIdInput = form.elements.sheet_id;
        const sheetId = sheetIdInput ? parseInt(sheetIdInput.value, 10) : null;
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
            if (password) password.value = '';

            // Mark workspace as unlocked in Alpine state so @sheet-switch guard works
            this.workspaceIsUnlocked = true;
            if (sheetId && !isNaN(sheetId)) {
                if (!Array.isArray(this.adminVerifiedSheetIds)) this.adminVerifiedSheetIds = [];
                if (!this.adminVerifiedSheetIds.includes(sheetId)) {
                    this.adminVerifiedSheetIds.push(sheetId);
                }
            }

            // Close the modal FIRST before any navigation
            this.workspaceModal = null;
            this.activeModal = null;

            const redirectUrl = result.redirect || window.location.href;
            try {
                const targetUrl = new URL(redirectUrl, window.location.origin);
                const currentUrl = new URL(window.location.href);
                const isSameUrl = targetUrl.pathname === currentUrl.pathname && targetUrl.search === currentUrl.search;

                if (isSameUrl) {
                    window.location.reload();
                } else {
                    window.location.href = redirectUrl;
                }
            } catch (e) {
                window.location.reload();
            }
        })
        .catch(error => {
            if (password) password.value = '';
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
    },
    globalUnlockModal: {
        show: false,
        year: null,
        groupId: null,
        sheetId: null,
        redirectTo: '',
        actionUrl: '',
        title: '',
        subtitle: '',
        error: '',
        isSubmitting: false,
    },
    openGlobalUnlockModal(detail = {}) {
        this.globalUnlockModal.year = detail.year || null;
        this.globalUnlockModal.groupId = detail.groupId || null;
        this.globalUnlockModal.sheetId = detail.sheetId || null;
        this.globalUnlockModal.redirectTo = detail.redirectTo || '';
        let action = detail.actionUrl || (detail.year ? `/company/year/${detail.year}/unlock` : (detail.groupId ? `/groups/${detail.groupId}/unlock` : ''));
        // Clean duplicate slashes while preserving protocol
        this.globalUnlockModal.actionUrl = action ? action.replace(/([^:]\/)\/+/g, '$1') : '';
        this.globalUnlockModal.title = detail.title || (detail.year ? `Workspace ${detail.year}` : 'Workspace');
        this.globalUnlockModal.subtitle = detail.subtitle || 'Masukkan kata sandi workspace untuk membuka seluruh data dan lembar kerja operasional.';
        this.globalUnlockModal.error = detail.error || '';
        this.globalUnlockModal.isSubmitting = false;
        this.globalUnlockModal.show = true;
        this.$nextTick(() => {
            const el = document.getElementById('global_unlock_password_input');
            if (el) el.focus();
        });
    },
    closeGlobalUnlockModal() {
        this.globalUnlockModal.show = false;
        this.globalUnlockModal.error = '';
        this.globalUnlockModal.isSubmitting = false;
    },
    globalInitiateModal: {
        show: false,
        isSubmitting: false,
        error: '',
    },
    openGlobalInitiateModal() {
        this.globalInitiateModal.show = true;
        this.globalInitiateModal.isSubmitting = false;
        this.globalInitiateModal.error = '';
        this.$nextTick(() => {
            const el = document.getElementById('global_initiate_year_input');
            if (el) el.focus();
        });
    },
    closeGlobalInitiateModal() {
        this.globalInitiateModal.show = false;
        this.globalInitiateModal.error = '';
    },
    workspacePasswordModal: { show: false, year: null, actionUrl: '', isSubmitting: false },
    openWorkspacePasswordModal(detail = {}) {
        this.workspacePasswordModal.year = detail.year || null;
        this.workspacePasswordModal.actionUrl = detail.actionUrl || (detail.year ? `/company/year/${detail.year}/password` : '');
        this.workspacePasswordModal.isSubmitting = false;
        this.workspacePasswordModal.show = true;
        this.$nextTick(() => document.getElementById('workspace_password_input')?.focus());
    },
    closeWorkspacePasswordModal() {
        this.workspacePasswordModal.show = false;
        this.workspacePasswordModal.isSubmitting = false;
    },
    globalConfirmModal: { show: false, action: '', method: 'POST', title: '', message: '', submitLabel: 'Konfirmasi', danger: false },
    openGlobalConfirmModal(detail = {}) {
        this.globalConfirmModal = { show: true, action: detail.action || '', method: detail.method || 'POST', title: detail.title || 'Konfirmasi tindakan', message: detail.message || '', submitLabel: detail.submitLabel || 'Konfirmasi', danger: !!detail.danger };
    },
    closeGlobalConfirmModal() {
        this.globalConfirmModal.show = false;
        this.globalInitiateModal.isSubmitting = false;
    },
    globalNoPasswordModal: {
        show: false,
        year: null,
        title: '',
    },
    openGlobalNoPasswordModal(detail = {}) {
        this.globalNoPasswordModal.year = detail.year || null;
        this.globalNoPasswordModal.title = detail.title || (detail.year ? `Ruang Kerja ${detail.year}` : 'Ruang Kerja');
        this.globalNoPasswordModal.show = true;
    },
    closeGlobalNoPasswordModal() {
        this.globalNoPasswordModal.show = false;
    }
}" @toast.window="triggerToast($event.detail.message, $event.detail.type || 'info')"
   <?php if(isset($group, $userRole) && $userRole === 'admin'): ?> @click.capture="guardWorkspaceNavigation($event)" <?php endif; ?>
   @open-user-profile.window="openUserProfile($event.detail.userId)"
   @open-workspace-unlock.window="openGlobalUnlockModal($event.detail)"
   @close-workspace-unlock.window="closeGlobalUnlockModal()"
   @open-workspace-password-settings.window="openWorkspacePasswordModal($event.detail)"
   @close-workspace-password-settings.window="closeWorkspacePasswordModal()"
   @open-workspace-no-password.window="openGlobalNoPasswordModal($event.detail)"
   @close-workspace-no-password.window="closeGlobalNoPasswordModal()"
   @open-initiate-workspace.window="openGlobalInitiateModal()"
   @close-initiate-workspace.window="closeGlobalInitiateModal()"
   @open-global-confirm.window="openGlobalConfirmModal($event.detail)"
   x-init="
       <?php if(session('error')): ?> exitErrorMessage = <?php echo \Illuminate\Support\Js::from(session('error'))->toHtml() ?>; <?php endif; ?>
       initWorkspaceHistory();
   ">

    <!-- Flash message triggers -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div x-init="triggerToast('<?php echo e(session('success')); ?>', 'success')"></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
        <div x-init="triggerToast('<?php echo e(session('error')); ?>', 'error')"></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('info')): ?>
        <div x-init="triggerToast('<?php echo e(session('info')); ?>', 'info')"></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

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

    <!-- SIDEBAR KIRI (Navy Blue with Gentle Gradient) -->
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed inset-y-0 left-0 z-50 w-72 sidebar-navy-gradient text-white transition-transform duration-250 ease-in-out lg:static lg:translate-x-0 flex flex-col border-r border-[#1D3A63] shadow-md">
        
        <!-- Header Brand -->
        <div class="h-20 flex items-center px-6 gap-3.5 border-b border-white/10 bg-white/5">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#315E91] to-[#173052] border border-white/20 flex items-center justify-center shadow-xs text-white font-black text-xl tracking-wider shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-base font-bold text-white tracking-tight block whitespace-nowrap overflow-hidden text-ellipsis">
                    Dadhen Mandiri
                </span>
                <p class="text-xs text-sky-200/80 font-medium whitespace-nowrap">Spreadsheet Kolaboratif</p>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Navigation Links -->
            <div>
                <p class="px-3 text-[11px] font-bold text-sky-200/70 uppercase tracking-wider mb-2">Navigasi Utama</p>
                <nav class="space-y-1">
                    <a href="<?php echo e(route('dashboard')); ?>" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 <?php echo e(request()->routeIs('dashboard') ? 'bg-[#315E91] text-white shadow-xs' : 'text-slate-300 hover:bg-white/10 hover:text-white'); ?>">
                        <svg class="w-5 h-5 <?php echo e(request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dasbor Utama
                    </a>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()?->isOwner()): ?>
                        <a href="<?php echo e(route('company.analytics')); ?>" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 <?php echo e(request()->routeIs('company.analytics*') || request()->routeIs('company.yearly-comparison*') ? 'bg-[#315E91] text-white shadow-xs' : 'text-slate-300 hover:bg-white/10 hover:text-white'); ?>">
                            <svg class="w-5 h-5 <?php echo e(request()->routeIs('company.analytics*') ? 'text-white' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Analitik Perusahaan
                        </a>

                        <a href="<?php echo e(route('company.users.index')); ?>" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 <?php echo e(request()->routeIs('company.users*') ? 'bg-[#315E91] text-white shadow-xs' : 'text-slate-300 hover:bg-white/10 hover:text-white'); ?>">
                            <svg class="w-5 h-5 <?php echo e(request()->routeIs('company.users*') ? 'text-white' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Manajemen Pengguna
                        </a>

                        <a href="<?php echo e(route('company.activity-logs.index')); ?>" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 <?php echo e(request()->routeIs('company.activity-logs*') ? 'bg-[#315E91] text-white shadow-xs' : 'text-slate-300 hover:bg-white/10 hover:text-white'); ?>">
                            <svg class="w-5 h-5 <?php echo e(request()->routeIs('company.activity-logs*') ? 'text-white' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Riwayat Aktivitas
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <button @click="activeModal = 'modal-friend'"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 text-left">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Jaringan Rekan Tim
                    </button>
                </nav>
            </div>

            <!-- Dynamic Year & Month Company Workspace Navigation -->
            <?php
                $sidebarYearsQuery = \App\Models\OperationalYear::with('monthlyWorkspaces')->orderBy('year', 'desc');
                if (Auth::user()?->isAdmin()) {
                    $sidebarYearsQuery->where('is_archived', false);
                }
                $sidebarYears = $sidebarYearsQuery->get();
                $currentGroupYearId = isset($group) && $group->monthlyWorkspace ? $group->monthlyWorkspace->year_id : null;
            ?>
            <div x-data="{ openYear: <?php echo e($currentGroupYearId ? $currentGroupYearId : ($sidebarYears->first()?->id ?? 'null')); ?> }">
                <div class="px-3 mb-2 flex items-center justify-between">
                    <p class="text-[11px] font-bold text-sky-200/70 uppercase tracking-wider">Tahun Operasional</p>
                    <span class="text-[10px] font-mono text-sky-200 bg-white/10 px-2 py-0.5 rounded-full border border-white/10 font-semibold">Perusahaan</span>
                </div>

                <div class="space-y-2 max-h-72 overflow-y-auto overscroll-contain pr-1" style="scrollbar-gutter: stable;">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $sidebarYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opYear): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="rounded-xl bg-white/5 border border-white/10 overflow-hidden" style="contain: layout paint;">
                            <!-- The year itself is only a group label. -->
                            <div class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold transition-colors"
                                 :class="openYear === <?php echo e($opYear->id); ?> ? 'text-white bg-white/10 border-b border-white/10' : 'text-slate-300 hover:text-white hover:bg-white/10'">
                                <span class="flex items-center gap-2 flex-1 cursor-default select-none" aria-label="Tahun operasional <?php echo e($opYear->year); ?>">
                                    <svg class="w-3.5 h-3.5 text-sky-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Tahun <?php echo e($opYear->year); ?></span>
                                </span>
                                <button type="button" 
                                        @click="openYear = (openYear === <?php echo e($opYear->id); ?> ? null : <?php echo e($opYear->id); ?>)" 
                                        class="flex items-center gap-1.5 text-slate-400 hover:text-white p-1 rounded transition-colors"
                                        title="Buka / Tutup Daftar Bulan">
                                    <span class="text-[10px] text-slate-400 font-normal">12 Bulan</span>
                                    <svg class="w-3.5 h-3.5 transition-transform duration-200" 
                                         :class="openYear === <?php echo e($opYear->id); ?> ? 'rotate-180 text-white' : 'text-slate-400'" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Months Grid -->
                            <div x-show="openYear === <?php echo e($opYear->id); ?>" x-collapse class="p-2 grid grid-cols-3 gap-1.5 bg-[#0f223a]/80">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $opYear->monthlyWorkspaces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mWork): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $isActiveMonth = isset($group) && $group->id === $mWork->group_id;
                                    ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->isOwner() || !$opYear->is_archived): ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->isAdmin() && !$opYear->hasPassword()): ?>
                                            <button type="button"
                                                    @click="$dispatch('open-workspace-no-password', { year: <?php echo e($opYear->year); ?>, title: 'Ruang Kerja <?php echo e($opYear->year); ?>' })"
                                                    title="Password Ruang Kerja <?php echo e($opYear->year); ?> belum diatur"
                                                    class="px-2 py-1.5 text-center rounded-lg text-[11px] font-semibold transition-colors truncate text-slate-300 bg-white/5 hover:bg-white/15 hover:text-white">
                                                <?php echo e(substr($mWork->name, 0, 3)); ?>

                                            </button>
                                        <?php elseif(Auth::user()->isAdmin() && !$opYear->isUnlockedForUser(Auth::id())): ?>
                                            <button type="button"
                                                    @click="$dispatch('open-workspace-unlock', { year: <?php echo e($opYear->year); ?>, title: 'Ruang Kerja <?php echo e($opYear->year); ?>', actionUrl: '<?php echo e(route('company.year.unlock', $opYear->year)); ?>', redirectTo: '<?php echo e(route('company.workspace', [$opYear->year, $mWork->month])); ?>' })"
                                                    title="Buka <?php echo e($mWork->name); ?> <?php echo e($opYear->year); ?>"
                                                    class="px-2 py-1.5 text-center rounded-lg text-[11px] font-semibold transition-colors truncate text-slate-300 bg-white/5 hover:bg-white/15 hover:text-white">
                                                <?php echo e(substr($mWork->name, 0, 3)); ?>

                                            </button>
                                        <?php else: ?>
                                        <a href="<?php echo e(route('group.workspace', $mWork->group_id)); ?>"
                                           title="<?php echo e($mWork->name); ?> <?php echo e($opYear->year); ?>"
                                           class="px-2 py-1.5 text-center rounded-lg text-[11px] font-semibold transition-all duration-150 truncate <?php echo e($isActiveMonth ? 'bg-[#315E91] text-white shadow-xs' : 'text-slate-300 bg-white/5 hover:bg-white/15 hover:text-white'); ?>">
                                            <?php echo e(substr($mWork->name, 0, 3)); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($opYear->is_archived): ?> 🔒<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </a>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php else: ?>
                                        <span title="🔒 Archived — hanya Owner yang dapat membuka"
                                              aria-disabled="true"
                                              class="px-2 py-1.5 text-center rounded-lg text-[11px] font-medium truncate text-slate-500 bg-white/5 opacity-50 cursor-not-allowed">
                                            <?php echo e(substr($mWork->name, 0, 3)); ?> 🔒
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-xs text-slate-400 px-3 py-2">Belum ada tahun operasional.</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <!-- Legacy Workspaces Accordion (if any) -->
                <?php
                    $legacyGroups = Auth::user()->groups->where('is_legacy', true);
                ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($legacyGroups->isNotEmpty()): ?>
                    <div x-data="{ openLegacy: false }" class="mt-3">
                        <button @click="openLegacy = !openLegacy" 
                                class="w-full flex items-center justify-between px-3 py-1.5 rounded-lg text-[11px] font-semibold text-sky-200/70 hover:text-white transition-colors">
                            <span>Workspace Arsip (<?php echo e($legacyGroups->count()); ?>)</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="openLegacy ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="openLegacy" x-collapse class="mt-1 space-y-1 pl-1">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $legacyGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->isOwner() || !$g->isArchived()): ?>
                                    <a href="<?php echo e(route('group.workspace', $g->id)); ?>"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs transition-colors <?php echo e(isset($group) && $group->id === $g->id ? 'bg-[#315E91] text-white' : 'text-slate-300 hover:text-white hover:bg-white/10'); ?>">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span class="truncate">
                                            <?php echo e($g->name); ?>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($g->isArchived()): ?> 🔒 Diarsipkan <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </span>
                                    </a>
                                <?php else: ?>
                                    <span aria-disabled="true" title="🔒 Diarsipkan — hanya Owner yang dapat membuka"
                                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs text-slate-500 opacity-60 cursor-not-allowed">
                                        <span>🔒</span><span class="truncate"><?php echo e($g->name); ?> — Diarsipkan</span>
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <!-- Profil Ringkas & Logout di Bagian Bawah -->
        <div class="p-4 border-t border-white/10 bg-white/5 flex items-center justify-between">
            <div class="flex items-center gap-3 overflow-hidden cursor-pointer hover:opacity-90 transition-opacity" 
                 @click="openUserProfile(<?php echo e(Auth::id()); ?>)" 
                 title="Lihat profil saya">
                <img class="w-9 h-9 rounded-full object-cover ring-2 ring-white/20" src="<?php echo e(Auth::user()->avatar_url); ?>" alt="<?php echo e(Auth::user()->name); ?>">
                <div class="truncate">
                    <p class="text-xs font-bold text-white truncate hover:text-sky-200"><?php echo e(Auth::user()->name); ?></p>
                    <p class="text-[11px] text-sky-200/80 truncate capitalize font-mono"><?php echo e(Auth::user()->display_role); ?></p>
                </div>
            </div>
            <form action="<?php echo e(route('logout')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" title="Keluar"
                        class="p-2 text-slate-300 hover:text-rose-400 hover:bg-rose-500/10 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden bg-transparent page-transition">
        
        <!-- TOPBAR -->
        <header class="h-16 navbar-glass px-4 sm:px-6 flex items-center justify-between sticky top-0 z-[100] shrink-0">
            <div class="flex items-center gap-3">
                <!-- Mobile sidebar toggle -->
                <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 rounded-xl text-[#526780] hover:bg-[#F5F7FA]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <!-- Breadcrumb / Active Workspace Indicator -->
                <div class="flex items-center gap-2 text-xs sm:text-sm">
                    <span class="text-[#7B8794] font-medium hidden sm:inline">Ruang Kerja:</span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($group)): ?>
                        <div class="flex items-center gap-2 bg-[#F5F7FA] text-[#142B4A] border border-[#E5EAF0] px-3 py-1 rounded-full font-bold">
                            <span class="w-2 h-2 rounded-full bg-[#142B4A] animate-pulse"></span>
                            <span><?php echo e($group->name); ?></span>
                        </div>
                    <?php else: ?>
                        <span class="font-bold text-[#263445]">Dasbor Dokumen &amp; Spreadsheet</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:gap-4" x-data="{ notifDropdownOpen: false }">
                <!-- Status Badge: Kolaborasi Aktif (Soft Green #DCFCE7) -->
                <div class="hidden md:flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#DCFCE7] border border-emerald-200 text-[#15803d] text-xs font-semibold">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span>
                    </span>
                    Kolaborasi Aktif
                </div>

                <!-- Notification Dropdown -->
                <div class="relative">
                    <?php
                        $unread = Auth::user()->unreadNotifications()->count();
                        $notifs = Auth::user()->notifications()->take(5)->get();
                    ?>
                    <button @click="notifDropdownOpen = !notifDropdownOpen" 
                            class="relative p-2 rounded-xl text-[#526780] hover:text-[#142B4A] hover:bg-[#F5F7FA] transition-colors focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unread > 0): ?>
                            <span class="absolute top-1.5 right-1.5 w-4 h-4 bg-rose-500 text-white rounded-full text-[10px] font-bold flex items-center justify-center ring-2 ring-white">
                                <?php echo e($unread); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>

                    <!-- Dropdown List -->
                    <div x-show="notifDropdownOpen" 
                         @click.away="notifDropdownOpen = false"
                         x-transition:enter="transition ease-out duration-180"
                         x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-120"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                         class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-[#E5EAF0] py-2 z-50" x-cloak>
                        
                        <div class="px-4 py-2 border-b border-[#E5EAF0] flex items-center justify-between">
                            <span class="text-xs font-bold text-[#263445]">Notifikasi</span>
                            <form action="<?php echo e(route('notifications.read-all')); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-[11px] text-[#142B4A] hover:underline font-bold">Tandai Dibaca</button>
                            </form>
                        </div>

                        <div class="max-h-72 overflow-y-auto divide-y divide-[#E5EAF0]">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $notifs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="p-3 text-xs hover:bg-[#F5F7FA] transition-colors <?php echo e(!$n->is_read ? 'bg-sky-50/40' : ''); ?>">
                                    <p class="font-bold text-[#263445]"><?php echo e($n->title); ?></p>
                                    <p class="text-[#526780] text-[11px] mt-0.5 line-clamp-2"><?php echo e($n->description); ?></p>
                                    <span class="text-[9px] text-[#7B8794] mt-1 inline-block"><?php echo e($n->created_at->diffForHumans()); ?></span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="p-4 text-center text-xs text-[#7B8794]">Tidak ada notifikasi baru</div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Avatar & Role Badge (Clickable to view Profile) -->
                <div class="flex items-center gap-2 pl-2 border-l border-[#E5EAF0] cursor-pointer hover:opacity-90 transition-opacity" 
                     @click="openUserProfile(<?php echo e(Auth::id()); ?>)" 
                     title="Lihat profil saya">
                    <img class="w-8 h-8 rounded-full object-cover ring-1 ring-[#E5EAF0]" src="<?php echo e(Auth::user()->avatar_url); ?>" alt="<?php echo e(Auth::user()->name); ?>">
                    <div class="hidden sm:block text-left">
                        <span class="text-xs font-bold text-[#263445] block leading-tight hover:text-[#142B4A]"><?php echo e(Auth::user()->name); ?></span>
                        <span class="text-[10px] text-[#7B8794] font-mono capitalize"><?php echo e(Auth::user()->display_role); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN VIEW AREA -->
        <main class="flex-1 min-h-0 min-w-0 overflow-y-auto flex flex-col">
            <?php echo e($slot ?? ''); ?>

            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($group, $userRole) && $userRole === 'admin'): ?>
        <!-- GLOBAL FULLSCREEN ADMIN ACCOUNT PASSWORD VERIFICATION MODAL -->
        <div x-show="activeModal === 'modal-exit-verification' || activeModal === 'modal-admin-exit'" x-transition.opacity
             class="fixed inset-0 z-[1100] overflow-y-auto" x-cloak role="dialog" aria-modal="true"
             aria-labelledby="exit-verification-title" @keydown.escape.window="cancelExit()">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-[#142B4A]/60 backdrop-blur-md" @click="cancelExit()"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 sm:p-7 text-left shadow-2xl border border-slate-100 z-10 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200/60 shadow-xs shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <h2 id="exit-verification-title" class="text-base font-bold text-[#263445]">Verifikasi Password Akun Admin</h2>
                                <p class="text-[11px] text-[#7B8794]">Autentikasi akun keluar dari ruang kerja</p>
                            </div>
                        </div>
                        <button type="button" @click="cancelExit()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg" title="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <p class="text-xs text-[#526780] leading-relaxed">
                        Anda sedang meninggalkan ruang kerja untuk menuju <strong x-text="workspaceExitDestinationLabel || 'halaman luar'"></strong>. Masukkan <strong class="text-[#142B4A]">Password Akun Login Anda</strong> untuk melanjutkan.
                    </p>

                    <form action="<?php echo e(route('groups.leave', $group->id)); ?>" method="POST" @submit.prevent="submitExitVerification($event)" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="destination" x-model="workspaceExitTarget">
                        
                        <!-- Alert Pesan Kesalahan -->
                        <div x-show="exitErrorMessage" x-cloak class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-start gap-2">
                            <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span x-text="exitErrorMessage"></span>
                        </div>

                        <div>
                            <label for="admin_account_exit_password" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                                Password Akun Admin
                            </label>
                            <div class="relative">
                                <input :type="showExitPassword ? 'text' : 'password'" 
                                       name="password" 
                                       id="admin_account_exit_password"
                                       x-model="exitPasswordInput"
                                       required 
                                       autocomplete="current-password" 
                                       autofocus
                                       placeholder="Masukkan kata sandi akun login..."
                                       :disabled="isSubmittingExit"
                                       class="w-full px-3.5 py-2.5 pr-10 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all disabled:bg-slate-50 disabled:text-slate-400">
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

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E5EAF0]">
                            <button type="button" @click="cancelExit()"
                                    :disabled="isSubmittingExit"
                                    class="px-4 py-2.5 rounded-xl btn-secondary-gradient font-bold text-xs disabled:opacity-50">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="isSubmittingExit"
                                    class="px-5 py-2.5 rounded-xl btn-primary-gradient font-bold text-xs flex items-center justify-center gap-2 disabled:opacity-60 shadow-xs">
                                <span x-show="isSubmittingExit" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span x-text="isSubmittingExit ? 'Memverifikasi...' : 'Verifikasi dan Lanjutkan'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- GLOBAL FULLSCREEN NO PASSWORD MODAL -->
    <div x-show="globalNoPasswordModal.show" 
         class="fixed inset-0 z-[1200] overflow-y-auto" 
         x-cloak 
         role="dialog" 
         aria-modal="true"
         @keydown.escape.window="closeGlobalNoPasswordModal()">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-[#142B4A]/60 backdrop-blur-md" @click="closeGlobalNoPasswordModal()"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 sm:p-7 text-center shadow-2xl border border-slate-100 z-10 space-y-5">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-2xl shadow-xs">
                    🔒
                </div>
                <div>
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[10px] uppercase tracking-wider">
                        Konfigurasi Diperlukan
                    </span>
                    <h3 class="text-base font-bold text-[#263445] mt-2" x-text="'Password ' + (globalNoPasswordModal.title || 'Ruang Kerja') + ' Belum Diatur'">
                        Password Ruang Kerja Belum Diatur
                    </h3>
                    <p class="text-xs text-[#7B8794] mt-2 leading-relaxed">
                        Owner perlu mengatur kata sandi untuk ruang kerja ini sebelum Admin dapat membuka atau mengakses lembar kerja operasional. Silakan hubungi Owner untuk mengonfigurasinya.
                    </p>
                </div>
                <div class="pt-2">
                    <button type="button" 
                            @click="closeGlobalNoPasswordModal()" 
                            class="w-full px-4 py-2.5 rounded-xl btn-primary-gradient font-bold text-xs shadow-xs">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION COMPONENT -->
    <div x-show="showToast" 
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         :class="{
            'bg-emerald-50 border-emerald-200 text-emerald-950': toastType === 'success',
            'bg-sky-50 border-sky-200 text-sky-950': toastType === 'info',
            'bg-rose-50 border-rose-200 text-rose-950': toastType === 'error',
            'bg-amber-50 border-amber-200 text-amber-950': toastType === 'warning'
         }"
         class="fixed bottom-5 right-5 z-[9999] max-w-sm w-[calc(100%-2.5rem)] sm:w-full rounded-xl shadow-2xl p-4 flex items-center gap-3 border" x-cloak>
        <div :class="{
            'bg-emerald-500': toastType === 'success',
            'bg-sky-500': toastType === 'info',
            'bg-rose-500': toastType === 'error',
            'bg-amber-500': toastType === 'warning'
        }" class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div class="flex-1 text-xs">
            <p class="font-semibold" x-text="toastType.toUpperCase()"></p>
            <p class="text-slate-600 mt-0.5" x-text="toastMessage"></p>
        </div>
        <button @click="showToast = false" class="text-slate-400 hover:text-slate-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <!-- GLOBAL MODAL DIALOGS -->

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
                 fetch('<?php echo e(route('friends.list')); ?>', {
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
                 fetch('<?php echo e(route('friends.search')); ?>?name=' + encodeURIComponent(q), {
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
                 fetch('<?php echo e(route('friends.store')); ?>', {
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
                            <span class="text-[10px] uppercase font-semibold text-slate-400 block">Ruang Kerja</span>
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
                                    <form action="<?php echo e(route('friends.store')); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="friend_id" :value="viewProfileData.id">
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors">
                                            Terima Pertemanan
                                        </button>
                                    </form>
                                </template>
                                <template x-if="viewProfileData.friendship_status === 'none'">
                                    <form action="<?php echo e(route('friends.store')); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
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

    <!-- GLOBAL FULLSCREEN WORKSPACE PASSWORD UNLOCK MODAL -->
    <div x-show="globalUnlockModal.show" 
         x-cloak
         class="fixed inset-0 z-[9999] overflow-y-auto"
         role="dialog"
         aria-modal="true"
         aria-labelledby="global-unlock-title"
         @keydown.escape.window="closeGlobalUnlockModal()">
        <div class="fixed inset-0 bg-[#142B4A]/50 backdrop-blur-sm transition-opacity" 
             @click="closeGlobalUnlockModal()"
             aria-hidden="true"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-md rounded-2xl border border-[#E5EAF0] shadow-2xl p-6 space-y-5 modal-enter text-left z-10"
                 @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center font-bold text-base shadow-xs shrink-0">
                            🔒
                        </div>
                        <div>
                            <h3 id="global-unlock-title" class="text-base font-bold text-[#263445]" x-text="'Masukkan Password ' + (globalUnlockModal.title || 'Ruang Kerja')"></h3>
                            <p class="text-[11px] text-[#7B8794]">Autentikasi Hak Akses Ruang Kerja</p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="closeGlobalUnlockModal()" 
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-[#7B8794] hover:text-[#263445] hover:bg-slate-100 transition-colors text-lg"
                            title="Tutup">
                        &times;
                    </button>
                </div>

                <form :action="globalUnlockModal.actionUrl" method="POST" class="space-y-4" @submit="globalUnlockModal.isSubmitting = true">
                    <?php echo csrf_field(); ?>
                    <template x-if="globalUnlockModal.sheetId">
                        <input type="hidden" name="sheet_id" :value="globalUnlockModal.sheetId">
                    </template>
                    <template x-if="globalUnlockModal.redirectTo">
                        <input type="hidden" name="redirect_to" :value="globalUnlockModal.redirectTo">
                    </template>

                    <p class="text-xs text-[#7B8794] leading-relaxed" x-text="globalUnlockModal.subtitle">
                        Masukkan kata sandi untuk membuka seluruh data dan lembar kerja operasional.
                    </p>

                    <div x-show="globalUnlockModal.error" x-cloak class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700">
                        <span x-text="globalUnlockModal.error"></span>
                    </div>

                    <div>
                        <label for="global_unlock_password_input" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                            Password Ruang Kerja
                        </label>
                        <input type="password" 
                               name="password" 
                               id="global_unlock_password_input" 
                               required 
                               autocomplete="current-password"
                               placeholder="Masukkan kata sandi ruang kerja" 
                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm text-[#263445] outline-none transition-all">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E5EAF0]">
                        <button type="button" 
                                @click="closeGlobalUnlockModal()" 
                                class="px-4 py-2.5 rounded-xl btn-secondary-gradient font-bold text-xs">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="globalUnlockModal.isSubmitting"
                                class="px-5 py-2.5 rounded-xl btn-primary-gradient font-bold text-xs flex items-center justify-center gap-2 disabled:opacity-60 shadow-xs">
                            <span x-show="globalUnlockModal.isSubmitting" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span x-text="globalUnlockModal.isSubmitting ? 'Memverifikasi...' : 'Masuk ke Ruang Kerja'">Masuk ke Ruang Kerja</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- GLOBAL FULLSCREEN WORKSPACE PASSWORD SETTINGS MODAL -->
    <div x-show="workspacePasswordModal.show" x-cloak class="fixed inset-0 z-[10000] overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="closeWorkspacePasswordModal()">
        <div class="fixed inset-0 bg-[#142B4A]/60 backdrop-blur-md" @click="closeWorkspacePasswordModal()"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-[#E5EAF0] bg-white p-6 shadow-2xl modal-enter" @click.stop>
                <div class="flex items-center justify-between border-b border-[#E5EAF0] pb-3">
                    <div><h3 class="text-base font-bold text-[#263445]" x-text="'Pengaturan Password Ruang Kerja ' + workspacePasswordModal.year"></h3><p class="mt-0.5 text-[11px] text-[#7B8794]">Akses Admin dikendalikan oleh konfigurasi ini.</p></div>
                    <button type="button" @click="closeWorkspacePasswordModal()" class="w-8 h-8 rounded-lg text-lg text-[#7B8794] hover:bg-slate-100">&times;</button>
                </div>
                <form :action="workspacePasswordModal.actionUrl" method="POST" class="space-y-4 pt-5" @submit="workspacePasswordModal.isSubmitting = true">
                    <?php echo csrf_field(); ?>
                    <div class="rounded-xl border border-amber-200 bg-[#FEF3C7] p-3 text-xs leading-relaxed text-[#b45309]">Password baru akan membatalkan sesi Admin yang aktif. Kosongkan kedua kolom untuk menghapus konfigurasi password.</div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?><div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div><label for="workspace_password_input" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#263445]">Password baru</label><input id="workspace_password_input" type="password" name="password" minlength="4" autocomplete="new-password" placeholder="Minimal 4 karakter" class="w-full rounded-xl border border-[#E5EAF0] px-3.5 py-2.5 text-sm outline-none focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10"></div>
                    <div><label for="workspace_password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#263445]">Konfirmasi password</label><input id="workspace_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru" class="w-full rounded-xl border border-[#E5EAF0] px-3.5 py-2.5 text-sm outline-none focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10"></div>
                    <div class="flex justify-end gap-3 border-t border-[#E5EAF0] pt-4"><button type="button" @click="closeWorkspacePasswordModal()" class="px-4 py-2.5 rounded-xl btn-secondary-gradient text-xs font-bold">Batal</button><button type="submit" :disabled="workspacePasswordModal.isSubmitting" class="px-5 py-2.5 rounded-xl btn-primary-gradient text-xs font-bold disabled:opacity-60" x-text="workspacePasswordModal.isSubmitting ? 'Menyimpan...' : 'Simpan'"></button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- GLOBAL FULLSCREEN WORKSPACE INITIATION MODAL -->
    <div x-show="globalInitiateModal.show" 
         x-cloak
         class="fixed inset-0 z-[9999] overflow-y-auto"
         role="dialog"
         aria-modal="true"
         aria-labelledby="global-initiate-title"
         @keydown.escape.window="closeGlobalInitiateModal()">
        <div class="fixed inset-0 bg-[#142B4A]/50 backdrop-blur-sm transition-opacity" 
             @click="closeGlobalInitiateModal()"
             aria-hidden="true"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-md rounded-2xl border border-[#E5EAF0] shadow-2xl p-6 space-y-5 modal-enter text-left z-10"
                 @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-700 border border-sky-200 flex items-center justify-center font-bold text-base shadow-xs shrink-0">
                            📅
                        </div>
                        <div>
                            <h3 id="global-initiate-title" class="text-base font-bold text-[#263445]">Inisiasi Tahun Operasional Baru</h3>
                            <p class="text-[11px] text-[#7B8794]">Pembuatan Otomatis 12 Ruang Kerja & 60 Spreadsheet</p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="closeGlobalInitiateModal()" 
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-[#7B8794] hover:text-[#263445] hover:bg-slate-100 transition-colors text-lg"
                            title="Tutup">
                        &times;
                    </button>
                </div>

                <form action="<?php echo e(route('company.years.store')); ?>" method="POST" class="space-y-4" @submit="globalInitiateModal.isSubmitting = true">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label for="global_initiate_year_input" class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">
                            Tahun Operasional (4 Digit)
                        </label>
                        <input type="number" 
                               name="year" 
                               id="global_initiate_year_input" 
                               required 
                               min="1000" 
                               max="9999" 
                               placeholder="Contoh: <?php echo e(date('Y') + 1); ?>" 
                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#E5EAF0] focus:border-[#142B4A] focus:ring-2 focus:ring-[#142B4A]/10 text-sm font-mono text-[#263445] placeholder-[#7B8794] outline-none transition-all">
                    <p class="text-[11px] text-[#7B8794] mt-1.5 leading-relaxed">
                        Sistem akan otomatis membuat 12 ruang kerja bulanan (Januari - Desember) dan 60 lembar kerja dengan formula standar operasional.
                    </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E5EAF0]">
                        <button type="button" 
                                @click="closeGlobalInitiateModal()" 
                                class="px-4 py-2.5 rounded-xl btn-secondary-gradient font-bold text-xs">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="globalInitiateModal.isSubmitting"
                                class="px-5 py-2.5 rounded-xl btn-primary-gradient font-bold text-xs flex items-center justify-center gap-2 disabled:opacity-60 shadow-xs">
                            <span x-show="globalInitiateModal.isSubmitting" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span x-text="globalInitiateModal.isSubmitting ? 'Memproses Inisiasi...' : 'Inisiasi Ruang Kerja'">Inisiasi Ruang Kerja</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- GLOBAL FULLSCREEN CONFIRMATION MODAL -->
    <div x-show="globalConfirmModal.show" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="closeGlobalConfirmModal()">
        <div class="fixed inset-0 bg-[#142B4A]/50 backdrop-blur-sm" @click="closeGlobalConfirmModal()"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-[#E5EAF0] bg-white p-6 shadow-2xl modal-enter" @click.stop>
                <h3 class="text-base font-bold text-[#263445]" x-text="globalConfirmModal.title"></h3>
                <p class="mt-2 text-sm leading-relaxed text-[#7B8794]" x-text="globalConfirmModal.message"></p>
                <form :action="globalConfirmModal.action" method="POST" class="mt-6 flex justify-end gap-3">
                    <?php echo csrf_field(); ?>
                    <template x-if="globalConfirmModal.method !== 'POST'"><input type="hidden" name="_method" :value="globalConfirmModal.method"></template>
                    <button type="button" @click="closeGlobalConfirmModal()" class="px-4 py-2.5 rounded-xl btn-secondary-gradient text-xs font-bold">Batal</button>
                    <button type="submit" :class="globalConfirmModal.danger ? 'btn-danger' : 'bg-amber-600 hover:bg-amber-700'" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white" x-text="globalConfirmModal.submitLabel"></button>
                </form>
            </div>
        </div>
    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <script src="<?php echo e(asset('js/spreadsheet-worksheet.js')); ?>?v=<?php echo e(filemtime(public_path('js/spreadsheet-worksheet.js'))); ?>"></script>
</body>
</html>
<?php /**PATH C:\Users\Crissencio Dheni S\Documents\JOB 1\resources\views/layouts/app.blade.php ENDPATH**/ ?>