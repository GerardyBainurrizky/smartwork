<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Pengiriman Driver') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $periodLabel }}</p>
            </div>
            @php
                $reportFileName = 'Laporan Pengiriman Driver';
                if ($fromDate && $toDate) {
                    $reportFileName .= ' ' . \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($toDate)->format('d M Y');
                }
            @endphp
            @include('reports.partials.export-buttons', [
                'exportType' => 'driver-visits',
                'exportFilename' => $reportFileName,
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="pt-4 mb-6">@include('reports.partials.back')</div>

        <div id="sw-filter-card" class="sw-report-filter bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.driver-visits') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cari Pengiriman</label>
                        <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Nama toko, kode, driver, rute..."
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Driver</label>
                        <select name="user_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Driver</option>
                            @foreach($driverUsers as $d)
                                <option value="{{ $d->id }}" {{ request('user_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tujuan Pengiriman</label>
                        <select name="store_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Toko</option>
                            @foreach($storesList ?? [] as $st)
                                <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status Pengiriman</label>
                        <select name="status" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Sedang Dikirim</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status Transaksi Driver</label>
                        <select name="transaction_status" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua</option>
                            <option value="paid" {{ request('transaction_status') === 'paid' ? 'selected' : '' }}>Ada Transaksi Driver</option>
                            <option value="none" {{ request('transaction_status') === 'none' ? 'selected' : '' }}>Tanpa Transaksi</option>
                        </select>
                    </div>

                    <div class="lg:col-span-1 flex flex-col sm:flex-row sm:items-end justify-end gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center w-full px-4 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                            Filter
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 pt-1 border-t border-gray-100 dark:border-gray-800 items-end">
                    <div class="lg:col-span-5">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="from_date" value="{{ request('from_date', $fromDate ?? '') }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                    <div class="lg:col-span-5">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="to_date" value="{{ request('to_date', $toDate ?? '') }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                    <div class="lg:col-span-2 flex justify-end">
                        @if(request()->anyFilled(['from_date', 'to_date', 'user_id', 'store_id', 'status', 'transaction_status', 'search']))
                        <a href="{{ route('admin.reports.driver-visits') }}"
                            class="inline-flex items-center justify-center w-full px-4 py-3 min-h-[3rem] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Reset
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Pengiriman</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Selesai</p>
                <p class="text-xl font-bold text-green-700 dark:text-green-400">{{ $stats['completed'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Sedang Dikirim</p>
                <p class="text-xl font-bold text-[#0DA4CE]">{{ $stats['in_progress'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Pembayaran Transaksi Driver</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">Rp {{ number_format($stats['total_transactions_amount'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Driver & Rute</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tujuan & Alamat</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Waktu & Durasi</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Lokasi Check In / Out</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Transaksi Driver</th>
                            <th class="px-3.5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Hasil & Catatan</th>
                            <th class="px-3.5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Foto</th>
                            <th class="px-3.5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-3.5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($deliveries as $delivery)
                        @php
                            $deliveryDur = '-';
                            if ($delivery->check_in_at && $delivery->check_out_at) {
                                $deliveryMin = max(0, (int) $delivery->check_in_at->diffInMinutes($delivery->check_out_at));
                                $deliveryDur = $deliveryMin >= 60 ? ((intdiv($deliveryMin, 60)) . 'j ' . ($deliveryMin % 60) . 'm') : $deliveryMin . 'm';
                            }
                            $hasCheckInCoord = $delivery->check_in_lat && $delivery->check_in_lng;
                            $hasCheckOutCoord = $delivery->check_out_lat && $delivery->check_out_lng;
                            $driverCheckInPhoto = $delivery->check_in_selfie ?: $delivery->storefront_photo;
                            $checkoutPhotosCount = $delivery->checkoutPhotos->count();
                            if ($checkoutPhotosCount === 0 && ($delivery->final_store_photo || $delivery->check_out_selfie)) {
                                $checkoutPhotosCount = count(array_filter([$delivery->final_store_photo, $delivery->check_out_selfie]));
                            }
                            $totalPhotosCount = ($driverCheckInPhoto ? 1 : 0) + $checkoutPhotosCount;
                            $hasTrx = ($delivery->transaction_status ?? 'none') === 'paid' && (float)($delivery->transaction_amount ?? 0) > 0;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 align-top">
                            {{-- Driver & Rute --}}
                            <td class="px-3.5 py-3 whitespace-nowrap">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $delivery->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Rute: {{ $delivery->route->name ?? '-' }}</p>
                                @if($delivery->routeStop?->sequence)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 mt-1">Pengiriman #{{ $delivery->routeStop->sequence }}</span>
                                @endif
                            </td>

                            {{-- Tujuan & Alamat --}}
                            <td class="px-3.5 py-3 max-w-[220px]">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $delivery->store->name ?? '-' }}</p>
                                @if($delivery->store?->code)
                                    <p class="text-xs font-mono text-[#0DA4CE]">{{ $delivery->store->code }}</p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $delivery->store->address ?? '-' }}</p>
                            </td>

                            {{-- Waktu & Durasi --}}
                            <td class="px-3.5 py-3 whitespace-nowrap text-xs space-y-1">
                                <div>
                                    <span class="text-gray-400">In:</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $delivery->check_in_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Out:</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $delivery->check_out_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                </div>
                                <div class="pt-0.5">
                                    <span class="text-gray-400">Durasi:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $deliveryDur }}</span>
                                </div>
                            </td>

                            {{-- Lokasi Check In / Out --}}
                            <td class="px-3.5 py-3 max-w-[200px] text-xs space-y-1.5">
                                @if($hasCheckInCoord)
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Check In:</span>
                                        <a href="https://www.google.com/maps?q={{ $delivery->check_in_lat }},{{ $delivery->check_in_lng }}" target="_blank" rel="noopener noreferrer" class="block text-[#0DA4CE] hover:underline font-medium truncate">
                                            {{ round((float)$delivery->check_in_lat, 4) }}, {{ round((float)$delivery->check_in_lng, 4) }}
                                        </a>
                                        @if($delivery->check_in_address)
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $delivery->check_in_address }}</p>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-gray-400">In: -</p>
                                @endif

                                @if($hasCheckOutCoord)
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Check Out:</span>
                                        <a href="https://www.google.com/maps?q={{ $delivery->check_out_lat }},{{ $delivery->check_out_lng }}" target="_blank" rel="noopener noreferrer" class="block text-[#0DA4CE] hover:underline font-medium truncate">
                                            {{ round((float)$delivery->check_out_lat, 4) }}, {{ round((float)$delivery->check_out_lng, 4) }}
                                        </a>
                                    </div>
                                @elseif($delivery->status === 'completed')
                                    <p class="text-gray-400">Out: -</p>
                                @endif
                            </td>

                            {{-- Transaksi Driver --}}
                            <td class="px-3.5 py-3 text-xs space-y-1 min-w-[170px]">
                                @if($hasTrx)
                                    <div class="bg-emerald-50/50 dark:bg-emerald-950/20 rounded-lg p-2 border border-emerald-100 dark:border-emerald-900/40">
                                        <div class="flex items-center justify-between font-bold text-emerald-700 dark:text-emerald-400 font-mono text-sm">
                                            <span>Rp {{ number_format((float) $delivery->transaction_amount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px] text-gray-600 dark:text-gray-300 mt-1">
                                            <span>Metode: <strong class="uppercase">{{ $delivery->payment_method ?? 'tunai' }}</strong></span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">Lunas</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400">Tidak Ada Transaksi</span>
                                @endif
                            </td>

                            {{-- Hasil & Catatan --}}
                            <td class="px-3.5 py-3 text-xs max-w-[200px]">
                                @if($delivery->visit_result)
                                    <p class="font-medium text-gray-800 dark:text-gray-200 line-clamp-2"><span class="text-gray-400">Hasil:</span> {{ $delivery->visit_result }}</p>
                                @endif
                                @if($delivery->initial_notes || $delivery->final_notes)
                                    <p class="text-gray-500 dark:text-gray-400 mt-1 line-clamp-2"><span class="text-gray-400">Catatan:</span> {{ $delivery->final_notes ?: $delivery->initial_notes }}</p>
                                @endif
                                @if(!$delivery->visit_result && !$delivery->initial_notes && !$delivery->final_notes)
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Foto --}}
                            <td class="px-3.5 py-3 text-center whitespace-nowrap text-xs">
                                @if($totalPhotosCount > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 font-semibold text-[11px]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $totalPhotosCount }} Foto
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if($delivery->status === 'completed') bg-[#90F022]/20 text-green-800 dark:text-green-300
                                    @else bg-[#0DA4CE]/10 text-[#0DA4CE] @endif">
                                    {{ $delivery->status === 'completed' ? 'Selesai' : 'Sedang Dikirim' }}
                                </span>
                            </td>

                            {{-- Aksi Detail --}}
                            <td class="px-3.5 py-3 text-center whitespace-nowrap text-xs">
                                <a href="{{ route('visit.show', $delivery->id) }}"
                                    class="inline-flex items-center px-2.5 py-1.5 bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] font-semibold rounded-lg transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="px-6 py-14 text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-2M9 7h2m-2 4h2m-2 4h2"/></svg>
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data pengiriman</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $deliveries->links('vendor.pagination.reports') }}

        @if($skippedStops->isNotEmpty())
        <div class="mt-8 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-4 py-3 bg-amber-50 dark:bg-amber-950 border-b border-amber-100 dark:border-amber-900">
                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">Pengiriman Dilewati ({{ $skippedStops->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Driver</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tujuan Pengiriman</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rute Pengiriman</th>
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
