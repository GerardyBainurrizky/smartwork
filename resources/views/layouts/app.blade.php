<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="appShell()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0DA4CE">
    <meta name="description" content="ISA SmartWork - Enterprise Agricultural Field Management">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SmartWork">

    <title>ISA SmartWork</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/images/logo-isa-smartwork.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- Apply theme & sidebar state synchronously before first paint to prevent dark/light blink and dropdown jump --}}
    <script>
        (function () {
            try {
                var swDark = localStorage.getItem('darkMode') === 'true';
                var el = document.documentElement;
                if (swDark) {
                    el.classList.add('dark');
                } else {
                    el.classList.remove('dark');
                }
                var swTc = document.querySelector('meta[name="theme-color"]');
                if (swTc) { swTc.setAttribute('content', swDark ? '#0b1220' : '#0DA4CE'); }

                // Restore persistent sidebar dropdown states before first paint
                var activeGroup = @json(
                    request()->routeIs('admin.routes.*') || request()->routeIs('admin.driver.routes.*') || request()->routeIs('admin.visits.*') || request()->routeIs('admin.driver.visits.*') || request()->routeIs('admin.attendance.*') ? 'operasional' : (
                    request()->routeIs('admin.users.*') || request()->routeIs('admin.stores.*') ? 'manajemen' : (
                    request()->routeIs('admin.reports.*') ? 'pelaporan' : (
                    request()->routeIs('admin.notifications.*') ? 'sistem' : (
                    request()->routeIs('admin.archives.*') ? 'pemeliharaan' : null
                )))));

                if (activeGroup) {
                    try { localStorage.setItem('sw_sb_' + activeGroup, 'true'); } catch (e) {}
                }

                var groups = ['operasional', 'manajemen', 'pelaporan', 'sistem', 'pemeliharaan'];
                var css = '';
                groups.forEach(function (g) {
                    var isStoredOpen = localStorage.getItem('sw_sb_' + g) === 'true';
                    var isCurrentActive = (g === activeGroup);
                    if (!isStoredOpen && !isCurrentActive) {
                        css += '#sw-sb-content-' + g + ' { display: none !important; }\n';
                    }
                });
                if (css) {
                    var s = document.createElement('style');
                    s.id = 'sw-sb-prepaint';
                    s.textContent = css;
                    document.head.appendChild(s);
                }
            } catch (e) {}
        })();
    </script>
    <style>
        /* Theme background & form fallback: applied before Tailwind CDN injects utilities, prevents dark/light flash and input repaint */
        html { background-color: #f9fafb; color: #111827; color-scheme: light; }
        html.dark { background-color: #030712; color: #f9fafb; color-scheme: dark; }
        html.dark body { background-color: #030712; color: #f9fafb; }
        html.dark .sw-card, html.dark .bg-white { background-color: #111827 !important; border-color: #1f2937 !important; }
        html.dark .sw-input, html.dark input:not([type="checkbox"]):not([type="radio"]), html.dark textarea, html.dark select {
            background-color: #111827 !important;
            border-color: #374151 !important;
            color: #e5e7eb !important;
        }
        html.dark select option {
            background-color: #111827 !important;
            color: #e5e7eb !important;
        }
        [x-cloak] { display: none !important; }

        /* Critical Mobile Sidebar Positioning: Prevents Sidebar Flash / Flicker during page transitions on mobile */
        @media (max-width: 1023px) {
            .sw-sidebar {
                display: flex !important;
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                z-index: 40 !important;
                transform: translateX(-100%) !important;
                visibility: hidden !important;
                pointer-events: none !important;
                transition: none !important;
            }
            .sw-sidebar.sw-sidebar-open {
                transform: translateX(0) !important;
                visibility: visible !important;
                pointer-events: auto !important;
                transition: transform .3s ease-out, visibility 0s linear !important;
            }

            /* Prevent mobile browser automatic zoom on form input focus (requires min 16px on mobile) */
            input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="hidden"]):not([type="range"]),
            textarea,
            select,
            .sw-input {
                font-size: 16px !important;
            }
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Figtree', 'sans-serif'] },
                    spacing: {
                        '68': '17rem',
                        '4.5': '1.125rem',
                    },
                    animation: { 'skeleton': 'skeleton 1.5s ease-in-out infinite', 'fade-in': 'fadeIn 0.5s ease-out', 'slide-up': 'slideUp 0.4s ease-out' },
                    keyframes: {
                        skeleton: { '0%, 100%': { opacity: '1' }, '50%': { opacity: '0.4' } },
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">

<div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
    {{-- Overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity x-cloak class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm lg:hidden"></div>

    {{-- Premium Sidebar --}}
    <aside :class="sidebarOpen ? 'sw-sidebar-open' : ''"
        class="sw-sidebar fixed inset-y-0 left-0 z-40 w-64 -translate-x-full flex flex-col bg-white dark:bg-gray-900 border-r border-gray-200/70 dark:border-gray-800 transform transition-transform duration-300 ease-out lg:translate-x-0 lg:static lg:inset-auto lg:z-auto shadow-sm">

        <div class="flex items-center gap-3.5 h-16 md:h-[4.5rem] px-5 border-b border-gray-100 dark:border-gray-800 shrink-0">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center shadow-sm shadow-[#0DA4CE]/20 shrink-0 overflow-hidden">
                <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="ISA SmartWork" class="w-8 h-8 object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-[15px] font-extrabold text-gray-900 dark:text-white tracking-tight leading-tight truncate">ISA SmartWork</p>
                <p class="text-[10px] font-medium text-gray-400 dark:text-gray-500 leading-tight mt-0.5 truncate">PT ISA Tri Selaras Gemilang</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto scrollbar-thin px-3 py-4 space-y-0.5">
            <div class="px-3 pb-1.5 pt-1">
                <p class="text-[10px] font-bold text-gray-400/80 dark:text-gray-500 uppercase tracking-[0.14em]">Menu Utama</p>
            </div>
            <x-sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="layout-dashboard">
                Dashboard
            </x-sidebar-link>
            @hasanyrole('sales|staff|driver')
            <x-sidebar-link href="{{ route('attendance.index') }}" :active="request()->routeIs('attendance.*')" icon="clock">
                Presensi
            </x-sidebar-link>
            @endhasanyrole

            @hasanyrole('staff')
            <x-sidebar-link href="{{ route('staff.report.index') }}" :active="request()->routeIs('staff.report.*')" icon="file-text">
                Laporan Presensi
            </x-sidebar-link>
            @php
                $staffUnreadCount = Auth::user()->unreadNotifications()->count();
                $staffUnreadLabel = $staffUnreadCount > 99 ? '99+' : ($staffUnreadCount > 0 ? (string)$staffUnreadCount : null);
            @endphp
            <x-sidebar-link href="{{ route('notifications.index') }}" :active="request()->routeIs('notifications.*')" icon="bell" :badge="$staffUnreadLabel">
                Notifikasi
            </x-sidebar-link>
            @endhasanyrole

            @hasanyrole('sales')
            <x-sidebar-link href="{{ route('route.index') }}" :active="request()->routeIs('route.*') && !request()->routeIs('route.history')" icon="route">
                Rencana Kunjungan
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('visit.index') }}" :active="request()->routeIs('visit.index') || request()->routeIs('visit.show') || request()->routeIs('visit.check-in-form') || request()->routeIs('visit.check-out-form')" icon="store">
                Kunjungan Hari Ini
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('visit.history') }}" :active="request()->routeIs('visit.history')" icon="clipboard-list">
                Riwayat Kunjungan
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('sales.stores.index') }}" :active="request()->routeIs('sales.stores.*')" icon="credit-card">
                Toko &amp; Piutang
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('stores.submissions.index') }}" :active="request()->routeIs('stores.submissions.*')" icon="plus-circle">
                Ajukan Toko Baru
            </x-sidebar-link>
            @php
                $salesUnreadCount = Auth::user()->unreadNotifications()->count();
                $salesUnreadLabel = $salesUnreadCount > 99 ? '99+' : ($salesUnreadCount > 0 ? (string)$salesUnreadCount : null);
            @endphp
            <x-sidebar-link href="{{ route('notifications.index') }}" :active="request()->routeIs('notifications.*')" icon="bell" :badge="$salesUnreadLabel">
                Notifikasi
            </x-sidebar-link>
            @endhasanyrole

            @hasrole('driver')
            <x-sidebar-link href="{{ route('route.index') }}" :active="request()->routeIs('route.*') && !request()->routeIs('route.history')" icon="route">
                Rencana Pengiriman
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('visit.index') }}" :active="request()->routeIs('visit.index') || request()->routeIs('visit.show') || request()->routeIs('visit.check-in-form') || request()->routeIs('visit.check-out-form')" icon="store">
                Pengiriman Hari Ini
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('visit.history') }}" :active="request()->routeIs('visit.history')" icon="clipboard-list">
                Riwayat Pengiriman
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('driver.stores.index') }}" :active="request()->routeIs('driver.stores.*')" icon="store">
                Daftar Toko
            </x-sidebar-link>
            @php
                $driverUnreadCount = Auth::user()->unreadNotifications()->count();
                $driverUnreadLabel = $driverUnreadCount > 99 ? '99+' : ($driverUnreadCount > 0 ? (string)$driverUnreadCount : null);
            @endphp
            <x-sidebar-link href="{{ route('notifications.index') }}" :active="request()->routeIs('notifications.*')" icon="bell" :badge="$driverUnreadLabel">
                Notifikasi
            </x-sidebar-link>
            @endhasrole

            @hasanyrole('super-admin|admin')
            @php
                $sidebarUnreadCount = Auth::user()->unreadNotifications()->count();
                $sidebarUnreadLabel = $sidebarUnreadCount > 99 ? '99+' : ($sidebarUnreadCount > 0 ? (string)$sidebarUnreadCount : null);

                // Tentukan group yang aktif berdasarkan route saat ini
                $grpOperasional = request()->routeIs('admin.routes.*') || request()->routeIs('admin.driver.routes.*')
                    || request()->routeIs('admin.visits.*') || request()->routeIs('admin.driver.visits.*')
                    || request()->routeIs('admin.attendance.*');
                $grpManajemen   = request()->routeIs('admin.users.*') || request()->routeIs('admin.stores.*');
                $grpPelaporan   = request()->routeIs('admin.reports.*');
                $grpSistem      = request()->routeIs('admin.notifications.*');
                $grpPemeliharaan = request()->routeIs('admin.archives.*');
            @endphp

            {{-- Group: OPERASIONAL --}}
            <div x-data="{
                open: {{ $grpOperasional ? 'true' : "(localStorage.getItem('sw_sb_operasional') === 'true')" }},
                toggle() {
                    this.open = !this.open;
                    try { localStorage.setItem('sw_sb_operasional', this.open ? 'true' : 'false'); } catch (e) {}
                }
            }" class="pt-4">
                <button type="button" @click="toggle()"
                    class="w-full flex items-center justify-between px-3 py-1.5 group">
                    <p class="text-[11px] font-bold text-gray-400/80 dark:text-gray-500 uppercase tracking-[0.14em] group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors">Operasional</p>
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-600 transition-transform duration-200 group-hover:text-gray-500 dark:group-hover:text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sw-sb-content-operasional" x-show="open" class="mt-1 space-y-0.5">
                    <x-sidebar-link href="{{ route('admin.routes.index') }}" :active="in_array(request()->get('role_hint'), ['driver'], true) ? false : (request()->routeIs('admin.routes.*') && !request()->routeIs('admin.routes.report'))" icon="route">
                        Rencana Kunjungan
                    </x-sidebar-link>
                    <x-sidebar-link href="{{ route('admin.driver.routes.index') }}" :active="request()->routeIs('admin.driver.routes.*') && !request()->routeIs('admin.driver.routes.report')" icon="truck">
                        Rencana Pengiriman
                    </x-sidebar-link>
                    <x-sidebar-link href="{{ route('admin.visits.index') }}" :active="in_array(request()->get('role_hint'), ['driver'], true) ? false : request()->routeIs('admin.visits.*')" icon="building">
                        Kunjungan
                    </x-sidebar-link>
                    <x-sidebar-link href="{{ route('admin.driver.visits.index') }}" :active="request()->routeIs('admin.driver.visits.*')" icon="package">
                        Pengiriman
                    </x-sidebar-link>
                    <x-sidebar-link href="{{ route('admin.attendance.index') }}" :active="request()->routeIs('admin.attendance.*')" icon="clock">
                        Presensi
                    </x-sidebar-link>
                </div>
            </div>

            {{-- Group: MANAJEMEN --}}
            <div x-data="{
                open: {{ $grpManajemen ? 'true' : "(localStorage.getItem('sw_sb_manajemen') === 'true')" }},
                toggle() {
                    this.open = !this.open;
                    try { localStorage.setItem('sw_sb_manajemen', this.open ? 'true' : 'false'); } catch (e) {}
                }
            }" class="pt-3">
                <button type="button" @click="toggle()"
                    class="w-full flex items-center justify-between px-3 py-1.5 group">
                    <p class="text-[11px] font-bold text-gray-400/80 dark:text-gray-500 uppercase tracking-[0.14em] group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors">Manajemen</p>
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-600 transition-transform duration-200 group-hover:text-gray-500 dark:group-hover:text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sw-sb-content-manajemen" x-show="open" class="mt-1 space-y-0.5">
                    <x-sidebar-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')" icon="users">
                        Pengguna
                    </x-sidebar-link>
                    <x-sidebar-link href="{{ route('admin.stores.index') }}" :active="request()->routeIs('admin.stores.*')" icon="store">
                        Master Toko
                    </x-sidebar-link>
                    @php
                        $pendingSubCount = \App\Models\StoreSubmission::where('status', 'pending')->count();
                        $pendingSubBadge = $pendingSubCount > 0 ? (string)$pendingSubCount : null;
                    @endphp
                    <x-sidebar-link href="{{ route('admin.stores.submissions.index') }}" :active="request()->routeIs('admin.stores.submissions.*')" icon="inbox" :badge="$pendingSubBadge">
                        Pengajuan Toko
                    </x-sidebar-link>
                    <x-sidebar-link href="{{ route('admin.receivables.index') }}" :active="request()->routeIs('admin.receivables.*')" icon="credit-card">
                        Piutang Toko
                    </x-sidebar-link>
                </div>
            </div>

            {{-- Group: PELAPORAN --}}
            <div x-data="{
                open: {{ $grpPelaporan ? 'true' : "(localStorage.getItem('sw_sb_pelaporan') === 'true')" }},
                toggle() {
                    this.open = !this.open;
                    try { localStorage.setItem('sw_sb_pelaporan', this.open ? 'true' : 'false'); } catch (e) {}
                }
            }" class="pt-3">
                <button type="button" @click="toggle()"
                    class="w-full flex items-center justify-between px-3 py-1.5 group">
                    <p class="text-[11px] font-bold text-gray-400/80 dark:text-gray-500 uppercase tracking-[0.14em] group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors">Pelaporan</p>
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-600 transition-transform duration-200 group-hover:text-gray-500 dark:group-hover:text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sw-sb-content-pelaporan" x-show="open" class="mt-1 space-y-0.5">
                    <x-sidebar-link href="{{ route('admin.reports.index') }}" :active="request()->routeIs('admin.reports.*')" icon="file-text">
                        Laporan
                    </x-sidebar-link>
                </div>
            </div>

            {{-- Group: SISTEM --}}
            <div x-data="{
                open: {{ $grpSistem ? 'true' : "(localStorage.getItem('sw_sb_sistem') === 'true')" }},
                toggle() {
                    this.open = !this.open;
                    try { localStorage.setItem('sw_sb_sistem', this.open ? 'true' : 'false'); } catch (e) {}
                }
            }" class="pt-3">
                <button type="button" @click="toggle()"
                    class="w-full flex items-center justify-between px-3 py-1.5 group">
                    <p class="text-[11px] font-bold text-gray-400/80 dark:text-gray-500 uppercase tracking-[0.14em] group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors">Sistem</p>
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-600 transition-transform duration-200 group-hover:text-gray-500 dark:group-hover:text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sw-sb-content-sistem" x-show="open" class="mt-1 space-y-0.5">
                    <x-sidebar-link href="{{ route('admin.notifications.index') }}" :active="request()->routeIs('admin.notifications.*')" icon="bell" :badge="$sidebarUnreadLabel">
                        Notifikasi
                    </x-sidebar-link>
                </div>
            </div>
            @endhasanyrole

            @hasrole('super-admin')
            {{-- Group: PEMELIHARAAN DATA --}}
            <div x-data="{
                open: {{ $grpPemeliharaan ? 'true' : "(localStorage.getItem('sw_sb_pemeliharaan') === 'true')" }},
                toggle() {
                    this.open = !this.open;
                    try { localStorage.setItem('sw_sb_pemeliharaan', this.open ? 'true' : 'false'); } catch (e) {}
                }
            }" class="pt-3">
                <button type="button" @click="toggle()"
                    class="w-full flex items-center justify-between px-3 py-1.5 group">
                    <p class="text-[11px] font-bold text-gray-400/80 dark:text-gray-500 uppercase tracking-[0.14em] group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors">Pemeliharaan Data</p>
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-600 transition-transform duration-200 group-hover:text-gray-500 dark:group-hover:text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sw-sb-content-pemeliharaan" x-show="open" class="mt-1 space-y-0.5">
                    <x-sidebar-link href="{{ route('admin.archives.index') }}" :active="request()->routeIs('admin.archives.*')" icon="archive">
                        Arsip &amp; Kelola Data
                    </x-sidebar-link>
                </div>
            </div>
            @endhasrole
        </nav>

        <div class="px-3 py-3 border-t border-gray-100 dark:border-gray-800 shrink-0">
            <x-sidebar-link href="{{ route('profile.edit') }}" :active="request()->routeIs('profile.*')" icon="settings">
                Profil Saya
            </x-sidebar-link>
        </div>

        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800">
            <p class="text-[10px] text-gray-400 dark:text-gray-500 text-center">PT ISA Tri Selaras Gemilang</p>
        </div>
    </aside>

    {{-- Main Content Area --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
        {{-- Top Bar --}}
        <header class="sticky top-0 z-20 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between h-14 px-4 sm:px-5 lg:px-4">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="p-2 -ml-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 lg:hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="ISA SmartWork" class="lg:hidden w-7 h-7 object-contain">
                    <div class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <span class="text-gray-300 dark:text-gray-600">/</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">@yield('breadcrumbs', 'Dashboard')</span>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <button @click="toggleDarkMode()" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <svg x-show="!darkMode" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="darkMode" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>

                    @hasanyrole('super-admin|admin|sales|staff|driver')
                    {{-- Notifications Dropdown --}}
                    <div class="relative" x-data="navbarNotifications()" x-init="init()">
                        <button @click="toggle()" type="button"
                            class="relative p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] transition"
                            aria-label="Notifications">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span x-show="unreadCount > 0" x-cloak
                                class="absolute top-1 right-1 min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center border-2 border-white dark:border-gray-900 shadow-sm"
                                x-text="unreadLabel">
                            </span>
                        </button>

                        <div x-show="open" @click.away="open = false" x-transition.opacity.scale.origin.top.right
                            class="absolute right-0 mt-2 w-80 sm:w-96 max-w-[calc(100vw-2rem)] bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 py-2 z-50 overflow-hidden" x-cloak>
                            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Notifikasi</h4>
                                    <span x-show="unreadCount > 0" x-cloak class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-cyan-50 dark:bg-cyan-950/40 text-[#0DA4CE] border border-cyan-200 dark:border-cyan-800" x-text="unreadCount + ' Baru'"></span>
                                </div>
                                @php
                                    $allNotificationsUrl = Auth::user()->hasAnyRole(['admin', 'super-admin']) ? route('admin.notifications.index') : route('notifications.index');
                                @endphp
                                <a href="{{ $allNotificationsUrl }}" class="text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#0DA4CE] dark:hover:text-cyan-300">
                                    Lihat Semua
                                </a>
                            </div>

                            {{-- Notification Items List --}}
                            <div class="max-h-80 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700/50 scrollbar-thin">
                                <template x-if="loading">
                                    <div class="p-4 text-center text-xs text-gray-400">
                                        Memuat notifikasi...
                                    </div>
                                </template>

                                <template x-if="!loading && items.length === 0">
                                    <div class="p-6 text-center text-xs text-gray-400 dark:text-gray-500">
                                        Tidak ada notifikasi aktivitas terbaru.
                                    </div>
                                </template>

                                <template x-for="item in items" :key="item.id">
                                    <a :href="item.url" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors relative"
                                       :class="!item.read ? 'bg-cyan-50/50 dark:bg-cyan-950/25' : ''">
                                        <div class="flex items-start gap-3">
                                            <div class="w-2 h-2 rounded-full mt-1.5 shrink-0"
                                                 :class="!item.read ? 'bg-[#0DA4CE]' : 'bg-transparent'"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs leading-tight truncate"
                                                   :class="!item.read ? 'font-bold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300'"
                                                   x-text="item.title"></p>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed" x-text="item.message"></p>
                                                <div class="flex items-center justify-between gap-2 mt-1.5 text-[10px] text-gray-400 dark:text-gray-500">
                                                    <span class="truncate font-semibold text-gray-600 dark:text-gray-300" x-text="item.info"></span>
                                                    <span class="shrink-0" x-text="item.time_ago"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>

                            <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 text-center">
                                <a href="{{ $allNotificationsUrl }}" class="text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-[#0DA4CE] dark:hover:text-[#0DA4CE]">
                                    Buka Halaman Pusat Notifikasi &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                    @endhasanyrole

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300 truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition.opacity.scale.origin.top.right
                            class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50" x-cloak>
                            <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Profil Saya
                            </a>
                            <form method="POST" action="{{ route('logout') }}" x-data="{ loggingOut: false }" @submit="if(loggingOut){ $event.preventDefault(); return false; } loggingOut = true;">
                                @csrf
                                <button type="submit" :disabled="loggingOut" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 transition disabled:opacity-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span x-text="loggingOut ? 'Keluar...' : 'Keluar'">Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 w-full min-w-0 px-5 sm:px-6 lg:px-8 py-5 lg:py-6">
            @isset($header)
                <div class="mb-5">
                    {{ $header }}
                </div>
            @endisset
            {{ $slot }}
        </main>

        <footer class="mt-auto shrink-0 py-3 text-center text-[10px] text-gray-400 dark:text-gray-600 border-t border-gray-100 dark:border-gray-800">
            &copy; {{ date('Y') }} PT ISA Tri Selaras Gemilang &middot; SmartWork v1.0
        </footer>
    </div>
</div>

{{-- Global Toast --}}
<div x-data="toastManager()" x-init="init()" @toast.window="show($event.detail.message, $event.detail.type)"
    x-show="visible" x-cloak x-transition
    class="fixed bottom-6 left-4 right-4 z-50 max-w-md mx-auto pointer-events-none">
    <div class="rounded-2xl px-5 py-3.5 shadow-xl border backdrop-blur-sm pointer-events-auto"
        :class="type === 'success' ? 'bg-green-50/95 dark:bg-green-950/95 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' :
            (type === 'error' ? 'bg-red-50/95 dark:bg-red-950/95 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200' :
            (type === 'warning' ? 'bg-amber-50/95 dark:bg-amber-950/95 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200' :
            'bg-gray-50/95 dark:bg-gray-900/95 border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200'))">
        <div class="flex items-center gap-3">
            <svg x-show="type === 'success'" class="w-5 h-5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="type === 'error'" class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="type === 'warning'" class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p class="font-medium text-sm" x-text="message"></p>
        </div>
    </div>
</div>

{{-- Session Timeout Guard --}}
@php
    $swSessionId = session()->getId();
    $swSessionFile = $swSessionId ? storage_path('framework/sessions/'.$swSessionId) : null;
    $swLastActivity = ($swSessionFile && is_file($swSessionFile)) ? (int) filemtime($swSessionFile) : time();
    $swExpiresAt = $swLastActivity + ((int) config('session.lifetime')) * 60;
    $swLifetimeSeconds = ((int) config('session.lifetime')) * 60;
@endphp
<div x-data="sessionGuard()" x-init="init()" x-cloak>

    {{-- Warning: approaching session expiry --}}
    <div x-show="warningVisible" x-cloak x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
        <div x-show="warningVisible" x-transition.scale.origin.center
            class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-100 dark:border-gray-700 p-6 sm:p-7">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 shrink-0 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Sesi Hampir Berakhir</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        Sesi Anda akan segera berakhir karena tidak ada aktivitas.
                    </p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                <button @click="logout()" :disabled="loggingOut" class="sw-btn sw-btn-ghost w-full sm:w-auto disabled:opacity-50">
                    <span x-show="!loggingOut">Keluar</span>
                    <span x-show="loggingOut">Keluar...</span>
                </button>
                <button @click="keepAlive()" :disabled="keepingAlive || loggingOut" class="sw-btn sw-btn-primary w-full sm:w-auto">
                    <svg x-show="keepingAlive" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-show="!keepingAlive">Tetap Masuk</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Session already expired --}}
    <div x-show="expiredVisible" x-cloak x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-100 dark:border-gray-700 p-6 sm:p-7 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="mt-4 text-base font-bold text-gray-900 dark:text-white">Sesi Berakhir</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.
            </p>
            <button @click="goLogin()" class="sw-btn sw-btn-primary w-full mt-6">Masuk Kembali</button>
        </div>
    </div>

    <form method="POST" action="{{ route('logout') }}" id="sw-session-logout-form" class="hidden">
        @csrf
    </form>
</div>

<script>
    window.SW_SESSION = {
        expiresAt: {{ $swExpiresAt }},
    };
</script>

<script>
    function appShell() {
        return {
            darkMode: localStorage.getItem('darkMode') === 'true',
            init() {
                if ('serviceWorker' in navigator) navigator.serviceWorker.register('/service-worker.js').catch(() => {});
                // Clean up early prepaint style after Alpine is active so interactive toggling works smoothly
                var prepaintStyle = document.getElementById('sw-sb-prepaint');
                if (prepaintStyle) prepaintStyle.remove();
            },
            toggleDarkMode() {
                this.darkMode = !this.darkMode;
                localStorage.setItem('darkMode', this.darkMode);
                if (this.darkMode) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                var themeColor = document.querySelector('meta[name="theme-color"]');
                if (themeColor) themeColor.setAttribute('content', this.darkMode ? '#0b1220' : '#0DA4CE');
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { darkMode: this.darkMode } }));
            }
        };
    }
    function toastManager() {
        return {
            visible: false, message: '', type: 'success', timer: null,
            init() { window.addEventListener('toast', (e) => this.show(e.detail.message, e.detail.type)); },
            show(m, t = 'success') {
                if (this.timer) clearTimeout(this.timer);
                this.message = m; this.type = t; this.visible = true;
                this.timer = setTimeout(() => this.visible = false, 4000);
            }
        };
    }
    function navbarNotifications() {
        return {
            open: false,
            loading: false,
            unreadCount: {{ Auth::user() ? Auth::user()->unreadNotifications()->count() : 0 }},
            unreadLabel: '{{ Auth::user() && Auth::user()->unreadNotifications()->count() > 99 ? "99+" : (Auth::user() ? Auth::user()->unreadNotifications()->count() : 0) }}',
            items: [],
            init() {
                // Initial fetch if authenticated
                this.fetchData();
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.fetchData();
                }
            },
            async fetchData() {
                this.loading = true;
                try {
                    const dropdownUrl = '{{ Auth::user() && Auth::user()->hasAnyRole(["admin", "super-admin"]) ? route("admin.notifications.dropdown") : route("notifications.dropdown") }}';
                    const res = await fetch(dropdownUrl, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.unreadCount = data.unread_count;
                        this.unreadLabel = data.unread_label;
                        this.items = data.items;
                    }
                } catch (e) {
                    console.error('Failed to load notifications', e);
                } finally {
                    this.loading = false;
                }
            }
        };
    }
    function sessionGuard() {
        return {
            expiresAt: (window.SW_SESSION && window.SW_SESSION.expiresAt) ? window.SW_SESSION.expiresAt * 1000 : null,
            warnMs: 10 * 60 * 1000,
            lifetimeSeconds: {{ $swLifetimeSeconds }},
            warningVisible: false,
            expiredVisible: false,
            keepingAlive: false,
            loggingOut: false,
            shown: false,
            warnTimer: null,
            expiredTimer: null,
            init() {
                if (!this.expiresAt) return;
                document.addEventListener('visibilitychange', () => { if (!document.hidden) this.schedule(); });
                this.schedule();
            },
            schedule() {
                if (this.warnTimer) clearTimeout(this.warnTimer);
                if (this.expiredTimer) clearTimeout(this.expiredTimer);
                const msToExpiry = this.expiresAt - Date.now();
                if (msToExpiry <= 0) { this.showExpired(); return; }
                this.warnTimer = setTimeout(() => this.showWarning(), Math.max(0, msToExpiry - this.warnMs));
                this.expiredTimer = setTimeout(() => this.showExpired(), msToExpiry);
            },
            showWarning() {
                if (this.shown) return;
                this.shown = true;
                this.warningVisible = true;
            },
            showExpired() {
                this.shown = true;
                this.warningVisible = false;
                this.expiredVisible = true;
            },
            async keepAlive() {
                if (this.keepingAlive || this.loggingOut) return;
                this.keepingAlive = true;
                try {
                    const res = await fetch('{{ route('session.keep-alive') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        if (res.status === 401 || res.status === 419) { this.showExpired(); return; }
                        window.showToast('Gagal memperpanjang sesi. Periksa koneksi Anda.', 'error');
                        return;
                    }
                    this.expiresAt = (data.expires_at || Math.floor(Date.now() / 1000) + this.lifetimeSeconds) * 1000;
                    this.shown = false;
                    this.warningVisible = false;
                    this.schedule();
                    window.showToast('Sesi diperpanjang.', 'success');
                } catch (e) {
                    window.showToast('Gagal memperpanjang sesi. Periksa koneksi Anda.', 'error');
                } finally {
                    this.keepingAlive = false;
                }
            },
            logout() {
                if (this.loggingOut) return;
                this.loggingOut = true;
                const form = document.getElementById('sw-session-logout-form');
                if (form) {
                    form.submit();
                }
            },
            goLogin() {
                window.location.href = '{{ route('login') }}';
            },
        };
    }
    window.showToast = (m, t) => window.dispatchEvent(new CustomEvent('toast', { detail: { message: m, type: t } }));
</script>
<style>
        [x-cloak] { display: none !important; }
        @media (max-width: 1023px) {
            .sw-sidebar {
                transform: translateX(-100%) !important;
                visibility: hidden;
                transition: transform .3s ease, visibility 0s linear .3s;
            }
            .sw-sidebar.sw-sidebar-open {
                transform: translateX(0) !important;
                visibility: visible;
                transition: transform .3s ease, visibility 0s;
            }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; } }
        .scrollbar-thin::-webkit-scrollbar { width: 5px; height: 5px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 999px; }
        .dark .scrollbar-thin::-webkit-scrollbar-thumb { background: #374151; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }

        @media (min-width: 1024px) {
            .sw-report-filter-grid { grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: 1rem; row-gap: 1rem; }
            .sw-report-filter-users .sw-report-filter-role { grid-column: span 4; }
            .sw-report-filter-users .sw-report-filter-status { grid-column: span 4; }
            .sw-report-filter-users .sw-report-filter-actions { grid-column: span 2; justify-content: flex-start; }
            .sw-report-filter-date-sales .sw-report-filter-dates { grid-column: span 4; }
            .sw-report-filter-date-sales .sw-report-filter-sales { grid-column: span 5; }
            .sw-report-filter-date-sales .sw-report-filter-actions { grid-column: span 2; justify-content: flex-start; }
            .sw-report-filter-routes .sw-report-filter-grid,
            .sw-report-filter-visits .sw-report-filter-grid,
            .sw-report-filter-driver-routes .sw-report-filter-grid,
            .sw-report-filter-driver-visits .sw-report-filter-grid { grid-template-columns: minmax(180px, 195px) minmax(180px, 195px) minmax(240px, 1fr) minmax(120px, max-content); }
            .sw-report-filter-routes .sw-report-filter-dates,
            .sw-report-filter-visits .sw-report-filter-dates,
            .sw-report-filter-driver-routes .sw-report-filter-dates,
            .sw-report-filter-driver-visits .sw-report-filter-dates,
            .sw-report-filter-attendance .sw-report-filter-dates { display: contents; }
            .sw-report-filter-routes .sw-report-filter-dates > div,
            .sw-report-filter-routes .sw-report-filter-sales,
            .sw-report-filter-routes .sw-report-filter-actions,
            .sw-report-filter-visits .sw-report-filter-dates > div,
            .sw-report-filter-visits .sw-report-filter-sales,
            .sw-report-filter-visits .sw-report-filter-actions,
            .sw-report-filter-driver-routes .sw-report-filter-dates > div,
            .sw-report-filter-driver-routes .sw-report-filter-sales,
            .sw-report-filter-driver-routes .sw-report-filter-actions,
            .sw-report-filter-driver-visits .sw-report-filter-dates > div,
            .sw-report-filter-driver-visits .sw-report-filter-sales,
            .sw-report-filter-driver-visits .sw-report-filter-actions { grid-column: auto; }
            .sw-report-filter-routes .sw-report-filter-actions,
            .sw-report-filter-visits .sw-report-filter-actions,
            .sw-report-filter-driver-routes .sw-report-filter-actions,
            .sw-report-filter-driver-visits .sw-report-filter-actions { justify-content: flex-start; }
            .sw-report-filter-attendance .sw-report-filter-grid { grid-template-columns: minmax(180px, 190px) minmax(180px, 190px) minmax(220px, 1.3fr) minmax(180px, 1fr) minmax(120px, max-content); }
            .sw-report-filter-attendance .sw-report-filter-dates > div,
            .sw-report-filter-attendance .sw-report-filter-sales,
            .sw-report-filter-attendance .sw-report-filter-status,
            .sw-report-filter-attendance .sw-report-filter-actions { grid-column: auto; }
            .sw-report-filter-attendance .sw-report-filter-actions { justify-content: flex-start; }
            .sw-report-filter-stores form > .grid { column-gap: 1rem; row-gap: 1rem; }
            .sw-report-filter-stores form > .flex { justify-content: flex-start; }
            .sw-report-filter-stores form > .flex > button { min-width: 120px; }
        }

        /* Make date/month/time picker indicators visible and bright in dark mode */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="month"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.85;
            transition: opacity 0.15s ease, filter 0.15s ease;
        }
        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="month"]::-webkit-calendar-picker-indicator:hover,
        input[type="time"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }
        .dark input[type="date"],
        .dark input[type="month"],
        .dark input[type="time"],
        html.dark input[type="date"],
        html.dark input[type="month"],
        html.dark input[type="time"] {
            color-scheme: light !important;
        }
        .dark input[type="date"]::-webkit-calendar-picker-indicator,
        .dark input[type="month"]::-webkit-calendar-picker-indicator,
        .dark input[type="time"]::-webkit-calendar-picker-indicator,
        html.dark input[type="date"]::-webkit-calendar-picker-indicator,
        html.dark input[type="month"]::-webkit-calendar-picker-indicator,
        html.dark input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(1.2) contrast(1.1) !important;
            opacity: 0.95 !important;
        }
        .dark input[type="date"]::-webkit-calendar-picker-indicator:hover,
        .dark input[type="month"]::-webkit-calendar-picker-indicator:hover,
        .dark input[type="time"]::-webkit-calendar-picker-indicator:hover,
        html.dark input[type="date"]::-webkit-calendar-picker-indicator:hover,
        html.dark input[type="month"]::-webkit-calendar-picker-indicator:hover,
        html.dark input[type="time"]::-webkit-calendar-picker-indicator:hover {
            filter: invert(1) brightness(1.4) contrast(1.2) !important;
            opacity: 1 !important;
        }

        /* Keep dark-mode inputs, textareas & selects readable when browsers autofill them */
        .dark input:-webkit-autofill,
        .dark input:-webkit-autofill:hover,
        .dark input:-webkit-autofill:focus,
        .dark textarea:-webkit-autofill,
        .dark textarea:-webkit-autofill:hover,
        .dark textarea:-webkit-autofill:focus,
        .dark select:-webkit-autofill,
        .dark select:-webkit-autofill:hover,
        .dark select:-webkit-autofill:focus {
            -webkit-text-fill-color: #e5e7eb !important;
            -webkit-box-shadow: 0 0 0 1000px #111827 inset !important;
            transition: background-color 9999s ease-in-out 0s;
            caret-color: #0DA4CE;
        }

        /* ---- SW Design System ---- */
        :root {
            --sw-brand: #0DA4CE;
            --sw-brand-dark: #097A99;
            --sw-accent: #90F022;
            --sw-border: #e8ecf1;
            --sw-surface: #ffffff;
        }

        .sw-card {
            background: var(--sw-surface);
            border: 1px solid var(--sw-border);
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06);
            transition: box-shadow .18s ease, border-color .18s ease;
        }
        .sw-card-hover:hover {
            border-color: #dbe3ec;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06), 0 2px 4px rgba(15, 23, 42, 0.04);
        }
        .dark .sw-card { background: #111827; border-color: #1f2937; }

        .sw-input {
            width: 100%;
            border: 1.5px solid #d4dbe4;
            border-radius: 0.75rem;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            font-weight: 400;
            color: #0f172a;
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease;
            outline: none;
            line-height: 1.25rem;
        }
        .sw-input:hover { border-color: #c2ccd6; }
        .sw-input:focus {
            border-color: var(--sw-brand);
            box-shadow: 0 0 0 3px rgba(13, 164, 206, 0.14);
        }
        .sw-input::placeholder { color: #9ca3af; }
        .dark .sw-input { background: #111827; border-color: #374151; color: #e5e7eb; }
        .dark .sw-input:hover { border-color: #4b5563; }
        .dark .sw-input::placeholder { color: #6b7280; }

        @media (max-width: 1023px) {
            .sw-input,
            input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="hidden"]):not([type="range"]),
            textarea,
            select {
                font-size: 16px !important;
            }
        }

        /* Dark mode select options & native elements */
        .dark select.sw-input option,
        .dark select option {
            background-color: #111827 !important;
            color: #f3f4f6 !important;
        }
        .dark input[type="radio"],
        .dark input[type="checkbox"] {
            background-color: #1f2937;
            border-color: #4b5563;
        }

        /* Normalize date inputs so they match select/text width, height & typography on mobile */
        .sw-input[type="date"],
        .sw-input[type="month"],
        .sw-input[type="time"] {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            min-height: 2.75rem;
        }
        select.sw-input { min-height: 2.75rem; }
        .sw-input[type="date"]::-webkit-datetime-edit {
            padding: 0;
            line-height: inherit;
            font-size: inherit;
            color: inherit;
        }
        .sw-input[type="date"]::-webkit-datetime-edit-fields-wrapper {
            padding: 0;
            line-height: inherit;
        }
        .sw-input[type="date"]::-webkit-date-and-time-value {
            line-height: inherit;
            font-size: inherit;
            color: inherit;
        }
        .sw-input[type="date"]::-webkit-calendar-picker-indicator {
            margin-left: auto;
            padding-left: 1rem;
            opacity: 0.8;
            cursor: pointer;
        }

        /* Report filter card (admin report pages): force full-width, equal, touch-friendly controls on mobile */
        #sw-filter-card input,
        #sw-filter-card select {
            display: block;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            min-height: 3rem;
            box-sizing: border-box;
        }
        #sw-filter-card input[type="date"] {
            display: block;
            width: 100% !important;
            min-height: 3rem;
            box-sizing: border-box;
            cursor: text;
        }
        #sw-filter-card input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.85;
        }
        @media (max-width: 640px) {
            #sw-filter-card input[type="date"] {
                font-size: clamp(0.9375rem, 4.2vw, 1rem);
                height: 2.875rem;
                min-height: 2.875rem;
            }
            #sw-filter-card select {
                font-size: 0.9375rem;
                height: 2.875rem;
                min-height: 2.875rem;
            }
            #sw-filter-card input[type="text"] {
                font-size: 16px;
            }
        }

        /* User list filter card (admin/users): force full-width, equal controls on mobile/tablet */
        #sw-user-filter-card form,
        #sw-user-filter-card .sw-filter-field {
            width: 100%;
            min-width: 0;
        }
        @media (max-width: 1023px) {
            #sw-user-filter-card .sw-filter-field,
            #sw-user-filter-card input,
            #sw-user-filter-card select {
                display: block;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                box-sizing: border-box;
            }
            #sw-user-filter-card input[type="text"],
            #sw-user-filter-card input[type="search"] {
                min-height: 2.75rem;
            }
            #sw-user-filter-card select.sw-user-filter-select,
            #sw-user-filter-card select {
                min-height: 3rem;
                padding-right: 2.5rem;
                text-align: left;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 0.75rem center;
                background-repeat: no-repeat;
                background-size: 1.125rem 1.125rem;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }
        }

        /* Route list filter card (admin/routes): balanced 2-col mobile grid, full-width controls */
        @media (max-width: 1023px) {
            #sw-route-filter-card form,
            #sw-route-filter-card .sw-route-field {
                width: 100%;
                min-width: 0;
            }
            #sw-route-filter-card form {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 0.75rem;
            }
            #sw-route-filter-card input,
            #sw-route-filter-card select {
                display: block;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                height: 3rem;
                min-height: 3rem;
                box-sizing: border-box;
            }
            #sw-route-filter-card button {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                height: 3rem;
                min-height: 3rem;
                box-sizing: border-box;
            }
            #sw-route-filter-card input[type="date"] {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                text-align: left !important;
                line-height: 2.75rem;
                padding: 0 0.5rem 0 1rem;
            }
            #sw-route-filter-card input[type="date"]::-webkit-datetime-edit {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                text-align: left !important;
                flex: 1 1 auto;
                min-width: 0;
                padding: 0;
                font-size: inherit;
                color: inherit;
            }
            #sw-route-filter-card input[type="date"]::-webkit-datetime-edit-fields-wrapper {
                display: inline-flex;
                align-items: center;
                justify-content: flex-start;
                text-align: left !important;
                flex: 1 1 auto;
                min-width: 0;
                padding: 0;
            }
            #sw-route-filter-card input[type="date"]::-webkit-calendar-picker-indicator {
                margin-left: auto;
                padding-left: 0.75rem;
                cursor: pointer;
            }
            #sw-route-filter-card select {
                padding-right: 2.5rem;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 0.75rem center;
                background-repeat: no-repeat;
                background-size: 1.125rem 1.125rem;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                text-align: left;
            }
        }

        /* Visit list filter card (admin/visits): balanced 2-col mobile grid, full-width controls */
        @media (max-width: 1023px) {
            #sw-visit-filter-card form,
            #sw-visit-filter-card .sw-visit-field {
                width: 100%;
                min-width: 0;
            }
            #sw-visit-filter-card form {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 0.75rem;
            }
            #sw-visit-filter-card input,
            #sw-visit-filter-card select {
                display: block;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                height: 3rem;
                min-height: 3rem;
                box-sizing: border-box;
            }
            #sw-visit-filter-card button {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                height: 3rem;
                min-height: 3rem;
                box-sizing: border-box;
            }
            #sw-visit-filter-card input[type="date"] {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                text-align: left !important;
                line-height: 2.75rem;
                padding: 0 0.5rem 0 1rem;
            }
            #sw-visit-filter-card input[type="date"]::-webkit-datetime-edit {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                text-align: left !important;
                flex: 1 1 auto;
                min-width: 0;
                padding: 0;
                font-size: inherit;
                color: inherit;
            }
            #sw-visit-filter-card input[type="date"]::-webkit-datetime-edit-fields-wrapper {
                display: inline-flex;
                align-items: center;
                justify-content: flex-start;
                text-align: left !important;
                flex: 1 1 auto;
                min-width: 0;
                padding: 0;
            }
            #sw-visit-filter-card input[type="date"]::-webkit-calendar-picker-indicator {
                margin-left: auto;
                padding-left: 0.75rem;
                cursor: pointer;
            }
            #sw-visit-filter-card select {
                padding-right: 2.5rem;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 0.75rem center;
                background-repeat: no-repeat;
                background-size: 1.125rem 1.125rem;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                text-align: left;
            }
        }

        /* Attendance filter card (admin/attendance): stacked full-width mobile layout */
        @media (max-width: 1023px) {
            #sw-attendance-filter-card form {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            #sw-attendance-filter-card form > div {
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                flex: none !important;
            }
            #sw-attendance-filter-card form > div:last-child {
                flex-direction: column;
                align-items: stretch;
            }
            #sw-attendance-filter-card .sw-attendance-pair {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 0.75rem;
                width: 100%;
                min-width: 0;
            }
            #sw-attendance-filter-card .sw-attendance-pair > div {
                width: 100% !important;
                min-width: 0 !important;
                max-width: none !important;
            }
            #sw-attendance-filter-card input,
            #sw-attendance-filter-card select {
                display: block;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                height: 3rem;
                min-height: 3rem;
                box-sizing: border-box;
            }
            #sw-attendance-filter-card form button,
            #sw-attendance-filter-card form a {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                height: 3rem;
                min-height: 3rem;
                box-sizing: border-box;
            }
            #sw-attendance-filter-card input[type="date"] {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                text-align: left !important;
                line-height: 2.75rem;
                padding: 0 0.5rem 0 1rem;
            }
            #sw-attendance-filter-card input[type="date"]::-webkit-datetime-edit {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                text-align: left !important;
                flex: 1 1 auto;
                min-width: 0;
                padding: 0;
                font-size: inherit;
                color: inherit;
            }
            #sw-attendance-filter-card input[type="date"]::-webkit-datetime-edit-fields-wrapper {
                display: inline-flex;
                align-items: center;
                justify-content: flex-start;
                text-align: left !important;
                flex: 1 1 auto;
                min-width: 0;
                padding: 0;
            }
            #sw-attendance-filter-card input[type="date"]::-webkit-calendar-picker-indicator {
                margin-left: auto;
                padding-left: 0.75rem;
                cursor: pointer;
            }
            #sw-attendance-filter-card select {
                padding-right: 2.5rem;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 0.75rem center;
                background-repeat: no-repeat;
                background-size: 1.125rem 1.125rem;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                text-align: left;
            }
        }

        .sw-input-error { border-color: #fca5a5; }
        .sw-input-error:hover { border-color: #f87171; }
        .sw-input-error:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12); }
        .dark .sw-input-error { border-color: #f87171; }
        .dark .sw-input-error:hover { border-color: #ef4444; }

        .sw-input-helper {
            display: flex;
            align-items: flex-start;
            gap: 0.375rem;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            line-height: 1.4;
            color: #94a3b8;
        }
        .sw-input-helper .sw-helper-icon { flex-shrink: 0; margin-top: 0.0625rem; }
        .dark .sw-input-helper { color: #94a3b8; }
        .sw-input-helper.sw-helper-error { color: #ef4444; }
        .dark .sw-input-helper.sw-helper-error { color: #f87171; }

        .sw-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.375rem;
        }
        .dark .sw-label { color: #cbd5e1; }

        .sw-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.625rem 1rem;
            transition: all .15s ease;
            cursor: pointer;
        }
        .sw-btn-primary { background: var(--sw-brand); color: #fff; box-shadow: 0 1px 2px rgba(13,164,206,.2); }
        .sw-btn-primary:hover { background: var(--sw-brand-dark); }
        .sw-btn-accent { background: var(--sw-accent); color: #0f172a; }
        .sw-btn-accent:hover { filter: brightness(0.96); }
        .sw-btn-ghost { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
        .sw-btn-ghost:hover { background: #f8fafc; }
        .dark .sw-btn-ghost { background: #111827; color: #e5e7eb; border-color: #374151; }

        .sw-table th {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
        }
        .sw-table tbody tr { transition: background-color .12s ease; }
        .sw-table tbody tr:hover { background: #f8fafc; }
        .dark .sw-table tbody tr:hover { background: #111827; }

        .sw-section-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.01em;
        }
        .dark .sw-section-title { color: #f1f5f9; }

        .sw-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border-radius: 999px;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
        }
        .sw-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3rem 1.5rem;
            color: #94a3b8;
        }
    </style>

    @stack('scripts')
</body>
</html>
