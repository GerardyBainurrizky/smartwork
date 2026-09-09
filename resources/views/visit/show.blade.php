<x-app-layout>
    @php
        $isAdminViewer = Auth::check() && Auth::user()->hasAnyRole(['admin', 'super-admin']);
        $isOwner = Auth::check() && (string) $visit->user_id === (string) Auth::id();
        $isDriverOwner = $visit->user && $visit->user->hasRole('driver');
        $isDriverViewer = Auth::check() && Auth::user()->hasRole('driver');
        $isDriverContext = $isDriverOwner || $isDriverViewer;
        $isSkipped = $visit->isSkipped();

        $pageTitle = $isSkipped ? 'Detail Kunjungan Dilewati' : ($isDriverContext ? 'Detail Pengiriman' : 'Detail Kunjungan');
        $pageSubtitle = $isSkipped
            ? 'Informasi lengkap bukti dan alasan kunjungan dilewati'
            : ($isDriverContext ? 'Informasi lengkap check in dan check out pengiriman' : 'Informasi lengkap check in dan check out kunjungan');

        $backHref = $isAdminViewer
            ? ($isDriverOwner ? route('admin.driver.visits.index') : route('admin.visits.index'))
            : ($visit->route_id ? route('route.show', $visit->route_id) : route('visit.index'));
        $backLabel = $isAdminViewer
            ? 'Kembali ke Monitoring ' . ($isDriverOwner ? 'Pengiriman' : 'Kunjungan')
            : ($isDriverContext ? 'Kembali ke Rute Pengiriman' : 'Kembali ke Rute');

        $skipReason = $visit->initial_notes
            ?: (str_starts_with((string) $visit->visit_result, 'Dilewati: ')
                ? trim(substr($visit->visit_result, 10))
                : ($visit->routeStop?->notes ?: ($visit->visit_result ?: '-')));

        $skipPhoto = $visit->final_store_photo ?: $visit->storefront_photo;
        $skipTime = $visit->check_in_at ?: ($visit->created_at ?: now());
        $skipLat = $visit->check_in_lat ?? $visit->check_out_lat;
        $skipLng = $visit->check_in_lng ?? $visit->check_out_lng;
        $skipAddress = $visit->check_in_address ?? $visit->check_out_address;
        $skipMapUrl = ($skipLat && $skipLng) ? "https://www.google.com/maps?q={$skipLat},{$skipLng}" : null;
    @endphp
    <x-slot name="header">
        <x-page-header icon="store" :title="$pageTitle" :subtitle="$pageSubtitle"></x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-back-button :href="$backHref" :label="$backLabel" class="mb-4" />

        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden mb-6">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $visit->store->name ?? 'Toko' }}</h1>
                            @if($visit->store && $visit->store->google_maps_url)
                            <a href="{{ $visit->store->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                                aria-label="Buka lokasi {{ $visit->store->name }} di Google Maps"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] border border-[#0DA4CE]/20 rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Buka Maps</span>
                            </a>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $visit->store->address ?? '' }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $visit->store->code ?? '' }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shrink-0
                        @if($isSkipped) bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-400 border border-red-200 dark:border-red-800
                        @elseif($visit->status === 'in_progress') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                        @else bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                        @endif">
                        @if($isSkipped) Dilewati
                        @elseif($visit->status === 'in_progress') {{ $isDriverContext ? 'Sedang Dikirim' : 'Sedang Dikunjungi' }}
                        @else Selesai
                        @endif
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $isDriverOwner ? 'Nama Driver' : 'Nama Sales' }}</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $visit->user->name ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $isDriverOwner ? 'Rute Pengiriman' : 'Rute Wilayah' }}</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $visit->route->name ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Tanggal</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ $skipTime ? \Carbon\Carbon::parse($skipTime)->format('d M Y') : '-' }}
                        </p>
                    </div>
                </div>

                @if(!$isSkipped && $visit->status === 'in_progress' && $isOwner)
                <div class="mt-4">
                    <a href="{{ route('visit.check-out-form', $visit->id) }}"
                        class="inline-flex items-center justify-center w-full px-4 py-3 bg-[#90F022] text-gray-900 dark:text-gray-100 rounded-xl text-sm font-semibold hover:bg-[#7ed81e] shadow-sm transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Check Out {{ $isDriverContext ? 'Pengiriman' : 'Kunjungan' }}
                    </a>
                </div>
                @endif
            </div>
        </div>

        @if($isSkipped)
        {{-- ======================================================== --}}
        {{-- EVENT KHUSUS: KUNJUNGAN DILEWATI (BUKAN CHECK IN/CHECK OUT) --}}
        {{-- ======================================================== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-red-200 dark:border-red-900/60 overflow-hidden mb-6">
            <div class="p-5 border-b border-red-100 dark:border-red-900/40 bg-red-50/50 dark:bg-red-950/20 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-red-700 dark:text-red-400 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        Kunjungan Dilewati
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $skipTime ? \Carbon\Carbon::parse($skipTime)->format('d M Y, H:i') : '-' }}
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-300">
                    Dilewati
                </span>
            </div>

            <div class="p-5 space-y-4">
                {{-- Alasan Dilewati --}}
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mb-1">Alasan Dilewati</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-relaxed">{{ $skipReason }}</p>
                </div>

                {{-- Lokasi Pengambilan (Realtime Latitude / Longitude) --}}
                <div class="space-y-2">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Lokasi Pengambilan</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60">
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Latitude</p>
                            <p class="text-sm font-mono font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ $skipLat !== null && $skipLat !== '' ? $skipLat : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60">
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Longitude</p>
                            <p class="text-sm font-mono font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ $skipLng !== null && $skipLng !== '' ? $skipLng : '-' }}</p>
                        </div>
                    </div>
                    @if($skipAddress)
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60">
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Alamat</p>
                        <p class="text-sm text-gray-700 dark:text-gray-200 mt-0.5">{{ $skipAddress }}</p>
                    </div>
                    @endif
                    @if($skipMapUrl)
                    <div class="pt-1">
                        <a href="{{ $skipMapUrl }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded-xl text-xs font-semibold hover:bg-[#0DA4CE]/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Lihat Lokasi di Google Maps</span>
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Waktu --}}
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Waktu</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-0.5">
                        {{ $skipTime ? \Carbon\Carbon::parse($skipTime)->format('d M Y, H:i') : '-' }}
                    </p>
                </div>

                {{-- Foto Bukti --}}
                @if($skipPhoto)
                <div class="space-y-2">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Foto Bukti</p>
                    <div class="max-w-md rounded-2xl overflow-hidden aspect-[4/3] bg-black border border-gray-200 dark:border-gray-700 shadow-sm">
                        <img src="{{ Storage::url($skipPhoto) }}" class="w-full h-full object-cover" alt="Foto Bukti Dilewati">
                    </div>
                </div>
                @endif
            </div>
        </div>
        @else
        {{-- Normal Visit Flow (Check In & Check Out) --}}
        @if($visit->status === 'completed' && $visit->check_in_at && $visit->check_out_at)
        <div class="mb-6" x-data="visitDetailPdfExport('{{ route('visit.pdf.detail', $visit->id) }}', '{{ $isDriverContext ? 'laporan-pengiriman' : 'laporan-kunjungan' }}')">
            <button type="button" @click="download()" :disabled="downloading"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-[#0DA4CE] hover:bg-[#097A99] text-white rounded-xl text-sm font-semibold shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed">
                
                {{-- Idle State --}}
                <span x-show="status === 'idle'" class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export PDF Laporan {{ $isDriverContext ? 'Pengiriman' : 'Kunjungan' }}
                </span>

                {{-- Loading State --}}
                <span x-show="status === 'loading'" x-cloak class="inline-flex items-center">
                    <svg class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Menyiapkan PDF...
                </span>

                {{-- Success State --}}
                <span x-show="status === 'success'" x-cloak class="inline-flex items-center text-white">
                    <svg class="w-4 h-4 mr-2 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Laporan Berhasil Diunduh
                </span>
            </button>
        </div>
        @endif

        {{-- Check In Details --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#0DA4CE]"></span>Check In {{ $isDriverContext ? 'Pengiriman' : 'Kunjungan' }}</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    {{ $visit->check_in_at ? \Carbon\Carbon::parse($visit->check_in_at)->format('d M Y, H:i') : '-' }}
                </p>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Latitude</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $visit->check_in_lat ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Longitude</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $visit->check_in_lng ?? '-' }}</p>
                    </div>
                </div>
                @if($visit->check_in_address)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Alamat</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $visit->check_in_address }}</p>
                </div>
                @endif
                @php
                    $checkInMapUrl = $visit->check_in_maps_url
                        ?? ($visit->check_in_lat && $visit->check_in_lng
                            ? 'https://www.google.com/maps?q=' . $visit->check_in_lat . ',' . $visit->check_in_lng
                            : null);
                @endphp
                @if($checkInMapUrl)
                <a href="{{ $checkInMapUrl }}" target="_blank"
                    class="inline-flex items-center px-3 py-2 bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded-lg text-xs font-semibold hover:bg-[#0DA4CE]/20 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    Lihat Lokasi Check In
                </a>
                @endif

                {{-- Foto Check In --}}
                @if($isDriverOwner || $isDriverViewer)
                    @php
                        $driverCheckInPhoto = $visit->check_in_selfie ?: $visit->storefront_photo;
                    @endphp
                    @if($driverCheckInPhoto)
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5 font-medium">Foto Toko / Selfie</p>
                        <div class="max-w-xs rounded-xl overflow-hidden aspect-[4/3] bg-black border border-gray-200 dark:border-gray-700">
                            <img src="{{ Storage::url($driverCheckInPhoto) }}" class="w-full h-full object-cover" alt="Foto Toko / Selfie">
                        </div>
                    </div>
                    @endif
                @else
                    @if($visit->storefront_photo || $visit->check_in_selfie)
                    <div class="grid grid-cols-2 gap-3">
                        @if($visit->storefront_photo)
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5 font-medium">Foto Toko</p>
                            <img src="{{ Storage::url($visit->storefront_photo) }}" class="rounded-xl w-full aspect-[4/3] object-cover" alt="Foto Toko">
                        </div>
                        @endif
                        @if($visit->check_in_selfie)
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5 font-medium">Selfie</p>
                            <img src="{{ Storage::url($visit->check_in_selfie) }}" class="rounded-xl w-full aspect-[3/4] object-cover" alt="Selfie">
                        </div>
                        @endif
                    </div>
                    @endif
                @endif

                @if($visit->initial_notes)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mb-0.5">{{ $isDriverContext ? 'Catatan Awal Pengiriman' : 'Catatan Kunjungan' }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed">{{ $visit->initial_notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Check Out Details --}}
        @if($visit->status === 'completed')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#90F022]"></span>Check Out {{ $isDriverContext ? 'Pengiriman' : 'Kunjungan' }}</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    {{ $visit->check_out_at ? \Carbon\Carbon::parse($visit->check_out_at)->format('d M Y, H:i') : '-' }}
                </p>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Latitude</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $visit->check_out_lat ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Longitude</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $visit->check_out_lng ?? '-' }}</p>
                    </div>
                </div>
                @if($visit->check_out_address)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Alamat Check Out</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $visit->check_out_address }}</p>
                </div>
                @endif
                @php
                    $checkOutMapUrl = $visit->check_out_maps_url
                        ?? ($visit->check_out_lat && $visit->check_out_lng
                            ? 'https://www.google.com/maps?q=' . $visit->check_out_lat . ',' . $visit->check_out_lng
                            : null);
                @endphp
                @if($checkOutMapUrl)
                <a href="{{ $checkOutMapUrl }}" target="_blank"
                    class="inline-flex items-center px-3 py-2 bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded-lg text-xs font-semibold hover:bg-[#0DA4CE]/20 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    Lihat Lokasi Check Out
                </a>
                @endif

                {{-- Foto Check Out (Multiple untuk Driver, 2 Foto untuk Sales) --}}
                @if($isDriverOwner || $isDriverViewer)
                    @php
                        $checkoutPhotos = $visit->checkoutPhotos ?? collect();
                        if ($checkoutPhotos->isEmpty() && ($visit->final_store_photo || $visit->check_out_selfie)) {
                            $fallbackList = array_filter([$visit->final_store_photo, $visit->check_out_selfie]);
                        } else {
                            $fallbackList = [];
                        }
                    @endphp
                    @if($checkoutPhotos->isNotEmpty() || count($fallbackList) > 0)
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-2 font-medium">Foto Barang/Dokumen Pengiriman</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($checkoutPhotos as $pIndex => $cPhoto)
                            <div class="relative rounded-xl overflow-hidden aspect-[4/3] bg-black border border-gray-200 dark:border-gray-700">
                                <img src="{{ Storage::url($cPhoto->photo_path) }}" class="w-full h-full object-cover" alt="Foto Barang/Dokumen #{{ $pIndex + 1 }}">
                                <span class="absolute bottom-1.5 left-1.5 px-2 py-0.5 rounded-md bg-black/60 text-white text-[10px] font-semibold">#{{ $pIndex + 1 }}</span>
                            </div>
                            @endforeach
                            @foreach($fallbackList as $fIndex => $fPath)
                            <div class="relative rounded-xl overflow-hidden aspect-[4/3] bg-black border border-gray-200 dark:border-gray-700">
                                <img src="{{ Storage::url($fPath) }}" class="w-full h-full object-cover" alt="Foto Barang/Dokumen #{{ $fIndex + 1 }}">
                                <span class="absolute bottom-1.5 left-1.5 px-2 py-0.5 rounded-md bg-black/60 text-white text-[10px] font-semibold">#{{ $fIndex + 1 }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @else
                    @if($visit->final_store_photo || $visit->check_out_selfie)
                    <div class="grid grid-cols-2 gap-3">
                        @if($visit->final_store_photo)
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5 font-medium">Foto Toko Akhir</p>
                            <img src="{{ Storage::url($visit->final_store_photo) }}" class="rounded-xl w-full aspect-[4/3] object-cover" alt="Foto Toko">
                        </div>
                        @endif
                        @if($visit->check_out_selfie)
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5 font-medium">Selfie Check Out</p>
                            <img src="{{ Storage::url($visit->check_out_selfie) }}" class="rounded-xl w-full aspect-[3/4] object-cover" alt="Selfie">
                        </div>
                        @endif
                    </div>
                    @endif
                @endif

                @if($visit->visit_result)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mb-0.5">{{ $isDriverContext ? 'Hasil Pengiriman' : 'Hasil Kunjungan' }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed">{{ $visit->visit_result }}</p>
                </div>
                @endif

                @if($visit->final_notes)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mb-0.5">Catatan Tambahan</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed">{{ $visit->final_notes }}</p>
                </div>
                @endif

                @if(! $isDriverContext && $visit->delivered_goods_summary)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Barang Diserahkan</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $visit->delivered_goods_summary }}</p>
                </div>
                @endif

                @if(! $isDriverContext && $visit->returned_goods)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Barang Retur</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $visit->returned_goods }}</p>
                </div>
                @endif

                {{-- Transaksi & Pembayaran Terstruktur --}}
                @php
                    $visitPayments = ! $isDriverContext
                        ? ($visit->payments ?? collect())->filter(fn ($p) =>
                            $p->source !== 'initial_payment' && ! str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                        )
                        : collect();
                    $visitTransactions = ! $isDriverContext
                        ? ($visit->transactions ?? collect())
                        : collect();
                    $driverHasTx = $isDriverContext && (float) ($visit->transaction_amount ?? 0) > 0;
                    $recSummary = ! $isDriverContext ? \App\Services\StoreReceivableService::getVisitReceivableSummary($visit) : [];
                @endphp

                @if(! $isDriverContext && ($visitPayments->isNotEmpty() || $visitTransactions->isNotEmpty() || (($visit->transaction_status ?? 'none') !== 'none' && $visit->transaction_amount)))
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 sm:p-5 border border-gray-100 dark:border-gray-700/60 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700 flex-wrap gap-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">
                                Rincian Transaksi / Pembayaran
                            </p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Detail faktur, alokasi pembayaran piutang, dan saldo</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                            @if($visit->transaction_status === 'piutang') bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300
                            @elseif($visit->transaction_status === 'paid') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300
                            @elseif($visit->transaction_status === 'mixed') bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300
                            @else bg-gray-100 text-gray-800 @endif">
                            @if($visit->transaction_status === 'piutang') Pembayaran Piutang Lama
                            @elseif($visit->transaction_status === 'paid') Transaksi Baru
                            @elseif($visit->transaction_status === 'mixed') Pembayaran Piutang + Transaksi Baru
                            @else Transaksi Kunjungan @endif
                        </span>
                    </div>

                    {{-- Section A: Pembayaran Piutang Lama jika ada --}}
                    @if($visitPayments->isNotEmpty())
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Pembayaran Piutang Lama</span>
                            </p>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                Total: Rp {{ number_format($recSummary['old_debt_paid'] ?? $visitPayments->sum('amount'), 0, ',', '.') }}
                            </span>
                        </div>

                        @foreach($visitPayments as $vPay)
                        @php
                            $trx = $vPay->transaction;
                            $payAmount = (float) $vPay->amount;
                            if ($trx) {
                                $totalTrxAmount = (float) $trx->transaction_amount;
                                $allPayments = $trx->relationLoaded('payments') ? $trx->payments : $trx->payments()->get();
                                $priorPayments = (float) $allPayments->filter(function ($otherPay) use ($vPay) {
                                    if ($otherPay->id === $vPay->id) return false;
                                    $opDate = $otherPay->payment_date ? $otherPay->payment_date->toDateString() : ($otherPay->created_at ? $otherPay->created_at->toDateString() : null);
                                    $vpDate = $vPay->payment_date ? $vPay->payment_date->toDateString() : ($vPay->created_at ? $vPay->created_at->toDateString() : null);
                                    if ($opDate && $vpDate && $opDate !== $vpDate) {
                                        return $opDate < $vpDate;
                                    }
                                    if ($otherPay->created_at && $vPay->created_at && $otherPay->created_at != $vPay->created_at) {
                                        return $otherPay->created_at < $vPay->created_at;
                                    }
                                    return $otherPay->id < $vPay->id;
                                })->sum('amount');
                                $trxBalanceBefore = max(0.0, round($totalTrxAmount - $priorPayments, 2));
                                $trxBalanceAfter = max(0.0, round($trxBalanceBefore - $payAmount, 2));
                            } else {
                                $totalTrxAmount = $payAmount;
                                $trxBalanceBefore = $payAmount;
                                $trxBalanceAfter = 0.0;
                            }
                        @endphp
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700 shadow-2xs space-y-3">
                            <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-gray-700/60 pb-2.5">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-900 dark:text-white font-mono text-sm sm:text-base">{{ $trx?->transaction_code ?? 'TRX' }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Tanggal Transaksi: <strong class="text-gray-700 dark:text-gray-300">{{ $trx?->transaction_date ? $trx->transaction_date->format('d M Y') : '-' }}</strong>
                                        </span>
                                    </div>
                                    @if($trx?->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $trx->description }}</p>
                                    @endif
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 shrink-0">
                                    {{ $vPay->payment_method }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-lg p-2.5">
                                    <span class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500 block">Nilai Faktur Awal</span>
                                    <span class="font-bold font-mono text-gray-900 dark:text-gray-100 mt-0.5 block">Rp {{ number_format($totalTrxAmount, 0, ',', '.') }}</span>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-lg p-2.5">
                                    <span class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500 block">Saldo Sebelum Pembayaran</span>
                                    <span class="font-bold font-mono text-amber-600 dark:text-amber-400 mt-0.5 block">Rp {{ number_format($trxBalanceBefore, 0, ',', '.') }}</span>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-lg p-2.5">
                                    <span class="text-[10px] uppercase font-semibold text-emerald-600 dark:text-emerald-400 block">Pembayaran Kunjungan Ini</span>
                                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5 block">Rp {{ number_format($payAmount, 0, ',', '.') }}</span>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-lg p-2.5">
                                    <span class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500 block">Sisa Piutang Faktur</span>
                                    <span class="font-bold font-mono {{ $trxBalanceAfter > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-0.5 block">Rp {{ number_format($trxBalanceAfter, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Section B: Transaksi Baru jika ada --}}
                    @if($visitTransactions->isNotEmpty())
                    <div class="space-y-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Transaksi Baru</span>
                            </p>
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 font-mono">
                                Total: Rp {{ number_format($recSummary['new_tx_total'] ?? $visitTransactions->sum('transaction_amount'), 0, ',', '.') }}
                            </span>
                        </div>

                        @foreach($visitTransactions as $vTrx)
                        @php
                            $trxAmount = (float) $vTrx->transaction_amount;
                            $trxPayments = $vTrx->relationLoaded('payments') ? $vTrx->payments : $vTrx->payments()->get();
                            $trxInitialPaid = (float) $trxPayments->filter(fn ($p) =>
                                (string)$p->source_id === (string)$visit->id ||
                                $p->source === 'initial_payment' ||
                                str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                            )->sum('amount');
                            $trxRemaining = max(0.0, round($trxAmount - $trxInitialPaid, 2));
                            $statusAtVisit = $trxRemaining <= 0.005 ? 'LUNAS' : ($trxInitialPaid > 0.005 ? 'SEBAGIAN' : 'BELUM_LUNAS');
                        @endphp
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700 shadow-2xs space-y-3">
                            <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-gray-700/60 pb-2.5">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-900 dark:text-white font-mono text-sm sm:text-base">{{ $vTrx->transaction_code }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Tanggal Transaksi: <strong class="text-gray-700 dark:text-gray-300">{{ $vTrx->transaction_date ? $vTrx->transaction_date->format('d M Y') : '-' }}</strong>
                                        </span>
                                    </div>
                                    @if($vTrx->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $vTrx->description }}</p>
                                    @endif
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase shrink-0
                                    @if($statusAtVisit === 'LUNAS') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($statusAtVisit === 'SEBAGIAN') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                    @else bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 @endif">
                                    {{ $statusAtVisit }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-lg p-2.5">
                                    <span class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500 block">Total Transaksi Baru</span>
                                    <span class="font-bold font-mono text-gray-900 dark:text-gray-100 mt-0.5 block">Rp {{ number_format($trxAmount, 0, ',', '.') }}</span>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-lg p-2.5">
                                    <span class="text-[10px] uppercase font-semibold text-emerald-600 dark:text-emerald-400 block">Uang Masuk Transaksi Baru</span>
                                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5 block">Rp {{ number_format($trxInitialPaid, 0, ',', '.') }}</span>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-lg p-2.5">
                                    <span class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500 block">Sisa Menjadi Piutang</span>
                                    <span class="font-bold font-mono {{ $trxRemaining > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-0.5 block">Rp {{ number_format($trxRemaining, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Section C: Ringkasan Piutang Toko --}}
                    @if(!empty($recSummary))
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700 space-y-3 text-xs">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">Ringkasan Saldo Piutang Toko</p>
                            <div class="space-y-2 pt-1">
                                <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                                    <span>Saldo Piutang Sebelum Kunjungan:</span>
                                    <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($recSummary['balance_before'], 0, ',', '.') }}</span>
                                </div>
                                @if($recSummary['old_debt_paid'] > 0)
                                <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                                    <span>(-) Pembayaran Piutang Lama:</span>
                                    <span class="font-mono font-semibold">Rp {{ number_format($recSummary['old_debt_paid'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-[11px] pl-4">
                                    <span>Sisa Piutang Lama:</span>
                                    <span class="font-mono">Rp {{ number_format(max(0.0, round($recSummary['balance_before'] - $recSummary['old_debt_paid'], 2)), 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if($recSummary['new_tx_total'] > 0)
                                <div class="flex items-center justify-between text-gray-600 dark:text-gray-400 pt-1 border-t border-dashed border-gray-100 dark:border-gray-700/50">
                                    <span>(+) Transaksi Baru:</span>
                                    <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($recSummary['new_tx_total'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                                    <span>(-) Uang Masuk Transaksi Baru:</span>
                                    <span class="font-mono font-semibold">Rp {{ number_format($recSummary['new_tx_initial_paid'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-[11px] pl-4">
                                    <span>Sisa Transaksi Baru yang Belum Dibayar:</span>
                                    <span class="font-mono font-semibold text-amber-600 dark:text-amber-400">Rp {{ number_format($recSummary['new_tx_remaining'], 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <div class="flex items-center justify-between pt-2.5 border-t border-dashed border-gray-200 dark:border-gray-700 font-bold text-sm">
                                    <span class="text-gray-900 dark:text-gray-100">Saldo Piutang Toko Setelah Kunjungan:</span>
                                    <span class="font-mono {{ $recSummary['balance_after'] > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        Rp {{ number_format($recSummary['balance_after'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Fallback ringkasan legacy jika tidak ada store transaction --}}
                    @if($visitPayments->isEmpty() && $visitTransactions->isEmpty() && $visit->transaction_amount)
                    <div class="flex items-center justify-between pt-2 text-xs">
                        <span class="text-gray-500">Nominal Transaksi:</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">Rp {{ number_format($visit->transaction_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>
                @elseif($isDriverContext && $driverHasTx)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60 space-y-2">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">
                            Transaksi Pengiriman
                        </p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                            Ada Transaksi
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Nominal Transaksi:</span>
                        <span class="text-sm font-bold font-mono text-gray-900 dark:text-white">Rp {{ number_format($visit->transaction_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Metode Pembayaran:</span>
                        <span class="font-bold uppercase text-gray-800 dark:text-gray-200">{{ strtoupper($visit->payment_method ?? 'tunai') }}</span>
                    </div>
                </div>
                @endif

                @if(! $isDriverContext)
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Metode Pembayaran</p>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        @if(($visit->transaction_status ?? 'none') === 'none')
                            Tidak Ada Transaksi
                        @elseif($visit->payment_method === 'tunai')
                            Tunai
                        @elseif($visit->payment_method === 'transfer')
                            Transfer
                        @elseif($visit->payment_method === 'qris')
                            QRIS
                        @else
                            {{ ucfirst($visit->payment_method ?? '-') }}
                        @endif
                    </p>
                </div>
                @endif

                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Durasi {{ $isDriverContext ? 'Pengiriman' : 'Kunjungan' }}</p>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        @php
                            $durationLabel = '-';
                            if ($visit->check_in_at && $visit->check_out_at) {
                                $durationMinutes = max(0, (int) \Carbon\Carbon::parse($visit->check_in_at)->diffInMinutes(\Carbon\Carbon::parse($visit->check_out_at)));
                                $durationHours = intdiv($durationMinutes, 60);
                                $durationRemainder = $durationMinutes % 60;
                                $durationLabel = $durationHours > 0
                                    ? ($durationRemainder > 0 ? $durationHours . ' Jam ' . $durationRemainder . ' Menit' : $durationHours . ' Jam')
                                    : $durationMinutes . ' Menit';
                            }
                        @endphp
                        {{ $durationLabel }}
                    </p>
                </div>
            </div>
        @endif
        @endif
    </div>

    @push('scripts')
    <script>
        function visitDetailPdfExport(endpointUrl, defaultBaseName) {
            return {
                downloading: false,
                status: 'idle', // 'idle' | 'loading' | 'success' | 'error'
                async download() {
                    if (this.downloading) return;
                    this.downloading = true;
                    this.status = 'loading';

                    try {
                        const response = await fetch(endpointUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': '*/*',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Gagal mengunduh laporan PDF (Status: ' + response.status + ').');
                        }

                        const blob = await response.blob();

                        let downloadFilename = '';
                        const disposition = response.headers.get('content-disposition');
                        if (disposition && disposition.indexOf('filename=') !== -1) {
                            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                            if (matches != null && matches[1]) {
                                downloadFilename = matches[1].replace(/['"]/g, '');
                            }
                        }
                        if (!downloadFilename) {
                            downloadFilename = defaultBaseName + '.pdf';
                        }

                        const objectUrl = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.style.display = 'none';
                        link.href = objectUrl;
                        link.download = downloadFilename;
                        document.body.appendChild(link);
                        link.click();

                        setTimeout(() => {
                            window.URL.revokeObjectURL(objectUrl);
                            link.remove();
                        }, 1000);

                        this.status = 'success';
                        setTimeout(() => {
                            this.status = 'idle';
                            this.downloading = false;
                        }, 1500);
                    } catch (err) {
                        console.error(err);
                        this.status = 'error';
                        if (window.showToast) {
                            window.showToast(err.message || 'Gagal mengunduh PDF. Silakan coba lagi.', 'error');
                        } else {
                            alert(err.message || 'Gagal mengunduh PDF. Silakan coba lagi.');
                        }
                        setTimeout(() => {
                            this.status = 'idle';
                            this.downloading = false;
                        }, 2000);
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>