<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="store" title="Master Toko" subtitle="Kelola data master toko dan jangkauan Sales">
            <a href="{{ route('admin.stores.create') }}"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-[#0b8fb5] focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] focus:ring-offset-2 transition-all duration-200 shadow-sm shadow-[#0DA4CE]/20">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Toko
            </a>
        </x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6" x-data="{
        deactivateModal: false,
        activateModal: false,
        detailModal: false,
        selectedStore: { id: '', name: '', code: '', owner: '', phone: '', address: '', city: '', kecamatan: '', province: '', sales: '', delivery: false, status: '' },
        openDeactivate(id, name, code) {
            this.selectedStore = { id, name, code };
            this.deactivateModal = true;
        },
        openActivate(id, name, code) {
            this.selectedStore = { id, name, code };
            this.activateModal = true;
        },
        openDetail(store) {
            this.selectedStore = store;
            this.detailModal = true;
        }
    }">
        {{-- Flash Messages dengan Auto-Dismiss 4 Detik --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"
                class="rounded-xl p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-100 text-sm flex items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" type="button" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 p-1 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"
                class="rounded-xl p-4 bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-700 text-red-900 dark:text-red-100 text-sm flex items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" type="button" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 p-1 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- Ringkasan Statistik Status Toko --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0 text-[#0DA4CE]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-none">{{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">Total Toko</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-none">{{ number_format($stats['active'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">Toko Aktif</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0 text-amber-600 dark:text-amber-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-extrabold text-amber-600 dark:text-amber-400 leading-none">{{ number_format($stats['unassigned_sales'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">Belum Ada Sales</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center shrink-0 text-blue-600 dark:text-blue-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-extrabold text-blue-600 dark:text-blue-400 leading-none">{{ number_format($stats['delivery_enabled'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">Tujuan Pengiriman</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Monitoring Jangkauan Sales & Frekuensi Kunjungan (Langkah 3 Tahap 1, 2, 3) --}}
        @if(!empty($salesMonitoring))
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Monitoring Jangkauan: {{ $selectedSalesUser->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Statistik cakupan kunjungan toko dan aktivitas kunjungan Sales ini</p>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-3 py-1.5 rounded-xl font-semibold bg-[#0DA4CE]/10 text-[#0DA4CE]">
                        Jangkauan: {{ $salesMonitoring['total_assigned'] }} Toko
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 mb-4">
                <div class="p-3 bg-gray-50 dark:bg-gray-800/40 rounded-xl">
                    <p class="text-xs text-gray-400">Total Jangkauan</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $salesMonitoring['total_assigned'] }}</p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl">
                    <p class="text-xs text-emerald-600 dark:text-emerald-400">Sudah Dikunjungi</p>
                    <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $salesMonitoring['visited_count'] }}</p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-xl">
                    <p class="text-xs text-amber-600 dark:text-amber-400">Belum Pernah Dikunjungi</p>
                    <p class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $salesMonitoring['unvisited_count'] }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-950/30 rounded-xl">
                    <p class="text-xs text-blue-600 dark:text-blue-400">Total Kunjungan</p>
                    <p class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ $salesMonitoring['total_visits'] }}</p>
                </div>
            </div>

            {{-- Toko Sering vs Belum Dikunjungi Tabs --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                {{-- Sering Dikunjungi --}}
                <div class="border border-gray-100 dark:border-gray-800 rounded-xl p-3.5">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2.5 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Toko Sering Dikunjungi
                    </h4>
                    <div class="space-y-2">
                        @forelse($salesMonitoring['top_visited'] as $topStore)
                            <div class="flex items-center justify-between text-xs p-2 bg-gray-50 dark:bg-gray-800/40 rounded-lg">
                                <span class="font-medium text-gray-900 dark:text-white truncate">{{ $topStore->name }}</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 shrink-0">{{ $topStore->visit_count }} Kunjungan</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 py-2">Belum ada data kunjungan.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Belum Pernah Dikunjungi --}}
                <div class="border border-gray-100 dark:border-gray-800 rounded-xl p-3.5">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2.5 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Toko Belum Pernah Dikunjungi
                    </h4>
                    <div class="space-y-2">
                        @forelse($salesMonitoring['unvisited_stores'] as $unvStore)
                            <div class="flex items-center justify-between text-xs p-2 bg-gray-50 dark:bg-gray-800/40 rounded-lg">
                                <span class="font-medium text-gray-900 dark:text-white truncate">{{ $unvStore->name }}</span>
                                <span class="text-[11px] text-amber-600 dark:text-amber-400 shrink-0">0 Kunjungan</span>
                            </div>
                        @empty
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 py-2">Semua toko jangkauan sudah pernah dikunjungi!</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Search & Comprehensive Filter --}}
        @php
            $filterInputClass = 'w-full min-w-0 border border-gray-300 dark:border-gray-600 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 transition focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/20';
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
            <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Filter &amp; Pengelolaan</h3>
            <form method="GET" action="{{ route('admin.stores.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    {{-- Search Toko --}}
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cari Toko</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/kode toko..."
                                class="{{ $filterInputClass }} !pl-10 h-10"
                                style="padding-left: 2.5rem;">
                        </div>
                    </div>

                    {{-- Filter Status --}}
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" class="{{ $filterInputClass }} h-10">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>

                    {{-- Filter Sales Penanggung Jawab --}}
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sales Penanggung Jawab</label>
                        <select name="sales_id" class="{{ $filterInputClass }} h-10">
                            <option value="">Semua Sales</option>
                            <option value="unassigned" {{ request('sales_id') === 'unassigned' ? 'selected' : '' }}>Belum Memiliki Sales</option>
                            @foreach($salesUsers as $sales)
                                <option value="{{ $sales->id }}" {{ request('sales_id') == $sales->id ? 'selected' : '' }}>
                                    {{ $sales->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Tujuan Pengiriman --}}
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tujuan Pengiriman</label>
                        <select name="delivery" class="{{ $filterInputClass }} h-10">
                            <option value="">Semua</option>
                            <option value="yes" {{ request('delivery') === 'yes' ? 'selected' : '' }}>Dapat Dikirim (Ya)</option>
                            <option value="no" {{ request('delivery') === 'no' ? 'selected' : '' }}>Bukan Pengiriman (Tidak)</option>
                        </select>
                    </div>

                    {{-- Button Filter & Reset --}}
                    <div class="sm:col-span-2 lg:col-span-2 flex items-center gap-2">
                        <button type="submit"
                            class="flex-1 inline-flex items-center justify-center px-4 h-10 bg-[#0DA4CE] text-white rounded-xl text-xs font-bold hover:bg-[#097A99] transition shadow-xs whitespace-nowrap">
                            Terapkan Filter
                        </button>
                        @if(request()->anyFilled(['search', 'sales_id', 'delivery', 'status']))
                        <a href="{{ route('admin.stores.index') }}"
                            class="inline-flex items-center justify-center px-3.5 h-10 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition shrink-0" title="Reset Filter">
                            Reset
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Mobile Cards --}}
        <div class="sm:hidden space-y-3.5">
            @forelse($stores ?? [] as $store)
            @php $isStoreActive = ($store->status === 'active' && !$store->trashed()); @endphp
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 dark:text-white truncate">{{ $store->name }}</p>
                        <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5">{{ $store->code }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0
                        {{ $isStoreActive ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isStoreActive ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                        {{ $isStoreActive ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-300 mt-2">
                    <p class="text-gray-400 dark:text-gray-500 truncate">{{ collect([$store->city, $store->kecamatan, $store->province])->filter()->implode(', ') ?: '-' }}</p>
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <span class="text-gray-400 dark:text-gray-500">Sales:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $store->salesPenanggungJawab?->name ?: 'Belum Ada' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-400 dark:text-gray-500">Tujuan Pengiriman:</span>
                        @if($store->salesPenanggungJawab)
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                ✓ Otomatis dapat dikirim Driver
                            </span>
                        @elseif($store->is_delivery_destination)
                            <span class="font-semibold text-blue-600 dark:text-blue-400">
                                ✓ Dapat dikirim Driver
                            </span>
                        @else
                            <span class="font-medium text-gray-400 dark:text-gray-500">
                                — Tidak menjadi tujuan pengiriman
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-2 mt-1">
                        <span class="text-gray-400 dark:text-gray-500">Piutang Saat Ini:</span>
                        @php
                            $mCardReceivable = \App\Services\StoreReceivableService::balanceForStore($store->id);
                        @endphp
                        @if((float)$mCardReceivable > 0.005)
                            <span class="font-bold text-red-600 dark:text-red-400">
                                Rp {{ number_format($mCardReceivable, 0, ',', '.') }}
                            </span>
                        @else
                            <span class="text-green-600 dark:text-green-400 font-medium">
                                Tidak Ada Piutang
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-3.5 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('admin.stores.show', $store->id) }}"
                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-[#0DA4CE] bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/40 rounded-xl hover:bg-cyan-100 transition">
                        Detail
                    </a>
                    <a href="{{ route('admin.stores.edit', $store->id) }}"
                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Edit
                    </a>
                    
                    @if($isStoreActive)
                    <button type="button" @click="openDeactivate('{{ $store->id }}', '{{ addslashes($store->name) }}', '{{ $store->code }}')"
                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-xl hover:bg-red-100 transition">
                        Nonaktif
                    </button>
                    @else
                    <button type="button" @click="openActivate('{{ $store->id }}', '{{ addslashes($store->name) }}', '{{ $store->code }}')"
                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-xl hover:bg-emerald-100 transition">
                        Aktifkan
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-12 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Tidak ada data toko</p>
                <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter Anda.</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop Table (Langkah 7) --}}
        <div class="hidden sm:block bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-left text-sm">
                    <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-5 py-3.5">Kode &amp; Nama Toko</th>
                            <th class="px-5 py-3.5">Wilayah</th>
                            <th class="px-5 py-3.5">Sales Penanggung Jawab</th>
                            <th class="px-5 py-3.5 text-center">Tujuan Pengiriman</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($stores ?? [] as $store)
                        @php $isStoreActive = ($store->status === 'active' && !$store->trashed()); @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ $store->name }}</span>
                                <span class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $store->code }}</span>
                                @if($store->owner)
                                <span class="text-xs text-gray-400 dark:text-gray-500 block">Pemilik: {{ $store->owner }}</span>
                                @endif
                                @php
                                    $stReceivable = \App\Services\StoreReceivableService::balanceForStore($store->id);
                                @endphp
                                @if((float)$stReceivable > 0.005)
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800/40">
                                        Piutang: Rp {{ number_format($stReceivable, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800/40">
                                        Tidak Ada Piutang
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ collect([$store->city, $store->kecamatan, $store->province])->filter()->implode(', ') ?: '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs">
                                @if($store->salesPenanggungJawab)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center font-bold text-[10px]">
                                            {{ strtoupper(substr($store->salesPenanggungJawab->name, 0, 1)) }}
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $store->salesPenanggungJawab->name }}</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                                        Belum Ada Sales
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center text-xs">
                                @if($store->salesPenanggungJawab)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20" title="Otomatis aktif karena memiliki Sales">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ya (Otomatis)
                                    </span>
                                @elseif($store->is_delivery_destination)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ya
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-gray-400 dark:text-gray-500">
                                        Tidak
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $isStoreActive ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $isStoreActive ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    {{ $isStoreActive ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.stores.show', $store->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0DA4CE] bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/40 rounded-xl hover:bg-cyan-100 transition">
                                        Detail
                                    </a>
                                    <a href="{{ route('admin.stores.edit', $store->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                        Edit
                                    </a>
                                    
                                    @if($isStoreActive)
                                    <button type="button" @click="openDeactivate('{{ $store->id }}', '{{ addslashes($store->name) }}', '{{ $store->code }}')"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/40 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                        Nonaktifkan
                                    </button>
                                    @else
                                    <button type="button" @click="openActivate('{{ $store->id }}', '{{ addslashes($store->name) }}', '{{ $store->code }}')"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition">
                                        Aktifkan Kembali
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <p class="font-semibold text-gray-700 dark:text-gray-300">Tidak ada data toko</p>
                                <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter status &amp; Sales.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($stores->hasPages())
        <div class="px-2">
            {{ $stores->links() }}
        </div>
        @endif

        {{-- Card Statistik Toko per Sales (Paling Bawah) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center text-[#0DA4CE] shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100">TOKO PER SALES</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Ringkasan jumlah toko berdasarkan Sales Penanggung Jawab</p>
                    </div>
                </div>
                <a href="{{ route('admin.stores.by-sales') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-50 dark:bg-gray-800 hover:bg-[#0DA4CE]/10 dark:hover:bg-[#0DA4CE]/20 text-[#0DA4CE] border border-gray-200 dark:border-gray-700 hover:border-[#0DA4CE]/40 rounded-xl text-xs font-bold transition self-start sm:self-auto">
                    <span>Lihat Detail</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-4">
                @forelse($storesPerSales as $item)
                <div class="p-3.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
                    <span class="font-medium text-xs text-gray-800 dark:text-gray-200 truncate pr-2">{{ $item['name'] }}</span>
                    <span class="font-bold font-mono text-xs text-[#0DA4CE] shrink-0">{{ $item['count'] }} toko</span>
                </div>
                @empty
                    @if(($unassignedStoresCount ?? 0) === 0)
                    <div class="col-span-full py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                        Belum ada toko yang ditugaskan kepada Sales.
                    </div>
                    @endif
                @endforelse

                @if(($unassignedStoresCount ?? 0) > 0)
                <div class="p-3.5 bg-amber-50/60 dark:bg-amber-950/20 rounded-xl border border-amber-200/60 dark:border-amber-900/40 flex items-center justify-between">
                    <span class="font-medium text-xs text-amber-700 dark:text-amber-400 truncate pr-2">Belum Ada Sales</span>
                    <span class="font-bold font-mono text-xs text-amber-600 dark:text-amber-400 shrink-0">{{ $unassignedStoresCount }} toko</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Modal Detail Toko --}}
        <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="detailModal = false">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="detailModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="detailModal = false"></div>
                <div x-show="detailModal" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="selectedStore.name"></h3>
                                <p class="text-xs font-mono text-gray-500 dark:text-gray-400" x-text="selectedStore.code"></p>
                            </div>
                        </div>
                        <button type="button" @click="detailModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">Pemilik</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="selectedStore.owner"></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">No. Telepon</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="selectedStore.phone"></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">Sales Penanggung Jawab</span>
                            <span class="font-semibold text-[#0DA4CE]" x-text="selectedStore.sales"></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">Tujuan Pengiriman (Driver)</span>
                            <span class="font-semibold" :class="selectedStore.delivery.includes('✓') ? 'text-emerald-600 dark:text-emerald-400' : (selectedStore.delivery.includes('Ya') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400')" x-text="selectedStore.delivery"></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400">Status Operasional</span>
                            <span class="font-semibold" :class="selectedStore.status === 'Aktif' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" x-text="selectedStore.status"></span>
                        </div>
                        <div class="py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 block mb-1">Alamat Lengkap:</span>
                            <p class="text-gray-900 dark:text-white" x-text="selectedStore.address"></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="selectedStore.city + ', ' + selectedStore.kecamatan + ', ' + selectedStore.province"></p>
                        </div>
                        <template x-if="selectedStore.maps_url">
                            <div class="pt-2">
                                <a :href="selectedStore.maps_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0DA4CE] hover:underline">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                    Buka Lokasi di Google Maps &rarr;
                                </a>
                            </div>
                        </template>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="detailModal = false" class="px-5 py-2 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-xl transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Nonaktifkan Toko --}}
        <div x-show="deactivateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="deactivateModal = false">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="deactivateModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="deactivateModal = false"></div>
                <div x-show="deactivateModal" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Nonaktifkan Toko?</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedStore.code + ' — ' + selectedStore.name"></p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-5 leading-relaxed">
                        Toko akan dinonaktifkan dari operasional baru. Data toko dan riwayat sebelumnya tetap tersimpan dan dapat diaktifkan kembali kapan saja.
                    </p>

                    <form :action="'{{ url('admin/stores') }}/' + selectedStore.id" method="POST" class="flex justify-end gap-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="deactivateModal = false" class="px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-xs">
                            Ya, Nonaktifkan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Aktifkan Kembali Toko --}}
        <div x-show="activateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="activateModal = false">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="activateModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="activateModal = false"></div>
                <div x-show="activateModal" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Aktifkan Kembali Toko?</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedStore.code + ' — ' + selectedStore.name"></p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-5 leading-relaxed">
                        Toko akan kembali aktif dan dapat digunakan kembali dalam operasional sistem.
                    </p>

                    <form :action="'{{ url('admin/stores') }}/' + selectedStore.id + '/restore'" method="POST" class="flex justify-end gap-3">
                        @csrf
                        <button type="button" @click="activateModal = false" class="px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-xs">
                            Ya, Aktifkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
