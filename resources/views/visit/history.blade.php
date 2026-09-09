<x-app-layout>
    @php
        $isDriver = Auth::user()->hasRole('driver');
    @endphp
    <x-slot name="header">
        <x-page-header icon="clipboard-list" :title="$isDriver ? 'Riwayat Pengiriman' : 'Riwayat Kunjungan'" :subtitle="$isDriver ? 'Riwayat seluruh pengiriman Anda' : 'Riwayat seluruh kunjungan toko Anda'"></x-page-header>
    </x-slot>

    <div class="w-full" x-data="visitHistoryExport()" @keydown.escape.window="if (status !== 'loading') showModal = false">
        {{-- Stats --}}
        @if($isDriver)
        {{-- STATS KHUSUS DRIVER: 4 CARDS (TANPA TOTAL TRANSAKSI) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 mb-6">
            {{-- Card 1: Total Pengiriman --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Total Pengiriman</p>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalVisits ?? 0 }}</p>
                </div>
            </div>

            {{-- Card 2: Pengiriman Selesai --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#90F022]/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Pengiriman Selesai</p>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $completedVisits ?? 0 }}</p>
                </div>
            </div>

            {{-- Card 3: Pengiriman Bulan Ini --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Pengiriman Bulan Ini</p>
                    </div>
                </div>
                <div class="mt-3 min-w-0">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $visitsThisMonth ?? 0 }}</p>
                </div>
            </div>

            {{-- Card 4: Uang Masuk Pengiriman --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Uang Masuk Pengiriman</p>
                    </div>
                </div>
                <div class="mt-3 min-w-0">
                    <p class="text-base sm:text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 whitespace-nowrap overflow-hidden">Rp {{ number_format($monthlyCashIn ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @else
        {{-- STATS KHUSUS SALES: 5 CARDS LENGKAP --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5 sm:gap-4 mb-6">
            {{-- Card 1: Total Kunjungan --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Total Kunjungan</p>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalVisits ?? 0 }}</p>
                </div>
            </div>

            {{-- Card 2: Kunjungan Selesai --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#90F022]/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate">Selesai</p>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $completedVisits ?? 0 }}</p>
                </div>
            </div>

            {{-- Card 3: Total Transaksi Baru Bulan Ini (Sales) --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Total Transaksi Baru Bulan Ini</p>
                    </div>
                </div>
                <div class="mt-3 min-w-0">
                    <p class="text-base sm:text-lg font-bold font-mono text-gray-900 dark:text-gray-100 whitespace-nowrap overflow-hidden">Rp {{ number_format($totalTransaction ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Card 4: Total Saldo Piutang (Sales) --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Total Saldo Piutang</p>
                    </div>
                </div>
                <div class="mt-3 min-w-0">
                    <p class="text-base sm:text-lg font-bold font-mono text-red-600 dark:text-red-400 whitespace-nowrap overflow-hidden">Rp {{ number_format($totalReceivableBalance ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Card 5: Pembayaran Piutang Lama Bulan Ini (Sales) --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Pembayaran Piutang Lama Bulan Ini</p>
                    </div>
                </div>
                <div class="mt-3 min-w-0">
                    <p class="text-base sm:text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 whitespace-nowrap overflow-hidden">Rp {{ number_format($monthlyDebtPayments ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 mb-6">
            <form method="GET" action="{{ route('visit.history') }}" class="visit-history-filter flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:gap-3">
                <div class="sw-date-range w-full sm:contents">
                    <p class="sw-date-range-title block sm:hidden">Rentang Tanggal</p>
                    <div class="sw-date-range-field w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5">Dari</label>
                        <div class="relative">
                            <svg class="sw-date-icon pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="date" name="from_date" value="{{ request('from_date') }}"
                                class="sw-input sw-date-input">
                        </div>
                    </div>
                    <div class="sw-date-range-field w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5">Sampai</label>
                        <div class="relative">
                            <svg class="sw-date-icon pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="date" name="to_date" value="{{ request('to_date') }}"
                                class="sw-input sw-date-input">
                        </div>
                    </div>
                </div>
                <div class="w-full sm:flex-1 sm:min-w-[140px]">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5">Status</label>
                    <select name="status" class="sw-input">
                        <option value="">Semua</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>{{ $isDriver ? 'Sedang Dikirim' : 'Sedang Dikunjungi' }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto sm:ml-auto flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
                    <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 sm:py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                        <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    @if(request()->anyFilled(['from_date', 'to_date', 'status']))
                    <a href="{{ route('visit.history') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 sm:py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Reset
                    </a>
                    @endif
                    <button type="button" @click="openExport('pdf')"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 sm:py-2.5 bg-[#097A99] text-white rounded-xl text-sm font-semibold hover:bg-[#0b93b8] transition shadow-sm">
                        <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export Rekap PDF
                    </button>
                    <button type="button" @click="openExport('excel')"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 sm:py-2.5 bg-green-600 text-white rounded-xl text-sm font-semibold hover:bg-green-700 transition shadow-sm">
                        <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Excel
                    </button>
                </div>
            </form>
        </div>

        <style>
            @media (max-width: 639px) {
                .visit-history-filter .sw-date-range {
                    display: grid;
                    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                    gap: 0.75rem;
                    width: 100%;
                    box-sizing: border-box;
                }
                .visit-history-filter .sw-date-range-title {
                    grid-column: 1 / -1;
                    display: block;
                    margin: 0;
                    font-size: 0.6875rem;
                    font-weight: 700;
                    letter-spacing: 0.05em;
                    text-transform: uppercase;
                    color: #64748b;
                }
                .dark .visit-history-filter .sw-date-range-title { color: #94a3b8; }
                .visit-history-filter .sw-date-range .sw-date-icon {
                    left: 0.5rem;
                }
                .visit-history-filter input[type="date"].sw-input,
                .visit-history-filter select.sw-input {
                    width: 100% !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                    box-sizing: border-box !important;
                    display: block !important;
                    height: 2.875rem !important;
                    min-height: 2.875rem !important;
                }
                .visit-history-filter input[type="date"].sw-input {
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    appearance: none;
                    text-align: left !important;
                    font-size: clamp(0.9375rem, 4.2vw, 1rem) !important;
                    line-height: normal !important;
                    padding: 0 0.5rem 0 1.875rem !important;
                }
                .visit-history-filter select.sw-input {
                    font-size: 0.9375rem !important;
                }
                .visit-history-filter input[type="date"].sw-input::-webkit-datetime-edit {
                    display: flex;
                    align-items: center;
                    justify-content: flex-start;
                    height: 100%;
                    padding: 0;
                    line-height: normal;
                    font-size: inherit;
                    color: inherit;
                }
                .visit-history-filter input[type="date"].sw-input::-webkit-datetime-edit-fields-wrapper {
                    display: inline-flex;
                    align-items: center;
                    height: 100%;
                    padding: 0;
                }
                .visit-history-filter input[type="date"].sw-input::-webkit-date-and-time-value {
                    display: flex;
                    align-items: center;
                    justify-content: flex-start;
                    height: 100%;
                    text-align: left;
                    line-height: normal;
                    font-size: inherit;
                    color: inherit;
                }
                .visit-history-filter input[type="date"].sw-input::-webkit-calendar-picker-indicator {
                    width: 1.25rem;
                    height: 1.25rem;
                    margin-right: 0.125rem;
                    opacity: 0.6;
                    cursor: pointer;
                }
                .dark .visit-history-filter input[type="date"].sw-input::-webkit-calendar-picker-indicator {
                    filter: invert(1);
                    opacity: 0.8;
                }
                .dark .visit-history-filter input[type="date"].sw-input::-webkit-calendar-picker-indicator:hover { opacity: 1; }
            }
        </style>

        {{-- Mobile card list --}}
        <div class="sm:hidden space-y-4">
            @forelse($visits ?? [] as $visit)
            @php
                $summary = ! $isDriver ? \App\Services\StoreReceivableService::getVisitReceivableSummary($visit) : [];
            @endphp
            <a href="{{ route('visit.show', $visit->id) }}" class="block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $visit->store->name ?? 'Toko' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $visit->check_in_at ? \Carbon\Carbon::parse($visit->check_in_at)->format('d M Y, H:i') : '-' }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0
                        @if($visit->status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                        @else bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                        @endif">
                        {{ $visit->status === 'completed' ? 'Selesai' : ($isDriver ? 'Sedang Dikirim' : 'Sedang Dikunjungi') }}
                    </span>
                </div>

                @if(! $isDriver)
                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Transaksi Baru:</span>
                        <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($summary['new_tx_total'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">Uang Masuk Transaksi Baru:</span>
                        <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($summary['new_tx_initial_paid'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Pembayaran Piutang Lama:</span>
                        <span class="font-mono font-semibold {{ $summary['old_debt_paid'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-gray-100' }}">
                            Rp {{ number_format($summary['old_debt_paid'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-dashed border-gray-100 dark:border-gray-700/50">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Saldo Piutang Toko:</span>
                        <span class="font-mono font-bold text-sm {{ $summary['balance_after'] > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            Rp {{ number_format($summary['balance_after'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @else
                @php
                    $dHasTx = (float) ($visit->transaction_amount ?? 0) > 0;
                @endphp
                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Transaksi Pengiriman:</span>
                        <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">
                            {{ $dHasTx ? 'Rp ' . number_format($visit->transaction_amount, 0, ',', '.') : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Metode Pembayaran:</span>
                        <span class="font-bold uppercase text-gray-700 dark:text-gray-300">
                            {{ $dHasTx ? strtoupper($visit->payment_method ?? 'tunai') : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-dashed border-gray-100 dark:border-gray-700/50 text-gray-500 dark:text-gray-400">
                        <span>Check Out: {{ $visit->check_out_at ? \Carbon\Carbon::parse($visit->check_out_at)->format('H:i') : '-' }}</span>
                        <span>{{ ($visit->photos_count ?? $visit->photos->count()) + ($visit->check_in_selfie ? 1 : 0) }} Foto</span>
                    </div>
                </div>
                @endif
            </a>
            @empty
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $isDriver ? 'Tidak ada riwayat pengiriman' : 'Tidak ada riwayat kunjungan' }}</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden sm:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60">
                <thead class="bg-gray-50 dark:bg-gray-800/40">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Toko</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $isDriver ? 'Check In Pengiriman' : 'Check In' }}</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $isDriver ? 'Check Out Pengiriman' : 'Check Out' }}</th>
                        @if(! $isDriver)
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Transaksi Baru</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Uang Masuk Transaksi Baru</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Pembayaran Piutang Lama</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Saldo Piutang Toko</th>
                        @else
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Transaksi</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Metode Pembayaran</th>
                        @endif
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                    @forelse($visits ?? [] as $visit)
                    @php
                        $summary = ! $isDriver ? \App\Services\StoreReceivableService::getVisitReceivableSummary($visit) : [];
                    @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition cursor-pointer" onclick="window.location='{{ route('visit.show', $visit->id) }}'">
                        <td class="px-4 py-4">
                            <p class="text-sm font-medium text-[#0DA4CE] dark:text-[#5BD8F7]">{{ $visit->store->name ?? 'Toko' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $visit->store->address ?? '' }}</p>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $visit->check_in_at ? \Carbon\Carbon::parse($visit->check_in_at)->format('d M Y, H:i') : '-' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $visit->check_out_at ? \Carbon\Carbon::parse($visit->check_out_at)->format('d M Y, H:i') : '-' }}
                        </td>
                        @if(! $isDriver)
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-mono font-semibold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($summary['new_tx_total'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($summary['new_tx_initial_paid'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-mono font-semibold {{ $summary['old_debt_paid'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-gray-100' }}">
                            Rp {{ number_format($summary['old_debt_paid'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-mono font-bold {{ $summary['balance_after'] > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            Rp {{ number_format($summary['balance_after'], 0, ',', '.') }}
                        </td>
                        @else
                        @php
                            $dHasTx = (float) ($visit->transaction_amount ?? 0) > 0;
                        @endphp
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-mono font-semibold text-gray-900 dark:text-gray-100">
                            {{ $dHasTx ? 'Rp ' . number_format($visit->transaction_amount, 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-bold uppercase text-gray-700 dark:text-gray-300">
                            {{ $dHasTx ? strtoupper($visit->payment_method ?? 'tunai') : '-' }}
                        </td>
                        @endif
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($visit->status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                @else bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                @endif">
                                {{ $visit->status === 'completed' ? 'Selesai' : ($isDriver ? 'Sedang Dikirim' : 'Sedang Dikunjungi') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ ! $isDriver ? '8' : '5' }}" class="px-6 py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $isDriver ? 'Tidak ada riwayat pengiriman' : 'Tidak ada riwayat kunjungan' }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        @if(isset($visits) && $visits->hasPages())
        <div class="mt-6">{{ $visits->links() }}</div>
        @endif

        {{-- Alert Modal (Filter Belum Dipilih) --}}
        <div x-show="showAlert" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="showAlert = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" @click.stop>
                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-950/40 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Filter Belum Dipilih</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Silakan pilih minimal satu filter terlebih dahulu sebelum mengunduh laporan.</p>
                <button type="button" @click="showAlert = false"
                    class="w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition">
                    OK
                </button>
            </div>
        </div>

        {{-- Modal Nama File Laporan & Download State (Konsisten dengan Admin) --}}
        <div x-show="showModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="if (status !== 'loading') showModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6" @click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="modalTitle"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">File akan diunduh sebagai <span class="font-semibold" x-text="extension"></span></p>
                    </div>
                    <button type="button" @click="if (status !== 'loading') showModal = false" :disabled="status === 'loading'" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-5">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nama File</label>
                    <input type="text" x-model="filename" @input="error = ''" :disabled="status === 'loading' || status === 'success'"
                        class="w-full border-2 border-gray-300 dark:border-gray-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 caret-[#0DA4CE] focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/30 transition disabled:opacity-60 disabled:cursor-not-allowed"
                        placeholder="Masukkan nama file (opsional)">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Kosongkan untuk menggunakan nama default.</p>
                </div>

                {{-- Validation / Error Message --}}
                <p x-show="error" x-cloak x-transition class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 mt-2 font-medium">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="error"></span>
                </p>

                {{-- Loading State --}}
                <div x-show="status === 'loading'" x-cloak x-transition class="mt-4 p-3 bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/60 rounded-xl flex items-center gap-3">
                    <svg class="animate-spin h-5 w-5 text-[#0DA4CE] shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <div class="text-xs">
                        <p class="font-semibold text-cyan-900 dark:text-cyan-200">Sedang menyiapkan file...</p>
                        <p class="text-cyan-700 dark:text-cyan-400 text-[11px] mt-0.5">Mohon tunggu hingga file selesai diunduh.</p>
                    </div>
                </div>

                {{-- Success State --}}
                <div x-show="status === 'success'" x-cloak x-transition class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl flex items-center gap-3">
                    <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="text-xs">
                        <p class="font-semibold text-emerald-900 dark:text-emerald-200">Laporan berhasil diunduh.</p>
                        <p class="text-emerald-700 dark:text-emerald-400 text-[11px] mt-0.5">Modal akan segera ditutup otomatis.</p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="closeModal()" :disabled="status === 'loading'" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Batal
                    </button>
                    <button type="button" @click="download()" :disabled="status === 'loading' || status === 'success'"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-white rounded-xl text-sm font-semibold transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="exportType === 'pdf' ? 'bg-[#097A99] hover:bg-[#086b80]' : 'bg-green-600 hover:bg-green-700'">
                        <template x-if="status === 'loading'">
                            <span class="inline-flex items-center">
                                <svg class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Mengunduh...
                            </span>
                        </template>
                        <template x-if="status === 'error'">
                            <span>Coba Lagi</span>
                        </template>
                        <template x-if="status !== 'loading' && status !== 'error'">
                            <span x-text="exportType === 'pdf' ? 'Unduh PDF' : 'Unduh Excel'"></span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function visitHistoryExport() {
            return {
                showModal: false,
                showAlert: false,
                status: 'idle', // 'idle' | 'loading' | 'success' | 'error'
                exportType: 'pdf',
                filename: '',
                error: '',
                isDriver: {{ $isDriver ? 'true' : 'false' }},

                get extension() {
                    return this.exportType === 'pdf' ? '.pdf' : '.xlsx';
                },

                get modalTitle() {
                    if (this.exportType === 'pdf') {
                        return this.isDriver ? 'Unduh PDF Rekap Pengiriman' : 'Unduh PDF Rekap Kunjungan';
                    }
                    return this.isDriver ? 'Unduh Excel Rekap Pengiriman' : 'Unduh Excel Rekap Kunjungan';
                },

                openExport(type) {
                    const fd = document.querySelector('input[name=from_date]');
                    const td = document.querySelector('input[name=to_date]');
                    const st = document.querySelector('select[name=status]');
                    const hasFilter = Boolean((fd && fd.value) || (td && td.value) || (st && st.value));

                    if (!hasFilter) {
                        this.showAlert = true;
                        return;
                    }

                    this.exportType = type;
                    this.filename = '';
                    this.error = '';
                    this.status = 'idle';
                    this.showModal = true;
                },

                closeModal() {
                    if (this.status === 'loading') return;
                    this.showModal = false;
                    this.status = 'idle';
                    this.error = '';
                },

                async download() {
                    if (this.status === 'loading') return;

                    this.error = '';
                    this.status = 'loading';

                    const fd = document.querySelector('input[name=from_date]');
                    const td = document.querySelector('input[name=to_date]');
                    const st = document.querySelector('select[name=status]');
                    const params = new URLSearchParams();

                    if (fd && fd.value) params.set('from_date', fd.value);
                    if (td && td.value) params.set('to_date', td.value);
                    if (st && st.value) params.set('status', st.value);

                    const name = this.filename.trim();
                    if (name !== '') {
                        const finalName = name.endsWith(this.extension) ? name.slice(0, -this.extension.length) : name;
                        params.set('filename', finalName);
                    }

                    const baseUrl = this.exportType === 'pdf'
                        ? '{{ route("visit.pdf.rekap") }}'
                        : '{{ route("visit.excel.rekap") }}';
                    const targetUrl = baseUrl + '?' + params.toString();

                    try {
                        const response = await fetch(targetUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': '*/*',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            const contentType = response.headers.get('content-type') || '';
                            let errorMsg = 'Gagal mengunduh laporan (Status: ' + response.status + ').';
                            if (contentType.includes('application/json')) {
                                const errData = await response.json().catch(() => ({}));
                                errorMsg = errData.message || errorMsg;
                            }
                            throw new Error(errorMsg);
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
                            const defaultBase = this.isDriver
                                ? (this.exportType === 'pdf' ? 'rekap-pengiriman' : 'rekap-pengiriman')
                                : (this.exportType === 'pdf' ? 'rekap-kunjungan' : 'rekap-kunjungan');
                            downloadFilename = (name ? name : defaultBase) + (name.endsWith(this.extension) ? '' : this.extension);
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
                            this.showModal = false;
                            this.status = 'idle';
                        }, 1200);
                    } catch (err) {
                        this.status = 'error';
                        this.error = err.message || 'Terjadi kesalahan saat mengunduh laporan. Silakan coba lagi.';
                    }
                },
            };
        }

        // Backward compatibility for any external triggers
        window.openExportModal = function(type) {
            const root = document.querySelector('[x-data*="visitHistoryExport"]');
            if (root && root.__x) {
                root.__x.$data.openExport(type);
            }
        };
    </script>
</x-app-layout>