<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Transaksi Kunjungan Sales') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Menampilkan transaksi yang terkait dengan aktivitas kunjungan Sales &middot; {{ $periodLabel }}</p>
            </div>
            @php
                $reportFileName = 'Laporan Transaksi Kunjungan Sales';
                if ($fromDate && $toDate) {
                    $reportFileName .= ' ' . \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($toDate)->format('d M Y');
                }
            @endphp
            @include('reports.partials.export-buttons', [
                'exportType' => 'transactions',
                'exportFilename' => $reportFileName,
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="mb-6">@include('reports.partials.back')</div>

        {{-- Filter Card --}}
        <div id="sw-filter-card" class="sw-report-filter sw-report-filter-transactions bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.transactions') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="from_date" value="{{ request('from_date', $fromDate ?? '') }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="to_date" value="{{ request('to_date', $toDate ?? '') }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Petugas Sales</label>
                        <select name="user_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Sales</option>
                            @foreach($salesUsers as $u)
                                <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Toko Pelanggan</label>
                        <select name="store_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Toko</option>
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ (string) request('store_id') === (string) $st->id ? 'selected' : '' }}>{{ $st->name }} ({{ $st->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end pt-1 border-t border-gray-100 dark:border-gray-800">
                    <div class="lg:col-span-6">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Pencarian</label>
                        <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Cari nama toko / kode toko / sales / kode transaksi..."
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status Transaksi</label>
                        <select name="transaction_status" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Status Transaksi</option>
                            <option value="has_transaction" {{ request('transaction_status') === 'has_transaction' ? 'selected' : '' }}>Ada Transaksi Baru</option>
                            <option value="has_old_debt_payment" {{ request('transaction_status') === 'has_old_debt_payment' ? 'selected' : '' }}>Ada Pembayaran Piutang Lama</option>
                            <option value="paid" {{ request('transaction_status') === 'paid' ? 'selected' : '' }}>Lunas / Transaksi Baru</option>
                            <option value="piutang" {{ request('transaction_status') === 'piutang' ? 'selected' : '' }}>Bayar Piutang Saja</option>
                            <option value="mixed" {{ request('transaction_status') === 'mixed' ? 'selected' : '' }}>Transaksi + Piutang (Mixed)</option>
                            <option value="none" {{ request('transaction_status') === 'none' ? 'selected' : '' }}>Tidak Ada Transaksi</option>
                        </select>
                    </div>

                    <div class="lg:col-span-3 flex flex-col sm:flex-row sm:items-end justify-end gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                            Filter
                        </button>
                        @if(request()->anyFilled(['from_date', 'to_date', 'user_id', 'store_id', 'search', 'transaction_status', 'period']))
                        <a href="{{ route('admin.reports.transactions') }}"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Reset
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- 5 Cards Statistik --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Jumlah Transaksi</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total_transactions'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Nilai Transaksi Baru</p>
                <p class="text-xl font-bold text-[#0DA4CE]">Rp {{ number_format($stats['total_amount'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Pembayaran Transaksi Baru</p>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($stats['total_paid_new'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Pembayaran Piutang Lama</p>
                <p class="text-xl font-bold text-teal-600 dark:text-teal-400">Rp {{ number_format($stats['total_paid_old'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Piutang Baru dari Transaksi</p>
                <p class="text-xl font-bold text-rose-600 dark:text-rose-400">Rp {{ number_format($stats['total_outstanding'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabel Data --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden" x-data="{ expandedRow: null }">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-10">No</th>
                            <th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal & Waktu</th>
                            <th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sales Penanggung Jawab</th>
                            <th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama Toko</th>
                            <th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kode Transaksi</th>
                            <th class="px-3 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nilai Transaksi Baru (Rp)</th>
                            <th class="px-3 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Pembayaran Transaksi Baru (Rp)</th>
                            <th class="px-3 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Piutang Baru dari Transaksi (Rp)</th>
                            <th class="px-3 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Saldo Piutang Sebelum Kunjungan (Rp)</th>
                            <th class="px-3 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Pembayaran Piutang Lama (Rp)</th>
                            <th class="px-3 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Saldo Piutang Setelah Kunjungan (Rp)</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status Transaksi</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($transactions as $index => $v)
                        @php
                            $rowTrxCodes = $v->transactions->pluck('transaction_code')->filter()->implode(', ');
                            if (!$rowTrxCodes && $v->computed_tx_count > 0) {
                                $rowTrxCodes = 'TRX-VISIT-' . substr($v->id, 0, 8);
                            }
                            $hasPhotos = $v->photos->isNotEmpty() || $v->storefront_photo || $v->check_in_selfie || $v->check_out_selfie || $v->final_store_photo;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-3 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ $transactions->firstItem() + $index }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $v->check_in_at?->format('d M Y') ?? '-' }}</div>
                                <div class="text-gray-400 dark:text-gray-500 font-mono">{{ $v->check_in_at?->format('H:i') ?? '-' }} - {{ $v->check_out_at?->format('H:i') ?? 'Selesai' }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $v->user->name ?? '-' }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $v->store->name ?? '-' }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $v->store->code ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs font-mono text-gray-700 dark:text-gray-300">
                                {{ $rowTrxCodes ?: '-' }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap text-sm font-semibold text-[#0DA4CE]">
                                Rp {{ number_format($v->computed_nilai_tx, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($v->computed_bayar_baru, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap text-sm font-bold text-rose-600 dark:text-rose-400">
                                Rp {{ number_format($v->computed_sisa, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap text-sm font-semibold text-amber-600 dark:text-amber-400">
                                Rp {{ number_format($v->computed_saldo_sebelum, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap text-sm font-semibold text-teal-600 dark:text-teal-400">
                                Rp {{ number_format($v->computed_bayar_lama, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                Rp {{ number_format($v->computed_saldo_setelah, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                @php
                                    $stBadge = match($v->transaction_status) {
                                        'paid' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/40', 'text' => 'text-emerald-700 dark:text-emerald-300', 'label' => 'Transaksi Baru'],
                                        'piutang' => ['bg' => 'bg-amber-100 dark:bg-amber-900/40', 'text' => 'text-amber-700 dark:text-amber-300', 'label' => 'Bayar Piutang'],
                                        'mixed' => ['bg' => 'bg-purple-100 dark:bg-purple-900/40', 'text' => 'text-purple-700 dark:text-purple-300', 'label' => 'Transaksi + Piutang'],
                                        default => ['bg' => 'bg-gray-100 dark:bg-gray-800', 'text' => 'text-gray-600 dark:text-gray-400', 'label' => 'Tanpa Transaksi']
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $stBadge['bg'] }} {{ $stBadge['text'] }}">
                                    {{ $stBadge['label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <button @click="expandedRow = expandedRow === '{{ $v->id }}' ? null : '{{ $v->id }}'"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 hover:bg-[#0DA4CE] hover:text-white dark:hover:bg-[#0DA4CE] text-gray-700 dark:text-gray-300 transition">
                                    <span x-text="expandedRow === '{{ $v->id }}' ? 'Tutup' : 'Rincian'"></span>
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{'rotate-180': expandedRow === '{{ $v->id }}'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </td>
                        </tr>

                        {{-- Expanded Detail Row --}}
                        <tr x-show="expandedRow === '{{ $v->id }}'" x-cloak class="bg-gray-50/80 dark:bg-gray-800/60">
                            <td colspan="13" class="px-6 py-5 border-t border-b border-gray-100 dark:border-gray-800">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                                    {{-- Section 1: Detail Transaksi Baru --}}
                                    <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-800">
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Transaksi Baru
                                        </h4>
                                        @if($v->transactions->isNotEmpty())
                                            @foreach($v->transactions as $trx)
                                                @php
                                                    $initPaid = (float) $trx->payments->filter(fn($p) => str_contains(strtolower($p->notes ?? ''), 'pembayaran awal') || $p->source === 'initial_payment')->sum('amount');
                                                    $rem = max(0.0, round((float) $trx->transaction_amount - $initPaid, 2));
                                                @endphp
                                                <div class="space-y-1 py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-800' : '' }}">
                                                    <div class="flex justify-between font-mono font-bold text-gray-800 dark:text-gray-200">
                                                        <span>{{ $trx->transaction_code }}</span>
                                                        <span class="text-xs px-2 py-0.5 rounded {{ $rem <= 0.005 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $trx->status }}</span>
                                                    </div>
                                                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                        <span>Nilai Transaksi Baru:</span>
                                                        <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($trx->transaction_amount, 0, ',', '.') }}</span>
                                                    </div>
                                                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                        <span>Pembayaran Transaksi Baru:</span>
                                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($initPaid, 0, ',', '.') }}</span>
                                                    </div>
                                                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                        <span>Piutang Baru dari Transaksi:</span>
                                                        <span class="font-bold text-rose-600 dark:text-rose-400">Rp {{ number_format($rem, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @elseif($v->computed_tx_count > 0)
                                            <div class="space-y-1">
                                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                    <span>Nilai Transaksi Baru:</span>
                                                    <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($v->computed_nilai_tx, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                    <span>Pembayaran Transaksi Baru:</span>
                                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($v->computed_bayar_baru, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                    <span>Piutang Baru dari Transaksi:</span>
                                                    <span class="font-bold text-rose-600 dark:text-rose-400">Rp {{ number_format($v->computed_sisa, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-gray-400 italic">Tidak ada transaksi baru pada kunjungan ini.</p>
                                        @endif
                                    </div>

                                    {{-- Section 2: Pembayaran Piutang Lama --}}
                                    <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-800">
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            Pembayaran Piutang Lama
                                        </h4>
                                        @php
                                            $oldPayments = $v->payments->filter(fn($p) => !str_contains(strtolower($p->notes ?? ''), 'pembayaran awal') && $p->source !== 'initial_payment');
                                        @endphp
                                        @if($oldPayments->isNotEmpty())
                                            <div class="space-y-2">
                                                @foreach($oldPayments as $op)
                                                    <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                                                        <div>
                                                            <div class="font-mono font-semibold text-gray-900 dark:text-white">{{ $op->transaction->transaction_code ?? 'Piutang Toko' }}</div>
                                                            <div class="text-[11px] text-gray-400">{{ strtoupper($op->payment_method ?? 'tunai') }}</div>
                                                        </div>
                                                        <div class="font-bold text-teal-600 dark:text-teal-400">
                                                            Rp {{ number_format($op->amount, 0, ',', '.') }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                <div class="flex justify-between font-bold pt-1 text-gray-900 dark:text-white">
                                                    <span>Total Pembayaran Piutang Lama:</span>
                                                    <span class="text-teal-600 dark:text-teal-400">Rp {{ number_format($v->computed_bayar_lama, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @elseif($v->computed_bayar_lama > 0)
                                            <div class="flex justify-between font-bold py-1 text-gray-900 dark:text-white">
                                                <span>Total Pembayaran Piutang Lama:</span>
                                                <span class="text-teal-600 dark:text-teal-400">Rp {{ number_format($v->computed_bayar_lama, 0, ',', '.') }}</span>
                                            </div>
                                        @else
                                            <p class="text-gray-400 italic">Tidak ada pembayaran piutang lama pada kunjungan ini.</p>
                                        @endif
                                    </div>

                                    {{-- Section 3: Ringkasan Piutang Toko --}}
                                    <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-800">
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            Ringkasan Piutang Toko
                                        </h4>
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                <span>Saldo Sebelum Kunjungan:</span>
                                                <span class="font-semibold text-amber-600 dark:text-amber-400">Rp {{ number_format($v->computed_saldo_sebelum, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                <span>Pembayaran Piutang Lama:</span>
                                                <span class="font-semibold text-teal-600 dark:text-teal-400">-Rp {{ number_format($v->computed_bayar_lama, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                                <span>Piutang Baru dari Transaksi:</span>
                                                <span class="font-semibold text-rose-600 dark:text-rose-400">+Rp {{ number_format($v->computed_sisa, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between font-bold pt-2 border-t border-gray-100 dark:border-gray-800 text-gray-900 dark:text-white">
                                                <span>Saldo Setelah Kunjungan:</span>
                                                <span class="text-indigo-600 dark:text-indigo-400">Rp {{ number_format($v->computed_saldo_setelah, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Section 4: Info Kunjungan & Lokasi --}}
                                    <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-800">
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            Aktivitas & Catatan
                                        </h4>
                                        <div class="space-y-2">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Hasil Kunjungan:</span>
                                                <p class="font-semibold text-gray-900 dark:text-white whitespace-pre-line">{{ $v->visit_result ?: '-' }}</p>
                                            </div>
                                            @if($v->final_notes || $v->initial_notes)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Catatan Kunjungan:</span>
                                                <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $v->final_notes ?: $v->initial_notes }}</p>
                                            </div>
                                            @endif
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Lokasi Check In:</span>
                                                <p class="text-gray-800 dark:text-gray-200">{{ $v->check_in_address ?: '-' }}</p>
                                                @if($v->check_in_lat && $v->check_in_lng)
                                                    <a href="https://www.google.com/maps?q={{ $v->check_in_lat }},{{ $v->check_in_lng }}" target="_blank" class="inline-flex items-center gap-1 text-[#0DA4CE] hover:underline font-mono text-[11px] mt-0.5">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                        {{ round($v->check_in_lat, 6) }}, {{ round($v->check_in_lng, 6) }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Dokumentasi Foto --}}
                                @if($hasPhotos)
                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <h5 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">Dokumentasi Foto Kunjungan</h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                                        @if($v->check_in_selfie)
                                            <div class="space-y-1 text-center">
                                                <img src="{{ asset('storage/' . $v->check_in_selfie) }}" alt="Selfie Check In" class="w-full h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span class="text-[10px] text-gray-500">Selfie Check In</span>
                                            </div>
                                        @endif
                                        @if($v->storefront_photo)
                                            <div class="space-y-1 text-center">
                                                <img src="{{ asset('storage/' . $v->storefront_photo) }}" alt="Foto Toko" class="w-full h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span class="text-[10px] text-gray-500">Foto Toko</span>
                                            </div>
                                        @endif
                                        @if($v->check_out_selfie)
                                            <div class="space-y-1 text-center">
                                                <img src="{{ asset('storage/' . $v->check_out_selfie) }}" alt="Selfie Check Out" class="w-full h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span class="text-[10px] text-gray-500">Selfie Check Out</span>
                                            </div>
                                        @endif
                                        @foreach($v->photos as $ph)
                                            <div class="space-y-1 text-center">
                                                <img src="{{ asset('storage/' . $ph->photo_path) }}" alt="Dokumentasi" class="w-full h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span class="text-[10px] text-gray-500">{{ ucfirst(str_replace('_', ' ', $ph->type)) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="13" class="px-6 py-14 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data transaksi kunjungan yang sesuai dengan filter.</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $transactions->links('vendor.pagination.reports') }}
    </div>
</x-app-layout>
