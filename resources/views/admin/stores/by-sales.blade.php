<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="store" title="Detail Toko per Sales" subtitle="Daftar toko berdasarkan Sales Penanggung Jawab">
            <x-back-button href="{{ route('admin.stores.index') }}" label="Kembali ke Master Toko" />
        </x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <a href="{{ route('dashboard') }}" class="hover:text-[#0DA4CE] transition">Dashboard</a>
            <span>/</span>
            <a href="{{ route('admin.stores.index') }}" class="hover:text-[#0DA4CE] transition">Master Toko</a>
            <span>/</span>
            <span class="text-gray-900 dark:text-gray-200 font-semibold">Toko per Sales</span>
        </nav>

        {{-- Group per Sales --}}
        <div class="space-y-6">
            @forelse($salesUsers as $sales)
            @php
                $stores = $sales->assignedStores ?? collect();
            @endphp
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-xs overflow-hidden">
                {{-- Group Header --}}
                <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50 dark:bg-gray-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $sales->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sales Penanggung Jawab</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-[#0DA4CE]/10 text-[#0DA4CE] self-start sm:self-auto">
                        {{ $stores->count() }} Toko
                    </span>
                </div>

                {{-- Table (Desktop & Tablet) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-800">
                            <tr>
                                <th class="px-5 py-3.5 w-12 text-center">No</th>
                                <th class="px-5 py-3.5">Kode Toko</th>
                                <th class="px-5 py-3.5">Nama Toko</th>
                                <th class="px-5 py-3.5">Wilayah / Alamat</th>
                                <th class="px-5 py-3.5">Tujuan Pengiriman</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($stores as $idx => $store)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                <td class="px-5 py-3.5 text-center text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $idx + 1 }}</td>
                                <td class="px-5 py-3.5 font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $store->code ?? '-' }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $store->name }}</div>
                                    @if($store->owner)
                                    <div class="text-xs text-gray-400 dark:text-gray-500">Pemilik: {{ $store->owner }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                    <div>{{ collect([$store->city, $store->kecamatan, $store->province])->filter()->implode(', ') ?: '-' }}</div>
                                    @if($store->address)
                                    <div class="text-[11px] text-gray-400 dark:text-gray-500 truncate max-w-xs">{{ $store->address }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Dapat Dikirim
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.stores.show', $store->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0DA4CE] bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/40 rounded-xl hover:bg-cyan-100 dark:hover:bg-cyan-900/40 transition">
                                        Detail Toko
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-xs text-gray-400 dark:text-gray-500">
                                    Belum ada toko yang ditugaskan kepada {{ $sales->name }}.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile List Cards --}}
                <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($stores as $idx => $store)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <span class="font-mono text-[11px] text-gray-400 dark:text-gray-500">#{{ $idx + 1 }} · {{ $store->code ?? '-' }}</span>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ $store->name }}</h4>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                                Aktif
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ collect([$store->city, $store->kecamatan, $store->province])->filter()->implode(', ') ?: '-' }}
                        </div>
                        <div class="pt-1 flex items-center justify-between gap-2">
                            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">✓ Dapat Dikirim Driver</span>
                            <a href="{{ route('admin.stores.show', $store->id) }}" class="text-xs font-bold text-[#0DA4CE] hover:underline">
                                Detail &rarr;
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-center text-xs text-gray-400 dark:text-gray-500">
                        Belum ada toko yang ditugaskan kepada {{ $sales->name }}.
                    </div>
                    @endforelse
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-8 text-center text-gray-400 dark:text-gray-500 text-xs sm:text-sm">
                Tidak ada data Sales aktif.
            </div>
            @endforelse

            {{-- Group Toko Tanpa Sales --}}
            @if(isset($unassignedStores) && $unassignedStores->isNotEmpty())
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-amber-200/60 dark:border-amber-900/40 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-amber-200/60 dark:border-amber-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-amber-50/50 dark:bg-amber-950/20">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-amber-700 dark:text-amber-300">Belum Ada Sales</h3>
                            <p class="text-xs text-amber-600/80 dark:text-amber-400/80">Toko aktif yang belum memiliki Sales Penanggung Jawab</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 self-start sm:self-auto">
                        {{ $unassignedStores->count() }} Toko
                    </span>
                </div>

                {{-- Table Unassigned --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-800">
                            <tr>
                                <th class="px-5 py-3.5 w-12 text-center">No</th>
                                <th class="px-5 py-3.5">Kode Toko</th>
                                <th class="px-5 py-3.5">Nama Toko</th>
                                <th class="px-5 py-3.5">Wilayah / Alamat</th>
                                <th class="px-5 py-3.5">Tujuan Pengiriman</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($unassignedStores as $idx => $store)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                <td class="px-5 py-3.5 text-center text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $idx + 1 }}</td>
                                <td class="px-5 py-3.5 font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $store->code ?? '-' }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $store->name }}</div>
                                    @if($store->owner)
                                    <div class="text-xs text-gray-400 dark:text-gray-500">Pemilik: {{ $store->owner }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                    <div>{{ collect([$store->city, $store->kecamatan, $store->province])->filter()->implode(', ') ?: '-' }}</div>
                                    @if($store->address)
                                    <div class="text-[11px] text-gray-400 dark:text-gray-500 truncate max-w-xs">{{ $store->address }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    @if($store->is_delivery_destination)
                                    <span class="inline-flex items-center gap-1 font-semibold text-blue-600 dark:text-blue-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Dapat Dikirim
                                    </span>
                                    @else
                                    <span class="text-gray-400 dark:text-gray-500">— Bukan Pengiriman</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.stores.show', $store->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0DA4CE] bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/40 rounded-xl hover:bg-cyan-100 dark:hover:bg-cyan-900/40 transition">
                                        Detail Toko
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile List Unassigned --}}
                <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($unassignedStores as $idx => $store)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <span class="font-mono text-[11px] text-gray-400 dark:text-gray-500">#{{ $idx + 1 }} · {{ $store->code ?? '-' }}</span>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ $store->name }}</h4>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                                Aktif
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ collect([$store->city, $store->kecamatan, $store->province])->filter()->implode(', ') ?: '-' }}
                        </div>
                        <div class="pt-1 flex items-center justify-between gap-2">
                            <span class="text-[11px] font-semibold {{ $store->is_delivery_destination ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400' }}">
                                {{ $store->is_delivery_destination ? '✓ Dapat Dikirim Driver' : '— Bukan Pengiriman' }}
                            </span>
                            <a href="{{ route('admin.stores.show', $store->id) }}" class="text-xs font-bold text-[#0DA4CE] hover:underline">
                                Detail &rarr;
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Pagination Sales --}}
        @if($salesUsers->hasPages())
        <div class="px-2">
            {{ $salesUsers->links() }}
        </div>
        @endif
    </div>
</x-app-layout>