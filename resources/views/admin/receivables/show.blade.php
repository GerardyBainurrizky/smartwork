<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="file-text" :title="'Detail Piutang: ' . $store->name" :subtitle="'Sales: ' . ($store->salesPenanggungJawab?->name ?? 'Belum Ada')"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto" x-data="{
        showModal: false,
        adjustmentType: 'add',
        rawAmount: '',
        formattedAmount: '',
        currentBalance: {{ $balance }},
        onAmountInput(event) {
            const digits = (event.target.value || '').replace(/\D/g, '');
            this.rawAmount = digits;
            this.formattedAmount = digits ? Number(digits).toLocaleString('id-ID') : '';
        },
        get remainingPreview() {
            const num = Number(this.rawAmount) || 0;
            if (this.adjustmentType === 'subtract') {
                return Math.max(0, this.currentBalance - num);
            }
            return this.currentBalance + num;
        },
        get isSubtractExceeding() {
            if (this.adjustmentType === 'subtract') {
                return (Number(this.rawAmount) || 0) > this.currentBalance;
            }
            return false;
        }
    }">
        <x-back-button href="{{ route('admin.receivables.index') }}" label="Kembali ke Master Piutang Toko" class="mb-2" />

        {{-- Store & Receivable Overview Card --}}
        <div class="sw-card p-5 sm:p-6 space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $store->name }}</h1>
                        <span class="text-xs font-mono px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 font-semibold text-gray-600 dark:text-gray-300">
                            {{ $store->code ?? '-' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                        Sales Penanggung Jawab: <strong class="text-gray-800 dark:text-gray-200">{{ $store->salesPenanggungJawab?->name ?? 'Belum Ada' }}</strong>
                        @if($store->city) • <span>{{ $store->city }}</span> @endif
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $store->address ?? '' }}</p>
                </div>
                <div class="flex items-center gap-3 self-start lg:self-auto">
                    <button @click="showModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#0DA4CE] hover:bg-[#097A99] text-white font-bold rounded-xl text-xs shadow-sm transition active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Penyesuaian Piutang</span>
                    </button>
                </div>
            </div>

            {{-- Metric Summary 4 Columns --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Piutang Toko</span>
                    <p class="text-2xl font-bold font-mono {{ $balance > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-1">
                        Rp {{ number_format($balance, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Saldo terbuka saat ini</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Transaksi Terbuka</span>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                        {{ $openTrxCount }} <span class="text-xs font-normal text-gray-400">faktur</span>
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Belum lunas / sebagian</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Transaksi Lunas</span>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                        {{ $paidTrxCount }} <span class="text-xs font-normal text-gray-400">faktur</span>
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Lunas 100%</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pembayaran Masuk</span>
                    <p class="text-2xl font-bold font-mono text-green-600 dark:text-green-400 mt-1">
                        Rp {{ number_format($totalStorePayments, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Dari riwayat pembayaran</p>
                </div>
            </div>
        </div>

        {{-- Transactions List Section --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between px-1">
                <div>
                    <h2 class="font-bold text-gray-900 dark:text-gray-100 text-base sm:text-lg">Daftar Transaksi Piutang Toko</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Rincian faktur penjualan dan histori pelunasan cicilan</p>
                </div>
                <span class="text-xs font-semibold px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                    Total {{ $transactions->total() }} Transaksi
                </span>
            </div>

            @if($transactions->isNotEmpty())
            <div class="space-y-4">
                @foreach($transactions as $trx)
                <div class="sw-card p-5 sm:p-6 border {{ $trx->remaining_amount > 0.005 ? 'border-amber-200 dark:border-amber-900/40 bg-gradient-to-r from-amber-50/20 via-white to-white dark:from-amber-950/10 dark:to-gray-800' : 'border-gray-200 dark:border-gray-700' }} shadow-sm space-y-4">
                    {{-- Card Header: Code, Badge & Action --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="font-bold font-mono text-base text-gray-900 dark:text-gray-100">{{ $trx->transaction_code }}</span>
                            <span class="text-xs text-gray-300 dark:text-gray-600">•</span>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $trx->transaction_date ? $trx->transaction_date->format('d M Y') : '-' }}
                            </span>
                            @if($trx->reference_type === 'opening_balance')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">Saldo Awal</span>
                            @elseif($trx->reference_type === 'visit')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">Kunjungan Sales</span>
                            @elseif($trx->reference_type === 'adjustment')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">Penyesuaian</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                @if($trx->status === 'LUNAS') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                @elseif($trx->status === 'SEBAGIAN') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                @else bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 @endif">
                                {{ $trx->status }}
                            </span>
                            <a href="{{ route('admin.receivables.transactions.show', $trx->id) }}"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded-xl text-xs font-bold transition">
                                <span>Lihat Detail</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>

                    @if($trx->description)
                    <p class="text-xs text-gray-600 dark:text-gray-400 bg-gray-50/70 dark:bg-gray-800/40 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700/40">{{ $trx->description }}</p>
                    @endif

                    {{-- Metric Grid: 3 Clear Columns --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3.5 text-xs border border-gray-100 dark:border-gray-700/60">
                        <div>
                            <span class="text-gray-400 dark:text-gray-500 uppercase font-semibold text-[10px] block">Total Transaksi</span>
                            <p class="font-bold text-gray-900 dark:text-gray-100 font-mono text-sm sm:text-base mt-0.5">Rp {{ number_format($trx->transaction_amount, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400 dark:text-gray-500 uppercase font-semibold text-[10px] block">Sudah Dibayar</span>
                            <p class="font-bold text-green-600 dark:text-green-400 font-mono text-sm sm:text-base mt-0.5">Rp {{ number_format($trx->total_paid, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400 dark:text-gray-500 uppercase font-semibold text-[10px] block">Sisa Piutang</span>
                            <p class="font-bold font-mono text-sm sm:text-base mt-0.5 {{ $trx->remaining_amount > 0.005 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                Rp {{ number_format($trx->remaining_amount, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Histori Pembayaran Transaksi Ini --}}
                    @if($trx->payments->isNotEmpty())
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Histori Pembayaran ({{ $trx->payments->count() }}):</p>
                        </div>
                        <div class="space-y-1.5">
                            @foreach($trx->payments as $pIndex => $pay)
                            <div class="flex items-center justify-between text-xs p-2.5 bg-white dark:bg-gray-800/80 rounded-lg border border-gray-100 dark:border-gray-700/60 shadow-2xs">
                                <div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">
                                        Pembayaran #{{ $pIndex + 1 }} • {{ $pay->payment_date ? $pay->payment_date->format('d M Y') : '-' }}
                                    </span>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Metode: <strong class="uppercase font-semibold text-gray-700 dark:text-gray-300">{{ strtoupper($pay->payment_method) }}</strong> • Dicatat oleh: {{ $pay->recorder?->name ?? 'Sistem' }}
                                        @if($pay->source === 'visit' && $pay->source_id)
                                            • <a href="{{ route('visit.show', $pay->source_id) }}" target="_blank" class="text-[#0DA4CE] hover:underline font-semibold">Kunjungan Sales</a>
                                        @endif
                                    </p>
                                </div>
                                <div class="text-right">
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

            {{-- Transactions Pagination --}}
            @if($transactions->hasPages())
            <div class="sw-card p-4">
                {{ $transactions->links() }}
            </div>
            @endif
            @else
            <div class="sw-card p-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                Belum Ada Transaksi
            </div>
            @endif
        </div>

        {{-- Adjustment Modal with Clear Add / Subtract Options --}}
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700" @click.away="showModal = false">
                <div class="flex items-center justify-between mb-4 pb-2.5 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Penyesuaian Piutang Toko</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $store->name }} (Saldo: Rp {{ number_format($balance, 0, ',', '.') }})</p>
                    </div>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('admin.receivables.adjust', $store->id) }}" method="POST" class="space-y-4">
                    @csrf

                    {{-- Action Selection: Tambah vs Kurangi --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">Aksi Penyesuaian <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer border-2 rounded-xl p-3 flex items-center gap-3 transition"
                                :class="adjustmentType === 'add' ? 'border-[#0DA4CE] bg-[#0DA4CE]/10 dark:bg-cyan-950/40 text-gray-900 dark:text-white' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                <input type="radio" name="adjustment_type" value="add" x-model="adjustmentType" class="sr-only">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"
                                    :class="adjustmentType === 'add' ? 'bg-[#0DA4CE] text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'">
                                    +
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold">Tambah Piutang</p>
                                    <p class="text-[10px] text-gray-500">Menaikkan saldo tagihan</p>
                                </div>
                            </label>

                            <label class="cursor-pointer border-2 rounded-xl p-3 flex items-center gap-3 transition"
                                :class="adjustmentType === 'subtract' ? 'border-red-500 bg-red-50 dark:bg-red-950/40 text-gray-900 dark:text-white' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                <input type="radio" name="adjustment_type" value="subtract" x-model="adjustmentType" class="sr-only">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"
                                    :class="adjustmentType === 'subtract' ? 'bg-red-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'">
                                    -
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold">Kurangi Piutang</p>
                                    <p class="text-[10px] text-gray-500">Memotong saldo tagihan</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Nominal Input with formatted dot display --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nominal Penyesuaian (Rp) <span class="text-red-500">*</span></label>
                        <input type="hidden" name="amount" :value="rawAmount">
                        <input type="text" inputmode="numeric" autocomplete="off" :value="formattedAmount" @input="onAmountInput($event)" required
                            class="sw-input w-full font-mono text-sm" placeholder="Contoh: 500.000">
                        
                        <p x-show="isSubtractExceeding" class="text-xs font-bold text-red-500 mt-1">
                            Nominal pengurangan melebihi saldo piutang toko (Maks: Rp {{ number_format($balance, 0, ',', '.') }}).
                        </p>
                    </div>

                    {{-- Preview Saldo --}}
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl space-y-1 text-xs border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-gray-500">
                            <span>Saldo Saat Ini:</span>
                            <span class="font-mono font-bold text-gray-800 dark:text-gray-200">Rp {{ number_format($balance, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-gray-200 dark:border-gray-700 font-bold">
                            <span>Estimasi Saldo Baru:</span>
                            <span class="font-mono text-sm" :class="remainingPreview > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                x-text="'Rp ' + Number(remainingPreview).toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Penyesuaian</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="sw-input w-full text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Alasan Penyesuaian <span class="text-red-500">*</span></label>
                        <textarea name="notes" required rows="3" class="sw-input w-full text-xs" placeholder="Tuliskan alasan koreksi atau dasar penyesuaian piutang (wajib diisi)..."></textarea>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="w-full sm:w-auto px-4 py-2 bg-gray-200 dark:bg-gray-700 text-xs font-bold rounded-xl text-gray-700 dark:text-gray-200">Batal</button>
                        <button type="submit" :disabled="!rawAmount || Number(rawAmount) <= 0 || isSubtractExceeding"
                            class="w-full sm:w-auto px-4 py-2 bg-[#0DA4CE] text-white text-xs font-bold rounded-xl disabled:opacity-50 hover:bg-[#097A99] transition">
                            Simpan Penyesuaian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>


