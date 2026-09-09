<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0DA4CE">
    <meta name="description" content="Isa SmartWork - Sistem manajemen operasional dan aktivitas sales">

    <title>Isa SmartWork</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/images/logo-isa-smartwork.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Figtree', 'sans-serif'] },
                    animation: { 'skeleton': 'skeleton 1.5s ease-in-out infinite' },
                    keyframes: { skeleton: { '0%, 100%': { opacity: '1' }, '50%': { opacity: '0.4' } } },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        @keyframes logo-in {
            0% { opacity: 0; transform: translateY(10px) scale(0.92); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-logo-in { animation: logo-in 0.6s cubic-bezier(0.22, 1, 0.36, 1) both; }

        @keyframes fade-up {
            0% { opacity: 0; transform: translateY(14px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up { animation: fade-up 0.6s cubic-bezier(0.22, 1, 0.36, 1) both; }

        @media (prefers-reduced-motion: reduce) {
            .animate-logo-in, .animate-fade-up { animation: none !important; }
            *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
        }

        .checkbox-input:checked + .checkbox-box {
            background-color: #0DA4CE;
            border-color: #0DA4CE;
        }
        .checkbox-box svg {
            opacity: 0;
            transform: scale(0.6);
            transition: opacity 150ms ease, transform 150ms ease;
        }
        .checkbox-input:checked + .checkbox-box svg {
            opacity: 1;
            transform: scale(1);
        }

        @media (min-width: 1024px) and (max-height: 740px) {
            .login-card { padding: 2rem !important; }
            .login-form-compact { margin-top: 1.25rem; }
            .login-form-compact > * + * { margin-top: 1.25rem; }
        }
    </style>
</head>
<body class="min-h-screen font-sans antialiased bg-gray-50">

<div class="flex min-h-screen">
    {{-- BRANDING PANEL (desktop) --}}
    <aside class="hidden lg:flex lg:w-[46%] xl:w-[44%] relative flex-col justify-between overflow-hidden px-12 xl:px-16 py-12 xl:py-14 text-white">
        <div class="absolute inset-0 bg-gradient-to-br from-[#054152] via-[#0a6077] to-[#0DA4CE]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_8%,rgba(255,255,255,0.16),transparent_40%)]"></div>
        <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-white/[0.04] blur-3xl"></div>
        <div class="absolute -bottom-44 -left-24 w-[30rem] h-[30rem] rounded-full bg-[#06465b]/50 blur-3xl"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNCkiPjxwYXRoIGQ9Ik0zNiAzNHYtNGgtMnY0aC00djJoNHY0aDJ2LTRoNHYtMmgtNHptMC0zMFYwaC0ydjRoLTR2Mmg0djRoMnYtNGg0di0yaC00eiIvPjwvZz48L2c+PC9zdmc+')] opacity-30"></div>

        <div class="relative flex items-center gap-4 xl:gap-5">
            <div class="relative shrink-0">
                <div class="absolute -inset-5 xl:-inset-6 rounded-full bg-cyan-300/15 blur-2xl opacity-70"></div>
                <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="Logo Isa SmartWork"
                     class="relative h-16 xl:h-20 w-auto object-contain drop-shadow-lg animate-logo-in">
            </div>
            <div class="animate-fade-up" style="animation-delay:100ms">
                <p class="text-xl xl:text-2xl font-extrabold tracking-tight leading-none">Isa SmartWork</p>
                <p class="mt-1.5 text-xs text-white/60 tracking-wide">PT ISA TRI SELARAS GEMILANG</p>
            </div>
        </div>

        <div class="relative flex-1 flex flex-col justify-center max-w-md">
            <h2 class="text-2xl xl:text-3xl font-bold text-white leading-snug tracking-tight animate-fade-up" style="animation-delay:240ms">
                Satu Platform untuk Aktivitas Operasional
            </h2>
            <p class="mt-4 text-sm text-white/70 leading-relaxed animate-fade-up" style="animation-delay:320ms">
                Kelola presensi, kunjungan, rute, dan aktivitas sales secara lebih terstruktur dalam satu sistem.
            </p>

            <ul class="mt-8 divide-y divide-white/10 animate-fade-up" style="animation-delay:420ms">
                <li class="py-3.5">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-teal-100/80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-semibold">Presensi &amp; Aktivitas</p>
                    </div>
                    <p class="mt-1.5 text-xs text-white/60 leading-relaxed pl-[26px]">Pemantauan aktivitas kerja secara terstruktur.</p>
                </li>
                <li class="py-3.5">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-teal-100/80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm font-semibold">Kunjungan &amp; Rute</p>
                    </div>
                    <p class="mt-1.5 text-xs text-white/60 leading-relaxed pl-[26px]">Perencanaan dan pencatatan kunjungan sales.</p>
                </li>
                <li class="py-3.5">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-teal-100/80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-sm font-semibold">Laporan Terintegrasi</p>
                    </div>
                    <p class="mt-1.5 text-xs text-white/60 leading-relaxed pl-[26px]">Data aktivitas tersusun dalam laporan yang mudah dipantau.</p>
                </li>
            </ul>
        </div>

        <div class="relative text-xs text-white/50">
            &copy; {{ date('Y') }} PT ISA TRI SELARAS GEMILANG
        </div>
    </aside>

    {{-- LOGIN AREA --}}
    <main class="flex-1 flex flex-col bg-[#f4f8fa] relative overflow-y-auto">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_72%_18%,rgba(13,164,206,0.07),transparent_55%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_85%,rgba(13,164,206,0.05),transparent_50%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_42%,rgba(255,255,255,0.85),transparent_55%)]"></div>
        <div class="relative w-full max-w-[540px] mx-auto my-auto px-4 sm:px-6 lg:px-12 py-8 sm:py-10">
            {{-- Mobile Brand (kept for other auth pages; on login the branding lives inside the card) --}}
            @if (! request()->routeIs('login'))
            <div class="lg:hidden flex flex-col items-center text-center mb-8">
                <div class="relative">
                    <div class="absolute -inset-5 rounded-full bg-[#0DA4CE]/15 blur-2xl"></div>
                    <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="Logo Isa SmartWork" class="relative h-16 w-auto object-contain animate-logo-in drop-shadow-sm">
                </div>
                <h1 class="mt-4 text-2xl font-extrabold text-gray-900 tracking-tight animate-fade-up" style="animation-delay:150ms">Isa SmartWork</h1>
                <p class="mt-1 text-sm text-gray-500 animate-fade-up tracking-wide" style="animation-delay:250ms">PT ISA TRI SELARAS GEMILANG</p>
            </div>
            @endif

            <div class="w-full max-w-md mx-auto">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>

</body>
</html>
