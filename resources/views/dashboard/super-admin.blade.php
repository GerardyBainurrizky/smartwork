<x-app-layout>
    @section('breadcrumbs', 'Dashboard')

    <div class="animate-fade-in space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-4">
                <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="ISA SmartWork" class="w-12 h-12 object-contain shrink-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Perusahaan</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->isoFormat('dddd, D MMMM YYYY') }}</p>
                </div>
            </div>
            <span class="self-start inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-[#0DA4CE]/10 text-[#0DA4CE] dark:bg-[#0DA4CE]/20">
                <span class="w-1.5 h-1.5 rounded-full bg-[#0DA4CE] animate-pulse"></span>
                Super Admin
            </span>
        </div>

        {{-- Card 1: Total Pengguna (Clickable Breakdown Roles) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Total Pengguna</h3>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">Seluruh akun terdaftar di sistem</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ $totalUsers }}</span>
                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Kelola Pengguna</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- Breakdown Roles (Clickable) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                {{-- Sales --}}
                <a href="{{ route('admin.users.index', ['role' => 'sales']) }}"
                   class="group block rounded-xl bg-gray-50 hover:bg-[#0DA4CE]/5 dark:bg-gray-800/50 dark:hover:bg-[#0DA4CE]/10 border border-gray-100 hover:border-[#0DA4CE]/30 dark:border-gray-700/60 transition-all p-3.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-500 group-hover:text-[#097A99] dark:text-gray-400 dark:group-hover:text-[#38BDF8] transition-colors">Sales</span>
                        <span class="w-2 h-2 rounded-full bg-[#0DA4CE]"></span>
                    </div>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalSales }}</p>
                    <p class="text-[11px] text-[#097A99] dark:text-[#38BDF8] font-medium mt-1">{{ $salesActiveCount }} aktif hari ini</p>
                </a>

                {{-- Driver --}}
                <a href="{{ route('admin.users.index', ['role' => 'driver']) }}"
                   class="group block rounded-xl bg-gray-50 hover:bg-amber-500/5 dark:bg-gray-800/50 dark:hover:bg-amber-500/10 border border-gray-100 hover:border-amber-500/30 dark:border-gray-700/60 transition-all p-3.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-500 group-hover:text-amber-700 dark:text-gray-400 dark:group-hover:text-amber-400 transition-colors">Driver</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalDriver ?? 0 }}</p>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400 font-medium mt-1">{{ $driverActiveCount }} aktif hari ini</p>
                </a>

                {{-- Staff --}}
                <a href="{{ route('admin.users.index', ['role' => 'staff']) }}"
                   class="group block rounded-xl bg-gray-50 hover:bg-orange-500/5 dark:bg-gray-800/50 dark:hover:bg-orange-500/10 border border-gray-100 hover:border-orange-500/30 dark:border-gray-700/60 transition-all p-3.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-500 group-hover:text-orange-700 dark:text-gray-400 dark:group-hover:text-orange-400 transition-colors">Staff</span>
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    </div>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalStaff }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium mt-1">Staff operasional</p>
                </a>

                {{-- Admin --}}
                <a href="{{ route('admin.users.index', ['role' => 'admin']) }}"
                   class="group block rounded-xl bg-gray-50 hover:bg-blue-500/5 dark:bg-gray-800/50 dark:hover:bg-blue-500/10 border border-gray-100 hover:border-blue-500/30 dark:border-gray-700/60 transition-all p-3.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-500 group-hover:text-blue-700 dark:text-gray-400 dark:group-hover:text-blue-400 transition-colors">Admin</span>
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    </div>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalAdminOnly ?? 0 }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium mt-1">Administrator</p>
                </a>

                {{-- Super Admin --}}
                <a href="{{ route('admin.users.index', ['role' => 'super-admin']) }}"
                   class="group block rounded-xl bg-gray-50 hover:bg-purple-500/5 dark:bg-gray-800/50 dark:hover:bg-purple-500/10 border border-gray-100 hover:border-purple-500/30 dark:border-gray-700/60 transition-all p-3.5 col-span-2 sm:col-span-1">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-500 group-hover:text-purple-700 dark:text-gray-400 dark:group-hover:text-purple-400 transition-colors">Super Admin</span>
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    </div>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalSuperAdmin ?? 0 }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium mt-1">Super Administrator</p>
                </a>
            </div>
        </div>

        {{-- Section 2: Ringkasan Uang Masuk & Piutang Hari Ini --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Card 2A: UANG MASUK SALES HARI INI --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Uang Masuk Sales Hari Ini</h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">Uang aktual yang diterima dari seluruh Sales hari ini</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.receivables.summary.today-payments') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                            <span>Lihat Detail</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    {{-- Total Nilai Utama --}}
                    <div class="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-500/15 rounded-xl p-3.5 mb-3">
                        <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300 mb-0.5">Total Uang Masuk Sales (Hari Ini)</p>
                        <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">
                            Rp {{ number_format($totalSalesCashInToday ?? 0, 0, ',', '.') }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2.5 pt-2.5 border-t border-emerald-500/15 text-[11px]">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block text-[10px]">Transaksi Baru:</span>
                                <span class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($totalNewTxCashInToday ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block text-[10px]">Bayar Piutang Lama:</span>
                                <span class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($totalOldDebtPaymentToday ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block text-[10px]">Pelunasan Lunas (Rp0):</span>
                                <span class="font-bold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($totalDebtSettlementToday ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-2 leading-tight">Bagian dari pembayaran piutang lama yang berhasil melunasi tagihan.</p>
                    </div>

                    {{-- Breakdown per Sales --}}
                    <div class="space-y-2">
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rincian per Sales</p>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-48 overflow-y-auto pr-1">
                            @forelse($salesCashInSummary ?? [] as $sc)
                            <div class="py-2 flex items-center justify-between text-xs">
                                <div class="min-w-0 pr-2">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $sc['name'] }}</p>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500">
                                        Baru: Rp {{ number_format($sc['new_tx'] ?? 0, 0, ',', '.') }} &middot; Lama: Rp {{ number_format($sc['old_debt'] ?? 0, 0, ',', '.') }}
                                        @if(($sc['settlement'] ?? 0) > 0)
                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">(Lunas: Rp {{ number_format($sc['settlement'], 0, ',', '.') }})</span>
                                        @endif
                                    </p>
                                </div>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 shrink-0">
                                    Rp {{ number_format($sc['amount'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                            @empty
                            <p class="py-3 text-center text-xs text-gray-400">Belum ada pembayaran Sales hari ini</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2B: UANG MASUK DRIVER HARI INI --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Uang Masuk Driver Hari Ini</h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">Uang diterima dari transaksi pengiriman Driver hari ini</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.driver.visits.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                            <span>Lihat Detail</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    {{-- Total Nilai Utama --}}
                    <div class="bg-amber-50/50 dark:bg-amber-950/20 border border-amber-500/15 rounded-xl p-3.5 mb-3">
                        <p class="text-xs font-medium text-amber-800 dark:text-amber-300 mb-0.5">Total Uang Masuk Pengiriman Driver</p>
                        <p class="text-2xl font-extrabold text-amber-700 dark:text-amber-400 tracking-tight">
                            Rp {{ number_format($totalDriverCashInToday ?? 0, 0, ',', '.') }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Transaksi pengiriman selesai & terbayar (status paid)</p>
                    </div>

                    {{-- Breakdown per Driver --}}
                    <div class="space-y-2">
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rincian per Driver</p>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-48 overflow-y-auto pr-1">
                            @forelse($driverCashInSummary ?? [] as $dc)
                            <div class="py-2 flex items-center justify-between text-xs">
                                <span class="font-semibold text-gray-900 dark:text-white truncate">{{ $dc['name'] }}</span>
                                <span class="font-bold text-amber-700 dark:text-amber-400 shrink-0">
                                    Rp {{ number_format($dc['amount'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                            @empty
                            <p class="py-3 text-center text-xs text-gray-400">Belum ada transaksi Driver hari ini</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2C: PIUTANG SALES (Current Outstanding Toko Sales) --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-red-500/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Piutang Sales</h3>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">Saldo piutang dari toko dengan Sales Penanggung Jawab</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.receivables.summary.by-sales') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                            <span>Lihat Piutang</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    {{-- Total Nilai Utama --}}
                    <div class="bg-red-50/50 dark:bg-red-950/20 border border-red-500/15 rounded-xl p-3.5 mb-3">
                        <p class="text-xs font-medium text-red-800 dark:text-red-300 mb-0.5">Total Piutang Toko Sales</p>
                        <p class="text-2xl font-extrabold text-red-700 dark:text-red-400 tracking-tight">
                            Rp {{ number_format($totalSalesReceivable ?? 0, 0, ',', '.') }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Saldo berjalan dari seluruh toko yang dikelola Sales</p>
                    </div>

                    {{-- Breakdown per Sales --}}
                    <div class="space-y-2">
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rincian Saldo per Sales</p>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-48 overflow-y-auto pr-1">
                            @forelse($salesReceivableSummary ?? [] as $sr)
                            <div class="py-2 flex items-center justify-between text-xs">
                                <span class="font-semibold text-gray-900 dark:text-white truncate">{{ $sr['name'] }}</span>
                                <span class="font-bold {{ $sr['amount'] > 0 ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' }} shrink-0">
                                    Rp {{ number_format($sr['amount'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                            @empty
                            <p class="py-3 text-center text-xs text-gray-400">Tidak ada saldo piutang aktif</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: PERFORMA KEUANGAN SALES (Chart 3 Seri & Ringkasan) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 border-b border-gray-100 dark:border-gray-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white uppercase tracking-tight">Performa Keuangan Sales</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pemasukan baru, pembayaran piutang lama, dan saldo piutang akhir hari toko Sales (7 Hari Terakhir)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        7 Hari Terakhir
                    </span>
                </div>
            </div>

            {{-- 3 Komponen Ringkasan Keuangan (7 Hari) --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:gap-4 mb-6">
                {{-- 1. Pemasukan Transaksi Baru (7 Hari) --}}
                <div class="rounded-xl bg-gradient-to-br from-emerald-500/5 via-emerald-500/[0.02] to-transparent border border-emerald-500/15 p-4 dark:bg-gray-800/40">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pemasukan Transaksi Baru</p>
                    </div>
                    <p class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        Rp {{ number_format($financialTrend['summary']['new_tx_cash_in'] ?? 0, 0, ',', '.') }}
                    </p>
                    <span class="inline-block mt-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Uang masuk dari transaksi baru</span>
                </div>

                {{-- 2. Pembayaran Piutang Lama (7 Hari) --}}
                <div class="rounded-xl bg-gradient-to-br from-indigo-500/5 via-indigo-500/[0.02] to-transparent border border-indigo-500/15 p-4 dark:bg-gray-800/40">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pembayaran Piutang Lama</p>
                    </div>
                    <p class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        Rp {{ number_format($financialTrend['summary']['old_debt_payment'] ?? 0, 0, ',', '.') }}
                    </p>
                    <span class="inline-block mt-1 text-[11px] font-medium text-indigo-600 dark:text-indigo-400">Pembayaran atas saldo piutang lama</span>
                    <div class="mt-2 pt-2 border-t border-indigo-500/15 flex flex-col gap-0.5 text-[11px]">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Pelunasan Lunas:</span>
                            <span class="font-bold text-indigo-700 dark:text-indigo-300">Rp {{ number_format($financialTrend['summary']['debt_settlement_cash'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight">Bagian dari pembayaran piutang lama yang berhasil melunasi transaksi</span>
                    </div>
                </div>

                {{-- 3. Saldo Piutang Berjalan Saat Ini (Seluruh Toko Aktif) --}}
                <div class="rounded-xl bg-gradient-to-br from-amber-500/5 via-amber-500/[0.02] to-transparent border border-amber-500/15 p-4 dark:bg-gray-800/40">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Saldo Piutang Berjalan — Seluruh Toko</p>
                    </div>
                    <p class="text-xl lg:text-2xl font-bold text-red-700 dark:text-red-400 tracking-tight">
                        Rp {{ number_format($totalCurrentReceivable ?? 0, 0, ',', '.') }}
                    </p>
                    <span class="inline-block mt-1 text-[11px] text-gray-400 dark:text-gray-500">Total piutang outstanding seluruh toko aktif</span>
                </div>
            </div>

            {{-- Wrapper Chart 3 Seri --}}
            <div class="sw-chart-card sw-financial-chart-card w-full">
                <div class="sw-chart-body min-h-[320px] h-[320px] relative w-full">
                    <div id="chart-financial" class="w-full h-full"></div>
                </div>
            </div>
        </div>

        {{-- Section 4: Ringkasan Operasional Lapangan (Presensi, Kunjungan, Pengiriman, Rute, Toko) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            {{-- Card 4A: Presensi Hari Ini --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#097A99]/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-[#097A99]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Presensi Hari Ini</h3>
                    </div>
                    <a href="{{ route('admin.attendance.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="flex items-baseline gap-2 mb-1">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $presentCount }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">dari {{ $operationalUserCount }} pengguna aktif</span>
                </div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-[#90F022] rounded-full transition-all" style="width: {{ $attendanceRate }}%"></div>
                    </div>
                    <span class="text-sm font-bold text-green-700 dark:text-green-400">{{ $attendanceRate }}%</span>
                </div>
                <div class="flex items-center justify-between text-[11px] px-2 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 text-gray-600 dark:text-gray-300 mb-3">
                    <span><strong class="text-green-600 dark:text-green-400">{{ $hadirCount ?? $presentCount }}</strong> Hadir</span>
                    <span>&middot;</span>
                    <span><strong class="text-amber-600 dark:text-amber-400">{{ $izinCount ?? 0 }}</strong> Izin</span>
                    <span>&middot;</span>
                    <span><strong class="text-pink-600 dark:text-pink-400">{{ $sakitCount ?? 0 }}</strong> Sakit</span>
                    <span>&middot;</span>
                    <span><strong class="text-gray-500 dark:text-gray-400">{{ $absentCount }}</strong> Belum</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-2.5 text-center">
                        <p class="text-base font-bold text-gray-900 dark:text-white">{{ $salesSudahPresensi ?? $salesPresent }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Sales</p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ $salesAbsent }} belum</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-2.5 text-center">
                        <p class="text-base font-bold text-gray-900 dark:text-white">{{ $driverSudahPresensi ?? ($driverPresent ?? 0) }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Driver</p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ $driverAbsent ?? 0 }} belum</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-2.5 text-center">
                        <p class="text-base font-bold text-gray-900 dark:text-white">{{ $staffSudahPresensi ?? $staffPresent }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Staff</p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ $staffAbsent ?? 0 }} belum</p>
                    </div>
                </div>
            </div>

            {{-- Card 4B: Kunjungan Hari Ini (Sales) --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Kunjungan Hari Ini</h3>
                    </div>
                    <a href="{{ route('admin.reports.visits') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $visitTotal }}</p>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-[#097A99]">{{ $visitInProgress }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Berjalan</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-green-700 dark:text-green-400">{{ $visitCompleted }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selesai</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ $visitSkipped }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Dilewati</p>
                    </div>
                </div>
            </div>

            {{-- Card 4C: Pengiriman Hari Ini (Driver) --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pengiriman Hari Ini</h3>
                    </div>
                    <a href="{{ route('admin.driver.visits.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $deliveryTotal ?? 0 }}</p>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-[#097A99]">{{ $deliveryInProgress ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Berjalan</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-green-700 dark:text-green-400">{{ $deliveryCompleted ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selesai</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $deliveryPending ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Belum</p>
                    </div>
                </div>
            </div>

            {{-- Card 4D: Master Toko --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Master Toko</h3>
                    </div>
                    <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Kelola Toko</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $totalStores }}</p>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-2.5">
                        <p class="text-base font-bold text-green-700 dark:text-green-400">{{ $activeStores }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Toko aktif</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-2.5">
                        <p class="text-base font-bold text-gray-700 dark:text-gray-300">{{ $totalStores - $activeStores }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Toko nonaktif</p>
                    </div>
                    <div class="rounded-lg bg-amber-50/50 dark:bg-amber-950/20 p-2.5">
                        <p class="text-base font-bold text-amber-600 dark:text-amber-400">{{ $unassignedStoresCount ?? 0 }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Belum ada Sales</p>
                    </div>
                    <div class="rounded-lg bg-blue-50/50 dark:bg-blue-950/20 p-2.5">
                        <p class="text-base font-bold text-blue-600 dark:text-blue-400">{{ $deliveryStoresCount ?? 0 }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Tujuan Kirim</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 5: Rencana Kunjungan & Rencana Pengiriman Hari Ini --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Rute Kunjungan Hari Ini (Sales) --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rencana Rute Kunjungan (Sales)</h3>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500">Hari ini</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.routes.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Rencana</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $routeTotal }}</p>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-[#0DA4CE]">{{ $routeActive }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Aktif</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-green-700 dark:text-green-400">{{ $routeCompleted }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selesai</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $routeDraft }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Belum mulai</p>
                    </div>
                </div>
            </div>

            {{-- Rute Pengiriman Hari Ini (Driver) --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rencana Rute Pengiriman (Driver)</h3>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500">Hari ini</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.driver.routes.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Rencana</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $deliveryRouteTotal ?? 0 }}</p>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-[#0DA4CE]">{{ $deliveryRouteActive ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Aktif</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-green-700 dark:text-green-400">{{ $deliveryRouteCompleted ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selesai</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-center">
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $deliveryRouteDraft ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Belum mulai</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 6: Grafik Tren & Ranking --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Grafik 1: Presensi 7 Hari Terakhir --}}
            <div class="sw-chart-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
                <div class="sw-chart-header flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Presensi 7 Hari Terakhir</h3>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Hadir, Izin, dan Sakit</p>
                    </div>
                    <a href="{{ route('admin.attendance.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="sw-chart-body min-h-[290px] h-[290px] relative w-full">
                    <div id="chart-attendance" class="w-full h-full"></div>
                </div>
            </div>

            {{-- Grafik 2: Kunjungan 7 Hari Terakhir (Sales) --}}
            <div class="sw-chart-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
                <div class="sw-chart-header flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Kunjungan 7 Hari Terakhir</h3>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Sales</p>
                    </div>
                    <a href="{{ route('admin.reports.visits') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="sw-chart-body min-h-[290px] h-[290px] relative w-full">
                    <div id="chart-visits" class="w-full h-full"></div>
                </div>
            </div>

            {{-- Grafik 3: Pengiriman 7 Hari Terakhir (Driver) --}}
            <div class="sw-chart-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
                <div class="sw-chart-header flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pengiriman 7 Hari Terakhir</h3>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Driver</p>
                    </div>
                    <a href="{{ route('admin.reports.driver-visits') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="sw-chart-body min-h-[290px] h-[290px] relative w-full">
                    <div id="chart-deliveries" class="w-full h-full"></div>
                </div>
            </div>

            {{-- Grafik 4: Top Sales Minggu Ini --}}
            <div class="sw-chart-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
                <div class="sw-chart-header flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top Sales Minggu Ini</h3>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Kunjungan selesai</p>
                    </div>
                    <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">Minggu Ini</span>
                </div>
                <div class="sw-chart-body min-h-[290px] h-[290px] relative w-full">
                    <div id="chart-top-sales" class="w-full h-full"></div>
                </div>
            </div>

            {{-- Grafik 5: Top Driver Minggu Ini --}}
            <div class="sw-chart-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
                <div class="sw-chart-header flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top Driver Minggu Ini</h3>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Pengiriman selesai</p>
                    </div>
                    <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">Minggu Ini</span>
                </div>
                <div class="sw-chart-body min-h-[290px] h-[290px] relative w-full">
                    <div id="chart-top-drivers" class="w-full h-full"></div>
                </div>
            </div>

            {{-- Grafik 6: Rencana Kunjungan 7 Hari Terakhir (Sales) --}}
            <div class="sw-chart-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
                <div class="sw-chart-header flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rencana Kunjungan 7 Hari Terakhir</h3>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Sales</p>
                    </div>
                    <a href="{{ route('admin.routes.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Rencana</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="sw-chart-body min-h-[290px] h-[290px] relative w-full">
                    <div id="chart-routes" class="w-full h-full"></div>
                </div>
            </div>

            {{-- Grafik 7: Rencana Pengiriman 7 Hari Terakhir (Driver) --}}
            <div class="sw-chart-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 lg:p-6 shadow-sm">
                <div class="sw-chart-header flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rencana Pengiriman 7 Hari Terakhir</h3>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Driver</p>
                    </div>
                    <a href="{{ route('admin.driver.routes.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#0DA4CE] hover:text-[#097A99] dark:text-[#38BDF8] dark:hover:text-cyan-300 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 dark:bg-[#0DA4CE]/20 dark:hover:bg-[#0DA4CE]/30 border border-[#0DA4CE]/20 transition-all duration-150 shrink-0">
                        <span>Lihat Rencana</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="sw-chart-body min-h-[290px] h-[290px] relative w-full">
                    <div id="chart-delivery-routes" class="w-full h-full"></div>
                </div>
            </div>
        </div>

        {{-- Section 7: Monitoring Real-Time Lapangan --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Performa Sales Hari Ini --}}
            <div id="sales-performance-section" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                <div class="px-5 lg:px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Performa Sales Hari Ini</h3>
                        @if(isset($salesPerformanceToday))
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Menampilkan {{ $salesPerformanceToday->firstItem() ?? 0 }}–{{ $salesPerformanceToday->lastItem() ?? 0 }} dari {{ $salesPerformanceToday->total() }} sales</p>
                        @endif
                    </div>
                    <span class="text-[11px] text-gray-400">10 / halaman</span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($salesPerformanceToday ?? [] as $perf)
                    <div class="px-5 lg:px-6 py-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center text-xs font-bold text-white shrink-0">{{ strtoupper(substr($perf['name'] ?? 'S', 0, 2)) }}</div>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $perf['name'] }}</p><p class="text-xs text-gray-400 dark:text-gray-500">{{ $perf['routes'] }} rute · {{ number_format(($perf['completed'] / max(1, $perf['routes'])) * 100, 0) }}% selesai</p></div>
                        <div class="flex items-center gap-3 text-center shrink-0"><div><p class="text-sm font-bold text-green-700 dark:text-green-400">{{ $perf['completed'] }}</p><p class="text-[10px] text-gray-400">Selesai</p></div><div><p class="text-sm font-bold text-[#097A99]">{{ $perf['in_progress'] }}</p><p class="text-[10px] text-gray-400">Berjalan</p></div><div><p class="text-sm font-bold text-red-600 dark:text-red-400">{{ $perf['skipped'] }}</p><p class="text-[10px] text-gray-400">Dilewati</p></div></div>
                    </div>
                    @empty
                    <div class="px-5 lg:px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada aktivitas kunjungan hari ini</div>
                    @endforelse
                </div>
                @if(isset($salesPerformanceToday) && $salesPerformanceToday->hasPages())
                <div class="px-5 lg:px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $salesPerformanceToday->links('vendor.pagination.tailwind') }}
                </div>
                @endif
            </div>

            {{-- Performa Driver Hari Ini --}}
            <div id="driver-performance-section" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                <div class="px-5 lg:px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Performa Driver Hari Ini</h3>
                        <span class="text-[11px] text-gray-400">10 / halaman</span>
                    </div>
                    @if(isset($driverPerformanceToday))
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Menampilkan {{ $driverPerformanceToday->firstItem() ?? 0 }}–{{ $driverPerformanceToday->lastItem() ?? 0 }} dari {{ $driverPerformanceToday->total() }} driver</p>
                    @endif
                    @php
                        $driverPerfTotal = $driverPerformanceTotalRoutes ?? collect($driverPerformanceToday ?? [])->sum('routes');
                        $driverPerfDone = $driverPerformanceTotalCompleted ?? collect($driverPerformanceToday ?? [])->sum('completed');
                        $driverPerfPct = $driverPerfTotal > 0 ? round(($driverPerfDone / $driverPerfTotal) * 100) : 0;
                    @endphp
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">@if($driverPerfTotal === 0) Belum ada aktivitas pengiriman hari ini @else {{ $driverPerfTotal }} rencana · {{ $driverPerfDone }} selesai · {{ $driverPerfPct }}% @endif</p>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($driverPerformanceToday ?? [] as $perf)
                    <div class="px-5 lg:px-6 py-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-xs font-bold text-white shrink-0">{{ strtoupper(substr($perf['name'] ?? 'D', 0, 2)) }}</div>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $perf['name'] }}</p><p class="text-xs text-gray-400 dark:text-gray-500">{{ $perf['routes'] }} rute · {{ $perf['completed'] }} selesai</p></div>
                        <div class="flex items-center gap-3 text-center shrink-0"><div><p class="text-sm font-bold text-green-700 dark:text-green-400">{{ $perf['completed'] }}</p><p class="text-[10px] text-gray-400">Selesai</p></div><div><p class="text-sm font-bold text-amber-600">{{ $perf['in_progress'] }}</p><p class="text-[10px] text-gray-400">Berjalan</p></div><div><p class="text-sm font-bold text-red-600 dark:text-red-400">{{ $perf['skipped'] }}</p><p class="text-[10px] text-gray-400">Dilewati</p></div></div>
                    </div>
                    @empty
                    <div class="px-5 lg:px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada aktivitas pengiriman hari ini</div>
                    @endforelse
                </div>
                @if(isset($driverPerformanceToday) && $driverPerformanceToday->hasPages())
                <div class="px-5 lg:px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $driverPerformanceToday->links('vendor.pagination.tailwind') }}
                </div>
                @endif
            </div>

            {{-- Pengguna Hari Ini (Sales + Driver + Staff) --}}
            <div id="users-today-section" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                <div class="px-5 lg:px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pengguna Hari Ini</h3>
                        @if(isset($usersTodayPaginated))
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Menampilkan {{ $usersTodayPaginated->firstItem() ?? 0 }}–{{ $usersTodayPaginated->lastItem() ?? 0 }} dari {{ $usersTodayPaginated->total() }} pengguna</p>
                        @endif
                    </div>
                    <span class="text-[11px] text-gray-400">10 / halaman</span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($usersTodayPaginated ?? [] as $row)
                    @php
                        $userStatusBadge = match($row['status']) {
                            'Hadir' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400',
                            'Izin' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                            'Sakit' => 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300',
                            default => 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400',
                        };
                    @endphp
                    <div class="px-5 lg:px-6 py-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-bold text-white shrink-0 @if($row['role'] === 'Sales') bg-[#0DA4CE] @elseif($row['role'] === 'Driver') bg-amber-500 @else bg-orange-500 @endif">{{ strtoupper(substr($row['name'] ?? 'U', 0, 2)) }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $row['name'] }} <span class="text-[11px] text-gray-400 font-normal">· {{ $row['role'] }}</span></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                @if($row['status'] === 'Izin' || $row['status'] === 'Sakit')
                                    <span class="truncate">{{ $row['absence_note'] ? Str::limit($row['absence_note'], 30) : $row['status'] }}</span>
                                @else
                                    {{ $row['clock_in'] }} — {{ $row['clock_out'] }}
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $userStatusBadge }}">{{ $row['status'] }}</span>
                    </div>
                    @empty
                    <div class="px-5 lg:px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada pengguna operasional hari ini</div>
                    @endforelse
                </div>
                @if(isset($usersTodayPaginated) && $usersTodayPaginated->hasPages())
                <div class="px-5 lg:px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $usersTodayPaginated->links('vendor.pagination.tailwind') }}
                </div>
                @endif
            </div>
        </div>

        {{-- Section 8: Aktivitas Terbaru Hari Ini --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
            <div class="px-5 lg:px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Aktivitas Hari Ini</h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">Riwayat aktivitas terbaru operasional lapangan</p>
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">Real-time</span>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @forelse($todayActivities as $activity)
                <div class="px-5 lg:px-6 py-3.5 flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                        @if($activity['kategori'] === 'presensi') bg-[#097A99]/10 text-[#097A99]
                        @elseif($activity['kategori'] === 'route') bg-indigo-100 dark:bg-indigo-900/30 text-indigo-500
                        @elseif($activity['kategori'] === 'check-in') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                        @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif">
                        @if($activity['kategori'] === 'presensi')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($activity['kategori'] === 'route')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        @elseif($activity['kategori'] === 'check-in')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-semibold">{{ $activity['label'] }}</span>
                            <span class="text-gray-500 dark:text-gray-400">&middot; {{ $activity['user'] }}</span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $activity['detail'] }}</p>
                    </div>
                    <span class="shrink-0 text-xs font-medium text-gray-400 dark:text-gray-500">{{ $activity['waktu']->format('H:i') }}</span>
                </div>
                @empty
                <div class="px-5 lg:px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada aktivitas hari ini</div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        .sw-chart-card { display: flex; flex-direction: column; min-width: 0; }
        .sw-chart-header { flex: 0 0 auto; position: relative; z-index: 1; padding-bottom: 12px; }
        .sw-chart-body { position: relative; width: 100%; min-height: 290px; }
        .sw-financial-chart-card .sw-chart-body { min-height: 320px; }
        .sw-financial-chart-card .apexcharts-legend {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 6px 14px !important;
            padding: 0 4px 10px 4px !important;
        }
        @media (min-width: 640px) {
            .sw-financial-chart-card .apexcharts-legend {
                justify-content: flex-end !important;
                gap: 8px 16px !important;
            }
        }
        .sw-financial-chart-card .apexcharts-legend-series {
            display: inline-flex !important;
            align-items: center !important;
            margin: 0 !important;
        }
        .sw-financial-chart-card .apexcharts-legend-text {
            font-size: 11px !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
        }
        @media (min-width: 640px) {
            .sw-financial-chart-card .apexcharts-legend-text {
                font-size: 12px !important;
            }
        }
        .sw-dashboard-chart { width: 100%; min-width: 0; }
        .sw-dashboard-chart .apexcharts-canvas,
        .sw-dashboard-chart .apexcharts-svg { max-width: 100% !important; }
        .sw-dashboard-chart .apexcharts-tooltip {
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(8px) !important;
        }
        .dark .sw-dashboard-chart .apexcharts-tooltip {
            border-color: #334155 !important;
            background: rgba(17, 24, 39, 0.98) !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5) !important;
        }
        .sw-dashboard-chart .apexcharts-xaxistooltip,
        .sw-dashboard-chart .apexcharts-yaxistooltip {
            display: none !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('click', function (event) {
            var link = event.target.closest('#users-today-section nav a, #sales-performance-section nav a, #driver-performance-section nav a');
            if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            event.preventDefault();

            var section = link.closest('[id$="-section"]');
            if (!section) return;

            section.setAttribute('aria-busy', 'true');
            section.classList.add('opacity-60', 'pointer-events-none');

            fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Gagal memuat halaman.');
                    return response.text();
                })
                .then(function (html) {
                    var documentResponse = new DOMParser().parseFromString(html, 'text/html');
                    var replacement = documentResponse.getElementById(section.id);
                    if (!replacement) throw new Error('Section tidak ditemukan.');

                    section.replaceWith(replacement);
                    window.history.replaceState({}, '', link.href);
                })
                .catch(function () {
                    window.location.assign(link.href + '#' + section.id);
                })
                .finally(function () {
                    var currentSection = document.getElementById(section.id);
                    if (currentSection) {
                        currentSection.removeAttribute('aria-busy');
                        currentSection.classList.remove('opacity-60', 'pointer-events-none');
                    }
                });
        });

        (function () {
            var charts = [];
            var attData = @json($attendanceTrend);
            var visitData = @json($visitTrend);
            var deliveryData = @json($deliveryTrend);
            var topSales = @json($topSalesWeek);
            var topDrivers = @json($topDriversWeek ?? []);
            var routeData = @json($routeTrend);
            var deliveryRouteData = @json($deliveryRouteTrend ?? []);
            var finData = @json($financialTrend);

            function destroyCharts() {
                charts.forEach(function (chart) {
                    try { chart.destroy(); } catch (e) {}
                });
                charts = [];
            }

            function getBaseConfig(darkMode, mutedText, gridColor, height) {
                return {
                    chart: {
                        toolbar: { show: false },
                        fontFamily: 'Figtree, sans-serif',
                        foreColor: mutedText,
                        height: height || 290,
                        width: '100%',
                        parentHeightOffset: 0,
                        redrawOnParentResize: true,
                        redrawOnWindowResize: true,
                        animations: {
                            enabled: true,
                            speed: 400,
                            dynamicAnimation: { enabled: true, speed: 200 }
                        }
                    },
                    noData: {
                        text: 'Belum ada data untuk periode ini',
                        align: 'center',
                        verticalAlign: 'middle',
                        style: {
                            color: mutedText,
                            fontSize: '13px',
                            fontFamily: 'Figtree, sans-serif'
                        }
                    },
                    dataLabels: { enabled: false },
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 4,
                        position: 'back',
                        padding: { left: 8, right: 12, top: 8, bottom: 0 },
                        xaxis: { lines: { show: false } },
                        yaxis: { lines: { show: true } }
                    },
                    xaxis: {
                        labels: {
                            style: { fontSize: '11px', colors: mutedText },
                            trim: true,
                            hideOverlappingLabels: true
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        crosshairs: {
                            show: true,
                            stroke: { color: darkMode ? '#475569' : '#94A3B8', width: 1, dashArray: 3 }
                        },
                        tooltip: { enabled: false }
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            style: { fontSize: '11px', colors: mutedText },
                            minWidth: 28,
                            maxWidth: 60,
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        style: { fontSize: '12px' },
                        fillSeriesColor: false
                    }
                };
            }

            function createChart(selector, options) {
                var el = document.querySelector(selector);
                if (!el) return null;
                try {
                    var chart = new ApexCharts(el, options);
                    charts.push(chart);
                    chart.render();
                    return chart;
                } catch (err) {
                    console.error('ApexCharts render error on ' + selector, err);
                    return null;
                }
            }

            // 1. Chart Performa Keuangan Sales
            function renderFinancialChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 320);
                var finPoints = (finData && finData.points) ? finData.points : [];
                var finRpFormatter = function(val) {
                    var num = Number(val || 0);
                    if (num >= 1000000000) return 'Rp ' + (num / 1000000000).toFixed(1) + ' M';
                    if (num >= 1000000) return 'Rp ' + (num / 1000000).toFixed(1) + ' jt';
                    if (num >= 1000) return 'Rp ' + (num / 1000).toFixed(0) + ' rb';
                    return 'Rp ' + num.toLocaleString('id-ID');
                };

                createChart('#chart-financial', {
                    ...base,
                    chart: {
                        ...base.chart,
                        type: 'line'
                    },
                    colors: ['#10B981', '#6366F1', '#F59E0B'],
                    stroke: {
                        curve: 'smooth',
                        width: [3, 3, 3],
                        dashArray: [0, 0, 0],
                        lineCap: 'round'
                    },
                    markers: {
                        size: finPoints.length > 0 ? 4 : 0,
                        strokeWidth: 2,
                        strokeColors: darkMode ? '#111827' : '#FFFFFF',
                        colors: ['#10B981', '#6366F1', '#F59E0B'],
                        hover: { size: 6, sizeOffset: 2 }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        fontSize: '12px',
                        fontWeight: 600,
                        itemMargin: { horizontal: 8, vertical: 4 },
                        labels: { colors: mutedText },
                        markers: { width: 10, height: 10, radius: 12, offsetX: -2 }
                    },
                    responsive: [
                        {
                            breakpoint: 640,
                            options: {
                                chart: {
                                    height: 350
                                },
                                legend: {
                                    position: 'top',
                                    horizontalAlign: 'left',
                                    fontSize: '11px',
                                    itemMargin: { horizontal: 4, vertical: 3 },
                                    markers: { width: 8, height: 8, radius: 8, offsetX: -2 }
                                },
                                yaxis: {
                                    labels: {
                                        minWidth: 32,
                                        maxWidth: 65,
                                        style: { fontSize: '10px' }
                                    }
                                },
                                xaxis: {
                                    labels: {
                                        style: { fontSize: '10px' }
                                    }
                                }
                            }
                        }
                    ],
                    series: [
                        {
                            name: 'Pemasukan Transaksi Baru',
                            data: finPoints.map(function (d) { return Number(d.new_tx_cash_in || 0); })
                        },
                        {
                            name: 'Pembayaran Piutang Lama',
                            data: finPoints.map(function (d) { return Number(d.old_debt_payment || 0); })
                        },
                        {
                            name: 'Saldo Piutang Akhir Hari',
                            data: finPoints.map(function (d) { return Number(d.outstanding_balance || 0); })
                        }
                    ],
                    xaxis: {
                        ...base.xaxis,
                        categories: finPoints.map(function (d) { return d.date; })
                    },
                    yaxis: {
                        ...base.yaxis,
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            ...base.yaxis.labels,
                            minWidth: 40,
                            maxWidth: 85,
                            formatter: finRpFormatter
                        }
                    },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        shared: true,
                        intersect: false,
                        custom: function({ series, dataPointIndex, w }) {
                            var pt = finPoints[dataPointIndex] || {};
                            var dateLabel = pt.full_date || pt.date || '';
                            var newTx = Number(series[0][dataPointIndex] || 0);
                            var oldDebt = Number(series[1][dataPointIndex] || 0);
                            var settlement = Number(pt.debt_settlement_cash || 0);
                            var outstanding = Number(series[2][dataPointIndex] || 0);
                            var totalCashIn = newTx + oldDebt;

                            return '<div class="px-4 py-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-xl border border-gray-100 dark:border-gray-800 text-xs leading-relaxed min-w-[260px]">' +
                                '<div class="font-bold text-gray-700 dark:text-gray-300 mb-2 pb-1.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">' +
                                    '<span>' + dateLabel + '</span>' +
                                    '<span class="text-[10px] font-normal text-gray-400">Ringkasan Harian</span>' +
                                '</div>' +
                                '<div class="space-y-1.5">' +
                                    '<div class="flex items-center justify-between gap-3">' +
                                        '<span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-[#10B981] inline-block"></span>Pemasukan Transaksi Baru:</span>' +
                                        '<span class="font-bold text-[#10B981]">Rp ' + newTx.toLocaleString('id-ID') + '</span>' +
                                    '</div>' +
                                    '<div class="flex items-center justify-between gap-3">' +
                                        '<span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-[#6366F1] inline-block"></span>Pembayaran Piutang Lama:</span>' +
                                        '<span class="font-bold text-[#6366F1]">Rp ' + oldDebt.toLocaleString('id-ID') + '</span>' +
                                    '</div>' +
                                    (settlement > 0 ? (
                                        '<div class="flex items-center justify-between gap-3 pl-4 text-[11px] text-gray-400 dark:text-gray-500">' +
                                            '<span>&bull; Pelunasan Lunas (Rp0):</span>' +
                                            '<span class="font-semibold text-indigo-500 dark:text-indigo-400">Rp ' + settlement.toLocaleString('id-ID') + '</span>' +
                                        '</div>' +
                                        '<div class="pl-4 text-[9px] text-gray-400 dark:text-gray-500 italic">Bagian dari Pembayaran Piutang Lama</div>'
                                    ) : '') +
                                    '<div class="flex items-center justify-between gap-3 py-1.5 border-t border-gray-100 dark:border-gray-800 text-[11px]">' +
                                        '<span class="text-gray-500 dark:text-gray-400 font-semibold">Total Uang Masuk:</span>' +
                                        '<span class="font-extrabold text-gray-900 dark:text-white">Rp ' + totalCashIn.toLocaleString('id-ID') + '</span>' +
                                    '</div>' +
                                    '<div class="flex items-center justify-between gap-3 pt-1.5 border-t border-dashed border-gray-200 dark:border-gray-700">' +
                                        '<span class="flex items-center gap-1.5 text-amber-700 dark:text-amber-400 font-medium"><span class="w-2.5 h-2.5 rounded-full bg-[#F59E0B] inline-block"></span>Saldo Piutang Akhir Hari:</span>' +
                                        '<span class="font-extrabold text-amber-700 dark:text-amber-400">Rp ' + outstanding.toLocaleString('id-ID') + '</span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            // 2. Chart Presensi 7 Hari Terakhir
            function renderAttendanceChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 290);
                createChart('#chart-attendance', {
                    ...base,
                    chart: {
                        ...base.chart,
                        type: 'bar',
                        stacked: true
                    },
                    colors: ['#10B981', '#F59E0B', '#EC4899'],
                    plotOptions: {
                        bar: {
                            columnWidth: '50%',
                            borderRadius: 4,
                            borderRadiusApplication: 'end'
                        }
                    },
                    series: [
                        {
                            name: 'Hadir',
                            data: attData.map(function (d) { return Number(d.hadir || 0); })
                        },
                        {
                            name: 'Izin',
                            data: attData.map(function (d) { return Number(d.izin || 0); })
                        },
                        {
                            name: 'Sakit',
                            data: attData.map(function (d) { return Number(d.sakit || 0); })
                        }
                    ],
                    xaxis: {
                        ...base.xaxis,
                        categories: attData.map(function (d) { return d.date; })
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        fontSize: '11px',
                        itemMargin: { horizontal: 6 },
                        labels: { colors: mutedText },
                        markers: { radius: 12 }
                    },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        shared: true,
                        intersect: false,
                        custom: function({ series, dataPointIndex, w }) {
                            var item = attData[dataPointIndex] || {};
                            var dateLabel = item.full_date || item.date || '';
                            var hadir = Number(series[0][dataPointIndex] || 0);
                            var izin = Number(series[1][dataPointIndex] || 0);
                            var sakit = Number(series[2][dataPointIndex] || 0);
                            var tot = hadir + izin + sakit;

                            return '<div class="px-3.5 py-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 text-xs leading-relaxed min-w-[200px]">' +
                                '<div class="font-semibold text-gray-500 dark:text-gray-400 mb-1.5 pb-1 border-b border-gray-100 dark:border-gray-800">' + dateLabel + '</div>' +
                                '<div class="space-y-1">' +
                                    '<div class="flex items-center justify-between gap-4"><span class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>Hadir:</span><span class="font-bold text-emerald-600 dark:text-emerald-400">' + hadir + '</span></div>' +
                                    '<div class="flex items-center justify-between gap-4"><span class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>Izin:</span><span class="font-bold text-amber-600 dark:text-amber-400">' + izin + '</span></div>' +
                                    '<div class="flex items-center justify-between gap-4"><span class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-pink-500 inline-block"></span>Sakit:</span><span class="font-bold text-pink-600 dark:text-pink-400">' + sakit + '</span></div>' +
                                    '<div class="flex items-center justify-between gap-4 pt-1 border-t border-gray-100 dark:border-gray-800 font-semibold"><span>Total Presensi:</span><span class="text-gray-900 dark:text-white">' + tot + '</span></div>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            // 3. Chart Kunjungan 7 Hari Terakhir (Sales)
            function renderVisitsChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 290);
                createChart('#chart-visits', {
                    ...base,
                    chart: { ...base.chart, type: 'bar' },
                    colors: ['#6366F1', '#10B981', '#EF4444'],
                    plotOptions: {
                        bar: {
                            columnWidth: '56%',
                            borderRadius: 4,
                            borderRadiusApplication: 'end',
                            distributed: false
                        }
                    },
                    states: { hover: { filter: { type: 'light', value: 0.08 } } },
                    series: [
                        { name: 'Total Kunjungan', data: visitData.map(function (d) { return Number(d.total || 0); }) },
                        { name: 'Selesai', data: visitData.map(function (d) { return Number(d.completed || 0); }) },
                        { name: 'Dilewati', data: visitData.map(function (d) { return Number(d.skipped || 0); }) }
                    ],
                    xaxis: {
                        ...base.xaxis,
                        categories: visitData.map(function (d) { return d.date; })
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        fontSize: '11px',
                        itemMargin: { horizontal: 6 },
                        labels: { colors: mutedText },
                        markers: { radius: 12 }
                    },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            var item = visitData[dataPointIndex] || {};
                            var dateLabel = item.full_date || item.date || '';
                            var tot = Number(series[0][dataPointIndex] || 0);
                            var comp = Number(series[1][dataPointIndex] || 0);
                            var skip = Number(series[2][dataPointIndex] || 0);

                            return '<div class="px-3.5 py-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 text-xs leading-relaxed">' +
                                '<div class="font-semibold text-gray-500 dark:text-gray-400 mb-1.5 pb-1 border-b border-gray-100 dark:border-gray-800">' + dateLabel + '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Total Kunjungan:</span>' +
                                    '<span class="font-bold text-indigo-600 dark:text-indigo-400">' + tot + '</span>' +
                                '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Kunjungan Selesai:</span>' +
                                    '<span class="font-bold text-emerald-600 dark:text-emerald-400">' + comp + '</span>' +
                                '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Dilewati:</span>' +
                                    '<span class="font-bold text-red-600 dark:text-red-400">' + skip + '</span>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            // 4. Chart Pengiriman 7 Hari Terakhir (Driver)
            function renderDeliveriesChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 290);
                createChart('#chart-deliveries', {
                    ...base,
                    chart: { ...base.chart, type: 'bar' },
                    colors: ['#F59E0B', '#10B981', '#EF4444'],
                    plotOptions: { bar: { columnWidth: '56%', borderRadius: 4, borderRadiusApplication: 'end', distributed: false } },
                    states: { hover: { filter: { type: 'light', value: 0.08 } } },
                    series: [
                        { name: 'Total Pengiriman', data: deliveryData.map(function (d) { return Number(d.total || 0); }) },
                        { name: 'Selesai', data: deliveryData.map(function (d) { return Number(d.completed || 0); }) },
                        { name: 'Dilewati', data: deliveryData.map(function (d) { return Number(d.skipped || 0); }) }
                    ],
                    xaxis: { ...base.xaxis, categories: deliveryData.map(function (d) { return d.date; }) },
                    legend: { position: 'top', horizontalAlign: 'right', fontSize: '11px', itemMargin: { horizontal: 6 }, labels: { colors: mutedText }, markers: { radius: 12 } },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            var item = deliveryData[dataPointIndex] || {};
                            var dateLabel = item.full_date || item.date || '';
                            var tot = Number(series[0][dataPointIndex] || 0);
                            var comp = Number(series[1][dataPointIndex] || 0);
                            var skip = Number(series[2][dataPointIndex] || 0);

                            return '<div class="px-3.5 py-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 text-xs leading-relaxed">' +
                                '<div class="font-semibold text-gray-500 dark:text-gray-400 mb-1.5 pb-1 border-b border-gray-100 dark:border-gray-800">' + dateLabel + '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Total Pengiriman:</span>' +
                                    '<span class="font-bold text-amber-600">' + tot + '</span>' +
                                '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Selesai:</span>' +
                                    '<span class="font-bold text-emerald-600">' + comp + '</span>' +
                                '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Dilewati:</span>' +
                                    '<span class="font-bold text-red-600">' + skip + '</span>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            // 5. Chart Top Sales Minggu Ini (Horizontal Bar)
            function renderTopSalesChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 290);
                var hasTopSales = topSales && topSales.length > 0;
                var cats = hasTopSales ? topSales.map(function (d) { return d.name || '-'; }) : ['Belum Ada Aktivitas'];
                var dataVals = hasTopSales ? topSales.map(function (d) { return Number(d.total || 0); }) : [0];

                createChart('#chart-top-sales', {
                    ...base,
                    chart: {
                        ...base.chart,
                        type: 'bar'
                    },
                    colors: ['#0DA4CE'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: topSales.length === 1 ? '30%' : (topSales.length <= 3 ? '45%' : '56%'),
                            borderRadiusApplication: 'end'
                        }
                    },
                    states: { hover: { filter: { type: 'light', value: 0.08 } } },
                    dataLabels: {
                        enabled: hasTopSales,
                        textAnchor: 'start',
                        offsetX: 8,
                        style: {
                            fontSize: '11px',
                            fontWeight: 700,
                            colors: [darkMode ? '#F3F4F6' : '#374151']
                        },
                        formatter: function (value) {
                            return value > 0 ? value.toLocaleString('id-ID') + ' kunjungan' : '';
                        }
                    },
                    series: [{
                        name: 'Kunjungan Selesai',
                        data: dataVals
                    }],
                    xaxis: {
                        ...base.xaxis,
                        categories: cats,
                        labels: {
                            ...base.xaxis.labels,
                            trim: true,
                            hideOverlappingLabels: true,
                            formatter: function (value) {
                                return Math.round(value);
                            }
                        }
                    },
                    yaxis: {
                        ...base.yaxis,
                        labels: {
                            ...base.yaxis.labels,
                            maxWidth: 130,
                            trim: true
                        }
                    },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            var item = topSales[dataPointIndex] || {};
                            var name = item.name || '-';
                            var count = Number(series[seriesIndex][dataPointIndex] || 0);

                            return '<div class="px-3.5 py-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 text-xs leading-relaxed">' +
                                '<div class="font-semibold text-gray-500 dark:text-gray-400 mb-1.5 pb-1 border-b border-gray-100 dark:border-gray-800">Ranking Sales (Minggu Ini)</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300 font-medium">' + name + ':</span>' +
                                    '<span class="font-bold text-[#0DA4CE] dark:text-[#38BDF8]">' + count + ' kunjungan selesai</span>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            // 6. Chart Top Driver Minggu Ini (Horizontal Bar)
            function renderTopDriversChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 290);
                var hasTopDrivers = topDrivers && topDrivers.length > 0;
                var cats = hasTopDrivers ? topDrivers.map(function (d) { return d.name || '-'; }) : ['Belum Ada Aktivitas'];
                var dataVals = hasTopDrivers ? topDrivers.map(function (d) { return Number(d.total || 0); }) : [0];

                createChart('#chart-top-drivers', {
                    ...base,
                    chart: { ...base.chart, type: 'bar' },
                    colors: ['#F59E0B'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: topDrivers.length === 1 ? '30%' : (topDrivers.length <= 3 ? '45%' : '56%'),
                            borderRadiusApplication: 'end'
                        }
                    },
                    states: { hover: { filter: { type: 'light', value: 0.08 } } },
                    dataLabels: {
                        enabled: hasTopDrivers,
                        textAnchor: 'start',
                        offsetX: 8,
                        style: {
                            fontSize: '11px',
                            fontWeight: 700,
                            colors: [darkMode ? '#F3F4F6' : '#374151']
                        },
                        formatter: function (value) {
                            return value > 0 ? value.toLocaleString('id-ID') + ' pengiriman' : '';
                        }
                    },
                    series: [{ name: 'Pengiriman Selesai', data: dataVals }],
                    xaxis: { ...base.xaxis, categories: cats },
                    yaxis: { ...base.yaxis, labels: { ...base.yaxis.labels, maxWidth: 130, trim: true } },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            var item = topDrivers[dataPointIndex] || {};
                            var name = item.name || '-';
                            var count = Number(series[seriesIndex][dataPointIndex] || 0);

                            return '<div class="px-3.5 py-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 text-xs leading-relaxed">' +
                                '<div class="font-semibold text-gray-500 dark:text-gray-400 mb-1.5 pb-1 border-b border-gray-100 dark:border-gray-800">Ranking Driver (Minggu Ini)</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300 font-medium">' + name + ':</span>' +
                                    '<span class="font-bold text-amber-600">' + count + ' pengiriman selesai</span>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            // 7. Chart Rencana Kunjungan 7 Hari Terakhir (Sales)
            function renderRoutesChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 290);
                createChart('#chart-routes', {
                    ...base,
                    chart: { ...base.chart, type: 'area' },
                    colors: ['#6366F1'],
                    stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: darkMode ? 0.28 : 0.32,
                            opacityTo: 0.02,
                            stops: [0, 90, 100]
                        }
                    },
                    markers: {
                        size: routeData.length > 0 ? 4 : 0,
                        strokeWidth: 2,
                        strokeColors: darkMode ? '#111827' : '#FFFFFF',
                        colors: ['#6366F1'],
                        hover: { size: 6, sizeOffset: 2 }
                    },
                    series: [{
                        name: 'Rencana Kunjungan',
                        data: routeData.map(function (d) { return Number(d.total || 0); })
                    }],
                    xaxis: {
                        ...base.xaxis,
                        categories: routeData.map(function (d) { return d.date; })
                    },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            var item = routeData[dataPointIndex] || {};
                            var dateLabel = item.full_date || item.date || '';
                            var count = Number(series[seriesIndex][dataPointIndex] || 0);

                            return '<div class="px-3.5 py-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 text-xs leading-relaxed">' +
                                '<div class="font-semibold text-gray-500 dark:text-gray-400 mb-1.5 pb-1 border-b border-gray-100 dark:border-gray-800">' + dateLabel + '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Rencana Kunjungan:</span>' +
                                    '<span class="font-bold text-indigo-600 dark:text-indigo-400">' + count + ' rencana</span>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            // 8. Chart Rencana Pengiriman 7 Hari Terakhir (Driver)
            function renderDeliveryRoutesChart(darkMode, mutedText, gridColor) {
                var base = getBaseConfig(darkMode, mutedText, gridColor, 290);
                createChart('#chart-delivery-routes', {
                    ...base,
                    chart: { ...base.chart, type: 'area' },
                    colors: ['#F59E0B'],
                    stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: darkMode ? 0.30 : 0.35,
                            opacityTo: 0.02,
                            stops: [0, 90, 100]
                        }
                    },
                    markers: {
                        size: deliveryRouteData.length > 0 ? 4 : 0,
                        strokeWidth: 2,
                        strokeColors: darkMode ? '#111827' : '#FFFFFF',
                        colors: ['#F59E0B'],
                        hover: { size: 6, sizeOffset: 2 }
                    },
                    series: [{ name: 'Rencana Pengiriman', data: deliveryRouteData.map(function (d) { return Number(d.total || 0); }) }],
                    xaxis: { ...base.xaxis, categories: deliveryRouteData.map(function (d) { return d.date; }) },
                    tooltip: {
                        theme: darkMode ? 'dark' : 'light',
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            var item = deliveryRouteData[dataPointIndex] || {};
                            var dateLabel = item.full_date || item.date || '';
                            var count = Number(series[seriesIndex][dataPointIndex] || 0);

                            return '<div class="px-3.5 py-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 text-xs leading-relaxed">' +
                                '<div class="font-semibold text-gray-500 dark:text-gray-400 mb-1.5 pb-1 border-b border-gray-100 dark:border-gray-800">' + dateLabel + '</div>' +
                                '<div class="flex items-center justify-between gap-4 py-0.5">' +
                                    '<span class="text-gray-600 dark:text-gray-300">Jumlah Rencana:</span>' +
                                    '<span class="font-bold text-amber-600">' + count + ' rencana</span>' +
                                '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            function renderAllCharts() {
                if (typeof ApexCharts === 'undefined') return;

                destroyCharts();

                var darkMode = document.documentElement.classList.contains('dark');
                var mutedText = darkMode ? '#9CA3AF' : '#6B7280';
                var gridColor = darkMode ? '#1F2937' : '#F1F5F9';

                renderFinancialChart(darkMode, mutedText, gridColor);
                renderAttendanceChart(darkMode, mutedText, gridColor);
                renderVisitsChart(darkMode, mutedText, gridColor);
                renderDeliveriesChart(darkMode, mutedText, gridColor);
                renderTopSalesChart(darkMode, mutedText, gridColor);
                renderTopDriversChart(darkMode, mutedText, gridColor);
                renderRoutesChart(darkMode, mutedText, gridColor);
                renderDeliveryRoutesChart(darkMode, mutedText, gridColor);
            }

            var attempts = 0;
            function tryInitCharts() {
                if (typeof ApexCharts !== 'undefined') {
                    renderAllCharts();
                } else if (attempts < 60) {
                    attempts++;
                    setTimeout(tryInitCharts, 50);
                } else {
                    // Try fallback CDN if primary was blocked
                    var fallback = document.createElement('script');
                    fallback.src = 'https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.49.0/apexcharts.min.js';
                    fallback.onload = function () { renderAllCharts(); };
                    document.head.appendChild(fallback);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', tryInitCharts);
            } else {
                tryInitCharts();
            }

            window.addEventListener('theme-changed', renderAllCharts);
        })();
    </script>
    @endpush
</x-app-layout>
