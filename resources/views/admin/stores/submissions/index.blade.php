<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="store" title="Pengajuan Toko Sales" subtitle="Verifikasi informasi toko baru yang diajukan oleh Sales"></x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        @if (session('success'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
                class="rounded-xl p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-100 text-sm flex items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                class="rounded-xl p-4 bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-700 text-red-900 dark:text-red-100 text-sm flex items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-3">
            <a href="{{ route('admin.stores.submissions.index', ['status' => 'pending']) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'pending' ? 'bg-[#0DA4CE] text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                Menunggu ({{ $stats['pending'] }})
            </a>
            <a href="{{ route('admin.stores.submissions.index', ['status' => 'approved']) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                Disetujui ({{ $stats['approved'] }})
            </a>
            <a href="{{ route('admin.stores.submissions.index', ['status' => 'rejected']) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'rejected' ? 'bg-red-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                Ditolak ({{ $stats['rejected'] }})
            </a>
            <a href="{{ route('admin.stores.submissions.index', ['status' => 'all']) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'all' ? 'bg-gray-800 dark:bg-gray-700 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                Semua ({{ $stats['total'] }})
            </a>
        </div>

        {{-- Submission Table --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-left text-sm">
                    <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-5 py-3.5">Nama Toko</th>
                            <th class="px-5 py-3.5">Diajukan Oleh</th>
                            <th class="px-5 py-3.5">Wilayah</th>
                            <th class="px-5 py-3.5">Tanggal</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($submissions as $sub)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ $sub->name }}</span>
                                @if($sub->owner)
                                    <span class="text-xs text-gray-400">Pemilik: {{ $sub->owner }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $sub->user?->name ?: 'Sales' }}</span>
                                <span class="text-gray-400 block text-[11px]">Sales Lapangan</span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ collect([$sub->city, $sub->kecamatan, $sub->province])->filter()->implode(', ') ?: '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ $sub->created_at?->format('d M Y, H:i') }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                @if($sub->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                        Menunggu
                                    </span>
                                @elseif($sub->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        Disetujui
                                    </span>
                                @elseif($sub->status === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                                        Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('admin.stores.submissions.show', $sub->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-[#0DA4CE] hover:bg-[#097A99] rounded-xl transition shadow-xs">
                                    Periksa &amp; Proses
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">
                                Tidak ada pengajuan toko pada status ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($submissions->hasPages())
            <div class="px-2">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
