<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Kunjungan Sales') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $periodLabel }}</p>
            </div>
            @php
                $reportFileName = 'Laporan Kunjungan Sales';
                if ($fromDate && $toDate) {
                    $reportFileName .= ' ' . \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($toDate)->format('d M Y');
                }
            @endphp
            @include('reports.partials.export-buttons', [
                'exportType' => 'visits',
                'exportFilename' => $reportFileName,
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="pt-4 mb-6">@include('reports.partials.back')</div>

        <div id="sw-filter-card" class="sw-report-filter bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.visits') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cari Kunjungan</label>
                        <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Nama toko, kode, sales, rute..."
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Sales</label>
                        <select name="user_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Sales</option>
                            @foreach($salesUsers as $s)
                                <option value="{{ $s->id }}" {{ request('user_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Toko</label>
                        <select name="store_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Toko</option>
                            @foreach($storesList ?? [] as $st)
                                <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status</label>
                        <select name="status" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Berlangsung</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2 flex flex-col sm:flex-row sm:items-end justify-end gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                            Filter
                        </button>
                        @if(request()->anyFilled(['from_date', 'to_date', 'user_id', 'store_id', 'status', 'search']))
                        <a href="{{ route('admin.reports.visits') }}"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Reset
                        </a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="from_date" value="{{ request('from_date', $fromDate ?? '') }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="to_date" value="{{ request('to_date', $toDate ?? '') }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Kunjungan</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Selesai</p>
                <p class="text-xl font-bold text-green-700 dark:text-green-400">{{ $stats['completed'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Berlangsung</p>
                <p class="text-xl font-bold text-[#0DA4CE]">{{ $stats['in_progress'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Transaksi</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($stats['total_transaction'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sales & Rute</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Toko & Alamat</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Waktu & Durasi</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Lokasi Check In / Out</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Transaksi & Pembayaran</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Hasil & Catatan</th>
                            <th class="px-3.5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Foto</th>
                            <th class="px-3.5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-3.5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($visits as $visit)
                        @php
                            $visitDur = '-';
                            if ($visit->check_in_at && $visit->check_out_at) {
                                $visitMin = max(0, (int) $visit->check_in_at->diffInMinutes($visit->check_out_at));
                                $visitDur = $visitMin >= 60 ? ((intdiv($visitMin, 60)) . 'j ' . ($visitMin % 60) . 'm') : $visitMin . 'm';
                            }
                            $vPayments = $visit->payments->where('source', '!=', 'initial_payment');
                            $vTransactions = $visit->transactions;
                            $hasCheckInCoord = $visit->check_in_lat && $visit->check_in_lng;
                            $hasCheckOutCoord = $visit->check_out_lat && $visit->check_out_lng;
                            $photoCount = ($visit->check_in_selfie ? 1 : 0) + ($visit->check_out_selfie ? 1 : 0) + ($visit->storefront_photo ? 1 : 0) + ($visit->final_store_photo ? 1 : 0) + $visit->photos->count();
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 align-top">
                            {{-- Sales & Rute --}}
                            <td class="px-3.5 py-3 whitespace-nowrap">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $visit->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Rute: {{ $visit->route->name ?? '-' }}</p>
                                @if($visit->routeStop?->sequence)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 mt-1">Stop #{{ $visit->routeStop->sequence }}</span>
                                @endif
                            </td>

                            {{-- Toko & Alamat --}}
                            <td class="px-3.5 py-3 max-w-[220px]">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $visit->store->name ?? '-' }}</p>
                                @if($visit->store?->code)
                                    <p class="text-xs font-mono text-[#0DA4CE]">{{ $visit->store->code }}</p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $visit->store->address ?? '-' }}</p>
                            </td>

                            {{-- Waktu & Durasi --}}
                            <td class="px-3.5 py-3 whitespace-nowrap text-xs space-y-1">
                                <div>
                                    <span class="text-gray-400">In:</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $visit->check_in_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Out:</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $visit->check_out_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                </div>
                                <div class="pt-0.5">
                                    <span class="text-gray-400">Durasi:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $visitDur }}</span>
                                </div>
                            </td>

                            {{-- Lokasi Check In / Out --}}
                            <td class="px-3.5 py-3 max-w-[200px] text-xs space-y-1.5">
                                @if($hasCheckInCoord)
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Check In:</span>
                                        <a href="https://www.google.com/maps?q={{ $visit->check_in_lat }},{{ $visit->check_in_lng }}" target="_blank" rel="noopener noreferrer" class="block text-[#0DA4CE] hover:underline font-medium truncate">
                                            {{ round((float)$visit->check_in_lat, 4) }}, {{ round((float)$visit->check_in_lng, 4) }}
                                        </a>
                                        @if($visit->check_in_address)
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $visit->check_in_address }}</p>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-gray-400">In: -</p>
                                @endif

                                @if($hasCheckOutCoord)
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Check Out:</span>
                                        <a href="https://www.google.com/maps?q={{ $visit->check_out_lat }},{{ $visit->check_out_lng }}" target="_blank" rel="noopener noreferrer" class="block text-[#0DA4CE] hover:underline font-medium truncate">
                                            {{ round((float)$visit->check_out_lat, 4) }}, {{ round((float)$visit->check_out_lng, 4) }}
                                        </a>
                                    </div>
                                @elseif($visit->status === 'completed')
                                    <p class="text-gray-400">Out: -</p>
                                @endif
                            </td>

                            {{-- Transaksi & Pembayaran --}}
                            <td class="px-3.5 py-3 text-xs space-y-1 min-w-[170px]">
                                @if($vTransactions->isNotEmpty() || $vPayments->isNotEmpty() || (($visit->transaction_status ?? 'none') === 'paid' && (float)$visit->transaction_amount > 0))
                                    @if($vTransactions->isNotEmpty())
                                        @foreach($vTransactions as $trx)
                                            <div class="bg-gray-50 dark:bg-gray-800/60 rounded p-1.5 border border-gray-100 dark:border-gray-700">
                                                <div class="flex justify-between font-mono font-bold text-gray-800 dark:text-gray-200 text-[11px]">
                                                    <span>{{ $trx->transaction_code }}</span>
                                                    <span class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($trx->transaction_amount, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-[10px] text-gray-500 mt-0.5">
                                                    <span>Status: {{ $trx->status }}</span>
                                                    <span>Sisa: Rp {{ number_format($trx->remaining_amount, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @elseif(($visit->transaction_status ?? 'none') === 'paid' && (float)$visit->transaction_amount > 0)
                                        <div class="font-semibold text-emerald-600 dark:text-emerald-400">
                                            Rp {{ number_format((float) $visit->transaction_amount, 0, ',', '.') }}
                                        </div>
                                    @endif

                                    @if($vPayments->isNotEmpty())
                                        @foreach($vPayments as $pmt)
                                            <div class="text-[11px] text-green-700 dark:text-green-300 font-medium">
                                                ✓ Bayar: Rp {{ number_format($pmt->amount, 0, ',', '.') }} ({{ strtoupper($pmt->payment_method) }})
                                            </div>
                                        @endforeach
                                    @endif
                                @else
                                    <span class="text-gray-400">Tidak ada transaksi</span>
                                @endif
                            </td>

                            {{-- Hasil & Catatan --}}
                            <td class="px-3.5 py-3 text-xs max-w-[200px]">
                                @if($visit->visit_result)
                                    <p class="font-medium text-gray-800 dark:text-gray-200 line-clamp-2"><span class="text-gray-400">Hasil:</span> {{ $visit->visit_result }}</p>
                                @endif
                                @if($visit->initial_notes || $visit->final_notes)
                                    <p class="text-gray-500 dark:text-gray-400 mt-1 line-clamp-2"><span class="text-gray-400">Catatan:</span> {{ $visit->final_notes ?: $visit->initial_notes }}</p>
                                @endif
                                @if(!$visit->visit_result && !$visit->initial_notes && !$visit->final_notes)
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Foto --}}
                            <td class="px-3.5 py-3 text-center whitespace-nowrap text-xs">
                                @if($photoCount > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 font-semibold text-[11px]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $photoCount }} Foto
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if($visit->status === 'completed') bg-[#90F022]/20 text-green-800 dark:text-green-300
                                    @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif">
                                    {{ $visit->status === 'completed' ? 'Selesai' : 'Berlangsung' }}
                                </span>
                            </td>

                            {{-- Aksi Detail --}}
                            <td class="px-3.5 py-3 text-center whitespace-nowrap text-xs">
                                <a href="{{ route('visit.show', $visit->id) }}"
                                    class="inline-flex items-center px-2.5 py-1.5 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] font-semibold rounded-lg transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="px-6 py-14 text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-2M9 7h2m-2 4h2m-2 4h2"/></svg>
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data kunjungan</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $visits->links('vendor.pagination.reports') }}

        @if($skippedStops->isNotEmpty())
        <div class="mt-8 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-4 py-3 bg-amber-50 dark:bg-amber-950 border-b border-amber-100 dark:border-amber-900">
                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">Kunjungan Dilewati ({{ $skippedStops->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sales</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Toko</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rute Wilayah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @foreach($skippedStops as $skip)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $skip->route->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $skip->store->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $skip->route->name ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $skip->route->date?->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $skip->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
