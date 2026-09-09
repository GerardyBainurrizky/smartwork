<x-app-layout>
    @php
        $isDriver = Auth::user()->hasRole('driver');
    @endphp
    <x-slot name="header">
        <x-page-header icon="history" :title="$isDriver ? 'Riwayat Rute Pengiriman' : 'Riwayat Rute'" :subtitle="$isDriver ? 'Riwayat seluruh rute pengiriman yang Anda buat' : 'Riwayat seluruh rute perjalanan yang Anda buat'"></x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        {{-- Stats Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Rute</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $totalRoutes ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#90F022]/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selesai</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $completedRoutes ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $isDriver ? 'Total Tujuan' : 'Total Toko' }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $totalStoresVisited ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-[#097A99]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#097A99] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Bulan Ini</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $routesThisMonth ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 mb-6">
            <form method="GET" action="{{ route('route.history') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dari Tanggal</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="sw-input">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Sampai Tanggal</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="sw-input">
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select name="status" class="sw-input">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Menunggu</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Sedang Berjalan</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                </div>
                @if(request()->anyFilled(['from_date', 'to_date', 'status']))
                <div>
                    <a href="{{ route('route.history') }}"
                        class="inline-flex items-center px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset
                    </a>
                </div>
                @endif
            </form>
        </div>

        {{-- Mobile: Card Layout --}}
        <div class="sm:hidden space-y-4">
            @forelse($routes ?? [] as $route)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="min-w-0">
                            <a href="{{ route('route.show', $route->id) }}" class="font-semibold text-gray-900 dark:text-gray-100 hover:text-[#0DA4CE] transition line-clamp-1">
                                {{ $route->name }}
                            </a>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ \Carbon\Carbon::parse($route->date)->isoFormat('dddd, D MMMM YYYY') }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0
                            @if($route->computed_status === 'draft') bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200
                            @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                            @elseif($route->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                            @else bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400
                            @endif">
                            @if($route->computed_status === 'draft') Menunggu
                            @elseif($route->computed_status === 'active') Sedang Berjalan
                            @elseif($route->computed_status === 'completed') Selesai
                            @else Dibatalkan
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ $route->stops_count ?? $route->stops->count() }} {{ $isDriver ? 'tujuan' : 'toko' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @if($route->started_at && $route->completed_at)
                                @php
                                    $_durMin = max(0, (int) \Carbon\Carbon::parse($route->started_at)->diffInMinutes(\Carbon\Carbon::parse($route->completed_at)));
                                    $_durH = intdiv($_durMin, 60);
                                    $_durM = $_durMin % 60;
                                    $_durLabel = $_durH > 0 ? ($_durM > 0 ? $_durH . ' Jam ' . $_durM . ' Menit' : $_durH . ' Jam') : $_durMin . ' Menit';
                                @endphp
                                {{ $_durLabel }}
                            @else
                                -
                            @endif
                        </span>
                    </div>

                    @if($route->stops->isNotEmpty())
                    <div class="flex flex-wrap gap-1">
                        @foreach($route->stops->take(4) as $stop)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs
                            @if($stop->status === 'visited') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                            @elseif($stop->status === 'skipped') bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400
                            @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                            @endif">
                            {{ $stop->store->name ?? 'Toko' }}
                        </span>
                        @endforeach
                        @if($route->stops->count() > 4)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs bg-gray-100 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500">
                            +{{ $route->stops->count() - 4 }}
                        </span>
                        @endif
                    </div>
                    @endif

                    <a href="{{ route('route.show', $route->id) }}"
                        class="inline-flex items-center justify-center w-full mt-4 px-4 py-2 bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded-xl text-sm font-semibold hover:bg-[#0DA4CE]/20 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Lihat Detail
                    </a>
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat rute</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop: Table Layout --}}
        <div class="hidden sm:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60">
                    <thead class="bg-gray-50 dark:bg-gray-800/40">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rute Wilayah</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $isDriver ? 'Tujuan' : 'Toko' }}</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durasi</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                        @forelse($routes ?? [] as $route)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-4">
                                <a href="{{ route('route.show', $route->id) }}" class="text-sm font-medium text-[#0DA4CE] dark:text-[#5BD8F7] hover:text-[#097A99] transition">
                                    {{ $route->name }}
                                </a>
                                @if($route->notes)
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-[200px]">{{ $route->notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($route->date)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                        {{ $route->stops_count ?? $route->stops->count() }} {{ $isDriver ? 'tujuan' : 'toko' }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        ({{ $route->stops->where('status', 'visited')->count() }} {{ $isDriver ? 'selesai' : 'dikunjungi' }})
                                    </span>
                                </div>
                            </td>
<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
    @if($route->started_at && $route->completed_at)
        @php
            $_durMin = max(0, (int) \Carbon\Carbon::parse($route->started_at)->diffInMinutes(\Carbon\Carbon::parse($route->completed_at)));
            $_durH = intdiv($_durMin, 60);
            $_durM = $_durMin % 60;
            $_durLabel = $_durH > 0 ? ($_durM > 0 ? $_durH . ' Jam ' . $_durM . ' Menit' : $_durH . ' Jam') : $_durMin . ' Menit';
        @endphp
        {{ $_durLabel }}
    @else
        -
    @endif
</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($route->computed_status === 'draft') bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200
                                    @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                    @elseif($route->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                    @else bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400
                                    @endif">
                                    @if($route->computed_status === 'draft') Menunggu
                                    @elseif($route->computed_status === 'active') Sedang Berjalan
                                    @elseif($route->computed_status === 'completed') Selesai
                                    @else Dibatalkan
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('route.show', $route->id) }}"
                                    class="inline-flex items-center px-3 py-1.5 bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] rounded-lg text-xs font-semibold hover:bg-[#0DA4CE]/20 transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat rute</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if(isset($routes) && $routes->hasPages())
        <div class="mt-6">
            {{ $routes->links() }}
        </div>
        @endif
    </div>
</x-app-layout>