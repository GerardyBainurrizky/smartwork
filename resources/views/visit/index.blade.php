<x-app-layout>
    @php
        $isDriver = Auth::user()->hasRole('driver');
    @endphp
    <x-slot name="header">
        <x-page-header icon="store" :title="$isDriver ? 'Pengiriman Hari Ini' : 'Kunjungan Hari Ini'" :subtitle="$isDriver ? 'Kunjungi tujuan sesuai rencana pengiriman hari ini' : 'Kunjungi toko sesuai rencana kunjungan hari ini'"></x-page-header>
    </x-slot>

    <div class="w-full space-y-5">
        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sw-card p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <div class="ml-3">
                         <p class="text-xs text-gray-500 dark:text-gray-400">{{ $isDriver ? 'Pengiriman Hari Ini' : 'Kunjungan Hari Ini' }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $todayCount }}</p>
                    </div>
                </div>
            </div>
            <div class="sw-card p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#90F022]/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selesai</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $todayCompleted }}</p>
                    </div>
                </div>
            </div>
            <div class="sw-card p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#097A99]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#097A99] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                    </div>
                    <div class="ml-3">
                         <p class="text-xs text-gray-500 dark:text-gray-400">{{ $isDriver ? 'Rencana Pengiriman Hari Ini' : 'Rencana Kunjungan Hari Ini' }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $todayPlanStats['total'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Visit --}}
        @if($activeVisit)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-[#0DA4CE]/30 overflow-hidden">
            <div class="p-5 bg-[#0DA4CE]/5 border-b border-[#0DA4CE]/10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-[#0DA4CE] animate-pulse"></div>
                         <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $isDriver ? 'Pengiriman Sedang Berlangsung' : 'Kunjungan Sedang Berlangsung' }}</h2>
                    </div>
                    <span class="text-xs text-[#0DA4CE] dark:text-[#5BD8F7] font-medium">{{ \Carbon\Carbon::parse($activeVisit->check_in_at)->diffForHumans() }}</span>
                </div>
            </div>
            <div class="p-5">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $activeVisit->store->name ?? 'Toko' }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $activeVisit->store->address ?? '' }}</p>
                <div class="mt-4 flex flex-col sm:flex-row gap-2">
                    <a href="{{ route('visit.show', $activeVisit->id) }}"
                        class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        Lihat Detail
                    </a>
                    <a href="{{ route('visit.check-out-form', $activeVisit->id) }}"
                        class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-[#90F022] text-gray-900 dark:text-gray-100 rounded-xl text-sm font-semibold hover:bg-[#7ed81e] transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                         {{ $isDriver ? 'Check Out Pengiriman' : 'Check Out Kunjungan' }}
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Rencana Pengiriman/Kunjungan Hari Ini --}}
        <div class="sw-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $isDriver ? 'Rencana Pengiriman Hari Ini' : 'Rencana Kunjungan Hari Ini' }}</h2>
                @if($todayRoute)
                <a href="{{ route('route.show', $todayRoute->id) }}" class="text-sm font-medium text-[#0DA4CE] dark:text-[#5BD8F7] hover:text-[#097A99] transition">Lihat Detail Rute</a>
                @endif
            </div>

            @if($todayStops->count() > 0)
            <div class="divide-y divide-gray-50 dark:divide-gray-700/60">
                @foreach($todayStops as $stop)
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0">
                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[#0DA4CE] text-white text-xs font-bold">
                            {{ $stop->sequence }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $stop->store->name ?? 'Toko' }}</p>
                                @if(!$isDriver)
                                    @php
                                        $vStBal = \App\Services\StoreReceivableService::balanceForStore($stop->store_id);
                                    @endphp
                                    @if((float)$vStBal > 0.005)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                            Piutang: Rp {{ number_format($vStBal, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-400 border border-green-200/60 dark:border-green-800/40">
                                            Piutang: Tidak Ada Piutang
                                        </span>
                                    @endif
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mt-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400 break-words line-clamp-1 flex-1 min-w-0">{{ $stop->store->address ?? '' }}</p>
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
                    <div class="flex items-center gap-2 sm:ml-auto">
                        @if($stop->computed_status === 'visited')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400">Selesai</span>
                            @if($stop->visit)
                            <a href="{{ route('visit.show', $stop->visit->id) }}" class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">Detail</a>
                            @endif
                        @elseif($stop->computed_status === 'in_progress')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]">Sedang Berjalan</span>
                            @if($stop->visit)
                            <a href="{{ route('visit.show', $stop->visit->id) }}" class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">Detail</a>
                            @endif
                        @elseif($stop->computed_status === 'skipped')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400">Dilewati</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $isDriver ? 'Belum Dikirim' : 'Belum Dikunjungi' }}</span>
                            <a href="{{ route('visit.check-in-form', $stop->id) }}"
                                class="inline-flex items-center px-4 py-2 bg-[#0DA4CE] text-white rounded-lg text-xs font-semibold hover:bg-[#097A99] transition shadow-sm">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ $isDriver ? 'Check In Pengiriman' : 'Check In Kunjungan' }}
                            </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                <h3 class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $isDriver ? 'Belum ada rencana pengiriman untuk hari ini' : 'Belum ada rencana kunjungan untuk hari ini' }}</h3>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">{{ $isDriver ? 'Buat rencana pengiriman untuk memulai pengiriman hari ini.' : 'Buat rencana kunjungan untuk memulai kunjungan toko hari ini.' }}</p>
                <a href="{{ route('route.create') }}" class="inline-flex items-center mt-4 px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl font-semibold text-sm hover:bg-[#097A99] transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ $isDriver ? 'Buat Rencana Pengiriman' : 'Buat Rencana Kunjungan' }}
                </a>
            </div>
            @endif
        </div>

        {{-- Pengiriman/Kunjungan Hari Ini (Riwayat cepat) --}}
        @if($todayVisits->count() > 0)
        <div class="sw-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $isDriver ? 'Pengiriman Hari Ini' : 'Kunjungan Hari Ini' }}</h2>
                <a href="{{ route('visit.history') }}" class="text-sm font-medium text-[#0DA4CE] dark:text-[#5BD8F7] hover:text-[#097A99] transition">Riwayat &rarr;</a>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-gray-700/60">
                @foreach($todayVisits as $visit)
                <a href="{{ route('visit.show', $visit->id) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                    <div class="w-9 h-9 flex items-center justify-center rounded-full shrink-0
                        @if($visit->status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-700 dark:text-green-400
                        @else bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] @endif">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $visit->store->name ?? 'Toko' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $visit->check_in_at ? \Carbon\Carbon::parse($visit->check_in_at)->format('d M Y, H:i') : '-' }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($visit->status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                        @else bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] @endif">
                        {{ $visit->status === 'completed' ? 'Selesai' : ($isDriver ? 'Sedang Dikirim' : 'Sedang Dikunjungi') }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-app-layout>