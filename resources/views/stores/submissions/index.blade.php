<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="store" title="Pengajuan Toko Baru" subtitle="Daftar informasi toko baru yang telah Anda ajukan kepada Admin">
            <a href="{{ route('stores.submissions.create') }}"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-[#0b8fb5] focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] focus:ring-offset-2 transition-all duration-200 shadow-sm shadow-[#0DA4CE]/20">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Informasikan Toko Baru
            </a>
        </x-page-header>
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

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Pengajuan Toko</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Informasi toko baru akan ditinjau Admin sebelum masuk ke Master Toko</p>
                </div>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($submissions as $item)
                    <div class="p-4 sm:p-5 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $item->name }}</h3>
                                    @if($item->status === 'pending')
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                                            Menunggu Verifikasi
                                        </span>
                                    @elseif($item->status === 'approved')
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                                            Disetujui
                                        </span>
                                    @elseif($item->status === 'rejected')
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/40">
                                            Ditolak
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $item->address }} &middot; {{ $item->city }}, {{ $item->province }}</p>
                                @if($item->notes)
                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 italic">"{{ $item->notes }}"</p>
                                @endif
                                @if($item->status === 'rejected' && $item->rejection_reason)
                                    <div class="mt-2 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 p-2 rounded-lg border border-red-200 dark:border-red-900/40">
                                        <span class="font-bold">Alasan Penolakan:</span> {{ $item->rejection_reason }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-left sm:text-right shrink-0 text-xs text-gray-400 dark:text-gray-500">
                                <p>Diajukan pada {{ $item->created_at?->format('d M Y, H:i') }}</p>
                                @if($item->reviewed_at)
                                    <p class="text-[11px] mt-0.5 text-gray-500 dark:text-gray-400">Ditinjau: {{ $item->reviewed_at->format('d M Y') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                        Belum ada pengajuan toko baru.
                    </div>
                @endforelse
            </div>
        </div>

        @if($submissions->hasPages())
            <div class="px-2">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
