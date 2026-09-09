<x-app-layout>
    @section('breadcrumbs', 'Dashboard')

    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Halo, {{ Auth::user()->name }}!</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->isoFormat('dddd, D MMMM YYYY') }}</p>
            </div>
            @if($attendanceStatus === 'absent' || $attendanceStatus === 'canceled')
            <a href="{{ route('attendance.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold shadow-md shadow-[#0DA4CE]/20 hover:shadow-lg transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Check In Kerja
            </a>
            @elseif($attendanceStatus === 'working')
            <a href="{{ route('attendance.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-green-500 text-white rounded-xl text-sm font-semibold shadow-md shadow-green-500/20 hover:shadow-lg transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Check Out Kerja
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

        {{-- Laporan Presensi Saya --}}
        <div class="sw-card overflow-hidden">
            <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Laporan Presensi Saya</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Buat dan unduh laporan presensi Anda berdasarkan periode yang dipilih.</p>
                </div>
                <a href="{{ route('staff.report.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold shadow-md shadow-[#0DA4CE]/20 hover:shadow-lg transition">
                    Buat Laporan
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
