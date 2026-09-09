<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Rute Sales') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500">Total Rute</p>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['total_routes'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#90F022]/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500">Tingkat Penyelesaian</p>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['completion_rate'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#097A99]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#097A99]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500">Total Toko Dikunjungi</p>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['total_stores_visited'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500">Sales Aktif</p>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['active_sales'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <form method="GET" action="{{ route('route.report') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Periode</label>
                    <select name="period" class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0DA4CE] focus:ring-[#0DA4CE]">
                        <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="yesterday" {{ request('period') === 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                        <option value="this_week" {{ request('period', 'this_week') === 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="last_week" {{ request('period') === 'last_week' ? 'selected' : '' }}>Minggu Lalu</option>
                        <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                        <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>Kustom</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]" x-show="'{{ request('period') }}' === 'custom'" x-cloak>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Dari</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0DA4CE] focus:ring-[#0DA4CE]">
                </div>
                <div class="flex-1 min-w-[150px]" x-show="'{{ request('period') }}' === 'custom'" x-cloak>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sampai</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0DA4CE] focus:ring-[#0DA4CE]">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sales</label>
                    <select name="user_id" class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0DA4CE] focus:ring-[#0DA4CE]">
                        <option value="">Semua Sales</option>
                        @foreach($salesUsers ?? [] as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Tampilkan
                    </button>
                </div>
            </form>
        </div>

        {{-- Per-Sales Performance Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Performa per Sales</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sales</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Rute</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Selesai</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Toko</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Rata-rata Toko/Rute</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Completion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($perSales ?? [] as $item)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-xs font-bold text-[#0DA4CE]">
                                        {{ strtoupper(substr($item['name'] ?? 'S', 0, 2)) }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $item['name'] ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-900 font-medium">{{ $item['total_routes'] ?? 0 }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#90F022]/20 text-green-800">
                                    {{ $item['completed_routes'] ?? 0 }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-900">{{ $item['total_stores'] ?? 0 }}</td>
                            <td class="px-4 py-4 text-center text-sm text-gray-500">{{ number_format($item['avg_stores'] ?? 0, 1) }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-[#90F022] h-2 rounded-full" style="width: {{ $item['completion_percent'] ?? 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 font-medium">{{ $item['completion_percent'] ?? 0 }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-3 text-sm text-gray-500">Tidak ada data untuk periode ini</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Daily Route Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Rute Harian</h3>
            </div>
            <div class="p-5">
                <div class="space-y-3">
                    @forelse($dailyStats ?? [] as $day)
                    <div class="flex items-center gap-4">
                        <div class="w-24 text-xs text-gray-500 shrink-0">
                            {{ \Carbon\Carbon::parse($day['date'])->format('d M') }}
                        </div>
                        <div class="flex-1 flex items-center gap-2">
                            <div class="flex-1 bg-gray-200 rounded-full h-5 overflow-hidden">
                                <div class="bg-[#0DA4CE] h-5 rounded-full flex items-center justify-end px-2"
                                    style="width: {{ min(($day['total'] / max($maxDailyRoutes, 1)) * 100, 100) }}%">
                                    <span class="text-xs text-white font-semibold">{{ $day['total'] }}</span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 text-xs text-green-700">
                                    <span class="w-2 h-2 rounded-full bg-[#90F022]"></span>
                                    {{ $day['completed'] }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs text-[#0DA4CE]">
                                    <span class="w-2 h-2 rounded-full bg-[#0DA4CE]"></span>
                                    {{ $day['active'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <p class="text-sm text-gray-500">Tidak ada data rute harian</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>