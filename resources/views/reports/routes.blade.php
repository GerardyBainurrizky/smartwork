<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Rencana Kunjungan Sales') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $periodLabel }}</p>
            </div>
            @include('reports.partials.export-buttons', [
                'exportType' => 'routes',
                'exportFilename' => 'Laporan Rencana Kunjungan Sales',
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="mb-6">@include('reports.partials.back')</div>

        <div id="sw-filter-card" class="sw-report-filter bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.routes') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cari Rencana</label>
                        <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Nama rute, sales, toko..."
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
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Toko / Tujuan</label>
                        <select name="store_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Toko</option>
                            @foreach($storesList ?? [] as $st)
                                <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status Rute</label>
                        <select name="status" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Sedang Berjalan</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2 flex flex-col sm:flex-row sm:items-end justify-end gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                            Filter
                        </button>
                        @if(request()->anyFilled(['from_date', 'to_date', 'user_id', 'store_id', 'status', 'search']))
                        <a href="{{ route('admin.reports.routes') }}"
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

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Rencana</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Selesai</p>
                <p class="text-xl font-bold text-green-700 dark:text-green-400">{{ $stats['completed'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Berjalan</p>
                <p class="text-xl font-bold text-[#0DA4CE]">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Draft</p>
                <p class="text-xl font-bold text-gray-400">{{ $stats['draft'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Target Toko</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['total_stops'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Toko Dikunjungi</p>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['visited_stops'] }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rute Wilayah</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sales Penanggung Jawab</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Target Toko</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Dikunjungi</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Progress</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status Rute</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Daftar Toko / Urutan</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Durasi</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($routes as $index => $route)
                        @php
                            $totalStops = $route->stops_count ?? $route->stops->count();
                            $visitedStops = $route->visited_stops_count ?? $route->stops->where('status', 'visited')->count();
                            $progress = $totalStops > 0 ? round(($visitedStops / $totalStops) * 100) : 0;
                            $routeDur = '-';
                            if ($route->started_at && $route->completed_at) {
                                $routeMin = max(0, (int) $route->started_at->diffInMinutes($route->completed_at));
                                $routeDur = $routeMin >= 60 ? ((intdiv($routeMin, 60)) . 'j ' . ($routeMin % 60) . 'm') : $routeMin . 'm';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 align-top">
                            <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ $routes->firstItem() + $index }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $route->name }}</p>
                                @if($route->code)
                                    <p class="text-xs font-mono text-[#0DA4CE]">{{ $route->code }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">{{ $route->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400 font-medium">{{ $route->date?->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $totalStops }}</td>
                            <td class="px-4 py-3 text-center text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $visitedStops }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div class="h-full bg-[#0DA4CE] rounded-full" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $progress }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($route->computed_status === 'completed') bg-[#90F022]/20 text-green-800 dark:text-green-300
                                    @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 text-[#0DA4CE]
                                    @else bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 @endif">
                                    {{ match($route->computed_status) { 'completed' => 'Selesai', 'active' => 'Berjalan', default => 'Draft' } }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs max-w-[240px] space-y-1">
                                @forelse($route->stops as $sIdx => $stop)
                                    <div class="flex items-start gap-1">
                                        <span class="font-mono text-gray-400 shrink-0">#{{ $stop->sequence }}.</span>
                                        <span class="text-gray-800 dark:text-gray-200 font-medium truncate">{{ $stop->store->name ?? '-' }}</span>
                                        @if($stop->status === 'visited')
                                            <span class="text-emerald-600 dark:text-emerald-400 text-[10px] shrink-0">✓</span>
                                        @elseif($stop->status === 'skipped')
                                            <span class="text-red-500 text-[10px] shrink-0">✕</span>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-gray-400">-</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-gray-700 dark:text-gray-300">{{ $routeDur }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 max-w-[180px] line-clamp-2">{{ $route->notes ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="px-6 py-14 text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data rencana kunjungan</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $routes->links('vendor.pagination.reports') }}
    </div>
</x-app-layout>
