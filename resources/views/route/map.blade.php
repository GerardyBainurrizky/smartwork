<x-app-layout>
    @php
        $isDriver = (Auth::check() && Auth::user()->hasRole('driver')) || ($route->user && $route->user->hasRole('driver'));
    @endphp
    <x-slot name="header">
        <x-page-header icon="map" :title="$isDriver ? 'Peta Rute Pengiriman' : 'Peta Rute'" :subtitle="$isDriver ? 'Visualisasi jalur pengiriman dan progres pengiriman tujuan' : 'Visualisasi jalur perjalanan dan progres kunjungan toko'"></x-page-header>
    </x-slot>

    @php
        $hasStartLocation = $attendance && !empty($attendance->clock_in_lat) && !empty($attendance->clock_in_lng);
        $startPoint = $hasStartLocation ? [
            'lat' => (float) $attendance->clock_in_lat,
            'lng' => (float) $attendance->clock_in_lng,
            'time' => $attendance->clock_in ? $attendance->clock_in->format('H:i') : null,
            'address' => $attendance->clock_in_address ?: 'Lokasi Presensi Check In',
            'sales_name' => $route->user->name ?? ($isDriver ? 'Driver' : 'Sales'),
        ] : null;

        $mapStops = $route->stops->sortBy('sequence')->values()->map(function ($stop) use ($isDriver) {
            $computedStatus = $stop->computed_status;
            $visit = $stop->relationLoaded('visit') && $stop->visit !== null
                ? $stop->visit
                : ($stop->relationLoaded('anyVisit') ? $stop->anyVisit : $stop->anyVisit()->first());

            $visitTimestamp = $visit && $visit->check_in_at ? $visit->check_in_at->timestamp : null;
            $visitedTime = $visit && $visit->check_out_at ? $visit->check_out_at->format('H:i') : ($visit && $visit->check_in_at ? $visit->check_in_at->format('H:i') : null);
            $skippedTime = $stop->status === 'skipped' && $stop->updated_at ? $stop->updated_at->format('H:i') : null;
            $skippedTimestamp = $stop->status === 'skipped' && $stop->updated_at ? $stop->updated_at->timestamp : null;

            return [
                'id' => $stop->id,
                'name' => $stop->store->name ?? 'Toko',
                'code' => $stop->store->code ?? '',
                'address' => $stop->store->address ?? '-',
                'city' => $stop->store->city ?? '',
                'lat' => $stop->store->latitude ? (float) $stop->store->latitude : null,
                'lng' => $stop->store->longitude ? (float) $stop->store->longitude : null,
                'sequence' => $stop->sequence,
                'status' => $computedStatus,
                'status_label' => match($computedStatus) {
                    'visited' => $isDriver ? 'Selesai Dikirim' : 'Selesai Dikunjungi',
                    'in_progress' => $isDriver ? 'Sedang Dikirim' : 'Sedang Dikunjungi',
                    'skipped' => 'Dilewati',
                    default => 'Menunggu',
                },
                'estimated_minutes' => $stop->estimated_duration_minutes,
                'notes' => $stop->notes,
                'visited_time' => $visitedTime,
                'skipped_time' => $skippedTime,
                'visit_timestamp' => $visitTimestamp,
                'skipped_timestamp' => $skippedTimestamp,
            ];
        })->values();

        $mapGps = $route->gpsLocations->map(function ($loc) {
            return ['lat' => (float) $loc->latitude, 'lng' => (float) $loc->longitude, 'time' => $loc->recorded_at];
        })->values();

        $visitedCount = $mapStops->where('status', 'visited')->count();
        $inProgressCount = $mapStops->where('status', 'in_progress')->count();
        $totalStopsCount = $mapStops->count();
    @endphp

    <div class="max-w-7xl mx-auto space-y-5">
        <x-back-button href="{{ route('route.show', $route->id) }}" :label="$isDriver ? 'Kembali ke Detail Rute Pengiriman' : 'Kembali ke Detail Rute'" class="mb-2" />

        {{-- Route Info & Summary Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate">{{ $route->name }}</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0
                                @if($route->computed_status === 'draft') bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200
                                @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                @elseif($route->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                @else bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400
                                @endif">
                                @if($route->computed_status === 'draft') Menunggu
                                @elseif($route->computed_status === 'active') Sedang Berjalan
                                @elseif($route->computed_status === 'completed') Selesai
                                @elseif($route->computed_status === 'cancelled') Dibatalkan
                                @endif
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                            <span>{{ $isDriver ? 'Driver' : 'Sales' }}: <strong>{{ $route->user->name ?? ($isDriver ? 'Driver' : 'Sales') }}</strong></span>
                            <span>&middot;</span>
                            <span>{{ \Carbon\Carbon::parse($route->date)->isoFormat('dddd, D MMMM YYYY') }}</span>
                            <span>&middot;</span>
                            <span>Progres: <strong class="text-gray-900 dark:text-white">{{ $visitedCount }}/{{ $totalStopsCount }} {{ $isDriver ? 'Tujuan Selesai' : 'Toko Selesai' }}</strong></span>
                        </p>
                    </div>

                    {{-- Legend Progres Visual --}}
                    <div class="flex items-center flex-wrap gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-semibold text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Titik Mulai
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-300 border border-slate-200 dark:border-gray-700 font-semibold text-[11px]">
                            <span class="w-2.5 h-1 bg-slate-500 rounded-sm"></span>
                            Sudah Ditempuh
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-semibold text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            {{ $isDriver ? 'Selesai Dikirim' : 'Selesai Dikunjungi' }}
                        </span>
                        @if($inProgressCount > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-semibold text-[11px]">
                            <span class="w-2.5 h-1 bg-amber-500 rounded-sm animate-pulse"></span>
                            {{ $isDriver ? 'Sedang Dikirim' : 'Sedang Dikunjungi' }}
                        </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800 font-semibold text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            Dilewati
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800 font-semibold text-[11px]">
                            <span class="w-2.5 h-1 bg-[#0DA4CE] rounded-sm"></span>
                            Rute Berikutnya
                        </span>
                    </div>
                </div>
            </div>

            {{-- Status Presensi Alert Notice --}}
            @if(!$hasStartLocation)
            <div class="px-5 py-3 bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Lokasi awal belum tersedia karena {{ $isDriver ? 'Driver' : 'Sales' }} belum melakukan Check In presensi pada tanggal rute ini. Jalur visual dimulai langsung dari tujuan urutan pertama.</span>
            </div>
            @endif

            {{-- Container Map --}}
            <div class="relative w-full">
                <div id="map" style="height: 540px;" class="w-full z-10"></div>
                <div id="route-routing-status" class="absolute bottom-4 left-4 z-20 pointer-events-none"></div>
            </div>
        </div>

        {{-- Route Stops Flow Timeline Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>{{ $isDriver ? 'Urutan Perjalanan Pengiriman' : 'Urutan Perjalanan Kunjungan' }}</span>
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    {{ $visitedCount }} dari {{ $totalStopsCount }} Selesai
                </span>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                {{-- Start Point Flow --}}
                @if($hasStartLocation)
                <div class="shrink-0 flex items-center gap-2 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 px-3 py-2 rounded-xl text-xs">
                    <div class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[10px] shrink-0">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-indigo-900 dark:text-indigo-200">Mulai: Presensi</p>
                        <p class="text-[10px] text-indigo-700 dark:text-indigo-300">{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-' }}</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif

                {{-- Stops Flow --}}
                @foreach($mapStops as $index => $stop)
                <div class="shrink-0 flex items-center gap-2 px-3 py-2 rounded-xl text-xs border
                    @if($stop['status'] === 'visited') bg-emerald-50/80 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/80 text-emerald-900 dark:text-emerald-200
                    @elseif($stop['status'] === 'in_progress') bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-200
                    @elseif($stop['status'] === 'skipped') bg-orange-50/90 dark:bg-orange-950/40 border-orange-300/80 dark:border-orange-800/70 text-orange-900 dark:text-orange-200
                    @else bg-cyan-50/70 dark:bg-cyan-950/40 border-cyan-200 dark:border-cyan-800 text-cyan-950 dark:text-cyan-200
                    @endif">
                    <div class="w-6 h-6 rounded-full font-bold flex items-center justify-center text-[11px] shrink-0 text-white
                        @if($stop['status'] === 'visited') bg-emerald-500
                        @elseif($stop['status'] === 'in_progress') bg-amber-500
                        @elseif($stop['status'] === 'skipped') bg-orange-500
                        @else bg-[#0DA4CE]
                        @endif">
                        @if($stop['status'] === 'visited')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @elseif($stop['status'] === 'skipped')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        @else
                            {{ $stop['sequence'] }}
                        @endif
                    </div>
                    <div class="max-w-[150px] truncate">
                        <p class="font-bold truncate">{{ $stop['name'] }}</p>
                        <p class="text-[10px] opacity-80">{{ $stop['status_label'] }}</p>
                    </div>
                </div>

                @if(!$loop->last)
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
                @endforeach
            </div>
        </div>

        {{-- Estimasi Perjalanan Per Segmen --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span>{{ $isDriver ? 'Estimasi Perjalanan Pengiriman' : 'Estimasi Perjalanan Kunjungan' }}</span>
            </h3>
            <div id="segment-timeline-panel" class="space-y-0.5">
                <p class="text-xs text-gray-400 dark:text-gray-500 text-center py-4">Menghitung estimasi perjalanan...</p>
            </div>
        </div>

        {{-- GPS Tracking Points (if active) --}}
        @if($route->status === 'active' && ($route->gpsLocations->count() ?? 0) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
            <div class="p-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#0DA4CE] animate-pulse"></span>
                    <span>Riwayat Titik GPS Perjalanan {{ $isDriver ? 'Driver' : 'Sales' }}</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60 text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/40 text-gray-500 dark:text-gray-400 font-semibold">
                            <tr>
                                <th class="px-4 py-2.5">Waktu</th>
                                <th class="px-4 py-2.5">Latitude</th>
                                <th class="px-4 py-2.5">Longitude</th>
                                <th class="px-4 py-2.5">Akurasi</th>
                                <th class="px-4 py-2.5">Kecepatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60 text-gray-700 dark:text-gray-300">
                            @foreach($route->gpsLocations->take(15) as $gps)
                            <tr>
                                <td class="px-4 py-2.5 font-medium whitespace-nowrap text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($gps->recorded_at)->format('H:i:s') }}
                                </td>
                                <td class="px-4 py-2.5 font-mono">{{ $gps->latitude }}</td>
                                <td class="px-4 py-2.5 font-mono">{{ $gps->longitude }}</td>
                                <td class="px-4 py-2.5">{{ $gps->accuracy ? round($gps->accuracy, 1) . 'm' : '-' }}</td>
                                <td class="px-4 py-2.5">{{ $gps->speed ? round($gps->speed, 1) . ' m/s' : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const startPoint = @json($startPoint);
            const stops = @json($mapStops);
            const gpsLocations = @json($mapGps);

            const validStops = stops.filter(s => s.lat && s.lng);

            // Jika tidak ada koordinat sama sekali (baik presensi maupun toko)
            if (!startPoint && validStops.length === 0) {
                document.getElementById('map').innerHTML = `
                    <div class="h-full flex items-center justify-center p-8 bg-gray-50 dark:bg-gray-900">
                        <div class="text-center">
                            <div class="w-14 h-14 rounded-2xl bg-gray-200 dark:bg-gray-800 text-gray-400 mx-auto flex items-center justify-center mb-3">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            </div>
                            <p class="font-bold text-gray-800 dark:text-gray-200 text-sm">Koordinat Belum Tersedia</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm">Toko dalam rute ini belum memiliki data koordinat GPS yang valid.</p>
                        </div>
                    </div>`;
                return;
            }

            // Kumpulkan seluruh titik untuk menentukan batas peta (bounds)
            const allPoints = [];
            if (startPoint) {
                allPoints.push([startPoint.lat, startPoint.lng]);
            }
            validStops.forEach(s => allPoints.push([s.lat, s.lng]));

            const bounds = L.latLngBounds(allPoints);
            const map = L.map('map', {
                scrollWheelZoom: false
            }).fitBounds(bounds.pad(0.25));

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);

            // Palet Warna Status Marker
            const markerColors = {
                visited: '#10B981',      // Emerald Green (Selesai Dikunjungi)
                in_progress: '#F59E0B',  // Amber (Sedang Dikunjungi)
                skipped: '#F97316',      // Orange / Amber (Dilewati - bukan biru aktif/error)
                pending: '#0DA4CE',      // Cyan (Menunggu / Kunjungan Berikutnya)
            };

            // 1. Tambahkan Marker Titik Awal Presensi Sales (jika tersedia)
            if (startPoint) {
                const startIcon = L.divIcon({
                    className: 'start-marker',
                    html: `
                        <div style="background:#4F46E5;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.35);">
                            <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17],
                });

                L.marker([startPoint.lat, startPoint.lng], { icon: startIcon, zIndexOffset: 1000 })
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width:200px;font-family:inherit;padding:2px;">
                            <div style="display:inline-block;padding:2px 8px;border-radius:8px;font-size:10px;font-weight:bold;background:#EEF2FF;color:#4F46E5;margin-bottom:4px;">
                                TITIK AWAL: PRESENSI {{ $isDriver ? 'DRIVER' : 'SALES' }}
                            </div>
                            <h4 style="margin:0;font-size:13px;font-weight:bold;color:#111827;">${startPoint.sales_name}</h4>
                            <p style="margin:4px 0 0 0;font-size:11px;color:#4B5563;">Check In: <strong>${startPoint.time || '-'}</strong></p>
                            <p style="margin:2px 0 0 0;font-size:11px;color:#6B7280;line-height:1.3;">${startPoint.address}</p>
                            <div style="margin-top:6px;font-size:10px;color:#9CA3AF;font-family:monospace;">${startPoint.lat.toFixed(6)}, ${startPoint.lng.toFixed(6)}</div>
                        </div>
                    `);
            }

            // 2. Tambahkan Marker Toko (Nomor urutan tetap sesuai urutan rencana RouteStop)
            validStops.forEach((stop) => {
                const color = markerColors[stop.status] || markerColors.pending;
                const isVisited = stop.status === 'visited';
                const isInProgress = stop.status === 'in_progress';
                const isSkipped = stop.status === 'skipped';
                const pulseClass = isInProgress ? 'animate-pulse' : '';

                let innerIcon = `<span style="font-size:12px;font-weight:800;">${stop.sequence}</span>`;
                if (isVisited) {
                    innerIcon = '<svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
                } else if (isSkipped) {
                    innerIcon = '<svg style="width:15px;height:15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>';
                }

                const stopIcon = L.divIcon({
                    className: 'store-marker',
                    html: `
                        <div style="background:${color};color:white;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;border:3px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);" class="${pulseClass}">
                            ${innerIcon}
                        </div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15],
                });

                const markerZIndex = isInProgress ? 800 : (isVisited ? 400 : (isSkipped ? 350 : 500));

                L.marker([stop.lat, stop.lng], { icon: stopIcon, zIndexOffset: markerZIndex })
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width:200px;font-family:inherit;padding:2px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                <span style="font-weight:bold;font-size:11px;color:${color};background:${color}18;padding:2px 7px;border-radius:6px;">
                                    Nomor Toko #${stop.sequence} &middot; ${stop.status_label}
                                </span>
                                <span style="font-size:10px;color:#6B7280;font-family:monospace;">${stop.code}</span>
                            </div>
                            <h4 style="margin:0;font-size:13px;font-weight:bold;color:#111827;">${stop.name}</h4>
                            <p style="margin:3px 0 0 0;font-size:11px;color:#6B7280;line-height:1.3;">${stop.address}</p>
                            ${stop.visited_time ? `<p style="margin:3px 0 0 0;font-size:11px;color:#10B981;font-weight:bold;">Waktu {{ $isDriver ? 'Pengiriman' : 'Kunjungan' }}: ${stop.visited_time}</p>` : ''}
                            ${stop.skipped_time ? `<p style="margin:3px 0 0 0;font-size:11px;color:#F97316;font-weight:bold;">Dilewati pada: ${stop.skipped_time}</p>` : ''}
                            ${stop.estimated_minutes ? `<p style="margin:4px 0 0 0;font-size:11px;color:#0DA4CE;font-weight:600;">Estimasi: ${stop.estimated_minutes} Menit</p>` : ''}
                            ${stop.notes ? `                             <p style="margin:4px 0 0 0;font-size:11px;color:#4B5563;background:#F3F4F6;padding:3px 6px;border-radius:4px;">Catatan {{ $isDriver ? 'Pengiriman' : 'Kunjungan' }}: ${stop.notes}</p>` : ''}
                        </div>
                    `);
            });

            // 3. Bangun Segmen Perjalanan Aktual (Segmen Selesai Kronologis + Segmen Aktif/Berikutnya)
            await buildAndRenderActualRouteSegments(map, startPoint, validStops);

            // 4. Gambar Jejak GPS Aktual (Jika Rute Sedang Berjalan & Ada GPS)
            if (gpsLocations.length > 0) {
                const gpsCoords = gpsLocations.map(l => [l.lat, l.lng]);
                L.polyline(gpsCoords, {
                    color: '#6366F1',
                    weight: 3,
                    opacity: 0.7,
                    dashArray: '6, 6',
                }).addTo(map);

                gpsLocations.forEach(loc => {
                    L.circleMarker([loc.lat, loc.lng], {
                        radius: 3.5,
                        fillColor: '#6366F1',
                        color: '#fff',
                        weight: 1.5,
                        opacity: 1,
                        fillOpacity: 0.85,
                    }).addTo(map);
                });
            }
        });

        /**
         * Render rute jalan nyata per segmen dengan alur perjalanan aktual:
         * - Segmen Riwayat Selesai / Dilewati: Mengikuti urutan kronologis aktual kunjungan/dilewati (ABU-ABU / MUTED)
         * - Segmen Sedang Berlangsung (In Progress): Dari titik aktual terakhir -> toko aktif (AMBER AKTIF)
         * - Segmen Rute Berikutnya (Pending): Dari titik aktual terakhir -> toko-toko belum selesai (BIRU UTAMA)
         *
         * Ketentuan Penting:
         * 1. Jalur menuju toko 'Dilewati' TIDAK BOLEH HILANG; jalur tersebut tetap tampil sebagai riwayat abu-abu.
         * 2. Jalur biru aktif berpindah menuju toko tujuan aktif berikutnya yang valid.
         */
        async function buildAndRenderActualRouteSegments(map, startPoint, validStops) {
            const statusEl = document.getElementById('route-routing-status');
            const isDriver = {{ $isDriver ? 'true' : 'false' }};
            const labelTitik = isDriver ? 'Tujuan Pengiriman' : 'Toko';

            // 1. KELOMPOK TOKO SELESAI / DILEWATI (Riwayat Perjalanan Masa Lalu)
            const historyStops = validStops
                .filter(s => s.status === 'visited' || s.status === 'skipped')
                .sort((a, b) => {
                    const timeA = a.visit_timestamp || a.skipped_timestamp || null;
                    const timeB = b.visit_timestamp || b.skipped_timestamp || null;
                    if (timeA && timeB) return timeA - timeB;
                    return a.sequence - b.sequence;
                });

            // 2. KELOMPOK TOKO SEDANG DIKUNJUNGI (in_progress)
            const inProgressStops = validStops.filter(s => s.status === 'in_progress');
            const activeStop = inProgressStops.length > 0 ? inProgressStops[0] : null;

            // 3. KELOMPOK TOKO BELUM DIKUNJUNGI (pending)
            const pendingStops = validStops
                .filter(s => s.status === 'pending')
                .sort((a, b) => a.sequence - b.sequence);

            // Bangun daftar segmen berurutan: [{ from, to, status }]
            const segmentDefs = [];

            // A. RIWAYAT
            const historyChain = [];
            if (startPoint) historyChain.push(startPoint);
            historyStops.forEach(s => historyChain.push(s));
            for (let i = 1; i < historyChain.length; i++) {
                segmentDefs.push({ from: historyChain[i - 1], to: historyChain[i], status: 'visited' });
            }

            // B. TITIK TERAKHIR
            let lastPoint = historyStops.length > 0
                ? historyStops[historyStops.length - 1]
                : (startPoint || null);

            // C. IN PROGRESS
            if (activeStop) {
                if (lastPoint) segmentDefs.push({ from: lastPoint, to: activeStop, status: 'in_progress' });
                lastPoint = activeStop;
            }

            // D. PENDING
            if (pendingStops.length > 0) {
                const remainingChain = [];
                if (lastPoint) remainingChain.push(lastPoint);
                pendingStops.forEach(s => remainingChain.push(s));
                for (let j = 1; j < remainingChain.length; j++) {
                    segmentDefs.push({ from: remainingChain[j - 1], to: remainingChain[j], status: 'pending' });
                }
            }

            // Eksekusi semua segmen secara paralel
            let segmentResults = [];
            try {
                segmentResults = await Promise.all(
                    segmentDefs.map(def => drawSingleRoadSegment(map, def.from, def.to, def.status))
                );
            } catch (err) {
                console.info('Routing segmen selesai.');
            }

            // Bangun data segmen lengkap (from name + to name + distance + duration)
            const segmentData = segmentDefs.map((def, i) => {
                const fromName = (def.from === startPoint)
                    ? 'Lokasi Presensi'
                    : (def.from.name || labelTitik);
                const toName = def.to.name || labelTitik;
                const res = segmentResults[i] || { distance: 0, duration: 0 };
                return {
                    fromName,
                    toName,
                    toSeq: def.to.sequence || null,
                    status: def.status,
                    distanceM: res.distance || 0,
                    durationS: res.duration || 0,
                };
            });

            // Render panel per-segmen
            renderSegmentPanel(segmentData, isDriver, labelTitik);

            // Overlay map: ringkasan jumlah segmen saja (bukan total jalur)
            const totalSegments = segmentData.length;
            if (statusEl && totalSegments > 0) {
                statusEl.innerHTML = `
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white/95 dark:bg-gray-900/95 text-gray-800 dark:text-gray-100 shadow-md border border-gray-200 dark:border-gray-700 text-xs font-semibold backdrop-blur-sm pointer-events-auto">
                        <svg class="w-3.5 h-3.5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <span>${totalSegments} segmen perjalanan</span>
                    </div>`;
            }
        }

        function renderSegmentPanel(segmentData, isDriver, labelTitik) {
            const panelEl = document.getElementById('segment-timeline-panel');
            if (!panelEl) return;

            if (segmentData.length === 0) {
                panelEl.innerHTML = `
                    <p class="text-xs text-gray-400 dark:text-gray-500 text-center py-4">
                        Estimasi perjalanan belum tersedia.
                    </p>`;
                return;
            }

            const statusColor = {
                visited: { dot: '#64748B', text: 'text-slate-500 dark:text-slate-400', bg: 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700' },
                in_progress: { dot: '#F59E0B', text: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-700' },
                pending: { dot: '#0DA4CE', text: 'text-[#0DA4CE]', bg: 'bg-cyan-50 dark:bg-cyan-950/40 border-cyan-200 dark:border-cyan-700' },
            };

            let html = '';

            segmentData.forEach((seg, idx) => {
                const sc = statusColor[seg.status] || statusColor.pending;
                const km = seg.distanceM > 0 ? (seg.distanceM / 1000).toFixed(1) : null;
                const mins = seg.durationS > 0 ? Math.round(seg.durationS / 60) : null;

                // Titik FROM hanya ditampilkan pada segmen pertama
                if (idx === 0) {
                    const fromIsStart = seg.fromName === 'Lokasi Presensi';
                    html += `
                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 ${fromIsStart ? 'bg-indigo-600 border-white' : 'bg-[#0DA4CE] border-white'} shadow">
                                ${fromIsStart
                                    ? `<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`
                                    : `<span class="text-white text-[11px] font-bold">${seg.toSeq ? seg.toSeq - 1 : '?'}</span>`
                                }
                            </div>
                        </div>
                        <div class="flex-1 min-w-0 pb-0.5 pt-1">
                            <p class="text-xs font-bold text-gray-900 dark:text-white leading-tight">${seg.fromName}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500">${fromIsStart ? 'Titik awal perjalanan' : labelTitik}</p>
                        </div>
                    </div>`;
                }

                // Connector dengan info segmen
                const distText = km ? `${km} km` : null;
                const timeText = mins ? `Estimasi normal ±${mins} mnt` : 'Estimasi waktu belum tersedia';
                html += `
                    <div class="flex items-stretch gap-3">
                        <div class="flex flex-col items-center shrink-0 w-8">
                            <div class="w-0.5 flex-1 my-0.5" style="background-color:${sc.dot};opacity:0.5;"></div>
                        </div>
                        <div class="flex-1 min-w-0 py-1.5">
                            <div class="inline-flex flex-wrap items-center gap-x-2 gap-y-1 px-2.5 py-1 rounded-lg border text-[11px] font-semibold ${sc.bg} ${sc.text}">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                ${distText ? `<span>${distText}</span><span class="opacity-40">•</span>` : ''}
                                <span>${timeText}</span>
                            </div>
                        </div>
                    </div>`;

                // Titik TO
                const toSeqLabel = seg.toSeq ? `${isDriver ? 'Tujuan Pengiriman' : 'Toko'} #${seg.toSeq}` : labelTitik;
                const toIsLast = idx === segmentData.length - 1;
                html += `
                    <div class="flex items-start gap-3 ${toIsLast ? '' : 'mb-0'}">
                        <div class="flex flex-col items-center shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 border-white shadow text-white text-[11px] font-bold" style="background-color:${sc.dot};">
                                ${seg.status === 'visited'
                                    ? `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>`
                                    : (seg.status === 'in_progress'
                                        ? `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`
                                        : `<span>${seg.toSeq || '?'}</span>`)
                                }
                            </div>
                        </div>
                        <div class="flex-1 min-w-0 pb-0.5 pt-1">
                            <p class="text-xs font-bold text-gray-900 dark:text-white leading-tight">${seg.toName}</p>
                            <p class="text-[10px] ${sc.text}">${toSeqLabel}</p>
                        </div>
                    </div>`;
            });

            panelEl.innerHTML = html;
        }

        /**
         * Fetch rute 1 segmen (from -> to) dari OSRM
         */
        async function drawSingleRoadSegment(map, from, to, status) {
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${from.lng},${from.lat};${to.lng},${to.lat}?overview=full&geometries=geojson`;

            // Konfigurasi visual per status segmen
            let mainColor = '#0DA4CE';
            let glowColor = '#097A99';
            let weight = 4.5;
            let glowWeight = 7;
            let opacity = 0.95;
            let glowOpacity = 0.35;
            let dashArray = null;

            if (status === 'visited') {
                // SUDAH DITEMPUH: Abu-abu / Muted
                mainColor = '#64748B'; // Slate gray
                glowColor = '#475569';
                weight = 3.5;
                glowWeight = 5.5;
                opacity = 0.55;
                glowOpacity = 0.2;
                dashArray = '6, 4';
            } else if (status === 'in_progress') {
                // SEDANG DIKUNJUNGI: Glowing Amber
                mainColor = '#F59E0B';
                glowColor = '#D97706';
                weight = 5.5;
                glowWeight = 8.5;
                opacity = 1.0;
                glowOpacity = 0.45;
            } else {
                // RUTE BERIKUTNYA / MENUNGGU: Cyan Primary Brand
                mainColor = '#0DA4CE';
                glowColor = '#097A99';
                weight = 4.5;
                glowWeight = 7;
                opacity = 0.95;
                glowOpacity = 0.35;
            }

            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 6500);

                const response = await fetch(osrmUrl, { signal: controller.signal });
                clearTimeout(timeoutId);

                const data = await response.json();

                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    
                    // Outer glow
                    L.geoJSON(route.geometry, {
                        style: {
                            color: glowColor,
                            weight: glowWeight,
                            opacity: glowOpacity,
                            lineCap: 'round',
                            lineJoin: 'round',
                        }
                    }).addTo(map);

                    // Main road line
                    L.geoJSON(route.geometry, {
                        style: {
                            color: mainColor,
                            weight: weight,
                            opacity: opacity,
                            dashArray: dashArray,
                            lineCap: 'round',
                            lineJoin: 'round',
                        }
                    }).addTo(map);

                    return { distance: route.distance, duration: route.duration };
                }
                throw new Error('Segment route error');
            } catch (e) {
                // Fallback polyline garis lurus jika OSRM gagal
                L.polyline([[from.lat, from.lng], [to.lat, to.lng]], {
                    color: mainColor,
                    weight: weight,
                    opacity: opacity,
                    dashArray: dashArray || '8, 6',
                }).addTo(map);

                return { distance: 0, duration: 0 };
            }
        }
    </script>

    <style>
        .custom-marker, .start-marker, .store-marker {
            background: transparent;
            border: none;
        }
        .leaflet-popup-content-wrapper {
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
    </style>
</x-app-layout>
