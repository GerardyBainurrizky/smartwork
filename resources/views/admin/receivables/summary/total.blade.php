<x-app-layout>
    @section('breadcrumbs')
        <a href="{{ route('admin.receivables.index') }}" class="hover:text-[#0DA4CE]">Master Piutang Toko</a>
        <span class="text-gray-300 dark:text-gray-600">/</span>
        <span>Detail Total Piutang</span>
    @endsection

    <x-slot name="header">
        <x-page-header icon="credit-card" title="Detail Total Piutang" subtitle="Rincian seluruh saldo piutang aktif toko yang berada dalam pengawasan perusahaan"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between">
            <x-back-button href="{{ route('admin.receivables.index') }}" label="Kembali ke Master Piutang Toko" />
        </div>

        {{-- Metric Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sw-card p-5 bg-gradient-to-br from-red-50/80 to-white dark:from-red-950/25 dark:to-gray-800 border-red-100 dark:border-red-900/40">
                <span class="text-[11px] font-bold uppercase tracking-wider text-red-700 dark:text-red-400">Total Piutang Berjalan</span>
                <p class="text-2xl sm:text-3xl font-extrabold font-mono text-red-600 dark:text-red-400 mt-2">
                    Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Akumulasi sisa tagihan toko aktif</p>
            </div>
            <div class="sw-card p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Toko Berpiutang</span>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                    {{ $indebtedStores->count() }} <span class="text-sm font-normal text-gray-400">toko</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Toko dengan saldo aktif &gt; Rp0</p>
            </div>
            <div class="sw-card p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Transaksi Terbuka</span>
                <p class="text-2xl sm:text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2">
                    {{ $totalOpenTransactions }} <span class="text-sm font-normal text-gray-400">faktur</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Belum lunas / sebagian terbayar</p>
            </div>
        </div>

        {{-- Filter & Search Box --}}
        <div class="sw-card p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.receivables.summary.total') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
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
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Cari Toko</label>
                    <div class="relative flex items-center gap-1.5">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / Kode Toko / Kota..." class="sw-input text-xs py-2 w-full">
                        <button type="submit" class="px-3 py-2 bg-gray-800 dark:bg-gray-700 text-white rounded-xl text-xs font-bold hover:bg-gray-900 transition shrink-0">
                            Cari
                        </button>
                    </div>
                </div>
                @if(request()->hasAny(['sales_id', 'search']))
                <div class="flex items-center pb-2">
                    <a href="{{ route('admin.receivables.summary.total') }}" class="text-xs text-red-600 hover:underline font-semibold">Reset Filter</a>
                </div>
                @endif
            </form>
        </div>

        {{-- Table Toko Berpiutang --}}
        <div class="sw-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Daftar Toko & Rincian Saldo Piutang</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Menampilkan toko dengan saldo piutang aktif</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $stores->total() }} Toko
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 font-semibold text-left">Nama Toko & Lokasi</th>
                            <th class="px-5 py-3.5 font-semibold text-left">Sales Penanggung Jawab</th>
                            <th class="px-5 py-3.5 font-semibold text-center">Transaksi Terbuka</th>
                            <th class="px-5 py-3.5 font-semibold text-right">Saldo Piutang</th>
                            <th class="px-5 py-3.5 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($stores as $st)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-4 text-left">
                                <a href="{{ route('admin.receivables.show', $st->id) }}" class="font-bold text-gray-900 dark:text-gray-100 hover:text-[#0DA4CE] transition">
                                    {{ $st->name }}
                                </a>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400 font-mono">
                                    <span>{{ $st->code ?? '-' }}</span>
                                    @if($st->city)
                                    <span class="font-sans">• {{ $st->city }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-left text-xs text-gray-700 dark:text-gray-300">
                                {{ $st->salesPenanggungJawab?->name ?? 'Belum Ada Sales' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                    {{ $st->open_transactions_count }} transaksi
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-bold font-mono text-red-600 dark:text-red-400">
                                Rp {{ number_format($st->receivable_balance, 0, ',', '.') }}
                            </td>
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
                            <td colspan="5" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                                Tidak ada toko dengan saldo piutang terbuka.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($stores->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $stores->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
