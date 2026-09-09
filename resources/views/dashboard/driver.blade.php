<x-app-layout>
    @section('breadcrumbs', 'Dashboard')

    <div class="animate-fade-in space-y-4">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Halo, {{ Auth::user()->name }}!</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->isoFormat('dddd, D MMMM YYYY') }}</p>
            </div>
            @if(!$todayAttendance || !$todayAttendance->clock_in)
            <a href="{{ route('attendance.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold shadow-md shadow-[#0DA4CE]/20 hover:shadow-lg transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Check In Kerja
            </a>
            @endif
        </div>

        {{-- Presensi Hari Ini --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border p-5
            @if($attendanceStatus === 'done') border-green-200 dark:border-green-800
            @elseif($attendanceStatus === 'working') border-amber-200 dark:border-amber-800
            @elseif($attendanceStatus === 'izin') border-amber-200 dark:border-amber-800
            @elseif($attendanceStatus === 'sakit') border-pink-200 dark:border-pink-800
            @elseif($attendanceStatus === 'canceled') border-red-200 dark:border-red-800
            @else border-gray-100 dark:border-gray-800 @endif">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0
                        @if($attendanceStatus === 'done') bg-green-100 dark:bg-green-900/30
                        @elseif($attendanceStatus === 'working') bg-amber-100 dark:bg-amber-900/30
                        @elseif($attendanceStatus === 'izin') bg-amber-100 dark:bg-amber-900/30
                        @elseif($attendanceStatus === 'sakit') bg-pink-100 dark:bg-pink-900/30
                        @elseif($attendanceStatus === 'canceled') bg-red-100 dark:bg-red-900/30
                        @else bg-gray-100 dark:bg-gray-800 @endif">
                        <svg class="w-7 h-7
                            @if($attendanceStatus === 'done') text-green-700 dark:text-green-400
                            @elseif($attendanceStatus === 'working') text-amber-600 dark:text-amber-400
                            @elseif($attendanceStatus === 'izin') text-amber-600 dark:text-amber-400
                            @elseif($attendanceStatus === 'sakit') text-pink-600 dark:text-pink-400
                            @elseif($attendanceStatus === 'canceled') text-red-600 dark:text-red-400
                            @else text-gray-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($attendanceStatus === 'izin')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            @elseif($attendanceStatus === 'sakit')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            @elseif($attendanceStatus === 'canceled')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Presensi Hari Ini</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold mt-1
                            @if($attendanceStatus === 'done') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                            @elseif($attendanceStatus === 'working') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                            @elseif($attendanceStatus === 'izin') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                            @elseif($attendanceStatus === 'sakit') bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400
                            @elseif($attendanceStatus === 'canceled') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                            @else bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 @endif">
                            @if($attendanceStatus === 'done') Presensi Selesai
                            @elseif($attendanceStatus === 'working') Sedang Bekerja
                            @elseif($attendanceStatus === 'izin') Izin
                            @elseif($attendanceStatus === 'sakit') Sakit
                            @elseif($attendanceStatus === 'canceled') Dibatalkan
                            @else Belum Presensi @endif
                        </span>
                        @if($todayAttendance && ($attendanceStatus === 'izin' || $attendanceStatus === 'sakit'))
                        <div class="mt-2 space-y-0.5 text-sm text-gray-500 dark:text-gray-400">
                            <p class="font-medium text-gray-800 dark:text-gray-200">Catatan: <span class="font-normal">{{ $todayAttendance->absence_note ?: '-' }}</span></p>
                        </div>
                        @elseif($todayAttendance && $todayAttendance->clock_in)
                        <div class="mt-2 space-y-0.5 text-sm text-gray-500 dark:text-gray-400">
                            <p>Check In: <span class="font-semibold text-gray-900 dark:text-white">{{ $todayAttendance->clock_in->format('H:i') }}</span></p>
                            @if($todayAttendance->clock_out)
                            <p>Check Out: <span class="font-semibold text-gray-900 dark:text-white">{{ $todayAttendance->clock_out->format('H:i') }}</span></p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @if($attendanceStatus === 'working')
                <a href="{{ route('attendance.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-green-500 text-white rounded-xl text-sm font-semibold shadow-md shadow-green-500/20 hover:shadow-lg transition">
                    Check Out Kerja
                </a>
                @elseif($attendanceStatus === 'absent')
                <a href="{{ route('attendance.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold shadow-md shadow-[#0DA4CE]/20 hover:shadow-lg transition">
                    Presensi Sekarang
                </a>
                @endif
            </div>
        </div>

        {{-- Stat Cards Hari Ini --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            {{-- Card 1: Total Rencana Pengiriman --}}
            <div class="sw-card p-4 sm:p-5 flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Total Pengiriman</p>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $todayPlanStats['total'] }}</p>
                </div>
            </div>

            {{-- Card 2: Pengiriman Selesai --}}
            <div class="sw-card p-4 sm:p-5 flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#90F022]/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Pengiriman Selesai</p>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $todayVisitStats['completed'] }}</p>
                </div>
            </div>

            {{-- Card 3: Pengiriman Bulan Ini --}}
            <div class="sw-card p-4 sm:p-5 flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Pengiriman Bulan Ini</p>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $monthlyStats['visits'] }}</p>
                </div>
            </div>

            {{-- Card 4: Uang Masuk Pengiriman --}}
            <div class="sw-card p-4 sm:p-5 flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Uang Masuk Pengiriman</p>
                    </div>
                </div>
                <div class="mt-3 min-w-0">
                    <p class="text-base sm:text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 truncate">Rp {{ number_format($todayVisitStats['cash_in'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Jadwal Pengiriman Hari Ini + Ringkasan Aktivitas --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="sw-card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Jadwal Pengiriman Hari Ini
                    </h3>
                    <a href="{{ route('route.index') }}" class="text-xs font-medium text-[#0DA4CE] hover:underline">Lihat Semua &rarr;</a>
                </div>
                <div class="p-3 divide-y divide-gray-50 dark:divide-gray-800">
                    @if($todayStops->count() > 0)
                        @foreach($todayStops as $stop)
                        <div class="flex items-center gap-3 p-2.5">
                            <span class="w-7 h-7 flex items-center justify-center rounded-lg text-xs font-bold shrink-0
                                @if($stop->computed_status === 'visited') bg-green-500 text-white
                                @elseif($stop->computed_status === 'in_progress') bg-amber-500 text-white
                                @elseif($stop->computed_status === 'skipped') bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400
                                @else bg-[#0DA4CE] text-white @endif">
                                {{ $stop->sequence }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $stop->store->name ?? 'Toko' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $stop->store->address ?? '' }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0
                                @if($stop->computed_status === 'visited') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                @elseif($stop->computed_status === 'in_progress') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                                @elseif($stop->computed_status === 'skipped') bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400
                                @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif">
                                @if($stop->computed_status === 'visited') Selesai
                                @elseif($stop->computed_status === 'in_progress') Proses
                                @elseif($stop->computed_status === 'skipped') Dilewati
                                @else Menunggu @endif
                            </span>
                        </div>
                        @endforeach
                    @else
                    <div class="text-center py-8 px-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada rencana pengiriman untuk hari ini.</p>
                        <a href="{{ route('route.create') }}" class="inline-flex items-center mt-3 px-4 py-2 bg-[#0DA4CE] text-white rounded-lg text-xs font-semibold hover:bg-[#097A99] transition">Buat Rencana Pengiriman</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="sw-card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Ringkasan Aktivitas Hari Ini
                    </h3>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($todayVisits as $visit)
                    <a href="{{ route('visit.show', $visit->id) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0
                            @if($visit->status === 'completed') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                            @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $visit->store->name ?? 'Toko' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $visit->check_in_at ? $visit->check_in_at->format('H:i') : '-' }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0
                            @if($visit->status === 'completed') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                            @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif">
                            {{ $visit->status === 'completed' ? 'Selesai' : 'Sedang Dikirim' }}
                        </span>
                    </a>
                    @empty
                    <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada pengiriman hari ini.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Statistik Bulanan --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="sw-card p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Rencana Bulan Ini</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $monthlyStats['routes'] }}</p>
                <span class="inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold
                    @if($monthlyStats['status'] === 'selesai') bg-[#90F022]/20 text-green-800 dark:bg-green-900/30 dark:text-green-400
                    @elseif($monthlyStats['status'] === 'berjalan') bg-[#0DA4CE]/10 text-[#0DA4CE] dark:bg-[#0DA4CE]/20
                    @else bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                    @endif">
                    @if($monthlyStats['status'] === 'selesai') Selesai
                    @elseif($monthlyStats['status'] === 'berjalan') Sedang Berjalan
                    @else Menunggu
                    @endif
                </span>
            </div>
            <div class="sw-card p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Pengiriman Bulan Ini</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $monthlyStats['visits'] }}</p>
            </div>
            <div class="sw-card p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Tingkat Penyelesaian</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $monthlyStats['routes'] > 0 ? round(($monthlyStats['completed_routes'] / $monthlyStats['routes']) * 100) : 0 }}%</p>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                window.location.reload();
            }
        });
    </script>
    @endpush
</x-app-layout>
