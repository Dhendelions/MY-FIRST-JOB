<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F5F7FA]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dadhen Mandiri' }} — Aplikasi Spreadsheet Kolaboratif Modern</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#142B4A',
                        'primary-hover': '#1B355A',
                        secondary: '#526780',
                        'page-bg': '#F5F7FA',
                        surface: '#FFFFFF',
                        'border-soft': '#E5EAF0',
                        'text-main': '#263445',
                        'text-muted': '#7B8794',
                        navy: {
                            950: '#060e1b',
                            900: '#142B4A',
                            800: '#1B355A',
                            700: '#234472',
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

    <style>
        body {
            background-color: #F5F7FA;
            color: #263445;
        }

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
        @media (prefers-reduced-motion: reduce) {
            .page-transition {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body class="h-full antialiased font-sans flex page-transition bg-[#F5F7FA]">
    @yield('content')
</body>
</html>
