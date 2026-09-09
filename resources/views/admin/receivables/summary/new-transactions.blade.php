<x-app-layout>
    @section('breadcrumbs')
        <a href="{{ route('admin.receivables.index') }}" class="hover:text-[#0DA4CE]">Master Piutang Toko</a>
        <span class="text-gray-300 dark:text-gray-600">/</span>
        <span>Transaksi Baru Bulan Ini</span>
    @endsection

    <x-slot name="header">
        <x-page-header icon="clipboard-list" title="Transaksi Baru Bulan Ini" subtitle="Daftar seluruh transaksi dan faktur penjualan baru pada bulan {{ now()->translatedFormat('F Y') }}"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between">
            <x-back-button href="{{ route('admin.receivables.index') }}" label="Kembali ke Master Piutang Toko" />
        </div>

        {{-- Metric Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sw-card p-5 bg-gradient-to-br from-purple-50/80 to-white dark:from-purple-950/25 dark:to-gray-800 border-purple-100 dark:border-purple-900/40">
                <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400">Total Nilai Transaksi Baru</span>
                <p class="text-2xl sm:text-3xl font-extrabold font-mono text-purple-600 dark:text-purple-400 mt-2">
                    Rp {{ number_format($totalTransactionAmount, 0, ',', '.') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Periode: {{ now()->translatedFormat('F Y') }}</p>
            </div>
            <div class="sw-card p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Transaksi Dibuat</span>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                    {{ $transactions->total() }} <span class="text-sm font-normal text-gray-400">faktur</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Faktur &amp; order penjualan bulan berjalan</p>
            </div>
        </div>

        {{-- Filter & Search Box --}}
        <div class="sw-card p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.receivables.summary.new-transactions') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Sales Penanggung Jawab</label>
                    <select name="sales_id" class="sw-input text-xs py-2 w-full" onchange="this.form.submit()">
                        <option value="">Semua Sales</option>
                        @foreach($sales as $s)
                        <option value="{{ $s->id }}" {{ (string) request('sales_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Cari Transaksi / Toko</label>
                    <div class="relative flex items-center gap-1.5">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode Faktur / Toko..." class="sw-input text-xs py-2 w-full">
                        <button type="submit" class="px-3 py-2 bg-gray-800 dark:bg-gray-700 text-white rounded-xl text-xs font-bold hover:bg-gray-900 transition shrink-0">
                            Cari
                        </button>
                    </div>
                </div>
                @if(request()->hasAny(['sales_id', 'search']))
                <div class="flex items-center pb-2">
                    <a href="{{ route('admin.receivables.summary.new-transactions') }}" class="text-xs text-red-600 hover:underline font-semibold">Reset Filter</a>
                </div>
                @endif
            </form>
        </div>

        {{-- Table Transaksi Baru --}}
        <div class="sw-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Daftar Transaksi Baru</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Menampilkan faktur yang diterbitkan pada bulan {{ now()->translatedFormat('F Y') }}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $transactions->total() }} Faktur
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 font-semibold text-left">Kode & Tanggal</th>
                            <th class="px-5 py-3 font-semibold text-left">Toko</th>
                            <th class="px-5 py-3 font-semibold text-left">Sales</th>
                            <th class="px-5 py-3 font-semibold text-right">Total Transaksi</th>
                            <th class="px-5 py-3 font-semibold text-right">Pembayaran</th>
                            <th class="px-5 py-3 font-semibold text-right">Sisa Tagihan</th>
                            <th class="px-5 py-3 font-semibold text-center">Status</th>
                            <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($transactions as $trx)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-3.5 text-left font-mono">
                                <a href="{{ route('admin.receivables.transactions.show', $trx->id) }}" class="font-bold text-[#0DA4CE] hover:underline">
                                    {{ $trx->transaction_code }}
                                </a>
                                <p class="text-[11px] text-gray-400 font-sans mt-0.5">{{ $trx->transaction_date ? $trx->transaction_date->format('d M Y') : '-' }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-left">
                                <a href="{{ route('admin.receivables.show', $trx->store_id) }}" class="font-bold text-gray-900 dark:text-gray-100 hover:text-[#0DA4CE] transition">
                                    {{ $trx->store?->name }}
                                </a>
                                <p class="text-[11px] text-gray-400 font-mono">{{ $trx->store?->code ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-left text-xs text-gray-700 dark:text-gray-300">
                                {{ $trx->store?->salesPenanggungJawab?->name ?? 'Belum Ada Sales' }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-gray-900 dark:text-gray-100">
                                Rp {{ number_format($trx->transaction_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-green-600 dark:text-green-400">
                                Rp {{ number_format($trx->total_paid, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold {{ $trx->remaining_amount > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                Rp {{ number_format($trx->remaining_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold
                                    @if($trx->status === 'LUNAS') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($trx->status === 'SEBAGIAN') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                    @else bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 @endif">
                                    {{ $trx->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.receivables.transactions.show', $trx->id) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded text-xs font-bold transition">
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                                Belum ada transaksi baru bulan ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $transactions->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
