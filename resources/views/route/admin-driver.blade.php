<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="truck" title="Rencana Pengiriman" subtitle="Buat, pantau, dan kelola seluruh rute pengiriman Driver">
            <a href="{{ route('admin.driver.routes.create') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-[#0b8fb5] focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] focus:ring-offset-2 transition-all duration-200 shadow-sm shadow-[#0DA4CE]/20">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Rencana Pengiriman
            </a>
        </x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto" x-data="adminRouteDelete()">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center">
                    <div class="w-11 h-11 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Rute</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center">
                    <div class="w-11 h-11 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aktif</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center">
                    <div class="w-11 h-11 rounded-xl bg-[#90F022]/20 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Selesai</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['completed'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        @php
            $filterInputClass = 'w-full border-2 border-gray-300 dark:border-gray-700 rounded-lg px-3 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 transition focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/30';
        @endphp
        <div id="sw-route-filter-card" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 mb-6">
            <form method="GET" action="{{ route('admin.driver.routes.index') }}" class="w-full grid grid-cols-2 gap-3 lg:flex lg:flex-row lg:flex-wrap lg:items-end lg:gap-3">
                <div class="sw-route-field w-full min-w-0 lg:flex-1 lg:min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}" class="{{ $filterInputClass }}">
                </div>
                <div class="sw-route-field w-full min-w-0 lg:flex-1 lg:min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Driver</label>
                    <select name="user_id" class="{{ $filterInputClass }}">
                        <option value="">Semua Driver</option>
                        @foreach($users ?? [] as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sw-route-field w-full min-w-0 lg:flex-1 lg:min-w-[140px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status Pengiriman</label>
                    <select name="status" class="{{ $filterInputClass }}">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
                <div class="sw-route-field flex flex-col items-stretch gap-2 w-full min-w-0 pt-5 lg:pt-0 lg:w-auto lg:flex-row lg:items-center lg:gap-3">
                    <button type="submit" class="inline-flex items-center justify-center w-full lg:w-auto px-5 py-3 lg:py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    @if(request()->anyFilled(['date', 'user_id', 'status']))
                    <a href="{{ route('admin.driver.routes.index') }}" class="inline-flex items-center justify-center w-full lg:w-auto px-5 py-3 lg:py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="sm:hidden space-y-4">
            @forelse($routes ?? [] as $route)
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-6 h-6 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-[10px] font-bold text-[#0DA4CE] shrink-0">
                                    {{ strtoupper(substr($route->user->name ?? 'D', 0, 2)) }}
                                </div>
                                <p class="text-xs text-gray-400 truncate">{{ $route->user->name ?? 'Driver' }}</p>
                            </div>
                            <a href="{{ route('route.show', $route->id) }}" class="font-semibold text-gray-900 dark:text-white hover:text-[#0DA4CE] transition line-clamp-1">
                                {{ $route->name }}
                            </a>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0 @if($route->computed_status === 'draft') bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 text-[#0DA4CE] @elseif($route->computed_status === 'completed') bg-[#90F022]/20 text-green-800 dark:text-green-300 @else bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 @endif">
                            @if($route->computed_status === 'draft') Menunggu @elseif($route->computed_status === 'active') Sedang Berjalan @elseif($route->computed_status === 'completed') Selesai @else Dibatalkan @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ \Carbon\Carbon::parse($route->date)->format('d M Y') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            {{ $route->progress['total'] ?? 0 }} tujuan
                        </span>
                    </div>
                    @php $p = $route->progress ?? ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'belum' => 0, 'dilewati' => 0]; @endphp
                    @if(($p['total'] ?? 0) > 0)
                    <div class="mb-3">
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex items-center gap-1 text-green-700 dark:text-green-400"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ $p['selesai'] }} Selesai</span>
                            <span class="inline-flex items-center gap-1 text-[#0DA4CE]"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>{{ $p['berjalan'] }} Berjalan</span>
                            <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ $p['belum'] }} Belum</span>
                            @if(($p['dilewati'] ?? 0) > 0)<span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>{{ $p['dilewati'] }} Dilewati</span>@endif
                        </div>
                        <div class="mt-2 h-1.5 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-[#90F022] rounded-full transition-all" style="width: {{ round((($p['selesai'] + $p['dilewati']) / max($p['total'], 1)) * 100) }}%"></div>
                        </div>
                    </div>
                    @endif
                    <a href="{{ route('route.show', $route->id) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-[#0DA4CE]/10 text-[#0DA4CE] rounded-xl text-sm font-semibold hover:bg-[#0DA4CE]/20 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        Lihat Detail
                    </a>
                    @if(in_array($route->status, ['draft', 'cancelled']))
                    <div class="flex gap-2 mt-2">
                        <a href="{{ route('admin.driver.routes.edit', $route->id) }}" class="inline-flex items-center justify-center flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">Edit</a>
                        <button type="button" @click="openDelete({ name: @js($route->name), url: @js(route('admin.driver.routes.destroy', $route->id)) })" class="inline-flex items-center justify-center px-4 py-2 bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-xl text-sm font-semibold hover:bg-red-100 dark:hover:bg-red-900 transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Hapus
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada rute pengiriman yang ditemukan</p>
            </div>
            @endforelse
        </div>

        <div class="hidden sm:block bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rute Pengiriman</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tujuan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Pengiriman</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($routes ?? [] as $route)
                        @php $p = $route->progress ?? ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'belum' => 0, 'dilewati' => 0]; @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-4 whitespace-nowrap"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-xs font-bold text-[#0DA4CE]">{{ strtoupper(substr($route->user->name ?? 'D', 0, 2)) }}</div><div><p class="text-sm font-medium text-gray-900 dark:text-white">{{ $route->user->name ?? '-' }}</p></div></div></td>
                            <td class="px-4 py-4"><a href="{{ route('route.show', $route->id) }}" class="text-sm font-medium text-[#0DA4CE] hover:text-[#097A99] transition">{{ $route->name }}</a></td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($route->date)->format('d M Y') }}</td>
                            <td class="px-4 py-4"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">{{ $p['total'] ?? 0 }} tujuan</span></td>
                            <td class="px-4 py-4">@if(($p['total'] ?? 0) > 0)<div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap"><span class="text-green-700 dark:text-green-400">{{ $p['selesai'] }} selesai</span> &middot; <span class="text-[#0DA4CE]">{{ $p['berjalan'] }} berjalan</span> &middot; <span>{{ $p['belum'] }} belum</span>@if(($p['dilewati'] ?? 0) > 0) &middot; <span class="text-red-600 dark:text-red-400">{{ $p['dilewati'] }} dilewati</span>@endif</div><div class="mt-1.5 h-1.5 w-32 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden"><div class="h-full bg-[#90F022] rounded-full transition-all" style="width: {{ round((($p['selesai'] + $p['dilewati']) / max($p['total'], 1)) * 100) }}%"></div></div>@endif</td>
                            <td class="px-4 py-4 whitespace-nowrap"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($route->computed_status === 'draft') bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 text-[#0DA4CE] @elseif($route->computed_status === 'completed') bg-[#90F022]/20 text-green-800 dark:text-green-300 @else bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 @endif">@if($route->computed_status === 'draft') Menunggu @elseif($route->computed_status === 'active') Sedang Berjalan @elseif($route->computed_status === 'completed') Selesai @else Dibatalkan @endif</span></td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('route.show', $route->id) }}" class="inline-flex items-center px-3 py-1.5 bg-[#0DA4CE]/10 text-[#0DA4CE] rounded-lg text-xs font-semibold hover:bg-[#0DA4CE]/20 transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Detail
                                    </a>
                                    @if(in_array($route->status, ['draft', 'cancelled']))
                                    <a href="{{ route('admin.driver.routes.edit', $route->id) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">Edit</a>
                                    <button type="button" @click="openDelete({ name: @js($route->name), url: @js(route('admin.driver.routes.destroy', $route->id)) })" class="inline-flex items-center px-3 py-1.5 bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg text-xs font-semibold hover:bg-red-100 dark:hover:bg-red-900 transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Hapus
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-6 py-16 text-center"><svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada rute pengiriman yang ditemukan</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal Konfirmasi Hapus Rencana Pengiriman --}}
        <div x-show="deleteRoute" x-cloak x-transition.opacity
            class="fixed inset-0 z-[60] bg-gray-900/50 dark:bg-gray-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="deleteRoute = null" @keydown.escape.window="deleteRoute = null">
            <div x-show="deleteRoute" x-transition.scale.origin.center
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Rencana Pengiriman</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Apakah Anda yakin ingin menghapus rencana pengiriman ini? Seluruh data rencana pengiriman akan dihapus dari Admin dan Driver yang dituju.</p>
                        </div>
                        <button type="button" @click="deleteRoute = null"
                            class="p-1.5 -m-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center text-[11px] font-bold text-white shrink-0">R</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="deleteRoute ? deleteRoute.name : ''"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button" @click="deleteRoute = null"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Batal
                        </button>
                        <button type="button" @click="confirmDelete()" :disabled="deleting"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition shadow-sm shadow-red-600/20 disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg x-show="deleting" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!deleting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function adminRouteDelete() {
                return {
                    deleteRoute: null,
                    deleting: false,
                    openDelete(route) {
                        this.deleteRoute = route;
                    },
                    async confirmDelete() {
                        if (!this.deleteRoute || this.deleting) return;
                        this.deleting = true;
                        const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                        const body = new FormData();
                        body.append('_method', 'DELETE');
                        body.append('_token', token);
                        try {
                            const res = await fetch(this.deleteRoute.url, {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                body
                            });
                            const data = await res.json().catch(() => ({}));
                            if (res.ok && data.status === 'success') {
                                window.showToast(data.message || 'Rencana pengiriman berhasil dihapus.', 'success');
                                this.deleteRoute = null;
                                setTimeout(() => window.location.reload(), 600);
                            } else {
                                window.showToast(data.message || 'Terjadi kesalahan saat menghapus rencana pengiriman.', 'error');
                                this.deleteRoute = null;
                            }
                        } catch (e) {
                            window.showToast('Terjadi kesalahan saat menghapus rencana pengiriman.', 'error');
                            this.deleteRoute = null;
                        } finally {
                            this.deleting = false;
                        }
                    }
                };
            }
        </script>

        {{ $routes->links('vendor.pagination.reports') }}
    </div>
</x-app-layout>
