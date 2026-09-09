<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Performa Aktivitas Sales') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ringkasan aktivitas Sales berdasarkan periode dan filter yang dipilih &middot; {{ $periodLabel }}</p>
            </div>
            @php
                $reportFileName = 'Laporan Performa Aktivitas Sales';
                if ($fromDate && $toDate) {
                    $reportFileName .= ' ' . \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($toDate)->format('d M Y');
                }
            @endphp
            @include('reports.partials.export-buttons', [
                'exportType' => 'sales-activities',
                'exportFilename' => $reportFileName,
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="mb-6">@include('reports.partials.back')</div>

        <div id="sw-filter-card" class="sw-report-filter sw-report-filter-sales-activities bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.sales-activities') }}" class="m-0 p-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
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
                            @foreach($salesFilterUsers as $u)
                                <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-3 flex items-center gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center flex-1 min-h-[3rem] px-4 py-3 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                            Filter
                        </button>
                        @if(request()->anyFilled(['from_date', 'to_date', 'user_id', 'period']))
                        <a href="{{ route('admin.reports.sales-activities') }}"
                            class="inline-flex items-center justify-center flex-1 min-h-[3rem] px-4 py-3 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition text-center">
                            Reset
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- KPI Cards - Aktivitas Operasional & Keuangan --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Sales</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $summary['total_sales'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Presensi</p>
                <p class="text-xl font-bold text-[#0DA4CE]">{{ $summary['total_attendance'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Rencana</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $summary['total_routes'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Kunjungan</p>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $summary['total_visits'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Nilai Transaksi Baru</p>
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($summary['total_new_tx_amount'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Uang Masuk Transaksi Baru</p>
                <p class="text-xl font-bold text-teal-600 dark:text-teal-400">Rp {{ number_format($summary['total_new_tx_paid'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Pembayaran Piutang Lama</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">Rp {{ number_format($summary['total_old_debt_paid'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Uang Masuk</p>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($summary['total_cash_in'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabel Performa Sales --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-10">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Petugas Sales</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Presensi</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rencana</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rencana Selesai</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Completion</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kunjungan</th>
                            <th class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kunjungan Selesai</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nilai Transaksi Baru</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Uang Masuk Tx Baru</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Bayar Piutang Lama</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Total Uang Masuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($performance as $index => $p)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-3 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-xs font-bold text-[#0DA4CE]">
                                        {{ strtoupper(substr($p['name'] ?? 'S', 0, 2)) }}
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $p['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300">{{ $p['attendance'] }}</td>
                            <td class="px-3 py-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300">{{ $p['routes'] }}</td>
                            <td class="px-3 py-3 text-center text-sm font-semibold text-[#0DA4CE]">{{ $p['completed_routes'] }}</td>
                            <td class="px-3 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div class="h-full bg-[#0DA4CE] rounded-full" style="width: {{ min($p['completion'], 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 font-mono">{{ $p['completion'] }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300">{{ $p['visits'] }}</td>
                            <td class="px-3 py-3 text-center text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $p['completed_visits'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400">
                                Rp {{ number_format($p['new_tx_amount'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm font-bold text-teal-600 dark:text-teal-400">
                                Rp {{ number_format($p['new_tx_paid'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm font-bold text-amber-600 dark:text-amber-400">
                                Rp {{ number_format($p['old_debt_paid'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($p['total_cash_in'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="12" class="px-6 py-14 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data aktivitas sales pada periode ini.</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50/80 dark:bg-gray-800/60 font-bold text-xs">
                            <td colspan="2" class="px-4 py-3 text-right text-gray-900 dark:text-white uppercase">Total:</td>
                            <td class="px-3 py-3 text-center text-[#0DA4CE]">{{ $summary['total_attendance'] }}</td>
                            <td class="px-3 py-3 text-center text-amber-600">{{ $summary['total_routes'] }}</td>
                            <td class="px-3 py-3 text-center text-[#0DA4CE]">{{ $summary['total_completed_routes'] }}</td>
                            <td class="px-3 py-3 text-center text-gray-700 dark:text-gray-300">
                                {{ $summary['avg_completion'] }}%
                            </td>
                            <td class="px-3 py-3 text-center text-gray-700 dark:text-gray-300">{{ $summary['total_visits'] }}</td>
                            <td class="px-3 py-3 text-center text-emerald-600">{{ $summary['total_completed_visits'] }}</td>
                            <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                Rp {{ number_format($summary['total_new_tx_amount'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-teal-600 dark:text-teal-400 whitespace-nowrap">
                                Rp {{ number_format($summary['total_new_tx_paid'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                Rp {{ number_format($summary['total_old_debt_paid'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                Rp {{ number_format($summary['total_cash_in'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
