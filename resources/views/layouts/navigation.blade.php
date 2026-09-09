{{-- Mobile overlay --}}
<div
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-20 bg-black/50 lg:hidden"
    @click="sidebarOpen = false"
    x-cloak
></div>

{{-- Sidebar --}}
<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto lg:z-auto"
>
    <div class="flex items-center h-16 px-6 border-b border-gray-200">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="ISA SmartWork" class="h-8 w-auto">
            <span class="text-lg font-bold text-gray-800">ISA SmartWork</span>
        </a>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        @php
            $isDriver = Auth::user()->hasRole('driver');
        @endphp
        <a
            href="{{ route('dashboard') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('dashboard') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>

        @hasanyrole('super-admin|admin')
        <a
            href="{{ route('admin.users.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('admin.users.*') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Users
        </a>
        @endhasanyrole

        <a
            href="{{ route('route.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('route.*') && !request()->routeIs('route.history') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            {{ $isDriver ? 'Rencana Pengiriman' : 'Rencana Kunjungan' }}
        </a>

        <a
            href="{{ route('route.history') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('route.history') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $isDriver ? 'Riwayat Rute Pengiriman' : 'Riwayat Rute' }}
        </a>

        <a
            href="{{ route('visit.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('visit.*') && !request()->routeIs('visit.history') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            {{ $isDriver ? 'Pengiriman' : 'Kunjungan' }}
        </a>

        <a
            href="{{ route('visit.history') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('visit.history') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $isDriver ? 'Riwayat Pengiriman' : 'Riwayat Kunjungan' }}
        </a>

        @if($isDriver)
        <a
            href="{{ route('driver.stores.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('driver.stores.*') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Daftar Toko
        </a>
        @endif

        <a
            href="{{ route('attendance.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('attendance.*') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Attendance
        </a>

        @hasanyrole('super-admin|admin')
        <div class="pt-3 mb-1">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</p>
        </div>

        <a
            href="{{ route('admin.notifications.index') }}"
            class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('admin.notifications.*') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Notifikasi
            </div>
            @php
                $navUnread = Auth::user()->unreadNotifications()->count();
            @endphp
            @if($navUnread > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[#0DA4CE] text-white">
                    {{ $navUnread > 99 ? '99+' : $navUnread }}
                </span>
            @endif
        </a>

        <a
            href="{{ route('admin.routes.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('admin.routes.*') && !request()->routeIs('admin.routes.report') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Monitoring Rute
        </a>

        <a
            href="{{ route('admin.routes.report') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('admin.routes.report') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Laporan Rute
        </a>

        <a
            href="{{ route('admin.visits.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('admin.visits.*') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Monitoring Kunjungan
        </a>

        <a
            href="{{ route('admin.reports.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('admin.reports.*') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Laporan
        </a>
        @endhasanyrole

        <a
            href="{{ route('profile.edit') }}"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                {{ request()->routeIs('profile.*') ? 'bg-cyan-50 text-[#0DA4CE]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Profile
        </a>
    </nav>

    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-400 dark:text-gray-500 text-center">PT ISA Tri Selaras Gemilang</p>
    </div>
</aside>

{{-- Top navbar --}}
<header class="sticky top-0 z-10 bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center">
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="inline-flex items-center justify-center p-2 -ml-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:hidden"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <nav class="hidden sm:flex items-center space-x-1 text-sm text-gray-500" aria-label="Breadcrumb">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                @yield('breadcrumbs', 'Dashboard')
            </nav>
        </div>

        <div class="flex items-center space-x-3">
            <button type="button" @click="toggleDarkMode()"
                class="relative p-2 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] transition"
                aria-label="Toggle dark mode">
                <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>

            @hasanyrole('super-admin|admin')
            <a href="{{ route('admin.notifications.index') }}"
                class="relative p-2 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0DA4CE]"
                aria-label="Notifications">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if(Auth::user()->unreadNotifications()->count() > 0)
                    <span class="absolute top-1 right-1 min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center border-2 border-white dark:border-gray-900 shadow-sm">
                        {{ Auth::user()->unreadNotifications()->count() > 99 ? '99+' : Auth::user()->unreadNotifications()->count() }}
                    </span>
                @endif
            </a>
            @endhasanyrole

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="flex items-center space-x-2 p-1.5 rounded-full text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0DA4CE]"
                    aria-label="User menu" aria-expanded="false">
                    <div class="w-8 h-8 rounded-full bg-[#097A99] flex items-center justify-center text-white text-sm font-semibold">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                    </div>
                    <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</span>
                    <svg class="hidden sm:block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false"
                    x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50" x-cloak>
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Profile
                    </a>
                    <hr class="border-gray-100 dark:border-gray-700">
                    <form method="POST" action="{{ route('logout') }}" x-data="{ loggingOut: false }" @submit="if(loggingOut){ $event.preventDefault(); return false; } loggingOut = true;">
                        @csrf
                        <button type="submit" :disabled="loggingOut" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span x-text="loggingOut ? 'Logging out...' : 'Logout'">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>