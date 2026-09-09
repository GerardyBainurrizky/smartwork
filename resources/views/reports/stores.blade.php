<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Master Toko') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Semua Periode</p>
            </div>
            @php
                $reportFileName = 'Laporan Master Toko';
                if ($city) {
                    $reportFileName .= ' ' . $city;
                } elseif ($province) {
                    $reportFileName .= ' ' . $province;
                }
            @endphp
            @include('reports.partials.export-buttons', [
                'exportType' => 'stores',
                'exportFilename' => $reportFileName,
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="mb-6">@include('reports.partials.back')</div>

        <div id="sw-filter-card" class="sw-report-filter sw-report-filter-stores bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.stores') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cari Toko</label>
                        <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Nama toko, pemilik, kode, atau alamat..."
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Sales Penanggung Jawab</label>
                        <select name="sales_id" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Sales</option>
                            <option value="unassigned" {{ request('sales_id') === 'unassigned' ? 'selected' : '' }}>Belum Ada Sales</option>
                            @foreach($salesUsers as $s)
                                <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Provinsi</label>
                        <select name="province" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Provinsi</option>
                            @foreach($provinces as $p)
                                <option value="{{ $p }}" {{ request('province') === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Kabupaten/Kota</label>
                        <select name="city" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Kota</option>
                            @foreach($cities as $c)
                                <option value="{{ $c }}" {{ request('city') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Kecamatan</label>
                        <select name="kecamatan" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Kecamatan</option>
                            @foreach($kecamatans as $k)
                                <option value="{{ $k }}" {{ request('kecamatan') === $k ? 'selected' : '' }}>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status</label>
                        <select name="status" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-2 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3">
                    <button type="submit"
                        class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'province', 'city', 'kecamatan', 'status', 'sales_id']))
                    <a href="{{ route('admin.reports.stores') }}"
                        class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Toko</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stores->total() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Dengan Koordinat</p>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stores->filter(fn ($s) => $s->latitude && $s->longitude)->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Kota</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $stores->pluck('city')->filter()->unique()->count() }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kode</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama Toko</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sales Penanggung Jawab</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Alamat Lengkap</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kecamatan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kab/Kota</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Provinsi</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Koordinat</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($stores as $index => $store)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ $stores->firstItem() + $index }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-bold text-[#0DA4CE]">{{ $store->code ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $store->name }}</p>
                                @if($store->owner)
                                    <p class="text-xs text-gray-400 dark:text-gray-500">Pemilik: {{ $store->owner }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $store->salesPenanggungJawab?->name ?? 'Belum Ada Sales' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $store->address ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $store->kecamatan ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $store->city ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $store->province ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-xs">
                                @if($store->latitude && $store->longitude)
                                    <a href="{{ $store->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-[#0DA4CE] hover:underline font-medium">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ round((float)$store->latitude, 4) }}, {{ round((float)$store->longitude, 4) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                @if($store->status === 'active' && !$store->trashed())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200 text-[11px]">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-red-50 text-red-600 border border-red-200 text-[11px]">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $store->created_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="px-6 py-14 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-2M9 7h2m-2 4h2m-2 4h2"/></svg>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data toko</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $stores->links('vendor.pagination.reports') }}
    </div>
</x-app-layout>