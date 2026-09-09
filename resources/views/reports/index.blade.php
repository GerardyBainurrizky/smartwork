<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Pusat Laporan & Monitoring Operasional') }}
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Ringkasan statistik real-time &amp; modul laporan resmi &middot; PT ISA Tri Selaras Gemilang
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                    Live Data
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-8 pb-12" x-data="{}" x-init="
        const scrollToCard = () => {
            const hash = window.location.hash;
            if (hash) {
                const targetEl = document.querySelector(hash);
                if (targetEl) {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
            }
            const lastCard = sessionStorage.getItem('admin_reports_last_card');
            if (lastCard) {
                sessionStorage.removeItem('admin_reports_last_card');
                const targetEl = document.getElementById(lastCard);
                if (targetEl) {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        };
        $nextTick(() => {
            scrollToCard();
            setTimeout(scrollToCard, 100);
        });
    ">
        {{-- ========================================================================= --}}
        {{-- SECTION A: RINGKASAN OPERASIONAL HARI INI                                 --}}
        {{-- ========================================================================= --}}
        <section class="space-y-4">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-2.5 h-5 bg-[#0DA4CE] rounded-sm"></div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                            Ringkasan Operasional Hari Ini
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Aktivitas harian presensi, kunjungan sales, dan pengiriman driver per tanggal {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                {{-- 1. Presensi Hari Ini --}}
                <a id="card-attendance-today" href="{{ route('admin.reports.attendance', ['period' => 'today']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-attendance-today')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-[#0DA4CE]/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Presensi Hadir</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ number_format($todayAttendance) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">user</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">Total check-in aktif</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </a>

                {{-- 2. Izin Hari Ini --}}
                <a id="card-attendance-izin" href="{{ route('admin.reports.attendance', ['period' => 'today', 'status' => 'izin']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-attendance-izin')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-amber-500/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Izin Hari Ini</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">{{ number_format($todayIzin) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">orang</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">Status pengajuan izin</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                </a>

                {{-- 3. Sakit Hari Ini --}}
                <a id="card-attendance-sakit" href="{{ route('admin.reports.attendance', ['period' => 'today', 'status' => 'sakit']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-attendance-sakit')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-rose-500/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Sakit Hari Ini</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-rose-600 dark:text-rose-400 tracking-tight">{{ number_format($todaySakit) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">orang</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">Status surat sakit</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                    </div>
                </a>

                {{-- 4. Sales Aktif & Master User --}}
                <a id="card-users-sales" href="{{ route('admin.reports.users', ['role' => 'sales']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-users-sales')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-emerald-500/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Sales Aktif</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">{{ number_format($activeSalesCount) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">/ {{ number_format($totalUsers) }} user</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">Master sales status aktif</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                    </div>
                </a>

                {{-- 5. Kunjungan Sales Hari Ini --}}
                <a id="card-visits-today" href="{{ route('admin.reports.visits', ['period' => 'today']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-visits-today')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-indigo-500/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Kunjungan Sales</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400 tracking-tight">{{ number_format($todaySalesVisits) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">check-in</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">
                                Selesai: <strong class="text-gray-700 dark:text-gray-300 font-semibold">{{ $todaySalesVisitsCompleted }}</strong> &middot; Skip: {{ $todaySalesVisitsSkipped }}
                            </p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                </a>

                {{-- 6. Rencana Kunjungan Sales Hari Ini --}}
                <a id="card-routes-today" href="{{ route('admin.reports.routes', ['period' => 'today']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-routes-today')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-violet-500/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Rencana Kunjungan</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-violet-600 dark:text-violet-400 tracking-tight">{{ number_format($todayPlannedSalesStops) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">toko</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">
                                Dari {{ $todaySalesRoutesCount }} rute sales hari ini
                            </p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-violet-500/10 text-violet-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        </div>
                    </div>
                </a>

                {{-- 7. Pengiriman Driver Hari Ini --}}
                <a id="card-driver-visits-today" href="{{ route('admin.reports.driver-visits', ['period' => 'today']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-visits-today')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-cyan-500/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Pengiriman Driver</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-cyan-600 dark:text-cyan-400 tracking-tight">{{ number_format($todayDriverDeliveries) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">tujuan</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">
                                Selesai: <strong class="text-gray-700 dark:text-gray-300 font-semibold">{{ $todayDriverDeliveriesCompleted }}</strong> &middot; Skip: {{ $todayDriverDeliveriesSkipped }}
                            </p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                    </div>
                </a>

                {{-- 8. Rencana Pengiriman Driver Hari Ini --}}
                <a id="card-driver-routes-today" href="{{ route('admin.reports.driver-routes', ['period' => 'today']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-routes-today')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-teal-500/40 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1 pr-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block truncate">Rencana Pengiriman</span>
                            <div class="mt-1.5 flex items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-extrabold text-teal-600 dark:text-teal-400 tracking-tight">{{ number_format($todayPlannedDriverStops) }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">tujuan</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 truncate">
                                Dari {{ $todayDriverRoutesCount }} rute driver hari ini
                            </p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-teal-500/10 text-teal-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 18h-2m-2.5-4h11.5m-11.5-4H20M5 6h12l4 5v5H5V6z"/></svg>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Financial Cash Flow Hari Ini --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 pt-1">
                <div class="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 dark:from-blue-950/40 dark:to-indigo-950/40 rounded-2xl border border-blue-200 dark:border-blue-900/50 p-4 sm:p-5 flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider block">Transaksi Masuk Sales Hari Ini</span>
                            <p class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                Rp{{ number_format($todaySalesCashIn ?? $todaySalesNewTransactionsAmount ?? 0, 0, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total uang yang diterima dari transaksi baru dan pembayaran piutang lama hari ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-blue-100/60 dark:border-blue-900/40 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
                        <span class="text-gray-600 dark:text-gray-300">
                            Uang Masuk Trx Baru: <strong class="text-gray-900 dark:text-white font-mono">Rp{{ number_format($todaySalesNewTxPaid ?? 0, 0, ',', '.') }}</strong>
                        </span>
                        <span class="text-gray-600 dark:text-gray-300">
                            Bayar Piutang Lama: <strong class="text-gray-900 dark:text-white font-mono">Rp{{ number_format($todaySalesOldDebtPaid ?? 0, 0, ',', '.') }}</strong>
                        </span>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-950/40 dark:to-teal-950/40 rounded-2xl border border-emerald-200 dark:border-emerald-900/50 p-4 sm:p-5 flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider block">Uang Masuk Driver Hari Ini</span>
                            <p class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                Rp{{ number_format($todayDriverCashIn, 0, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total pembayaran transaksi driver yang diterima hari ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-emerald-100/60 dark:border-emerald-900/40 text-[11px] text-gray-600 dark:text-gray-300">
                        <span>Pengiriman COD / lunas terverifikasi</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ========================================================================= --}}
        {{-- SECTION B: STATISTIK BISNIS & PIUTANG                                     --}}
        {{-- ========================================================================= --}}
        <section class="space-y-4 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-200 dark:border-gray-800 pb-3 overflow-hidden">
                <div class="flex items-start sm:items-center gap-2.5 min-w-0 flex-1 overflow-hidden">
                    <div class="w-2.5 h-5 bg-[#6366F1] rounded-sm shrink-0 mt-0.5 sm:mt-0"></div>
                    <div class="min-w-0 flex-1 overflow-hidden">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-tight break-words">
                            Kondisi Piutang &amp; Transaksi Perusahaan
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 break-words">
                            Data piutang tersinkron dengan buku besar piutang
                        </p>
                    </div>
                </div>
                <a href="{{ route('admin.receivables.index') }}" class="inline-flex items-center text-xs font-semibold text-[#6366F1] hover:underline self-start sm:self-auto shrink-0 pt-1 sm:pt-0 whitespace-nowrap">
                    Kelola Piutang &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                {{-- Piutang Toko Saat Ini --}}
                <a id="card-receivables-total" href="{{ route('admin.receivables.summary.total') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-receivables-total')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-red-500/40 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Piutang Toko (Current)</span>
                        <p class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white mt-1.5 whitespace-nowrap">
                            Rp{{ number_format($totalReceivable, 0, ',', '.') }}
                        </p>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Total saldo piutang seluruh toko yang masih outstanding
                    </p>
                </a>

                {{-- Toko Berpiutang --}}
                <a id="card-receivables-indebted-stores" href="{{ route('admin.receivables.summary.indebted-stores') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-receivables-indebted-stores')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-amber-500/40 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Toko Berpiutang</span>
                        <div class="mt-1.5 flex items-baseline gap-2">
                            <span class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ number_format($storesWithReceivable) }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">toko</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Toko dengan outstanding &gt; Rp0
                    </p>
                </a>

                {{-- Transaksi Terbuka --}}
                <a id="card-receivables-open-trx" href="{{ route('admin.receivables.summary.open-transactions') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-receivables-open-trx')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-orange-500/40 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wider block">Transaksi Terbuka</span>
                        <div class="mt-1.5 flex items-baseline gap-2">
                            <span class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ number_format($totalOpenTransactions) }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">invoice</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Faktur dengan sisa tagihan aktif
                    </p>
                </a>

                {{-- Transaksi Lunas --}}
                <a id="card-receivables-paid-trx" href="{{ route('admin.receivables.summary.paid-transactions') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-receivables-paid-trx')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md hover:border-emerald-500/40 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Transaksi Lunas</span>
                        <div class="mt-1.5 flex items-baseline gap-2">
                            <span class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ number_format($totalPaidTransactions) }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">invoice</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Faktur selesai terbayar penuh
                    </p>
                </a>
            </div>

            {{-- Ringkasan Keuangan Bulan Berjalan --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4 pt-1">
                <a id="card-receivables-new-trx" href="{{ route('admin.receivables.summary.new-transactions') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-receivables-new-trx')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Transaksi Baru (Bulan Ini)</span>
                        <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mt-1 whitespace-nowrap">
                            Rp{{ number_format($newTransactionsThisMonth, 0, ',', '.') }}
                        </p>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Total nilai transaksi baru yang dibuat bulan ini
                    </p>
                </a>

                <a id="card-receivables-new-trx-payments" href="{{ route('admin.receivables.summary.new-transaction-payments') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-receivables-new-trx-payments')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase">Uang Masuk Trx Baru (Bulan Ini)</span>
                        <p class="text-lg sm:text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 whitespace-nowrap">
                            Rp{{ number_format($newTransactionsCashInThisMonth, 0, ',', '.') }}
                        </p>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Pembayaran yang diterima untuk transaksi baru bulan ini
                    </p>
                </a>

                <a id="card-receivables-payments" href="{{ route('admin.receivables.summary.payments') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-receivables-payments')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5 hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">Bayar Piutang Lama (Bulan Ini)</span>
                        <p class="text-lg sm:text-xl font-bold text-blue-600 dark:text-blue-400 mt-1 whitespace-nowrap">
                            Rp{{ number_format($debtPaymentsThisMonth, 0, ',', '.') }}
                        </p>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        Total pembayaran piutang lama yang diterima bulan ini
                    </p>
                </a>
            </div>
        </section>

        {{-- ========================================================================= --}}
        {{-- SECTION C & D: LAPORAN SALES VS DRIVER (ISOLATED)                        --}}
        {{-- ========================================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- SECTION C: LAPORAN SALES --}}
            <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 space-y-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-5 bg-[#10B981] rounded-sm"></div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Laporan Sales &amp; Piutang</h3>
                        </div>
                        <a href="{{ route('admin.receivables.summary.by-sales') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 border border-emerald-200/80 dark:border-emerald-800/80 transition shadow-sm">
                            <span>Lihat Semua Sales</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    {{-- Highlight Top Indebted Sales --}}
                    @if($topIndebtedSales && $topIndebtedSales['total_receivable'] > 0)
                        <div class="mt-4 p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50">
                            <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide block">
                                Piutang Sales Terbesar
                            </span>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $topIndebtedSales['user']->name }}</span>
                                <span class="text-sm font-extrabold text-amber-600 dark:text-amber-400">
                                    Rp{{ number_format($topIndebtedSales['total_receivable'], 0, ',', '.') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $topIndebtedSales['indebted_stores_count'] }} toko berpiutang &middot; {{ $topIndebtedSales['open_transactions_count'] }} transaksi terbuka
                            </p>
                        </div>
                    @endif

                    {{-- Mini Table Ringkasan Piutang per Sales --}}
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-800">
                                    <th class="py-2 font-semibold">Nama Sales</th>
                                    <th class="py-2 text-center font-semibold">Toko Piutang</th>
                                    <th class="py-2 text-center font-semibold">Trx Terbuka</th>
                                    <th class="py-2 text-right font-semibold">Total Piutang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse(array_slice($salesReceivablesSummary, 0, 4) as $sRow)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $sRow['user']->name }}</td>
                                        <td class="py-2.5 text-center text-gray-600 dark:text-gray-300">{{ $sRow['indebted_stores_count'] }}</td>
                                        <td class="py-2.5 text-center text-gray-600 dark:text-gray-300">{{ $sRow['open_transactions_count'] }}</td>
                                        <td class="py-2.5 text-right font-bold text-gray-900 dark:text-white">
                                            Rp{{ number_format($sRow['total_receivable'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-3 text-center text-gray-400">Tidak ada data piutang sales.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Workflow Sales: Rute &rarr; Visit &rarr; Piutang</span>
                    <a id="card-detail-sales-visits" href="{{ route('admin.reports.visits') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-detail-sales-visits')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 border border-emerald-200/80 dark:border-emerald-800/80 transition shadow-sm">
                        <span>Detail Kunjungan</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </section>

            {{-- SECTION D: LAPORAN DRIVER --}}
            <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 space-y-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-5 bg-[#0DA4CE] rounded-sm"></div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Laporan Transaksi &amp; Pengiriman Driver</h3>
                        </div>
                        <a id="card-driver-visits-all" href="{{ route('admin.reports.driver-visits') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-visits-all')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 dark:text-cyan-300 dark:bg-cyan-950/50 dark:hover:bg-cyan-900/60 border border-cyan-200/80 dark:border-cyan-800/80 transition shadow-sm">
                            <span>Lihat Semua Driver</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <a id="card-driver-deliveries-month" href="{{ route('admin.reports.driver-visits', ['period' => 'this_month']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-deliveries-month')" class="group/dcard p-3.5 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/60 dark:hover:bg-gray-800 border border-gray-100 dark:border-gray-800 transition block">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block">
                                Pengiriman Bulan Ini
                            </span>
                            <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mt-1">
                                {{ number_format($driverStats['total_deliveries_month']) }} <span class="text-xs font-normal text-gray-400">tujuan</span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Selesai: <strong class="text-emerald-600 dark:text-emerald-400">{{ $driverStats['completed_deliveries_month'] }}</strong>
                            </p>
                        </a>

                        <a id="card-driver-cashin-month" href="{{ route('admin.reports.driver-visits', ['period' => 'this_month']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-cashin-month')" class="group/dcard p-3.5 rounded-xl bg-cyan-50/60 hover:bg-cyan-100/60 dark:bg-cyan-950/30 dark:hover:bg-cyan-900/40 border border-cyan-200 dark:border-cyan-900/50 transition block">
                            <span class="text-[11px] font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wide block">
                                Uang Masuk Bulan Ini
                            </span>
                            <p class="text-lg sm:text-xl font-extrabold text-cyan-700 dark:text-cyan-300 mt-1 whitespace-nowrap">
                                Rp{{ number_format($driverStats['total_amount_month'], 0, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Pembayaran transaksi driver
                            </p>
                        </a>
                    </div>

                    <div class="mt-4 space-y-2">
                        <a id="card-driver-deliveries-with-trx" href="{{ route('admin.reports.driver-visits', ['period' => 'this_month']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-deliveries-with-trx')" class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/40 dark:hover:bg-gray-800/70 text-xs transition">
                            <span class="text-gray-600 dark:text-gray-300">Pengiriman Berhasil dengan Transaksi (Bulan Ini)</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $driverStats['deliveries_with_trx_month'] }}</span>
                        </a>
                        <a id="card-driver-deliveries-without-trx" href="{{ route('admin.reports.driver-visits', ['period' => 'this_month']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-deliveries-without-trx')" class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/40 dark:hover:bg-gray-800/70 text-xs transition">
                            <span class="text-gray-600 dark:text-gray-300">Pengiriman Tanpa Transaksi / Hanya Antar</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $driverStats['deliveries_without_trx_month'] }}</span>
                        </a>
                        <a id="card-driver-cashin-today" href="{{ route('admin.reports.driver-visits', ['period' => 'today']) }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-cashin-today')" class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/40 dark:hover:bg-gray-800/70 text-xs transition">
                            <span class="text-gray-600 dark:text-gray-300">Uang Masuk Driver Hari Ini</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Rp{{ number_format($driverStats['today_cash_in'], 0, ',', '.') }}</span>
                        </a>
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Workflow Driver: Rute Driver &rarr; Pengiriman &rarr; Pembayaran Transaksi</span>
                    <a id="card-driver-routes-bottom" href="{{ route('admin.reports.driver-routes') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-driver-routes-bottom')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl font-semibold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 dark:text-cyan-300 dark:bg-cyan-950/50 dark:hover:bg-cyan-900/60 border border-cyan-200/80 dark:border-cyan-800/80 transition shadow-sm">
                        <span>Lihat Rute Driver</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </section>
        </div>

        {{-- ========================================================================= --}}
        {{-- SECTION E: MODUL & LAPORAN DETAIL                                        --}}
        {{-- ========================================================================= --}}
        <section class="space-y-4">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-2.5 h-5 bg-amber-500 rounded-sm"></div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                            Modul Laporan Lengkap &amp; Ekspor
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Pilih modul laporan untuk melihat rincian detail, filter tanggal, serta ekspor PDF dan Excel resmi
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                {{-- 1. Laporan Master Toko --}}
                <a id="card-reports-stores" href="{{ route('admin.reports.stores') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-stores')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-[#6366F1]/30 dark:hover:border-[#6366F1]/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-[#6366F1]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-[#6366F1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Master Toko</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Master data toko, lokasi koordinat, owner, dan sales PJ</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-[#6366F1] group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 2. Laporan Kunjungan Sales --}}
                <a id="card-reports-visits" href="{{ route('admin.reports.visits') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-visits')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-[#F59E0B]/30 dark:hover:border-[#F59E0B]/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-[#F59E0B]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M22 12v9m-15 3v-7a2 2 0 012-2h3a2 2 0 012 2v7m-5 0h5"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Kunjungan Sales</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Check-in, dokumentasi etalase, nota transaksi, dan skip stop</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-amber-500 dark:text-amber-400 group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 3. Laporan Pengiriman Driver --}}
                <a id="card-reports-driver-visits" href="{{ route('admin.reports.driver-visits') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-driver-visits')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-[#10B981]/30 dark:hover:border-[#10B981]/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-[#10B981]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 18h-2m-2.5-4h11.5m-11.5-4H20M5 6h12l4 5v5H5V6z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Pengiriman Driver</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Pengiriman pesanan driver, pembayaran transaksi, selfie, dan hasil pengiriman</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-emerald-500 dark:text-emerald-400 group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 4. Laporan Presensi --}}
                <a id="card-reports-attendance" href="{{ route('admin.reports.attendance') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-attendance')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-[#EF4444]/30 dark:hover:border-[#EF4444]/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-[#EF4444]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Presensi</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Presensi sales, driver &amp; staff, jam masuk, keluar, dan izin/sakit</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-red-500 dark:text-red-400 group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 5. Laporan Rencana Rute Sales --}}
                <a id="card-reports-routes" href="{{ route('admin.reports.routes') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-routes')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-[#10B981]/30 dark:hover:border-[#10B981]/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-[#10B981]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Rencana Kunjungan</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Rencana perjalanan sales, rute kunjungan toko, dan status</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-emerald-500 dark:text-emerald-400 group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 6. Laporan Rencana Pengiriman Driver --}}
                <a id="card-reports-driver-routes" href="{{ route('admin.reports.driver-routes') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-driver-routes')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-[#0DA4CE]/30 dark:hover:border-[#0DA4CE]/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-[#0DA4CE]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 18h-2m-2.5-4h11.5m-11.5-4H20M5 6h12l4 5v5H5V6z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Rencana Pengiriman</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Rencana perjalanan driver, rute pengiriman tujuan, dan status</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-[#0DA4CE] group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 7. Laporan Pengguna --}}
                <a id="card-reports-users" href="{{ route('admin.reports.users') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-users')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-[#8B5CF6]/30 dark:hover:border-[#8B5CF6]/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-[#8B5CF6]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-[#8B5CF6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Pengguna</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Data akun pengguna seluruh role, status aktif, dan tanggal dibuat</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-[#8B5CF6] group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 8. Laporan Transaksi Visit Sales --}}
                <a id="card-reports-transactions" href="{{ route('admin.reports.transactions') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-transactions')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-emerald-500/30 dark:hover:border-emerald-500/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Laporan Transaksi Kunjungan</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Rekap transaksi kunjungan lapangan, metode bayar, dan total omzet</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-emerald-500 dark:text-emerald-400 group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>

                {{-- 9. Laporan Aktivitas Sales --}}
                <a id="card-reports-sales-activities" href="{{ route('admin.reports.sales-activities') }}" @click="sessionStorage.setItem('admin_reports_last_card', 'card-reports-sales-activities')" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 sm:p-6 hover:shadow-md hover:border-indigo-500/30 dark:hover:border-indigo-500/40 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Performa Aktivitas Sales</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Evaluasi performa sales, rasio kehadiran, target kunjungan rute</p>
                    <span class="inline-flex items-center mt-3 text-xs font-semibold text-indigo-500 dark:text-indigo-400 group-hover:translate-x-1 transition-transform">
                        Buka Laporan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
