<x-app-layout>
    @php
        $isDriver = Auth::user()->hasRole('driver');
    @endphp
    <x-slot name="header">
        <x-page-header icon="route" :title="$isDriver ? 'Rute Pengiriman' : 'Rute Perjalanan'" :subtitle="$isDriver ? 'Kelola rute pengiriman Anda' : 'Kelola rute kunjungan toko Anda'">
            <a href="{{ route('route.create') }}"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-[#0b8fb5] focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] focus:ring-offset-2 transition-all duration-200 shadow-sm shadow-[#0DA4CE]/20">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $isDriver ? 'Tambah Rencana Pengiriman' : 'Tambah Rencana Kunjungan' }}
            </a>
        </x-page-header>
    </x-slot>

    <div class="animate-fade-in space-y-5">

            {{-- Today's Routes --}}
            @if($todayRoutes->count() > 0)
                <div class="space-y-4">
                    @foreach($todayRoutes as $route)
                    <div class="sw-card overflow-hidden">
                        <div class="p-5">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl bg-[#0DA4CE]/10">
                                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rute Hari Ini</h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($route->date)->translatedFormat('l, d F Y') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center self-start px-3 py-1 rounded-full text-xs font-semibold
                                    @if($route->computed_status === 'draft') bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200
                                    @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                    @elseif($route->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                    @else bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400
                                    @endif">
                                    @if($route->computed_status === 'draft')
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Menunggu
                                    @elseif($route->computed_status === 'active')
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        Sedang Berjalan
                                    @elseif($route->computed_status === 'completed')
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Selesai
                                    @else
                                        Dibatalkan
                                    @endif
                                </span>
                            </div>

                            <h3 class="text-base font-medium text-gray-800 dark:text-gray-100 mb-3">{{ $route->name }}</h3>

                            @if($route->stops->count() > 0)
                                <div class="space-y-2">
                                    @foreach($route->stops as $stop)
                                        <div class="rounded-lg border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-800/40 p-3">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#0DA4CE] text-xs font-bold text-white">
                                                    {{ $stop->sequence }}
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $stop->store->name ?? 'Toko #'.$stop->store_id }}</p>
                                                </div>
                                                <span class="inline-flex shrink-0 items-center px-2.5 py-1 rounded-full text-xs font-medium
                                                    @if($stop->computed_status === 'pending') bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                                    @elseif($stop->computed_status === 'in_progress') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                                    @elseif($stop->computed_status === 'visited') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                                    @elseif($stop->computed_status === 'skipped') bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400
                                                    @endif">
                                                    @if($stop->computed_status === 'pending') Menunggu
                                                    @elseif($stop->computed_status === 'in_progress') Proses
                                                    @elseif($stop->computed_status === 'visited') {{ $isDriver ? 'Selesai' : 'Dikunjungi' }}
                                                    @elseif($stop->computed_status === 'skipped') Dilewati
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="mt-2 pl-0 sm:pl-10 space-y-1.5">
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                                    @if(!$isDriver && $stop->estimated_duration_minutes)
                                                    <span class="inline-flex items-center gap-1 font-semibold text-[#0DA4CE] dark:text-[#5BD8F7] shrink-0">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        {{ $stop->estimated_duration_minutes }} mnt
                                                    </span>
                                                    @endif
                                                    @if($stop->store && $stop->store->address)
                                                    <p class="min-w-0 flex-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $stop->store->address }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
                                                    <div>
                                                        @if(!$isDriver)
                                                            @php
                                                                $stBal = \App\Services\StoreReceivableService::balanceForStore($stop->store_id);
                                                            @endphp
                                                            @if((float)$stBal > 0.005)
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                                                    Piutang: Rp {{ number_format($stBal, 0, ',', '.') }}
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-400 border border-green-200/60 dark:border-green-800/40">
                                                                    Piutang: Tidak Ada Piutang
                                                                </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    @if($stop->store && $stop->store->google_maps_url)
                                                    <a href="{{ $stop->store->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                                                        aria-label="Buka lokasi {{ $stop->store->name }} di Google Maps"
                                                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#5BD8F7] hover:underline shrink-0">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        <span>Buka Maps</span>
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-4 flex flex-col sm:flex-row flex-wrap gap-2">
                                @if($route->status === 'draft')
                                    <button onclick="startRoute('{{ $route->id }}')"
                                            class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2.5 bg-[#90F022] border border-transparent rounded-lg font-semibold text-sm text-gray-900 dark:text-gray-100 hover:bg-[#7ed41e] focus:outline-none focus:ring-2 focus:ring-[#90F022] focus:ring-offset-2 transition-all duration-200 shadow-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Mulai Rute
                                    </button>
                                @endif
                                <a href="{{ route('route.show', $route->id) }}"
                                   class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/40 focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] focus:ring-offset-2 transition-all duration-200">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="sw-card overflow-hidden">
                    <div class="px-6 py-8 flex flex-col items-center text-center">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-[#0DA4CE]/10 mb-3">
                            <svg class="w-6 h-6 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Belum ada rute untuk hari ini</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md">
                            {{ $isDriver ? 'Rencanakan pengiriman Anda hari ini. Gunakan tombol' : 'Rencanakan kunjungan toko Anda hari ini. Gunakan tombol' }} <span class="font-medium text-[#0DA4CE] dark:text-[#5BD8F7]">{{ $isDriver ? 'Tambah Rencana Pengiriman' : 'Tambah Rencana Kunjungan' }}</span> di kanan atas untuk memulai.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Upcoming Routes + Recent Routes (balanced desktop grid) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
            {{-- Upcoming Routes --}}
            <div class="sw-card overflow-hidden">
                <div class="p-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Rute Mendatang
                    </h2>

                    @if($upcomingRoutes->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($upcomingRoutes as $upcoming)
                                <div class="p-4 bg-gray-50 dark:bg-gray-800/40 rounded-lg border border-gray-100 dark:border-gray-700/60 hover:border-[#0DA4CE]/30 hover:shadow-sm transition-all duration-200">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate pr-2">{{ $upcoming->name }}</h3>
                                        <span class="inline-flex items-center flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-medium
                                            @if($upcoming->computed_status === 'draft') bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200
                                            @elseif($upcoming->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                            @elseif($upcoming->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                            @else bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400
                                            @endif">
                                            @if($upcoming->computed_status === 'completed') Selesai
                                            @elseif($upcoming->computed_status === 'active') Sedang Berjalan
                                            @elseif($upcoming->computed_status === 'cancelled') Dibatalkan
                                            @else Menunggu
                                            @endif
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                        <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ \Carbon\Carbon::parse($upcoming->date)->translatedFormat('l, d M Y') }}
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                            {{ $upcoming->stops->count() }} toko
                                        </span>
                                        <a href="{{ route('route.show', $upcoming->id) }}" class="text-xs font-medium text-[#0DA4CE] dark:text-[#5BD8F7] hover:text-[#0b8fb5] transition-colors">
                                            Detail &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="mx-auto w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700/50 mb-3">
                                <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada rute mendatang</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Routes Timeline --}}
            <div class="sw-card overflow-hidden">
                <div class="p-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Riwayat Rute
                    </h2>

                    @if($recentRoutes->total() > 0)
                        <div class="relative">
                            @php $currentDate = null; @endphp
                            @foreach($recentRoutes as $recent)
                                @php
                                    $dateLabel = \Carbon\Carbon::parse($recent->date)->translatedFormat('l, d F Y');
                                    $showDate = $currentDate !== $dateLabel;
                                    $currentDate = $dateLabel;
                                @endphp
                                @if($showDate && !$loop->first)
                                    <div class="border-t border-gray-100 dark:border-gray-700/60 my-3"></div>
                                @endif
                                <div class="flex items-center gap-4 py-3 px-2 hover:bg-gray-50 dark:hover:bg-gray-800/40 rounded-lg transition-colors duration-150">
                                    <div class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full
                                        @if($recent->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-700 dark:text-green-400
                                        @elseif($recent->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                        @elseif($recent->computed_status === 'cancelled') bg-red-50 dark:bg-red-950/40 text-red-500 dark:text-red-400
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300
                                        @endif">
                                        @if($recent->computed_status === 'completed')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        @elseif($recent->computed_status === 'active')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        @elseif($recent->computed_status === 'cancelled')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $recent->name }}</p>
                                            <span class="inline-flex items-center flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-medium
                                                @if($recent->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                                @elseif($recent->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                                @elseif($recent->computed_status === 'cancelled') bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400
                                                @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                                @endif">
                                                @if($recent->computed_status === 'completed') Selesai
                                                @elseif($recent->computed_status === 'active') Sedang Berjalan
                                                @elseif($recent->computed_status === 'cancelled') Dibatalkan
                                                @else Menunggu
                                                @endif
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $dateLabel }} &middot; {{ $recent->stops->count() }} toko
                                        </p>
                                    </div>
                                    <a href="{{ route('route.show', $recent->id) }}" class="flex-shrink-0 text-xs font-medium text-[#0DA4CE] dark:text-[#5BD8F7] hover:text-[#0b8fb5] transition-colors">
                                        Lihat
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 flex flex-col items-center gap-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Menampilkan {{ $recentRoutes->firstItem() }}–{{ $recentRoutes->lastItem() }} dari {{ $recentRoutes->total() }} rute
                            </p>
                            @if($recentRoutes->hasPages())
                                {{ $recentRoutes->links('route._pagination') }}
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="mx-auto w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700/50 mb-3">
                                <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat rute</p>
                        </div>
                    @endif
                </div>
            </div>
            </div>{{-- /grid --}}
        </div>

    @push('scripts')
    <script>
        function startRoute(routeId) {
            if (!confirm('Mulai rute perjalanan ini?')) return;

            fetch('{{ url('route') }}/' + routeId + '/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = '{{ url('route') }}/' + routeId;
                } else {
                    if (window.dispatchEvent) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: data.message || 'Gagal memulai rute.', type: 'error' }
                        }));
                    }
                    alert(data.message || 'Gagal memulai rute.');
                }
            })
            .catch(() => {
                if (window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Terjadi kesalahan. Silakan coba lagi.', type: 'error' }
                    }));
                }
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        }
    </script>
    @endpush
</x-app-layout>
