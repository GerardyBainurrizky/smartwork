<x-app-layout>
    @section('breadcrumbs')
        <a href="{{ route('admin.receivables.index') }}" class="hover:text-[#0DA4CE]">Master Piutang Toko</a>
        <span class="text-gray-300 dark:text-gray-600">/</span>
        <span>Piutang Berdasarkan Sales</span>
    @endsection

    <x-slot name="header">
        <x-page-header icon="user" title="Piutang Berdasarkan Sales" subtitle="Distribusi portofolio piutang toko berdasarkan Sales Penanggung Jawab"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between">
            <x-back-button href="{{ route('admin.receivables.index') }}" label="Kembali ke Master Piutang Toko" />
        </div>

        {{-- Metric Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sw-card p-5 bg-gradient-to-br from-red-50/80 to-white dark:from-red-950/25 dark:to-gray-800 border-red-100 dark:border-red-900/40">
                <span class="text-[11px] font-bold uppercase tracking-wider text-red-700 dark:text-red-400">Total Akumulasi Piutang Perusahaan</span>
                <p class="text-2xl sm:text-3xl font-extrabold font-mono text-red-600 dark:text-red-400 mt-2">
                    Rp {{ number_format($totalOverallReceivable, 0, ',', '.') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total seluruh piutang di bawah penanganan seluruh Sales</p>
            </div>
            <div class="sw-card p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Personel Sales Terlibat</span>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                    {{ $salesPaginated->total() }} <span class="text-sm font-normal text-gray-400">grup sales</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Termasuk toko tanpa sales jika ada</p>
            </div>
        </div>

        {{-- Sales List Card Cards --}}
        <div class="space-y-4">
            @forelse($salesPaginated as $sGroup)
            <div class="sw-card p-5 sm:p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-[#0DA4CE]"></span>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">{{ $sGroup['name'] }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $sGroup['total_stores'] }} Toko Ditangani • {{ $sGroup['indebted_stores_count'] }} Toko Memiliki Piutang • {{ $sGroup['open_transactions_count'] }} Transaksi Terbuka
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-gray-400 uppercase font-semibold">Total Piutang Sales:</span>
                        <p class="text-xl sm:text-2xl font-extrabold font-mono {{ $sGroup['total_receivable'] > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            Rp {{ number_format($sGroup['total_receivable'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                {{-- Toko-toko di bawah sales ini --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold text-left">Nama Toko</th>
                                <th class="px-4 py-2.5 font-semibold text-left">Kode Toko</th>
                                <th class="px-4 py-2.5 font-semibold text-left">Kota / Wilayah</th>
                                <th class="px-4 py-2.5 font-semibold text-center">Trx Terbuka</th>
                                <th class="px-4 py-2.5 font-semibold text-right">Saldo Piutang</th>
                                <th class="px-4 py-2.5 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($sGroup['stores'] as $st)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                <td class="px-4 py-3 text-left">
                                    <a href="{{ route('admin.receivables.show', $st->id) }}" class="font-bold text-gray-900 dark:text-gray-100 hover:text-[#0DA4CE] transition">
                                        {{ $st->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-left font-mono text-xs text-gray-400">
                                    {{ $st->code ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-left text-xs text-gray-600 dark:text-gray-300">
                                    {{ $st->city ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $st->open_transactions_count > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $st->open_transactions_count }} trx
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold {{ $st->receivable_balance > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    Rp {{ number_format($st->receivable_balance, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.receivables.show', $st->id) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded text-xs font-bold transition">
                                        <span>Detail Toko</span>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @empty
            <div class="sw-card p-12 text-center text-gray-400">
                Tidak ada data sales atau toko yang tercatat.
            </div>
            @endforelse

            @if($salesPaginated->hasPages())
            <div class="sw-card p-4">
                {{ $salesPaginated->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
