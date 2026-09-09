<x-app-layout>
    @section('breadcrumbs')
        <a href="{{ route('admin.receivables.index') }}" class="hover:text-[#0DA4CE]">Master Piutang Toko</a>
        <span class="text-gray-300 dark:text-gray-600">/</span>
        <span>Riwayat Pembayaran Piutang</span>
    @endsection

    <x-slot name="header">
        <x-page-header icon="history" title="Riwayat Pembayaran Piutang" subtitle="Daftar pembayaran piutang lama berdasarkan tanggal"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between">
            <x-back-button href="{{ route('admin.receivables.index') }}" label="Kembali ke Master Piutang Toko" />
        </div>

        {{-- Metric Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sw-card p-5 bg-gradient-to-br from-green-50/80 to-white dark:from-green-950/25 dark:to-gray-800 border-green-100 dark:border-green-900/40">
                <span class="text-[11px] font-bold uppercase tracking-wider text-green-700 dark:text-green-400">Total Pembayaran Periode Ini</span>
                <p class="text-2xl sm:text-3xl font-extrabold font-mono text-green-600 dark:text-green-400 mt-2">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Akumulasi pembayaran piutang lama</p>
            </div>
            <div class="sw-card p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Transaksi Pembayaran</span>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                    {{ $totalPaymentsCount ?? $payments->total() }} <span class="text-sm font-normal text-gray-400">transaksi</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pembayaran yang tercatat pada filter aktif</p>
            </div>
        </div>

        {{-- Filter & Search Box --}}
        <div class="sw-card p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.receivables.summary.trends') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
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
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Periode Waktu</label>
                    <select name="period" class="sw-input text-xs py-2 w-full" onchange="this.form.submit()">
                        <option value="all" {{ request('period', 'all') == 'all' ? 'selected' : '' }}>Semua Periode</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="7days" {{ request('period') == '7days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="30days" {{ request('period') == '30days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="this_year" {{ request('period') == 'this_year' ? 'selected' : '' }}>Tahun Ini</option>
                    </select>
                </div>
                @if(request()->hasAny(['sales_id', 'period', 'from_date', 'to_date']))
                <div class="flex items-center pb-2">
                    <a href="{{ route('admin.receivables.summary.trends') }}" class="text-xs text-red-600 hover:underline font-semibold">Reset Filter</a>
                </div>
                @endif
            </form>
        </div>

        {{-- Tabel Ringkasan Pembayaran Piutang Berdasarkan Tanggal (10 Tanggal per Halaman) --}}
        <div class="sw-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Ringkasan Pembayaran per Tanggal</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar total pembayaran piutang lama per tanggal</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $dateSummaries->total() }} Tanggal
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 font-semibold text-left">Tanggal</th>
                            <th class="px-5 py-3.5 font-semibold text-center">Jumlah Pembayaran</th>
                            <th class="px-5 py-3.5 font-semibold text-right">Total Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($dateSummaries as $ds)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-3.5 text-left font-semibold text-gray-800 dark:text-gray-200">
                                {{ $ds['date'] }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-950/60 dark:text-green-300">
                                    {{ $ds['count'] }} pembayaran
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-green-600 dark:text-green-400">
                                Rp {{ number_format($ds['total'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                                Belum ada pembayaran piutang untuk periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($dateSummaries->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $dateSummaries->links() }}
            </div>
            @endif
        </div>

        {{-- Table Rincian Riwayat Pembayaran --}}
        <div class="sw-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Rincian Pembayaran Masuk</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar transaksi pembayaran piutang</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $payments->total() }} Data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 font-semibold text-left">Tanggal Pembayaran</th>
                            <th class="px-5 py-3 font-semibold text-left">Kode Faktur</th>
                            <th class="px-5 py-3 font-semibold text-left">Toko</th>
                            <th class="px-5 py-3 font-semibold text-left">Sales Penanggung Jawab</th>
                            <th class="px-5 py-3 font-semibold text-center">Metode</th>
                            <th class="px-5 py-3 font-semibold text-right">Nominal</th>
                            <th class="px-5 py-3 font-semibold text-left">Dicatat Oleh</th>
                            <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($payments as $pay)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-3.5 text-left text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ $pay->payment_date ? $pay->payment_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-left font-mono">
                                <a href="{{ route('admin.receivables.transactions.show', $pay->store_transaction_id) }}" class="font-bold text-[#0DA4CE] hover:underline">
                                    {{ $pay->transaction?->transaction_code ?? '-' }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-left">
                                <a href="{{ route('admin.receivables.show', $pay->transaction?->store_id) }}" class="font-bold text-gray-900 dark:text-gray-100 hover:text-[#0DA4CE] transition">
                                    {{ $pay->transaction?->store?->name ?? '-' }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-left text-xs text-gray-700 dark:text-gray-300">
                                {{ $pay->transaction?->store?->salesPenanggungJawab?->name ?? 'Belum Ada Sales' }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ $pay->payment_method }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-green-600 dark:text-green-400">
                                Rp {{ number_format($pay->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-left text-xs text-gray-500 dark:text-gray-400">
                                {{ $pay->recorder?->name ?? 'Sistem' }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.receivables.transactions.show', $pay->store_transaction_id) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded text-xs font-bold transition">
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                                Belum ada riwayat pembayaran untuk tren ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $payments->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
