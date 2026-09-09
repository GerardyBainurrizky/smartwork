<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="credit-card" :title="'Detail Transaksi: ' . $transaction->transaction_code" subtitle="Informasi lengkap transaksi piutang dan histori pembayaran"></x-page-header>
    </x-slot>

    <div class="space-y-6 max-w-7xl mx-auto" x-data="{
        showPayModal: false,
        rawPayAmount: '',
        formattedPayAmount: '',
        remaining: {{ $transaction->remaining_amount }},
        onPayAmountInput(event) {
            const digits = (event.target.value || '').replace(/\D/g, '');
            let num = Number(digits) || 0;
            if (num > this.remaining) {
                num = this.remaining;
            }
            this.rawPayAmount = num > 0 ? String(num) : '';
            this.formattedPayAmount = num > 0 ? num.toLocaleString('id-ID') : '';
        }
    }">
        <x-back-button href="{{ route('admin.receivables.show', $transaction->store_id) }}" label="Kembali ke Detail Piutang Toko" class="mb-2" />

        {{-- Transaction Details Card --}}
        <div class="sw-card p-5 sm:p-6 space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <span class="text-xs font-mono text-gray-500 uppercase font-semibold">Kode Transaksi</span>
                    <h1 class="text-2xl sm:text-3xl font-bold font-mono text-gray-900 dark:text-gray-100 mt-0.5">{{ $transaction->transaction_code }}</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                        Toko: <a href="{{ route('admin.receivables.show', $transaction->store_id) }}" class="font-bold text-[#0DA4CE] hover:underline">{{ $transaction->store?->name }}</a>
                        @if($transaction->store?->code) <span class="font-mono">({{ $transaction->store->code }})</span> @endif
                    </p>
                </div>
                <div class="flex flex-col lg:items-end gap-2">
                    <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold self-start lg:self-auto
                        @if($transaction->status === 'LUNAS') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                        @elseif($transaction->status === 'SEBAGIAN') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                        @else bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 @endif">
                        {{ $transaction->status }}
                    </span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Tanggal Transaksi: <strong>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '-' }}</strong>
                    </p>
                </div>
            </div>

            {{-- Metric Summary --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Nominal Transaksi</p>
                    <p class="text-2xl font-bold font-mono text-gray-900 dark:text-gray-100 mt-1">
                        Rp {{ number_format($transaction->transaction_amount, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Nilai faktur tercatat</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Total Dibayar</p>
                    <p class="text-2xl font-bold font-mono text-green-600 dark:text-green-400 mt-1">
                        Rp {{ number_format($transaction->total_paid, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Akumulasi pembayaran</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Sisa Piutang</p>
                    <p class="text-2xl font-bold font-mono {{ $transaction->remaining_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-1">
                        Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Kekurangan tagihan</p>
                </div>
            </div>

            {{-- Metadata Info --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-gray-50/50 dark:bg-gray-800/30 rounded-xl p-4 border border-gray-100 dark:border-gray-800">
                <div>
                    <span class="text-gray-400">Sales Penanggung Jawab:</span>
                    <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5 text-sm">{{ $transaction->store?->salesPenanggungJawab?->name ?? 'Belum Ada' }}</p>
                </div>
                <div>
                    <span class="text-gray-400">Dibuat Oleh:</span>
                    <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5 text-sm">{{ $transaction->creator?->name ?? 'Sistem' }}</p>
                </div>
                @if($transaction->description)
                <div class="sm:col-span-2">
                    <span class="text-gray-400">Keterangan:</span>
                    <p class="text-gray-700 dark:text-gray-300 mt-0.5 leading-relaxed">{{ $transaction->description }}</p>
                </div>
                @endif
                @if($transaction->reference_type)
                <div class="sm:col-span-2">
                    <span class="text-gray-400">Referensi:</span>
                    <p class="text-gray-700 dark:text-gray-300 mt-0.5">
                        @if($transaction->reference_type === 'visit' && $transaction->reference_id)
                            Kunjungan Sales (<a href="{{ route('visit.show', $transaction->reference_id) }}" target="_blank" class="text-[#0DA4CE] font-bold hover:underline">Buka Detail Kunjungan</a>)
                        @elseif($transaction->reference_type === 'opening_balance')
                            Saldo Piutang Awal Perusahaan
                        @elseif($transaction->reference_type === 'adjustment')
                            Penyesuaian Saldo Piutang
                        @else
                            {{ ucfirst($transaction->reference_type) }}
                        @endif
                    </p>
                </div>
                @endif
            </div>

            {{-- Action Tambah Pembayaran jika masih ada sisa --}}
            @if($transaction->remaining_amount > 0.005)
            <div class="pt-2">
                <button type="button" @click="rawPayAmount = ''; formattedPayAmount = ''; showPayModal = true"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#90F022] text-gray-900 font-bold rounded-xl text-xs hover:bg-[#7ed81e] shadow-sm transition active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Pembayaran Transaksi</span>
                </button>
            </div>
            @endif
        </div>

        {{-- Payment History Card --}}
        <div class="sw-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm sm:text-base">Histori Pembayaran</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar rekaman pembayaran untuk faktur ini</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    {{ $transaction->payments->count() }} Pembayaran
                </span>
            </div>

            @if($transaction->payments->isNotEmpty())
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($transaction->payments as $idx => $pay)
                <div class="p-5 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100">Pembayaran #{{ $idx + 1 }}</span>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $pay->payment_date ? $pay->payment_date->format('d M Y') : '-' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Metode: <strong class="uppercase font-semibold text-gray-800 dark:text-gray-200">{{ strtoupper($pay->payment_method) }}</strong>
                            • Dicatat oleh: <strong>{{ $pay->recorder?->name ?? 'Sistem' }}</strong>
                        </p>
                        @if($pay->notes)
                        <p class="text-[11px] text-gray-500 italic">{{ $pay->notes }}</p>
                        @endif
                        @if($pay->source === 'visit' && $pay->source_id)
                        <p class="text-[11px] text-[#0DA4CE]">
                            Sumber: <a href="{{ route('visit.show', $pay->source_id) }}" target="_blank" class="hover:underline font-semibold">Kunjungan Sales</a>
                        </p>
                        @endif
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <span class="text-[10px] text-gray-400 uppercase font-bold block">Nominal Dibayar</span>
                        <p class="text-base sm:text-lg font-bold font-mono text-green-600 dark:text-green-400">
                            Rp {{ number_format($pay->amount, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                Belum ada pembayaran untuk transaksi ini.
            </div>
            @endif
        </div>

        {{-- Modal Tambah Pembayaran --}}
        <div x-show="showPayModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700" @click.away="showPayModal = false">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Pembayaran Transaksi</h3>
                    <button type="button" @click="showPayModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('admin.receivables.transactions.payments.store', $transaction->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Sisa Piutang Transaksi:</span>
                        <p class="text-lg font-bold font-mono text-red-600 dark:text-red-400 mt-0.5">
                            Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nominal Pembayaran (Rp) <span class="text-red-500">*</span></label>
                        <input type="hidden" name="amount" :value="rawPayAmount">
                        <input type="text" inputmode="numeric" autocomplete="off" :value="formattedPayAmount" @input="onPayAmountInput($event)" required class="sw-input w-full font-mono text-sm" placeholder="Contoh: 100.000">
                        <div class="flex justify-end mt-1.5">
                            <button type="button" @click="rawPayAmount = String(remaining); formattedPayAmount = remaining.toLocaleString('id-ID');" class="text-[11px] text-[#0DA4CE] font-bold hover:underline">
                                Bayar Lunas (Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }})
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran <span class="text-red-500">*</span></label>
                        <select name="payment_method" required class="sw-input w-full text-xs">
                            <option value="tunai">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="sw-input w-full text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Catatan Pembayaran (Opsional)</label>
                        <textarea name="notes" rows="2" class="sw-input w-full text-xs" placeholder="Contoh: Pembayaran cicilan kedua / pelunasan"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showPayModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-xs font-bold rounded-xl text-gray-700 dark:text-gray-200">Batal</button>
                        <button type="submit" :disabled="!rawPayAmount || Number(rawPayAmount) <= 0 || Number(rawPayAmount) > remaining" class="px-4 py-2 bg-[#90F022] text-gray-900 text-xs font-bold rounded-xl disabled:opacity-50 hover:bg-[#7ed81e]">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>


