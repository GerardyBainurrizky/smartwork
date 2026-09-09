<x-app-layout>
    @section('breadcrumbs', 'Laporan Presensi')

    <x-slot name="header">
        <x-page-header icon="clipboard-list" title="Laporan Presensi"
            subtitle="Riwayat dan laporan presensi pribadi Anda. Data yang ditampilkan hanya milik Anda."></x-page-header>
    </x-slot>

    <style>
        @media (max-width: 639px) {
            .sw-date-input {
                font-size: 16px !important;
                height: 2.75rem;
                appearance: none;
                -webkit-appearance: none;
                text-align: left;
            }
            .sw-date-input::-webkit-date-and-time-value {
                display: block;
            }
            .sw-date-input::-webkit-calendar-picker-indicator {
                opacity: 0.6;
            }
            .sw-select {
                font-size: 16px !important;
                height: 2.75rem;
            }
        }
        .dark .sw-date-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }
    </style>

    @php
        $activeFilters = request()->hasAny(['from_date', 'to_date', 'status']);
    @endphp

    <div class="max-w-7xl mx-auto space-y-5">
        {{-- Filter Laporan --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sm:p-6">
            <form method="GET" action="{{ route('staff.report.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    <div>
                        <label for="sw-from-date" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Dari</label>
                        <input id="sw-from-date" type="date" name="from_date" value="{{ $fromDate }}" max="{{ $toDate }}"
                            class="sw-date-input w-full border-2 border-gray-200 dark:border-gray-700 rounded-xl text-base sm:text-sm px-3 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                    <div>
                        <label for="sw-to-date" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Sampai</label>
                        <input id="sw-to-date" type="date" name="to_date" value="{{ $toDate }}" min="{{ $fromDate }}"
                            class="sw-date-input w-full border-2 border-gray-200 dark:border-gray-700 rounded-xl text-base sm:text-sm px-3 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                    <div>
                        <label for="sw-status" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status</label>
                        <select id="sw-status" name="status"
                            class="sw-select w-full border-2 border-gray-200 dark:border-gray-700 rounded-xl text-base sm:text-sm px-3 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Status</option>
                            <option value="checked_in" {{ $status === 'checked_in' ? 'selected' : '' }}>Check In</option>
                            <option value="checked_out" {{ $status === 'checked_out' ? 'selected' : '' }}>Selesai</option>
                            <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <button type="submit"
                            class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-3 bg-[#0DA4CE] text-white rounded-xl text-sm font-bold shadow-md shadow-[#0DA4CE]/20 hover:bg-[#097A99] hover:shadow-lg transition">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter
                        </button>
                        @if($activeFilters)
                        <a href="{{ route('staff.report.index') }}"
                            class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Reset
                        </a>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <button type="submit" formaction="{{ route('staff.report.pdf') }}"
                            class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-3 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 rounded-xl text-sm font-bold hover:bg-red-100 dark:hover:bg-red-900/40 transition shadow-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Export PDF
                        </button>
                        <button type="submit" formaction="{{ route('staff.report.excel') }}"
                            class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-3 bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 rounded-xl text-sm font-bold hover:bg-green-100 dark:hover:bg-green-900/40 transition shadow-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Presensi</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Selesai</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['checked_out'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Berlangsung</p>
                <p class="text-xl font-bold text-[#0DA4CE] mt-1">{{ $stats['checked_in'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Dibatalkan</p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['canceled'] }}</p>
            </div>
        </div>

        {{-- Data Presensi --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Riwayat Presensi</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $periodLabel }}</span>
            </div>

            @if($attendances->isEmpty())
                <div class="px-5 py-14 text-center">
                    <svg class="w-14 h-14 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data presensi pada periode ini.</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Ubah rentang tanggal atau lakukan presensi terlebih dahulu.</p>
                </div>
            @else
                {{-- Mobile: kartu --}}
                <div class="sm:hidden divide-y divide-gray-50 dark:divide-gray-800/60">
                    @foreach($attendances as $att)
                    @php
                        $attDuration = '-';
                        if ($att->clock_in && $att->clock_out) {
                            $attMinutes = max(0, (int) $att->clock_in->diffInMinutes($att->clock_out));
                            $attDuration = $attMinutes >= 60
                                ? ((intdiv($attMinutes, 60)) . 'j ' . ($attMinutes % 60) . 'm')
                                : $attMinutes . 'm';
                        }
                    @endphp
                    <div class="px-4 py-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $att->date->format('d M Y') }}</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0
                                @if($att->is_canceled) bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400
                                @elseif($att->clock_out !== null) bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-400
                                @elseif($att->clock_in !== null) bg-[#0DA4CE]/10 text-[#0DA4CE]
                                @else bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400
                                @endif">
                                {{ $att->status_presensi_label_staff }}
                            </span>
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>Masuk <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $att->clock_in?->format('H:i') ?? '-' }}</span></span>
                            <span>·</span>
                            <span>Keluar <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $att->clock_out?->format('H:i') ?? '-' }}</span></span>
                            <span>·</span>
                            <span>Durasi <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $attDuration }}</span></span>
                        </div>
                        @if($att->clock_in_address)
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 truncate">{{ $att->clock_in_address }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Desktop/Tablet: tabel --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Masuk</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Keluar</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Durasi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Lokasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                            @foreach($attendances as $att)
                            @php
                                $attDuration = '-';
                                if ($att->clock_in && $att->clock_out) {
                                    $attMinutes = max(0, (int) $att->clock_in->diffInMinutes($att->clock_out));
                                    $attDuration = $attMinutes >= 60
                                        ? ((intdiv($attMinutes, 60)) . 'j ' . ($attMinutes % 60) . 'm')
                                        : $attMinutes . 'm';
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $att->date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-center whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $att->clock_in?->format('H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-center whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $att->clock_out?->format('H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-center whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $attDuration }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($att->is_canceled) bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400
                                        @elseif($att->clock_out !== null) bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-400
                                        @elseif($att->clock_in !== null) bg-[#0DA4CE]/10 text-[#0DA4CE]
                                        @else bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400
                                        @endif">
                                        {{ $att->status_presensi_label_staff }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[220px]">{{ $att->clock_in_address ?? $att->clock_out_address ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($attendances->total() > 0)
        <div class="pt-1">{{ $attendances->links('vendor.pagination.staff-report') }}</div>
        @endif
    </div>
</x-app-layout>