<x-app-layout>
    @section('breadcrumbs', 'Dashboard')

    <div class="animate-fade-in space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-4">
                <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="ISA SmartWork" class="w-12 h-12 object-contain shrink-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Admin</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->isoFormat('dddd, D MMMM YYYY') }}</p>
                </div>
            </div>
        </div>

        {{-- Baris Statistik --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 overflow-hidden">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="w-11 h-11 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight">{{ $totalSales }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">Total Sales</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 104 0 2 2 0 00-4 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0 2 2 0 00-4 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight">{{ $totalDriver ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">Total Driver</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="w-11 h-11 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight">{{ $totalStaff }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">Total Staff</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between min-w-0 overflow-hidden">
                <div class="min-w-0">
                    <div class="flex items-start justify-between gap-2 sm:gap-3 mb-2 min-w-0">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1 overflow-hidden">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div class="min-w-0 flex-1 overflow-hidden">
                                <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight truncate">{{ $totalStores }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">Toko Terdaftar</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.stores.index') }}" class="text-[11px] font-semibold text-[#0DA4CE] hover:underline shrink-0 pt-1 whitespace-nowrap">Kelola &rarr;</a>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 pt-2.5 mt-2 text-[11px] border-t border-gray-100 dark:border-gray-800">
                    <span class="text-amber-600 dark:text-amber-400 font-medium whitespace-nowrap">{{ $unassignedStoresCount ?? 0 }} Tanpa Sales</span>
                    <span class="text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">{{ $deliveryStoresCount ?? 0 }} Tujuan Kirim</span>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 col-span-1 sm:col-span-2 lg:col-span-4 min-w-0">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3.5 sm:gap-4">
                        <div class="w-11 h-11 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-1.5 sm:gap-2">
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $todayAttendance }}</p>
                                <span class="text-xs text-gray-500 dark:text-gray-400">dari {{ $attendanceBase }} pengguna</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 ml-1">{{ $attendanceRate }}%</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                                Presensi Hari Ini &middot; 
                                <span class="text-green-600 dark:text-green-400 font-semibold">{{ $hadirCount ?? $todayAttendance }} Hadir</span> &middot; 
                                <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ $izinCount ?? 0 }} Izin</span> &middot; 
                                <span class="text-pink-600 dark:text-pink-400 font-semibold">{{ $sakitCount ?? 0 }} Sakit</span> &middot; 
                                <span class="text-gray-500 dark:text-gray-400 font-semibold">{{ $totalAbsent }} Belum Presensi</span>
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.attendance.index') }}" class="inline-flex items-center text-xs font-semibold text-[#0DA4CE] hover:underline self-start md:self-auto shrink-0">Monitoring Lengkap &rarr;</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4">
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 p-3.5 border border-gray-100 dark:border-gray-800 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-gray-900 dark:text-white">Sales</span>
                            <span class="text-[11px] font-semibold text-green-700 dark:text-green-400">{{ $salesSudahPresensi ?? $salesPresent }} / {{ $salesTotal }} presensi</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            <span>{{ $salesPresent }} Hadir</span>
                            <span>&middot;</span>
                            <span class="text-amber-600">{{ $salesIzin ?? 0 }} Izin</span>
                            <span>&middot;</span>
                            <span class="text-pink-600">{{ $salesSakit ?? 0 }} Sakit</span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">{{ $salesAbsent }} belum presensi</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 p-3.5 border border-gray-100 dark:border-gray-800 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-gray-900 dark:text-white">Staff</span>
                            <span class="text-[11px] font-semibold text-green-700 dark:text-green-400">{{ $staffSudahPresensi ?? $staffPresent }} / {{ $staffTotal }} presensi</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            <span>{{ $staffPresent }} Hadir</span>
                            <span>&middot;</span>
                            <span class="text-amber-600">{{ $staffIzin ?? 0 }} Izin</span>
                            <span>&middot;</span>
                            <span class="text-pink-600">{{ $staffSakit ?? 0 }} Sakit</span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">{{ $staffAbsent }} belum presensi</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 p-3.5 border border-gray-100 dark:border-gray-800 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-gray-900 dark:text-white">Driver</span>
                            <span class="text-[11px] font-semibold text-green-700 dark:text-green-400">{{ $driverSudahPresensi ?? $driverPresent }} / {{ $driverTotal }} presensi</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            <span>{{ $driverPresent }} Hadir</span>
                            <span>&middot;</span>
                            <span class="text-amber-600">{{ $driverIzin ?? 0 }} Izin</span>
                            <span>&middot;</span>
                            <span class="text-pink-600">{{ $driverSakit ?? 0 }} Sakit</span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">{{ $driverAbsent }} belum presensi</p>
                    </div>
                </div>
            </div>
            {{-- Kunjungan Hari Ini --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between h-full min-w-0">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Kunjungan Hari Ini</span>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">{{ $todayVisits->count() }}</span>
                        <span class="text-[11px] text-gray-400">kunjungan</span>
                    </div>
                </div>
                <div class="pt-2.5 mt-2.5 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400 leading-tight">
                    <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $activeVisits }} berjalan</span> &middot;
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ $completedVisits }} selesai</span> &middot;
                    <span class="text-gray-400 font-medium">{{ $pendingVisits }} belum</span>
                </div>
            </div>

            {{-- Rute Aktif --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between h-full min-w-0">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Rute Aktif</span>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-extrabold text-[#0DA4CE]">{{ $activeRoutesCount + $activeDeliveryRoutesCount }}</span>
                        <span class="text-[11px] text-gray-400">total rute</span>
                    </div>
                </div>
                <div class="pt-2.5 mt-2.5 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400 leading-tight">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $activeRoutesCount }} Kunjungan</span> &middot;
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $activeDeliveryRoutesCount }} Pengiriman</span>
                </div>
            </div>

            {{-- Rute Selesai --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between h-full min-w-0">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Rute Selesai</span>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $completedRoutesCount + $completedDeliveryRoutesCount }}</span>
                        <span class="text-[11px] text-gray-400">total rute</span>
                    </div>
                </div>
                <div class="pt-2.5 mt-2.5 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400 leading-tight">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $completedRoutesCount }} Kunjungan</span> &middot;
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $completedDeliveryRoutesCount }} Pengiriman</span>
                </div>
            </div>

            {{-- Pengiriman Hari Ini --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between h-full min-w-0">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Pengiriman Hari Ini</span>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">{{ $totalDeliveriesCount }}</span>
                        <span class="text-[11px] text-gray-400">pengiriman</span>
                    </div>
                </div>
                <div class="pt-2.5 mt-2.5 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400 leading-tight">
                    <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $activeDeliveries }} berjalan</span> &middot;
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ $completedDeliveries }} selesai</span> &middot;
                    <span class="text-gray-400 font-medium">{{ $pendingDeliveries }} belum</span>
                </div>
            </div>
        </div>

        {{-- Ringkasan Uang Masuk & Piutang (Sales & Driver) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Card 1: Uang Masuk Sales Hari Ini --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Uang Masuk Sales Hari Ini</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#0DA4CE]/10 text-[#0DA4CE] dark:text-[#5BD8F7]">Harian</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">Total pembayaran transaksi baru + pembayaran piutang lama hari ini</p>
                    <p class="text-2xl font-bold font-mono text-gray-900 dark:text-white mt-2">
                        Rp {{ number_format($totalSalesCashInToday ?? 0, 0, ',', '.') }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Breakdown per Sales</p>
                        <div class="divide-y divide-gray-50 dark:divide-gray-800/60 max-h-36 overflow-y-auto pr-1">
                            @forelse($salesCashInSummary ?? [] as $item)
                            <div class="py-1.5 flex items-center justify-between text-xs gap-2">
                                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[140px]">{{ $item['name'] }}</span>
                                <span class="font-mono font-semibold text-gray-900 dark:text-white shrink-0">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 py-2">Belum ada sales</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('admin.receivables.summary.today-payments') }}" class="inline-flex items-center text-xs font-semibold text-[#0DA4CE] hover:underline">
                        Lihat Detail &rarr;
                    </a>
                </div>
            </div>

            {{-- Card 2: Piutang Sales --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Piutang Sales</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-500/10 text-red-600 dark:text-red-400">Saldo Berjalan</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">Total saldo piutang toko yang masih belum lunas</p>
                    <p class="text-2xl font-bold font-mono text-red-600 dark:text-red-400 mt-2">
                        Rp {{ number_format($totalSalesReceivable ?? 0, 0, ',', '.') }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Breakdown per Sales</p>
                        <div class="divide-y divide-gray-50 dark:divide-gray-800/60 max-h-36 overflow-y-auto pr-1">
                            @forelse($salesReceivableSummary ?? [] as $item)
                            <div class="py-1.5 flex items-center justify-between text-xs gap-2">
                                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[140px]">{{ $item['name'] }}</span>
                                <span class="font-mono font-semibold text-red-600 dark:text-red-400 shrink-0">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 py-2">Belum ada sales</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('admin.receivables.summary.by-sales') }}" class="inline-flex items-center text-xs font-semibold text-[#0DA4CE] hover:underline">
                        Lihat Detail &rarr;
                    </a>
                </div>
            </div>

            {{-- Card 3: Uang Masuk Driver Hari Ini --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Uang Masuk Driver Hari Ini</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400">Harian</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">Total pembayaran transaksi pengiriman Driver hari ini</p>
                    <p class="text-2xl font-bold font-mono text-gray-900 dark:text-white mt-2">
                        Rp {{ number_format($totalDriverCashInToday ?? 0, 0, ',', '.') }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Breakdown per Driver</p>
                        <div class="divide-y divide-gray-50 dark:divide-gray-800/60 max-h-36 overflow-y-auto pr-1">
                            @forelse($driverCashInSummary ?? [] as $item)
                            <div class="py-1.5 flex items-center justify-between text-xs gap-2">
                                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[140px]">{{ $item['name'] }}</span>
                                <span class="font-mono font-semibold text-gray-900 dark:text-white shrink-0">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 py-2">Belum ada driver</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('admin.driver.visits.index') }}" class="inline-flex items-center text-xs font-semibold text-amber-500 hover:underline">
                        Lihat Detail &rarr;
                    </a>
                </div>
            </div>
        </div>

        {{-- Sales & Driver Aktif Hari Ini --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sales &amp; Driver Aktif Hari Ini</h3>
                <div class="flex items-center gap-4 text-xs font-semibold"><span class="text-[#0DA4CE]">Sales Aktif: {{ $activeSalesCount }}</span><span class="text-amber-500">Driver Aktif: {{ $activeDriverCount }}</span></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100 dark:divide-gray-800">
                <div class="divide-y divide-gray-50 dark:divide-gray-800 max-h-[300px] overflow-y-auto">
                    <p class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider">Aktivitas Sales</p>
                    @forelse($activeSalesToday as $sales)
                    <div class="p-4 flex items-center gap-3"><div class="w-8 h-8 rounded-lg bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center text-xs font-bold shrink-0">{{ strtoupper(substr($sales['name'], 0, 2)) }}</div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $sales['name'] }}</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ $sales['activity'] }} &middot; {{ $sales['time'] }}</p></div></div>
                    @empty
                    <div class="p-4 text-xs text-gray-400">Tidak ada sales aktif</div>
                    @endforelse
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800 max-h-[300px] overflow-y-auto">
                    <p class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider">Aktivitas Driver</p>
                    @forelse($activeDriversToday as $driver)
                    <div class="p-4 flex items-center gap-3"><div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-bold shrink-0">{{ strtoupper(substr($driver['name'], 0, 2)) }}</div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $driver['name'] }}</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ $driver['activity'] }} &middot; {{ $driver['time'] }}</p></div></div>
                    @empty
                    <div class="p-4 text-xs text-gray-400">Tidak ada driver aktif</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Rute Kunjungan & Rute Pengiriman Terbaru --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rute Kunjungan Terbaru</h3>
                    <a href="{{ route('admin.routes.index') }}" class="text-xs font-medium text-[#0DA4CE] hover:text-[#097A99]">Lihat semua &rarr;</a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($recentRoutes as $route)
                    <a href="{{ route('route.show', $route->id) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 @if($route->computed_status === 'completed') bg-green-100 text-green-700 @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg></div>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $route->name }}</p><p class="text-xs text-gray-500">{{ $route->user->name }} &middot; {{ $route->stops->count() }} toko</p></div>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $route->computed_status }}</span>
                    </a>
                    @empty
                    <div class="p-8 text-center text-sm text-gray-500">Tidak ada rute kunjungan hari ini</div>
                    @endforelse
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rute Pengiriman Terbaru</h3>
                    <a href="{{ route('admin.driver.routes.index') }}" class="text-xs font-medium text-amber-500 hover:text-amber-600">Lihat semua &rarr;</a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($recentDeliveryRoutes as $route)
                    <a href="{{ route('route.show', $route->id) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-amber-100 text-amber-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg></div>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $route->name }}</p><p class="text-xs text-gray-500">{{ $route->user->name }} &middot; {{ $route->stops->count() }} tujuan</p></div>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $route->computed_status }}</span>
                    </a>
                    @empty
                    <div class="p-8 text-center text-sm text-gray-500">Tidak ada rute pengiriman hari ini</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Kunjungan Terbaru & Pengiriman Terbaru --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Kunjungan Terbaru</h3>
                    <a href="{{ route('admin.visits.index') }}" class="text-xs font-medium text-[#0DA4CE] hover:text-[#097A99]">Lihat semua &rarr;</a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($recentVisits as $visit)
                    <a href="{{ route('visit.show', $visit->id) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 @if($visit->status === 'completed') bg-green-100 text-green-700 @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $visit->user->name }}</p><p class="text-xs text-gray-500">{{ $visit->store->name }} &middot; {{ $visit->check_in_at?->format('H:i') }}</p></div>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $visit->status === 'completed' ? 'Selesai' : 'Berjalan' }}</span>
                    </a>
                    @empty
                    <div class="p-8 text-center text-sm text-gray-500">Tidak ada kunjungan hari ini</div>
                    @endforelse
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pengiriman Terbaru</h3>
                    <a href="{{ route('admin.driver.visits.index') }}" class="text-xs font-medium text-amber-500 hover:text-amber-600">Lihat semua &rarr;</a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($recentDeliveries as $visit)
                    <a href="{{ route('visit.show', $visit->id) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-amber-100 text-amber-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7l9-4 9 4v10l-9 4-9-4V7zm0 0l9 4 9-4M12 11v10"/></svg></div>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $visit->user->name }}</p><p class="text-xs text-gray-500">{{ $visit->store->name }} &middot; {{ $visit->check_in_at?->format('H:i') }}</p></div>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $visit->status === 'completed' ? 'Selesai' : 'Berjalan' }}</span>
                    </a>
                    @empty
                    <div class="p-8 text-center text-sm text-gray-500">Tidak ada pengiriman hari ini</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
