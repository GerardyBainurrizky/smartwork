<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="package" title="Pengiriman" subtitle="Pantau aktivitas pengiriman khusus Driver"></x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5"><div class="flex items-center gap-3.5 sm:gap-4"><div class="w-11 h-11 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div><div class="min-w-0"><p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['total'] ?? 0 }}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 leading-snug">Total Pengiriman</p></div></div></div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5"><div class="flex items-center gap-3.5 sm:gap-4"><div class="w-11 h-11 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="min-w-0"><p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['completed'] ?? 0 }}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 leading-snug">Selesai</p></div></div></div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5"><div class="flex items-center gap-3.5 sm:gap-4"><div class="w-11 h-11 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div><div class="min-w-0"><p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['in_progress'] ?? 0 }}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 leading-snug">Sedang Berjalan</p></div></div></div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5"><div class="flex items-center gap-3.5 sm:gap-4"><div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="min-w-0"><p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['pending'] ?? 0 }}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 leading-snug">Belum Dikirim</p></div></div></div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5 col-span-2 sm:col-span-1"><div class="flex items-center gap-3.5 sm:gap-4"><div class="w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg></div><div class="min-w-0"><p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['skipped'] ?? 0 }}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 leading-snug">Dilewati</p></div></div></div>
        </div>

        @php $filterInputClass = 'w-full border-2 border-gray-300 dark:border-gray-700 rounded-lg px-3 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 transition focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/30'; @endphp
        <div id="sw-visit-filter-card" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 mb-6">
            <form method="GET" action="{{ route('admin.driver.visits.index') }}" class="w-full grid grid-cols-2 gap-3 lg:flex lg:flex-row lg:flex-wrap lg:items-end lg:gap-3">
                <div class="sw-visit-field w-full min-w-0 lg:flex-1 lg:min-w-[150px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal</label><input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}" class="{{ $filterInputClass }}"></div>
                <div class="sw-visit-field w-full min-w-0 lg:flex-1 lg:min-w-[150px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Driver</label><select name="user_id" class="{{ $filterInputClass }}"><option value="">Semua Driver</option>@foreach($users ?? [] as $user)<option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select></div>
                <div class="sw-visit-field w-full min-w-0 lg:flex-1 lg:min-w-[140px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status Pengiriman</label><select name="status" class="{{ $filterInputClass }}"><option value="">Semua Status</option><option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Belum Dikirim</option><option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Sedang Berjalan</option><option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option><option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Dilewati</option></select></div>
                <div class="sw-visit-field flex flex-col items-stretch gap-2 w-full min-w-0 pt-5 lg:pt-0 lg:w-auto lg:flex-row lg:items-center lg:gap-3"><button type="submit" class="inline-flex items-center justify-center w-full lg:w-auto px-5 py-3 lg:py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>Filter</button>@if(request()->anyFilled(['date', 'user_id', 'status']))<a href="{{ route('admin.driver.visits.index') }}" class="inline-flex items-center justify-center w-full lg:w-auto px-5 py-3 lg:py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>Reset</a>@endif</div>
            </form>
        </div>

                <div class="sm:hidden space-y-4">
            @forelse($stops ?? [] as $stop)
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400">{{ $stop->route?->user?->name ?? 'Driver' }}</p>
                        <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $stop->store?->name ?? 'Tujuan' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $stop->route?->name ?? '-' }}</p>
                    </div>
                    @php $ms = $stop->monitor_status; @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0 @if($ms === 'completed') bg-[#90F022]/20 text-green-800 dark:text-green-300 @elseif($ms === 'in_progress') bg-[#0DA4CE]/10 text-[#0DA4CE] @elseif($ms === 'skipped') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 @else bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 @endif">@if($ms === 'completed') Selesai @elseif($ms === 'in_progress') Sedang Berjalan @elseif($ms === 'skipped') Dilewati @else Belum Dikirim @endif</span>
                </div>
                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <span class="inline-flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ $stop->visit?->check_in_at ? \Carbon\Carbon::parse($stop->visit->check_in_at)->format('H:i') : '-' }}</span>
                    <span class="inline-flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ $stop->visit?->check_out_at ? \Carbon\Carbon::parse($stop->visit->check_out_at)->format('H:i') : '-' }}</span>
                </div>
                @if($stop->visit)
                <a href="{{ route('visit.show', $stop->visit->id) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-[#0DA4CE]/10 text-[#0DA4CE] rounded-xl text-sm font-semibold hover:bg-[#0DA4CE]/20 transition">Lihat Detail</a>
                @endif
            </div>
            @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-12 text-center"><svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada pengiriman ditemukan</p></div>
            @endforelse
        </div>

        <div class="hidden sm:block bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Driver</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tujuan Pengiriman</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status Pengiriman</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Check In</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Check Out</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Dilewati</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Lokasi</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Waktu</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Progres Pengiriman</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($stops ?? [] as $stop)
                        @php $ms = $stop->monitor_status; @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-xs font-bold text-[#0DA4CE]">{{ strtoupper(substr($stop->route?->user?->name ?? 'D', 0, 2)) }}</div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $stop->route?->user?->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $stop->store?->name ?? 'Tujuan' }}</p>
                                <p class="text-xs text-gray-400 truncate max-w-[200px]">{{ $stop->store?->address ?? '' }}</p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($ms === 'completed') bg-[#90F022]/20 text-green-800 dark:text-green-300 @elseif($ms === 'in_progress') bg-[#0DA4CE]/10 text-[#0DA4CE] @elseif($ms === 'skipped') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 @else bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 @endif">@if($ms === 'completed') Selesai @elseif($ms === 'in_progress') Sedang Berjalan @elseif($ms === 'skipped') Dilewati @else Belum Dikirim @endif</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $stop->visit?->check_in_at ? \Carbon\Carbon::parse($stop->visit->check_in_at)->format('d M Y, H:i') : '-' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $stop->visit?->check_out_at ? \Carbon\Carbon::parse($stop->visit->check_out_at)->format('d M Y, H:i') : '-' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $stop->status === 'skipped' ? 'Ya' : '-' }}</td>
                            <td class="px-4 py-4"><p class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-[180px]">{{ $stop->visit?->check_in_address ?? '-' }}</p></td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $stop->visit?->check_in_at ? \Carbon\Carbon::parse($stop->visit->check_in_at)->format('H:i') : '-' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $stop->sequence }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                @if($stop->visit)
                                <a href="{{ route('visit.show', $stop->visit->id) }}" class="text-[#0DA4CE] hover:text-[#097A99] font-semibold text-sm">Detail</a>
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="px-6 py-16 text-center"><svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada pengiriman ditemukan</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $stops->links('vendor.pagination.reports') }}
    </div>
</x-app-layout>
