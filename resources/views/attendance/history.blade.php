<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="history" title="Riwayat Attendance" subtitle="Riwayat presensi dan status kedatangan Anda"></x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/60 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700/60">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ now()->isoFormat('MMMM YYYY') }}</h3>
            </div>

            @forelse ($records as $record)
                <div class="p-4 border-b border-gray-50 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-100">
                                {{ $record->date->isoFormat('dddd, D MMMM') }}
                            </p>
                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ $record->clock_in?->format('H:i') ?? '--:--' }}
                                </span>
                                <span>→</span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ $record->clock_out?->format('H:i') ?? '--:--' }}
                                </span>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium
                            {{ $record->status === 'present' ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-400' : '' }}
                            {{ $record->status === 'late' ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400' : '' }}
                            {{ $record->status === 'absent' ? 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400' : '' }}
                            {{ $record->status === 'pending' ? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' : '' }}
                            {{ $record->status === 'izin' ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400' : '' }}
                            {{ $record->status === 'sakit' ? 'bg-pink-100 dark:bg-pink-950/40 text-pink-700 dark:text-pink-400' : '' }}
                            {{ $record->status === 'canceled' ? 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400' : '' }}">
                            {{ $record->status === 'present' ? 'Hadir' : '' }}
                            {{ $record->status === 'late' ? 'Terlambat' : '' }}
                            {{ $record->status === 'absent' ? 'Tidak Hadir' : '' }}
                            {{ $record->status === 'pending' ? 'Pending' : '' }}
                            {{ $record->status === 'izin' ? 'Izin' : '' }}
                            {{ $record->status === 'sakit' ? 'Sakit' : '' }}
                            {{ $record->status === 'canceled' ? 'Dibatalkan' : '' }}
                        </span>
                    </div>
                    @if ($record->clock_in_address)
                        <div class="mt-2 flex items-start gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            <span class="line-clamp-1">{{ $record->clock_in_address }}</span>
                        </div>
                    @endif
                    @if ($record->absence_note)
                        <div class="mt-1 flex items-start gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="line-clamp-2"><strong class="font-medium text-gray-600 dark:text-gray-300">Keterangan:</strong> {{ $record->absence_note }}</span>
                        </div>
                    @elseif ($record->notes && !$record->is_canceled)
                        <div class="mt-1 flex items-start gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="line-clamp-2"><strong class="font-medium text-gray-600 dark:text-gray-300">Keterangan:</strong> {{ $record->notes }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Belum ada riwayat presensi</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Lakukan check-in untuk memulai</p>
                </div>
            @endforelse
        </div>

        @if ($records->hasPages())
            <div class="mt-6">
                {{ $records->links() }}
            </div>
        @endif

        <div class="mt-6 flex justify-center">
            <x-back-button href="{{ route('attendance.index') }}" label="Kembali ke Presensi" />
        </div>
    </div>
</x-app-layout>