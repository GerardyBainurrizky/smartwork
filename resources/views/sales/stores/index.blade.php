<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="credit-card" title="Toko & Piutang" subtitle="Daftar toko tanggung jawab dan pemantauan saldo piutang"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        {{-- KPI Cards Ringkasan Sales --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            {{-- Total Toko --}}
            <div class="sw-card p-4.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Total Toko</span>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5 leading-tight">
                    {{ $totalStores }} <span class="text-xs font-normal text-gray-400">Toko</span>
                </p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Tanggung jawab Anda</p>
            </div>

            {{-- Toko Berpiutang --}}
            <div class="sw-card p-4.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Toko Berpiutang</span>
                <p class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1.5 leading-tight">
                    {{ $storesWithReceivable }} <span class="text-xs font-normal text-gray-400">Toko</span>
                </p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Memiliki saldo piutang > 0</p>
            </div>

            {{-- Total Saldo Piutang --}}
            <div class="sw-card p-4.5 bg-gradient-to-br from-red-50/70 to-white dark:from-red-950/20 dark:to-gray-800 border-red-100 dark:border-red-900/40">
                <span class="text-[11px] font-bold uppercase tracking-wider text-red-700 dark:text-red-400 block">Total Saldo Piutang</span>
                <p class="text-lg sm:text-xl xl:text-2xl font-extrabold font-mono text-red-600 dark:text-red-400 mt-1.5 whitespace-normal break-words leading-tight">
                    Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Seluruh saldo piutang toko yang masih belum lunas.</p>
            </div>

            {{-- Transaksi Terbuka --}}
            <div class="sw-card p-4.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Transaksi Terbuka</span>
                <p class="text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1.5 leading-tight">
                    {{ $totalOpenTransactions }} <span class="text-xs font-normal text-gray-400">Trx</span>
                </p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Faktur belum lunas / tempo</p>
            </div>
        </div>

        {{-- Search & Filter Section --}}
        <div class="sw-card p-4 sm:p-5">
            <form method="GET" action="{{ route('sales.stores.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 sm:gap-4 items-end">
                    {{-- 1. Status Piutang --}}
                    <div class="sm:col-span-4">
                        <label for="status-piutang-filter" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1.5">Status Piutang</label>
                        <select id="status-piutang-filter" name="status" class="sw-input text-xs py-2.5 w-full" onchange="this.form.submit()">
                            <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="with" {{ request('status') == 'with' ? 'selected' : '' }}>Ada Piutang</option>
                            <option value="without" {{ request('status') == 'without' ? 'selected' : '' }}>Tidak Ada Piutang</option>
                        </select>
                    </div>

                    {{-- 2. Cari Toko --}}
                    <div class="sm:col-span-6">
                        <label for="search-toko-input" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1.5">Cari Toko</label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input id="search-toko-input" type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari nama toko / kode toko..."
                                class="sw-input text-xs py-2.5 !pl-11 pr-3 w-full"
                                style="padding-left: 2.75rem;">
                        </div>
                    </div>

                    {{-- 3. Action Buttons --}}
                    <div class="sm:col-span-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-[#0DA4CE] hover:bg-[#097A99] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('sales.stores.index') }}" class="px-3.5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold transition flex items-center justify-center shrink-0" title="Reset Filter">
                            <span>Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Daftar Toko Grid Cards --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-gray-900 dark:text-gray-100 text-base">Daftar Toko Tanggung Jawab</h3>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $stores->total() }} Toko
                </span>
            </div>

            @if($stores->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($stores as $st)
                <div class="sw-card p-5 space-y-3.5 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        {{-- Top Header Toko --}}
                        <div class="flex items-start justify-between gap-2.5 pb-2.5 border-b border-gray-100 dark:border-gray-800">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-gray-100 text-base leading-snug break-words">
                                    {{ $st->name }}
                                </h4>
                                <p class="text-[11px] font-mono text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $st->code ?? '-' }}
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0
                                @if($st->receivable_balance > 0.005) bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300
                                @else bg-green-100 text-green-800 dark:bg-green-950/60 dark:text-green-300 @endif">
                                {{ $st->receivable_balance > 0.005 ? 'Memiliki Piutang' : 'Tidak Ada Piutang' }}
                            </span>
                        </div>

                        {{-- Alamat --}}
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2.5 line-clamp-2">
                            {{ $st->address ?: 'Alamat belum diatur' }}
                        </p>
                    </div>

                    <div class="space-y-3 pt-2">
                        {{-- Piutang Box --}}
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 block">Total Piutang Toko</span>
                                <p class="text-base sm:text-lg font-bold font-mono {{ $st->receivable_balance > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-0.5">
                                    Rp {{ number_format($st->receivable_balance, 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 block">Transaksi Terbuka</span>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 mt-0.5">
                                    {{ $st->open_transactions_count }} Trx
                                </p>
                            </div>
                        </div>

                        {{-- Action Button --}}
                        <a href="{{ route('sales.stores.show', $st->id) }}"
                            class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] font-bold rounded-xl text-xs transition">
                            <span>Lihat Detail &amp; Faktur</span>
                            <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($stores->hasPages())
            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-800">
                {{ $stores->links() }}
            </div>
            @endif
            @else
            <div class="sw-card p-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                Tidak ada data toko yang sesuai dengan pencarian.
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
