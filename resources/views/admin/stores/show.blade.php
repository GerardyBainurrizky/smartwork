<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="store" title="Monitoring Detail Toko" subtitle="Detail profil toko, jangkauan Sales, tujuan pengiriman, dan riwayat aktivitas terpisah">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.stores.edit', $store->id) }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl font-semibold text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Toko
                </a>
            </div>
        </x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-[#0DA4CE] transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Master Toko
        </a>

        {{-- Profil Header Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $store->name }}</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">{{ $store->code }}</span>
                            @if($store->status === 'active' && !$store->trashed())
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">Aktif</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/60">Nonaktif</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $store->address }} &middot; {{ collect([$store->city, $store->kecamatan, $store->province])->filter()->implode(', ') }}</p>
                    </div>
                </div>

                {{-- Status Badges --}}
                <div class="flex items-center gap-2 shrink-0">
                    @if($store->salesPenanggungJawab)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            ✓ Otomatis dapat dikirim Driver
                        </span>
                    @elseif($store->is_delivery_destination)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            ✓ Dapat dikirim Driver
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-medium text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700">
                            — Tidak menjadi tujuan pengiriman
                        </span>
                    @endif
                </div>
            </div>

            {{-- Detail Fields Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-5 text-xs sm:text-sm">
                <div class="p-3.5 bg-gray-50 dark:bg-gray-800/40 rounded-xl">
                    <span class="text-gray-400 dark:text-gray-500 block text-xs">Pemilik / Kontak</span>
                    <span class="font-bold text-gray-900 dark:text-white mt-0.5 block">{{ $store->owner ?: '-' }}</span>
                    <span class="text-xs text-gray-500">{{ $store->phone ?: 'Tidak ada nomor telepon' }}</span>
                </div>

                <div class="p-3.5 bg-gray-50 dark:bg-gray-800/40 rounded-xl">
                    <span class="text-gray-400 dark:text-gray-500 block text-xs">Sales Penanggung Jawab</span>
                    @if($store->salesPenanggungJawab)
                        <span class="font-bold text-[#0DA4CE] mt-0.5 block">{{ $store->salesPenanggungJawab->name }}</span>
                        <span class="text-xs text-gray-500">{{ $store->salesPenanggungJawab->username }}</span>
                    @else
                        <span class="font-semibold text-amber-600 dark:text-amber-400 mt-0.5 block">Belum Ada Sales</span>
                        <span class="text-[11px] text-gray-400">Toko Umum</span>
                    @endif
                </div>

                <div class="p-3.5 bg-gray-50 dark:bg-gray-800/40 rounded-xl">
                    <span class="text-gray-400 dark:text-gray-500 block text-xs">Total Kunjungan Sales</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 text-lg mt-0.5 block">{{ $stats['total_sales_visits'] }} Kunjungan</span>
                    <span class="text-[11px] text-gray-400">
                        {{ $stats['last_sales_visit'] ? 'Terakhir: ' . $stats['last_sales_visit']->check_in_at?->format('d M Y') : 'Belum pernah dikunjungi' }}
                    </span>
                </div>

                <div class="p-3.5 bg-gray-50 dark:bg-gray-800/40 rounded-xl">
                    <span class="text-gray-400 dark:text-gray-500 block text-xs">Total Pengiriman Driver</span>
                    <span class="font-bold text-blue-600 dark:text-blue-400 text-lg mt-0.5 block">{{ $stats['total_driver_deliveries'] }} Pengiriman</span>
                    <span class="text-[11px] text-gray-400">
                        {{ $stats['last_driver_delivery'] ? 'Terakhir: ' . $stats['last_driver_delivery']->check_in_at?->format('d M Y') : 'Belum pernah dikirim' }}
                    </span>
                </div>
            </div>

            @if($store->google_maps_url)
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                    <div class="text-gray-500 dark:text-gray-400">
                        Koordinat: {{ $store->latitude ?: '-' }}, {{ $store->longitude ?: '-' }}
                    </div>
                    <a href="{{ $store->google_maps_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-[#0DA4CE] hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Buka Lokasi di Google Maps &rarr;
                    </a>
                </div>
            @endif
        </div>

        {{-- AKTIVITAS TERPISAH: 1. Riwayat Kunjungan Sales (BR-02, BR-03, Tahap 4) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Kunjungan Sales</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Aktivitas kunjungan oleh Sales Penanggung Jawab pada toko ini</p>
                    </div>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                    {{ $salesVisits->total() }} Total Kunjungan
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-left text-xs sm:text-sm">
                    <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-gray-500 text-xs font-semibold uppercase">
                        <tr>
                            <th class="px-5 py-3">Tanggal &amp; Waktu</th>
                            <th class="px-5 py-3">Sales</th>
                            <th class="px-5 py-3">Rute</th>
                            <th class="px-5 py-3">Hasil / Transaksi</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($salesVisits as $sv)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-bold text-gray-900 dark:text-white block">{{ $sv->check_in_at?->format('d M Y') ?: '-' }}</span>
                                <span class="text-xs text-gray-400">{{ $sv->check_in_at?->format('H:i') }} - {{ $sv->check_out_at?->format('H:i') ?: '...' }}</span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                {{ $sv->user?->name ?: 'Sales' }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                {{ $sv->route?->name ?: '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                @if(($sv->transaction_status ?? 'none') === 'paid')
                                    <span class="font-bold text-emerald-600">Rp {{ number_format($sv->transaction_amount ?? 0, 0, ',', '.') }}</span>
                                    <span class="text-gray-400 block text-[11px] uppercase">({{ $sv->payment_method }})</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                @if($sv->status === 'completed')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">Selesai</span>
                                @elseif($sv->status === 'in_progress')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">Berjalan</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">{{ $sv->status }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <a href="{{ route('visit.show', $sv->id) }}" class="text-xs font-semibold text-[#0DA4CE] hover:underline">
                                    Detail Visit &rarr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-xs text-gray-400">
                                Belum ada riwayat kunjungan Sales untuk toko ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($salesVisits->hasPages())
                <div class="p-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $salesVisits->links() }}
                </div>
            @endif
        </div>

        {{-- AKTIVITAS TERPISAH: 2. Riwayat Pengiriman Driver (BR-05, BR-07, BR-09, Tahap 4) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Pengiriman Driver</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Aktivitas pengiriman barang ke toko ini oleh armada Driver</p>
                    </div>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50">
                    {{ $driverVisits->total() }} Total Pengiriman
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-left text-xs sm:text-sm">
                    <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-gray-500 text-xs font-semibold uppercase">
                        <tr>
                            <th class="px-5 py-3">Tanggal &amp; Waktu</th>
                            <th class="px-5 py-3">Driver</th>
                            <th class="px-5 py-3">Rencana Pengiriman</th>
                            <th class="px-5 py-3">Barang Terkirim</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($driverVisits as $dv)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-bold text-gray-900 dark:text-white block">{{ $dv->check_in_at?->format('d M Y') ?: '-' }}</span>
                                <span class="text-xs text-gray-400">{{ $dv->check_in_at?->format('H:i') }} - {{ $dv->check_out_at?->format('H:i') ?: '...' }}</span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                {{ $dv->user?->name ?: 'Driver' }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                {{ $dv->route?->name ?: '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300 max-w-[200px] truncate">
                                {{ $dv->delivered_goods_summary ?: ($dv->delivered_goods ?: '-') }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                @if($dv->status === 'completed')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">Terkirim</span>
                                @elseif($dv->status === 'in_progress')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">Proses</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">{{ $dv->status }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <a href="{{ route('visit.show', $dv->id) }}" class="text-xs font-semibold text-[#0DA4CE] hover:underline">
                                    Detail Pengiriman &rarr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-xs text-gray-400">
                                Belum ada riwayat pengiriman Driver untuk toko ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($driverVisits->hasPages())
                <div class="p-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $driverVisits->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
