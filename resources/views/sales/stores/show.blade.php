<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="file-text" :title="'Detail Toko: ' . $store->name" :subtitle="'Kode: ' . ($store->code ?? '-')"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        <x-back-button href="{{ route('sales.stores.index') }}" label="Kembali ke Daftar Toko &amp; Piutang" class="mb-2" />

        {{-- Store & Receivable Overview Card --}}
        <div class="sw-card p-5 sm:p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $store->name }}</h1>
                        <span class="text-xs font-mono px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 font-semibold text-gray-600 dark:text-gray-300">
                            {{ $store->code ?? '-' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                        Alamat: <span class="text-gray-700 dark:text-gray-300">{{ $store->address ?: 'Alamat belum diatur' }}</span>
                        @if($store->city) • <span>{{ $store->city }}</span> @endif
                    </p>
                </div>
            </div>

            {{-- Metric Summary 3 Columns --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Saldo Piutang Toko</span>
                    <p class="text-2xl font-bold font-mono {{ $balance > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-1">
                        Rp {{ number_format($balance, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Seluruh saldo piutang toko yang masih belum lunas.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Transaksi Terbuka</span>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                        {{ $openTrxCount }} <span class="text-xs font-normal text-gray-400">Faktur</span>
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Belum lunas / sebagian</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Faktur Tercatat</span>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                        {{ $transactions->total() }} <span class="text-xs font-normal text-gray-400">Faktur</span>
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Riwayat transaksi toko</p>
                </div>
            </div>
        </div>

        {{-- Transactions List --}}
        <div class="sw-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Riwayat Transaksi &amp; Faktur Toko</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Rincian faktur dan pembayaran toko</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $transactions->total() }} Transaksi
                </span>
            </div>

            @if($transactions->isNotEmpty())
            <div class="p-4 sm:p-5 space-y-4">
                @foreach($transactions as $trx)
                <div class="bg-white dark:bg-gray-800/90 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 p-4 sm:p-5 space-y-4 shadow-xs hover:border-gray-300 dark:hover:border-gray-600 transition">
                    {{-- Row Top Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="font-bold font-mono text-sm sm:text-base text-gray-900 dark:text-gray-100">{{ $trx->transaction_code }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $trx->transaction_date ? $trx->transaction_date->format('d M Y') : '-' }}
                            </span>
                            @if($trx->reference_type === 'opening_balance')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">Saldo Awal</span>
                            @elseif($trx->reference_type === 'visit')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">Kunjungan Sales</span>
                            @endif
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold self-start sm:self-auto shrink-0
                            @if($trx->status === 'LUNAS') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                            @elseif($trx->status === 'SEBAGIAN') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                            @else bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 @endif">
                            {{ $trx->status }}
                        </span>
                    </div>

                    @if($trx->description)
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">{{ $trx->description }}</p>
                    @endif

                    {{-- Metric Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3.5 text-xs border border-gray-100 dark:border-gray-700/60">
                        <div>
                            <span class="text-gray-400 dark:text-gray-500 block">Nominal Faktur:</span>
                            <p class="font-bold text-gray-900 dark:text-gray-100 font-mono text-sm mt-0.5">Rp {{ number_format($trx->transaction_amount, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400 dark:text-gray-500 block">Total Dibayar:</span>
                            <p class="font-bold text-green-600 dark:text-green-400 font-mono text-sm mt-0.5">Rp {{ number_format($trx->total_paid, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400 dark:text-gray-500 block">Sisa Piutang:</span>
                            <p class="font-bold font-mono text-sm mt-0.5 {{ $trx->remaining_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                Rp {{ number_format($trx->remaining_amount, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Histori Pembayaran Transaksi Ini --}}
                    @if($trx->payments->isNotEmpty())
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                        <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Histori Pembayaran ({{ $trx->payments->count() }}):</p>
                        <div class="space-y-2">
                            @foreach($trx->payments as $pIndex => $pay)
                            <div class="flex items-center justify-between text-xs p-3 bg-gray-50/80 dark:bg-gray-800/80 rounded-xl border border-gray-100 dark:border-gray-700/60 shadow-2xs">
                                <div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">
                                        Pembayaran #{{ $pIndex + 1 }} • {{ $pay->payment_date ? $pay->payment_date->format('d M Y') : '-' }}
                                    </span>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Metode: <strong class="uppercase font-semibold text-gray-700 dark:text-gray-300">{{ $pay->payment_method }}</strong> • Dicatat oleh: {{ $pay->recorder?->name ?? 'Sistem' }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-bold text-green-600 dark:text-green-400 font-mono text-sm">Rp {{ number_format($pay->amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($transactions->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                {{ $transactions->links() }}
            </div>
            @endif
            @else
            <div class="p-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                Belum Ada Transaksi
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
