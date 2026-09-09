<x-app-layout>
    @section('breadcrumbs', 'Daftar Toko')

    <x-slot name="header">
        <x-page-header icon="store" title="Daftar Toko" subtitle="Informasi alamat dan navigasi toko tujuan pengiriman"></x-page-header>
    </x-slot>

    <div class="w-full space-y-5">
        {{-- Stat Card Total Toko --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Toko</p>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($totalStoresCount, 0, ',', '.') }}</p>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Toko Tersedia</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4">
            <form method="GET" action="{{ route('driver.stores.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Cari toko berdasarkan nama, kode, atau alamat..."
                        class="w-full pl-10 pr-10 py-2.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE] transition">
                    @if(!empty($search))
                    <a href="{{ route('driver.stores.index') }}"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
                        title="Hapus pencarian">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit"
                        class="w-full sm:w-auto px-5 py-2.5 bg-[#0DA4CE] hover:bg-[#097A99] text-white text-sm font-semibold rounded-xl transition shadow-sm flex items-center justify-center gap-1.5 active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Cari</span>
                    </button>
                    @if(!empty($search))
                    <a href="{{ route('driver.stores.index') }}"
                        class="w-full sm:w-auto px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-xl transition text-center">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
            @if(!empty($search))
            <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 px-1">
                <p>Menampilkan hasil pencarian untuk: <span class="font-bold text-gray-800 dark:text-gray-200">"{{ $search }}"</span> ({{ $stores->total() }} toko)</p>
            </div>
            @endif
        </div>

        {{-- Grid Daftar Toko --}}
        @if($stores->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($stores as $store)
            @php
                $mapsUrl = $store->google_maps_url;
                $addressParts = array_filter([
                    $store->address,
                    $store->kecamatan,
                    $store->city,
                    $store->province,
                ]);
                $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : null;
                $hasCoords = $store->latitude !== null && $store->longitude !== null && is_numeric($store->latitude) && is_numeric($store->longitude) && ((float)$store->latitude != 0 || (float)$store->longitude != 0);
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between hover:border-[#0DA4CE]/40 transition duration-200">
                <div class="space-y-3">
                    {{-- Header Card Toko --}}
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white leading-snug break-words">
                                {{ $store->name }}
                            </h3>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                @if($store->code)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-gray-100 dark:bg-gray-700/80 text-gray-700 dark:text-gray-300">
                                    {{ $store->code }}
                                </span>
                                @endif
                                @if($store->type)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-[#0DA4CE]/10 text-[#0DA4CE] dark:text-[#5BD8F7]">
                                    {{ $store->type }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Alamat Toko --}}
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Alamat</p>
                        @if($fullAddress)
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed break-words whitespace-normal">
                            {{ $fullAddress }}
                        </p>
                        @else
                        <p class="text-xs italic text-gray-400 dark:text-gray-500">
                            Alamat belum tersedia
                        </p>
                        @endif
                    </div>
                </div>

                {{-- Action Button: Buka di Google Maps --}}
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                    @if($mapsUrl)
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 dark:bg-[#0DA4CE]/15 dark:hover:bg-[#0DA4CE]/25 text-[#0DA4CE] dark:text-[#5BD8F7] border border-[#0DA4CE]/20 transition shadow-2xs active:scale-95">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Buka di Google Maps</span>
                    </a>
                    @else
                    <div class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-medium bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700/50 select-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Lokasi Belum Tersedia</span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-2">
            {{ $stores->links('vendor.pagination.reports') }}
        </div>
        @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-8 sm:p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3 text-gray-400 dark:text-gray-500">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            @if(!empty($search))
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Toko Tidak Ditemukan</h3>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                Belum ada toko yang sesuai dengan kata kunci <span class="font-bold text-gray-700 dark:text-gray-300">"{{ $search }}"</span>.
            </p>
            <a href="{{ route('driver.stores.index') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-[#0DA4CE] text-white rounded-xl text-xs font-semibold hover:bg-[#097A99] transition shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Lihat Semua Toko</span>
            </a>
            @else
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Belum Ada Toko</h3>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                Saat ini belum ada toko tujuan pengiriman yang terdaftar di dalam sistem.
            </p>
            @endif
        </div>
        @endif
    </div>
</x-app-layout>
