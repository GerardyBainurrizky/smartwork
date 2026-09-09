<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="credit-card" title="Master Piutang Toko" subtitle="Pantau saldo piutang, transaksi, dan pembayaran toko secara terpusat."></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto" x-data="{
        showNewTrxModal: false,
        storeSearch: '',
        selectedStoreId: '',
        selectedStoreName: '',
        selectedStoreCode: '',
        selectedStoreCity: '',
        isStoreDropdownOpen: false,
        rawAmount: '',
        formattedAmount: '',
        rawPaidAmount: '',
        formattedPaidAmount: '',
        onAmountInput(event) {
            const digits = (event.target.value || '').replace(/\D/g, '');
            this.rawAmount = digits;
            this.formattedAmount = digits ? Number(digits).toLocaleString('id-ID') : '';
        },
        onPaidAmountInput(event) {
            const digits = (event.target.value || '').replace(/\D/g, '');
            let num = Number(digits) || 0;
            const total = Number(this.rawAmount) || 0;
            if (total > 0 && num > total) {
                num = total;
            }
            this.rawPaidAmount = num > 0 ? String(num) : '';
            this.formattedPaidAmount = num > 0 ? num.toLocaleString('id-ID') : '';
        },
        get newTrxRemaining() {
            const total = Number(this.rawAmount) || 0;
            const paid = Number(this.rawPaidAmount) || 0;
            return Math.max(0, total - paid);
        },
        allStores: {{ json_encode($allStores->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code ?? '-', 'city' => $s->city ?? '-'])) }},
        get filteredStores() {
            if (!this.storeSearch) return this.allStores;
            const q = this.storeSearch.toLowerCase();
            return this.allStores.filter(s => s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q) || s.city.toLowerCase().includes(q));
        },
        selectStore(st) {
            this.selectedStoreId = st.id;
            this.selectedStoreName = st.name;
            this.selectedStoreCode = st.code;
            this.selectedStoreCity = st.city;
            this.isStoreDropdownOpen = false;
        },
        clearStore() {
            this.selectedStoreId = '';
            this.selectedStoreName = '';
            this.selectedStoreCode = '';
            this.selectedStoreCity = '';
            this.storeSearch = '';
        }
    }">
        {{-- KPI Cards Overview (8 Final KPI Cards with explicit period labels and click navigations) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            {{-- 1. TOTAL PIUTANG (Saldo saat ini, tidak reset) --}}
            <div class="sw-card p-4.5 bg-gradient-to-br from-red-50/80 to-white dark:from-red-950/25 dark:to-gray-800 border-red-100 dark:border-red-900/40 hover:border-red-300 dark:hover:border-red-700 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-red-700 dark:text-red-400 block">Total Piutang</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-red-100/80 text-red-800 dark:bg-red-950/60 dark:text-red-300">Saldo saat ini</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-extrabold font-mono text-red-600 dark:text-red-400 mt-2 whitespace-normal break-words leading-tight">
                        Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 mt-2 pt-2 border-t border-red-100/60 dark:border-red-900/30">
                    <span>Total saldo piutang yang masih outstanding</span>
                    <a href="{{ route('admin.receivables.summary.total') }}" class="inline-flex items-center gap-1 text-xs font-bold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition group shrink-0 ml-1">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- 2. TOKO BERPIUTANG (Kondisi saat ini, tidak reset) --}}
            <div class="sw-card p-4.5 hover:border-[#0DA4CE] dark:hover:border-[#0DA4CE] transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Toko Berpiutang</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">Saat ini</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2 leading-tight">
                        {{ $storesWithReceivable }} <span class="text-xs font-normal text-gray-400">toko distinct</span>
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Saldo aktif &gt; Rp0</span>
                    <a href="{{ route('admin.receivables.summary.indebted-stores') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0DA4CE] hover:text-[#097A99] dark:hover:text-[#5BD8F7] transition group shrink-0 ml-1">
                        <span>Lihat Toko</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- 3. TRANSAKSI TERBUKA (Kondisi saat ini, tidak reset) --}}
            <div class="sw-card p-4.5 hover:border-amber-400 dark:hover:border-amber-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Transaksi Terbuka</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">Saat ini</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2 leading-tight">
                        {{ $totalOpenTransactions }} <span class="text-xs font-normal text-gray-400">trx</span>
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Sisa tagihan &gt; Rp0</span>
                    <a href="{{ route('admin.receivables.summary.open-transactions') }}" class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 hover:text-amber-700 transition group shrink-0 ml-1">
                        <span>Lihat Transaksi</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- 4. TOTAL PEMBAYARAN PIUTANG LAMA (Kumulatif All Time piutang lama) --}}
            <div class="sw-card p-4.5 hover:border-green-400 dark:hover:border-green-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Total Pembayaran Piutang Lama</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-50 text-green-700 dark:bg-green-950/50 dark:text-green-300">Akumulasi piutang lama</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-bold font-mono text-green-600 dark:text-green-400 mt-2 whitespace-normal break-words leading-tight">
                        Rp {{ number_format($totalPayments, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Akumulasi pembayaran untuk piutang yang sudah ada</span>
                    <a href="{{ route('admin.receivables.summary.payments') }}" class="inline-flex items-center gap-1 text-xs font-bold text-green-600 dark:text-green-400 hover:text-green-700 transition group shrink-0 ml-1">
                        <span>Lihat Pembayaran</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- 5. PEMBAYARAN PIUTANG HARI INI (Hari ini saja) --}}
            <div class="sw-card p-4.5 hover:border-emerald-400 dark:hover:border-emerald-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Pembayaran Piutang Hari Ini</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Hari ini</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-2 whitespace-normal break-words leading-tight">
                        Rp {{ number_format($todayPayments, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Pembayaran piutang lama yang diterima hari ini</span>
                    <a href="{{ route('admin.receivables.summary.today-payments') }}" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 transition group shrink-0 ml-1">
                        <span>Lihat Pembayaran</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- 6. TRANSAKSI LUNAS (Saldo transaksi = Rp0) --}}
            <div class="sw-card p-4.5 hover:border-blue-400 dark:hover:border-blue-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Transaksi Lunas</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">Saldo transaksi = Rp0</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2 leading-tight">
                        {{ $totalPaidTransactions }} <span class="text-xs font-normal text-gray-400">trx</span>
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Terbayar 100%</span>
                    <a href="{{ route('admin.receivables.summary.paid-transactions') }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 transition group shrink-0 ml-1">
                        <span>Lihat Transaksi</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- 7. TOTAL TRANSAKSI BARU BULAN INI (Bulan berjalan) --}}
            <div class="sw-card p-4.5 hover:border-purple-400 dark:hover:border-purple-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Total Transaksi Baru Bulan Ini</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">{{ now()->translatedFormat('F Y') }}</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-bold font-mono text-purple-600 dark:text-purple-400 mt-2 whitespace-normal break-words leading-tight">
                        Rp {{ number_format($newTransactionsThisMonth, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Total nilai transaksi baru yang dibuat bulan ini</span>
                    <a href="{{ route('admin.receivables.summary.new-transactions') }}" class="inline-flex items-center gap-1 text-xs font-bold text-purple-600 dark:text-purple-400 hover:text-purple-700 transition group shrink-0 ml-1">
                        <span>Lihat Transaksi</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- 8. UANG MASUK TRANSAKSI BARU BULAN INI (Bulan berjalan) --}}
            <div class="sw-card p-4.5 hover:border-teal-400 dark:hover:border-teal-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Uang Masuk Transaksi Baru Bulan Ini</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300">{{ now()->translatedFormat('F Y') }}</span>
                    </div>
                    <p class="text-lg sm:text-xl xl:text-2xl font-bold font-mono text-teal-600 dark:text-teal-400 mt-2 whitespace-normal break-words leading-tight">
                        Rp {{ number_format($newTransactionsCashInThisMonth, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Pembayaran yang diterima untuk transaksi baru bulan ini</span>
                    <a href="{{ route('admin.receivables.summary.new-transaction-payments') }}" class="inline-flex items-center gap-1 text-xs font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 transition group shrink-0 ml-1">
                        <span>Lihat Pembayaran</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Actions & Filter Section --}}
        <div class="sw-card p-4 sm:p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Filter & Pengelolaan</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Saring data piutang dan buat transaksi toko baru</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="clearStore(); rawAmount = ''; formattedAmount = ''; rawPaidAmount = ''; formattedPaidAmount = ''; showNewTrxModal = true;"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#90F022] text-gray-900 font-bold rounded-xl text-xs hover:bg-[#7ed81e] shadow-xs transition active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Transaksi</span>
                    </button>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.receivables.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                {{-- Filter Sales --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Sales Penanggung Jawab</label>
                    <select name="sales_id" class="sw-input text-xs py-2 w-full" onchange="this.form.submit()">
                        <option value="">Semua Sales</option>
                        @foreach($sales as $sa)
                        <option value="{{ $sa->id }}" {{ (string) request('sales_id') === (string) $sa->id ? 'selected' : '' }}>{{ $sa->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Periode --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Periode</label>
                    <select name="period" class="sw-input text-xs py-2 w-full" onchange="this.form.submit()">
                        <option value="all" {{ request('period', 'all') == 'all' ? 'selected' : '' }}>Semua Periode</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="7days" {{ request('period') == '7days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="30days" {{ request('period') == '30days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="this_year" {{ request('period') == 'this_year' ? 'selected' : '' }}>Tahun Ini</option>
                    </select>
                </div>

                {{-- Filter Status --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Status Piutang</label>
                    <select name="receivable_status" class="sw-input text-xs py-2 w-full" onchange="this.form.submit()">
                        <option value="all" {{ request('receivable_status') == 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="with" {{ request('receivable_status') == 'with' ? 'selected' : '' }}>Berpiutang (Sisa &gt; Rp0)</option>
                        <option value="without" {{ request('receivable_status') == 'without' ? 'selected' : '' }}>Tidak Ada Piutang</option>
                    </select>
                </div>

                {{-- Filter Toko Baru --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Toko Baru</label>
                    <select name="is_new_store" class="sw-input text-xs py-2 w-full" onchange="this.form.submit()">
                        <option value="" {{ !request('is_new_store') ? 'selected' : '' }}>Semua Toko</option>
                        <option value="yes" {{ request('is_new_store') === 'yes' ? 'selected' : '' }}>Toko Baru (30 Hari Terakhir)</option>
                    </select>
                </div>

                {{-- Search Cari Toko --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Cari Toko</label>
                    <div class="relative flex items-center gap-1.5">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari Toko..."
                                class="sw-input text-xs py-2 !pl-9 pr-3 w-full block"
                                style="padding-left: 2.25rem;">
                        </div>
                        <button type="submit" class="px-3.5 py-2 bg-gray-800 dark:bg-gray-700 text-white rounded-xl text-xs font-bold hover:bg-gray-900 transition shrink-0">
                            Cari
                        </button>
                    </div>
                </div>

                @if(request()->hasAny(['search', 'sales_id', 'period', 'receivable_status', 'is_new_store', 'city', 'store_id']))
                <div class="lg:col-span-5 flex justify-end pt-1">
                    <a href="{{ route('admin.receivables.index') }}" class="text-xs text-gray-500 hover:text-red-600 dark:hover:text-red-400 font-semibold inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset Semua Filter</span>
                    </a>
                </div>
                @endif
            </form>
        </div>

        {{-- Master Table: Daftar Piutang Toko --}}
        <div class="sw-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Daftar Piutang Toko</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar toko dan saldo piutang berdasarkan transaksi yang tercatat</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $stores->count() }} Toko
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 font-semibold text-left min-w-[160px]">Nama Toko</th>
                            <th class="px-5 py-3.5 font-semibold text-left min-w-[140px]">Sales Penanggung Jawab</th>
                            <th class="px-5 py-3.5 font-semibold text-center min-w-[120px]">Transaksi Terbuka</th>
                            <th class="px-5 py-3.5 font-semibold text-right min-w-[130px]">Total Piutang</th>
                            <th class="px-5 py-3.5 font-semibold text-center min-w-[120px]">Status</th>
                            <th class="px-5 py-3.5 font-semibold text-right min-w-[80px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($stores as $st)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            {{-- Nama Toko & Kode --}}
                            <td class="px-5 py-4 text-left">
                                <a href="{{ route('admin.receivables.show', $st->id) }}" class="font-bold text-gray-900 dark:text-gray-100 hover:text-[#0DA4CE] transition">
                                    {{ $st->name }}
                                </a>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[11px] font-mono text-gray-400 dark:text-gray-500">{{ $st->code ?? '-' }}</span>
                                    @if($st->city)
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">• {{ $st->city }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Sales Penanggung Jawab --}}
                            <td class="px-5 py-4 text-left text-gray-700 dark:text-gray-300">
                                @if($st->salesPenanggungJawab)
                                    <span class="font-medium text-xs">{{ $st->salesPenanggungJawab->name }}</span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum Ada</span>
                                @endif
                            </td>

                            {{-- Transaksi Terbuka --}}
                            <td class="px-5 py-4 text-center">
                                @if($st->open_transactions_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        {{ $st->open_transactions_count }} transaksi
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">0 transaksi</span>
                                @endif
                            </td>

                            {{-- Total Piutang (Right Aligned) --}}
                            <td class="px-5 py-4 text-right font-bold font-mono {{ $st->receivable_balance > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                Rp {{ number_format($st->receivable_balance, 0, ',', '.') }}
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-5 py-4 text-center">
                                @if($st->receivable_balance > 0.005)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300">
                                        Berpiutang
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-950/60 dark:text-green-300">
                                        Tidak Ada Piutang
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.receivables.show', $st->id) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded-lg text-xs font-bold transition">
                                    <span>Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                                Tidak ada data toko yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            @if($stores->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $stores->links() }}
            </div>
            @endif
        </div>

        {{-- Visual Analytics Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Pembayaran Piutang (Ringkasan Pembayaran Piutang) --}}
            <div class="sw-card p-5 space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm">Pembayaran Piutang</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total pembayaran piutang lama berdasarkan tanggal</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-gray-400 uppercase font-semibold block">Total Periode</span>
                            <span class="text-xs font-mono font-bold text-green-600 dark:text-green-400">
                                Rp {{ number_format($totalPeriodPayments ?? $totalPayments, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    @if($chartPayments->isNotEmpty())
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($chartPayments as $cp)
                        <div class="py-3 flex items-center justify-between text-xs hover:bg-gray-50/50 dark:hover:bg-gray-800/30 px-1 rounded-lg transition">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $cp['date'] }}</span>
                                <span class="text-[11px] text-gray-400">({{ $cp['count'] }} pembayaran)</span>
                            </div>
                            <span class="font-bold font-mono text-green-600 dark:text-green-400">
                                Rp {{ number_format($cp['total'], 0, ',', '.') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="py-12 text-center text-xs text-gray-400 dark:text-gray-500">
                        Belum ada pembayaran piutang untuk periode ini.
                    </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <span class="text-[11px] text-gray-400">Menampilkan 5 tanggal pembayaran terbaru</span>
                    <a href="{{ route('admin.receivables.summary.trends', request()->only(['sales_id', 'period', 'from_date', 'to_date'])) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0DA4CE] hover:text-[#097A99] dark:hover:text-[#5BD8F7] transition group">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- Piutang Berdasarkan Sales --}}
            <div id="sales-receivables-card" class="sw-card p-5 space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm">Piutang Berdasarkan Sales</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Saldo piutang saat ini berdasarkan Sales</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-gray-400 uppercase font-semibold block">Total Piutang</span>
                            <span class="text-xs font-mono font-bold text-red-600 dark:text-red-400">
                                Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    @if(isset($paginatedSalesReceivables) && $paginatedSalesReceivables->isNotEmpty())
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($paginatedSalesReceivables as $idx => $sr)
                        @php
                            $rankNumber = ($paginatedSalesReceivables->currentPage() - 1) * $paginatedSalesReceivables->perPage() + $idx + 1;
                        @endphp
                        <div class="py-3 flex items-center justify-between text-xs hover:bg-gray-50/50 dark:hover:bg-gray-800/30 px-1 rounded-lg transition">
                            <div class="flex items-start gap-2.5 min-w-0 pr-2">
                                <span class="font-mono text-xs font-bold text-gray-400 dark:text-gray-500 w-5 pt-0.5">{{ sprintf('%02d', $rankNumber) }}</span>
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 dark:text-white truncate">{{ $sr['name'] }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ $sr['store_count'] }} toko ({{ $sr['indebted_count'] }} berpiutang)
                                    </p>
                                </div>
                            </div>
                            <span class="font-bold font-mono text-right shrink-0 {{ $sr['total'] > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                Rp {{ number_format($sr['total'], 0, ',', '.') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="py-12 text-center text-xs text-gray-400 dark:text-gray-500">
                        Belum ada Sales dengan piutang aktif.
                    </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    {{-- Compact Pagination for Sales Card --}}
                    <div>
                        @if(isset($paginatedSalesReceivables) && $paginatedSalesReceivables->hasPages())
                        <div class="flex items-center gap-1 text-xs">
                            @if($paginatedSalesReceivables->onFirstPage())
                                <span class="px-2 py-1 rounded text-gray-400 text-[11px] font-medium border border-gray-200 dark:border-gray-700 opacity-50 cursor-not-allowed">‹</span>
                            @else
                                <a href="{{ $paginatedSalesReceivables->previousPageUrl() }}#sales-receivables-card" class="px-2 py-1 rounded text-gray-700 dark:text-gray-200 text-[11px] font-medium border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition">‹</a>
                            @endif

                            <span class="px-2 py-0.5 text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                                {{ $paginatedSalesReceivables->currentPage() }} / {{ $paginatedSalesReceivables->lastPage() }}
                            </span>

                            @if($paginatedSalesReceivables->hasMorePages())
                                <a href="{{ $paginatedSalesReceivables->nextPageUrl() }}#sales-receivables-card" class="px-2 py-1 rounded text-gray-700 dark:text-gray-200 text-[11px] font-medium border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition">›</a>
                            @else
                                <span class="px-2 py-1 rounded text-gray-400 text-[11px] font-medium border border-gray-200 dark:border-gray-700 opacity-50 cursor-not-allowed">›</span>
                            @endif
                        </div>
                        @endif
                    </div>

                    <a href="{{ route('admin.receivables.summary.by-sales') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0DA4CE] hover:text-[#097A99] dark:hover:text-[#5BD8F7] transition group">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Insights: Top Toko, Transaksi Terbaru & Pembayaran Terbaru --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Top Toko dengan Piutang Terbesar --}}
            <div class="sw-card p-5 space-y-3 flex flex-col justify-between">
                <div>
                    <div class="pb-2.5 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm">Top Toko Piutang Terbesar</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Saldo piutang tertinggi saat ini</p>
                    </div>
                    @if($topStores->isNotEmpty())
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($topStores as $idx => $tStore)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <a href="{{ route('admin.receivables.show', $tStore->id) }}" class="font-bold text-gray-900 dark:text-white hover:text-[#0DA4CE] truncate block">
                                    {{ sprintf('%02d', $idx + 1) }}. {{ $tStore->name }}
                                </a>
                                <p class="text-[11px] text-gray-500">Sales Penanggung Jawab: {{ $tStore->salesPenanggungJawab?->name ?? 'Belum Ada' }}</p>
                            </div>
                            <span class="font-bold font-mono text-red-600 dark:text-red-400 shrink-0">
                                Rp {{ number_format($tStore->receivable_balance, 0, ',', '.') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-gray-400 py-6 text-center">Tidak Ada Piutang</p>
                    @endif
                </div>
                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-right">
                    <a href="{{ route('admin.receivables.summary.indebted-stores') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0DA4CE] hover:text-[#097A99] dark:hover:text-[#5BD8F7] transition group">
                        <span>Lihat Toko</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- Transaksi Baru Terbaru --}}
            <div class="sw-card p-5 space-y-3 flex flex-col justify-between">
                <div>
                    <div class="pb-2.5 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm">Transaksi Baru Terbaru</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Transaksi/faktur baru yang terakhir dibuat</p>
                    </div>
                    @if($recentTransactions->isNotEmpty())
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recentTransactions as $rTrx)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <a href="{{ route('admin.receivables.transactions.show', $rTrx->id) }}" class="font-bold font-mono text-[#0DA4CE] hover:underline block truncate">
                                    {{ $rTrx->transaction_code }}
                                </a>
                                <p class="text-[11px] text-gray-800 dark:text-gray-200 font-medium truncate">{{ $rTrx->store?->name }}</p>
                                <p class="text-[10px] text-gray-400 truncate">
                                    Sales Penanggung Jawab: {{ $rTrx->store?->salesPenanggungJawab?->name ?? 'Belum Ada' }}
                                </p>
                                <p class="text-[10px] text-gray-400">
                                    {{ $rTrx->transaction_date ? $rTrx->transaction_date->format('d M Y') : '-' }}
                                    @if($rTrx->creator) • Dicatat: {{ $rTrx->creator->name }} @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold font-mono text-gray-900 dark:text-white">Rp {{ number_format($rTrx->transaction_amount, 0, ',', '.') }}</p>
                                <span class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded {{ $rTrx->status === 'LUNAS' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : ($rTrx->status === 'SEBAGIAN' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300') }}">
                                    {{ $rTrx->status }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-gray-400 py-6 text-center">Belum Ada Transaksi</p>
                    @endif
                </div>
                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-right">
                    <a href="{{ route('admin.receivables.summary.open-transactions') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0DA4CE] hover:text-[#097A99] dark:hover:text-[#5BD8F7] transition group">
                        <span>Lihat Transaksi</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- Pembayaran Terbaru --}}
            <div class="sw-card p-5 space-y-3 flex flex-col justify-between">
                <div>
                    <div class="pb-2.5 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm">Pembayaran Terbaru</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Pembayaran terakhir, dengan jenis pembayaran ditampilkan</p>
                    </div>
                    @if($recentPayments->isNotEmpty())
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recentPayments as $rPay)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <a href="{{ route('admin.receivables.transactions.show', $rPay->store_transaction_id) }}" class="font-bold font-mono text-[#0DA4CE] hover:underline block truncate">
                                    {{ $rPay->transaction?->transaction_code ?? 'TRX' }}
                                </a>
                                <p class="text-[11px] text-gray-800 dark:text-gray-200 font-medium truncate">{{ $rPay->transaction?->store?->name }}</p>
                                <p class="text-[10px] text-gray-400 truncate">
                                    Sales Penanggung Jawab: {{ $rPay->transaction?->store?->salesPenanggungJawab?->name ?? 'Belum Ada' }}
                                </p>
                                <p class="text-[10px] text-gray-400">
                                    {{ $rPay->payment_date ? $rPay->payment_date->format('d M Y') : '-' }} • {{ strtoupper($rPay->payment_method) }}
                                    @if($rPay->recorder) • Dicatat: {{ $rPay->recorder->name }} @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold font-mono text-green-600 dark:text-green-400">Rp {{ number_format($rPay->amount, 0, ',', '.') }}</p>
                                @if($rPay->isNewTransactionPayment())
                                    <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300">
                                        PEMBAYARAN TRANSAKSI BARU
                                    </span>
                                @else
                                    <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300">
                                        PEMBAYARAN PIUTANG LAMA
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-gray-400 py-6 text-center">Belum Ada Pembayaran</p>
                    @endif
                </div>
                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-right">
                    <a href="{{ route('admin.receivables.summary.payments') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0DA4CE] hover:text-[#097A99] dark:hover:text-[#5BD8F7] transition group">
                        <span>Lihat Pembayaran</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Transaksi Baru --}}
        <div x-show="showNewTrxModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700 max-h-[90vh] overflow-y-auto" @click.away="showNewTrxModal = false">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Buat Transaksi / Faktur Baru</h3>
                    <button type="button" @click="showNewTrxModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('admin.receivables.transactions.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="store_id" :value="selectedStoreId" required>

                    {{-- Searchable Dropdown Toko --}}
                    <div class="relative" @click.away="isStoreDropdownOpen = false">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Pilih Toko <span class="text-red-500">*</span></label>
                        
                        <button type="button" @click="isStoreDropdownOpen = !isStoreDropdownOpen; if(isStoreDropdownOpen) { $nextTick(() => { $refs.storeSearchInputNewTrx.focus() }); }"
                            class="w-full flex items-center justify-between border border-gray-300 dark:border-gray-600 rounded-xl px-3.5 py-2.5 bg-white dark:bg-gray-800 text-left transition focus:outline-none focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE]">
                            <div class="min-w-0 flex-1">
                                <template x-if="selectedStoreId">
                                    <div>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white truncate" x-text="selectedStoreName"></p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate" x-text="selectedStoreCode + ' • ' + selectedStoreCity"></p>
                                    </div>
                                </template>
                                <template x-if="!selectedStoreId">
                                    <span class="text-xs sm:text-sm text-gray-400 dark:text-gray-500">Pilih toko...</span>
                                </template>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform duration-200" :class="isStoreDropdownOpen ? 'rotate-180 text-[#0DA4CE]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="isStoreDropdownOpen" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 z-50 overflow-hidden flex flex-col max-h-64">
                            <div class="p-2.5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/75 dark:bg-gray-800/80">
                                <div class="flex items-center border border-gray-200 dark:border-gray-600 rounded-xl px-2.5 py-1.5 bg-white dark:bg-gray-900">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    <input type="text" x-ref="storeSearchInputNewTrx" x-model="storeSearch"
                                        class="w-full py-1 text-xs bg-transparent border-0 outline-none text-gray-900 dark:text-white placeholder-gray-400"
                                        placeholder="Cari nama atau kode toko...">
                                    <button type="button" x-show="storeSearch" @click="storeSearch = ''" class="text-gray-400 hover:text-gray-600 p-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700/50 scrollbar-thin">
                                <template x-for="st in filteredStores" :key="st.id">
                                    <div @click="selectStore(st)"
                                        class="px-4 py-3 hover:bg-[#0DA4CE]/5 dark:hover:bg-gray-700/50 cursor-pointer transition flex items-center justify-between"
                                        :class="selectedStoreId === st.id ? 'bg-[#0DA4CE]/10 dark:bg-cyan-950/40' : ''">
                                        <div class="min-w-0 pr-2">
                                            <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white truncate" x-text="st.name"></p>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-mono mt-0.5" x-text="st.code + (st.city && st.city !== '-' ? ' • ' + st.city : '')"></p>
                                        </div>
                                        <svg x-show="selectedStoreId === st.id" class="w-4 h-4 text-[#0DA4CE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </template>
                                <template x-if="filteredStores.length === 0">
                                    <div class="px-4 py-6 text-center text-xs text-gray-400 dark:text-gray-500">Toko tidak ditemukan</div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Transaksi <span class="text-red-500">*</span></label>
                            <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="sw-input w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Kode Transaksi</label>
                            <input type="text" disabled value="Dibuat Otomatis Sistem" class="sw-input w-full bg-gray-100 dark:bg-gray-900 text-gray-500 font-mono text-xs cursor-not-allowed">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nominal Transaksi (Rp) <span class="text-red-500">*</span></label>
                        <input type="hidden" name="transaction_amount" :value="rawAmount">
                        <input type="text" inputmode="numeric" autocomplete="off" :value="formattedAmount" @input="onAmountInput($event)" required class="sw-input w-full font-mono text-sm" placeholder="0">
                    </div>

                    <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl space-y-3 border border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Pembayaran Awal / DP (Rp)</label>
                                <input type="hidden" name="paid_amount" :value="rawPaidAmount">
                                <input type="text" inputmode="numeric" autocomplete="off" :value="formattedPaidAmount" @input="onPaidAmountInput($event)" class="sw-input w-full font-mono text-xs" placeholder="0 (Opsional)">
                            </div>
                            <div x-show="Number(rawPaidAmount) > 0">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                                <select name="payment_method" class="sw-input w-full text-xs">
                                    <option value="tunai">Tunai</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="font-semibold text-gray-600 dark:text-gray-400">Sisa Piutang:</span>
                            <span class="font-bold font-mono" :class="newTrxRemaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                x-text="'Rp ' + Number(newTrxRemaining).toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Keterangan / Deskripsi</label>
                        <textarea name="description" rows="2" class="sw-input w-full text-xs" placeholder="Contoh: Faktur penjualan barang / PO"></textarea>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                        <button type="button" @click="showNewTrxModal = false" class="w-full sm:w-auto px-4 py-2 bg-gray-200 dark:bg-gray-700 text-xs font-bold rounded-xl text-gray-700 dark:text-gray-200">Batal</button>
                        <button type="submit" :disabled="!selectedStoreId || !rawAmount || Number(rawAmount) <= 0" class="w-full sm:w-auto px-4 py-2 bg-[#90F022] text-gray-900 text-xs font-bold rounded-xl disabled:opacity-50 hover:bg-[#7ed81e]">Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
