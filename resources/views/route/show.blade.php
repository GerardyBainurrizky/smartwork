<x-app-layout>
    @php
        $isDriver = Auth::user()->hasRole('driver') || ($route->user && $route->user->hasRole('driver'));
        $isOwner = (string) $route->user_id === (string) auth()->id();
    @endphp
    <x-slot name="header">
        <x-page-header icon="route" :title="$isDriver ? 'Detail Rute Pengiriman' : 'Detail Rute'" :subtitle="$isDriver ? 'Lihat dan kelola progres rute pengiriman Anda' : 'Lihat dan kelola progres rute kunjungan Anda'"></x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto" x-data="routeDetail()">
        <x-back-button href="{{ route('route.index') }}" :label="$isDriver ? 'Kembali ke Daftar Rute Pengiriman' : 'Kembali ke Daftar Rute'" class="mb-4" />

        {{-- Route Header Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden mb-6">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 truncate">{{ $route->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($route->date)->isoFormat('dddd, D MMMM YYYY') }}
                        </p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shrink-0
                        @if($route->computed_status === 'draft') bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200
                        @elseif($route->computed_status === 'active') bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                        @elseif($route->computed_status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                        @else bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400
                        @endif">
                        @if($route->computed_status === 'draft') Menunggu
                        @elseif($route->computed_status === 'active') Sedang Berjalan
                        @elseif($route->computed_status === 'completed') Selesai
                        @elseif($route->computed_status === 'cancelled') Dibatalkan
                        @endif
                    </span>
                </div>

                @if($route->notes)
                <div class="mt-4 bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $route->notes }}</p>
                </div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Mulai</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ $route->started_at ? \Carbon\Carbon::parse($route->started_at)->format('d M Y, H:i') : '-' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Selesai</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ $route->completed_at ? \Carbon\Carbon::parse($route->completed_at)->format('d M Y, H:i') : '-' }}
                        </p>
                    </div>
                </div>

                {{-- Action Buttons & Status Indicators --}}
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @if($isOwner && $route->status === 'draft')
                    <button @click="startRoute()" :disabled="loading"
                        class="inline-flex items-center px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] disabled:opacity-50 transition-all shadow-sm shadow-[#0DA4CE]/20">
                        <svg x-show="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Mulai Rute
                    </button>
                    @endif

                    @if($route->computed_status === 'active')
                    <div class="inline-flex items-center px-4 py-2.5 bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] border border-[#0DA4CE]/30 dark:border-[#0DA4CE]/40 rounded-xl text-sm font-semibold cursor-default select-none shadow-xs">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#0DA4CE] mr-2 animate-pulse"></span>
                        Rute Sedang Berjalan
                    </div>
                    @elseif($route->computed_status === 'completed')
                    <div class="inline-flex items-center px-4 py-2.5 bg-[#90F022]/15 dark:bg-[#90F022]/20 text-green-800 dark:text-green-300 border border-green-300 dark:border-green-800/60 rounded-xl text-sm font-semibold cursor-default select-none shadow-xs">
                        <svg class="w-4 h-4 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Rute Selesai
                    </div>
                    @endif

                    <a href="{{ route('route.map', $route->id) }}"
                        class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Peta
                    </a>

                    @if($isOwner && in_array($route->status, ['draft', 'cancelled']))
                    <button @click="confirmDelete()" :disabled="loading"
                        class="inline-flex items-center px-4 py-2.5 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 rounded-xl text-sm font-semibold hover:bg-red-100 disabled:opacity-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Hapus Rute
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Route Stops List --}}
        @php
            $activeDeliveryStop = $isDriver ? ($route->stops->first(fn($s) => $s->computed_status === 'in_progress')) : null;
        @endphp
        <div class="mb-6">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $isDriver ? 'Daftar Tujuan Pengiriman' : 'Daftar Toko' }}</h3>
                <div class="flex items-center gap-2">
                    @if($isOwner && $route->status === 'draft')
                    <button type="button" @click="openEditRouteModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-amber-500/10 text-amber-700 hover:bg-amber-500/20 dark:bg-amber-500/15 dark:text-amber-400 dark:hover:bg-amber-500/25 border border-amber-500/20 transition shadow-2xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>{{ $isDriver ? 'Edit Pengiriman' : 'Edit Kunjungan' }}</span>
                    </button>
                    @elseif((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && $route->status === 'draft')
                    <a href="{{ $isDriver ? route('admin.driver.routes.edit', $route->id) : route('admin.routes.edit', $route->id) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-amber-500/10 text-amber-700 hover:bg-amber-500/20 dark:bg-amber-500/15 dark:text-amber-400 dark:hover:bg-amber-500/25 border border-amber-500/20 transition shadow-2xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>{{ $isDriver ? 'Edit Pengiriman' : 'Edit Kunjungan' }}</span>
                    </a>
                    @endif
                    @if(($isOwner || auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && in_array($route->status, ['draft', 'active']))
                    <button type="button" @click="openAddStopModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#0DA4CE]/10 text-[#0DA4CE] hover:bg-[#0DA4CE]/20 dark:bg-[#0DA4CE]/15 dark:text-[#5BD8F7] dark:hover:bg-[#0DA4CE]/25 border border-[#0DA4CE]/20 transition shadow-2xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ $isDriver ? 'Tambah Tujuan Pengiriman' : 'Tambah Toko Kunjungan' }}</span>
                    </button>
                    @endif
                </div>
            </div>
            <div class="space-y-0">
                @forelse($route->stops ?? [] as $index => $stop)
                @php
                    $isStopWaitingPrevious = $isDriver && $activeDeliveryStop && (string) $activeDeliveryStop->id !== (string) $stop->id && $stop->computed_status === 'pending';
                @endphp
                <div class="flex gap-4" id="stop-{{ $stop->id }}">
                    {{-- Timeline connector --}}
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0
                            @if($stop->status === 'visited') bg-[#90F022] text-gray-900 dark:text-gray-100
                            @elseif($stop->status === 'skipped') bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400
                            @else bg-[#0DA4CE] text-white
                            @endif">
                            {{ $stop->sequence ?? $index + 1 }}
                        </div>
                        @if(!$loop->last)
                        <div class="w-0.5 flex-1 min-h-[1.5rem] bg-gray-200 dark:bg-gray-700"></div>
                        @endif
                    </div>

                    {{-- Stop card --}}
                    <div class="flex-1 min-w-0 pb-5">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 sm:p-5
                            @if($stop->status === 'visited') border-l-4 border-l-[#90F022]
                            @elseif($stop->status === 'skipped') border-l-4 border-l-gray-300 dark:border-l-gray-700
                            @else border-l-4 border-l-[#0DA4CE]
                            @endif">
                            
                            {{-- Header Card: Nama Toko & Status Badge --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100 break-words leading-snug">
                                        {{ $stop->store->name ?? 'Toko' }}
                                    </h4>
                                    @if(!$isDriver)
                                        @php
                                            $stReceivable = \App\Services\StoreReceivableService::balanceForStore($stop->store_id);
                                        @endphp
                                        @if((float)$stReceivable > 0.005)
                                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[11px] font-bold bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                                Piutang: Rp {{ number_format($stReceivable, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[11px] font-medium bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-400 border border-green-200/60 dark:border-green-800/40">
                                                Piutang: Tidak Ada Piutang
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0
                                    @if($stop->status === 'visited') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400
                                    @elseif($stop->status === 'skipped') bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                    @elseif($isStopWaitingPrevious) bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40
                                    @else bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]
                                    @endif">
                                    @if($stop->status === 'visited') {{ $isDriver ? 'Selesai' : 'Dikunjungi' }}
                                    @elseif($stop->status === 'skipped') Dilewati
                                    @elseif($isStopWaitingPrevious) Menunggu Pengiriman Sebelumnya
                                    @else Menunggu
                                    @endif
                                </span>
                            </div>

                            {{-- Info Baris 2: Estimasi Durasi (Sales) & Kode Toko --}}
                            <div class="mt-1.5 flex items-center gap-2.5 text-xs text-gray-500 dark:text-gray-400">
                                @if(!$isDriver && $stop->estimated_duration_minutes)
                                <span class="inline-flex items-center gap-1 text-[#0DA4CE] dark:text-[#5BD8F7] font-semibold shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $stop->estimated_duration_minutes }} mnt
                                </span>
                                @endif
                                <span class="font-mono text-gray-500 dark:text-gray-400 font-medium">{{ $stop->store->code ?? '-' }}</span>
                            </div>

                            {{-- Info Baris 3: Badge Type (misal outlet) --}}
                            @if($stop->store->type ?? false)
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-700/80 text-gray-600 dark:text-gray-300 border border-gray-200/60 dark:border-gray-600/60 uppercase tracking-wider">
                                    {{ $stop->store->type }}
                                </span>
                            </div>
                            @endif

                            {{-- Info Baris 4: Alamat Lengkap Toko & Buka Maps --}}
                            @if($stop->store && ($stop->store->address || $stop->store->google_maps_url))
                            <div class="mt-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed break-words whitespace-normal flex-1 min-w-0">
                                    {{ $stop->store->address ?: 'Alamat belum diatur' }}
                                </p>
                                @if($stop->store->google_maps_url)
                                <a href="{{ $stop->store->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                                    aria-label="Buka lokasi {{ $stop->store->name }} di Google Maps"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] border border-[#0DA4CE]/20 transition shrink-0 self-start sm:self-auto">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Buka Maps</span>
                                </a>
                                @endif
                            </div>
                            @endif

                            {{-- Catatan Stop jika ada --}}
                            @if($stop->notes)
                            <div class="mt-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl p-3 flex items-start gap-2.5 border border-gray-100 dark:border-gray-700/60">
                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                <p class="text-xs text-gray-600 dark:text-gray-300 break-words">{{ $stop->notes }}</p>
                            </div>
                            @endif

                            {{-- Tombol Aksi --}}
                            @if($stop->visit)
                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                                <a href="{{ route('visit.show', $stop->visit->id) }}"
                                    class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-xs font-semibold transition shadow-2xs
                                    @if($stop->visit->status === 'completed') bg-[#90F022]/20 dark:bg-[#90F022]/20 text-green-800 dark:text-green-400 hover:bg-[#90F022]/30
                                    @else bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7] hover:bg-[#0DA4CE]/20
                                    @endif">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    {{ $stop->visit->status === 'in_progress' ? ($isDriver ? 'Lanjutkan Pengiriman' : 'Lanjutkan Kunjungan') : ($isDriver ? 'Lihat Pengiriman' : 'Lihat Kunjungan') }}
                                </a>
                            </div>
                            @elseif($stop->status === 'pending' && $isOwner)
                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex flex-col gap-2">
                                @if(in_array($route->status, ['draft', 'cancelled']))
                                <button @click="showToast('Rute belum dimulai. Silakan klik Mulai Rute terlebih dahulu sebelum melakukan check-in {{ $isDriver ? 'pengiriman' : 'kunjungan' }}.', 'error')"
                                    class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-xs font-semibold hover:bg-[#097A99] transition shadow-xs">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Check In
                                </button>
                                <button @click="showToast('Rute belum dimulai. Silakan klik Mulai Rute terlebih dahulu sebelum melewati {{ $isDriver ? 'pengiriman' : 'kunjungan' }}.', 'error')"
                                    class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                    Lewati
                                </button>
                                @else
                                <a href="{{ route('visit.check-in-form', $stop->id) }}"
                                    @click.prevent="attemptCheckIn('{{ $stop->id }}', '{{ route('visit.check-in-form', $stop->id) }}')"
                                    class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-xs font-semibold hover:bg-[#097A99] transition shadow-xs">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Check In
                                </a>
                                <button @click="openSkipModal('{{ $stop->id }}')" :disabled="loading"
                                    class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                    Lewati
                                </button>
                                @endif

                                {{-- Sequential Blocker / Stop Error Alert --}}
                                <div x-show="stopErrors['{{ $stop->id }}']" x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-1"
                                    class="mt-3 rounded-xl border border-amber-300 dark:border-amber-700/70 bg-amber-50 dark:bg-amber-950/40 p-3.5 text-xs transition-all shadow-xs">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-amber-900 dark:text-amber-200 text-xs sm:text-sm" x-text="stopErrors['{{ $stop->id }}']?.title || 'Belum dapat Check In'"></p>
                                            <p class="mt-1 text-amber-800 dark:text-amber-300/90 leading-relaxed font-normal break-words" x-text="stopErrors['{{ $stop->id }}']?.message"></p>
                                        </div>
                                        <button type="button" @click="clearStopError('{{ $stop->id }}')" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300 p-1 rounded-lg transition shrink-0" aria-label="Tutup pesan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Belum ada toko dalam rute ini</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition>
            <div class="fixed inset-0 bg-black/40" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-sm w-full p-6">
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-red-100 dark:bg-red-950/40 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Hapus Rute?</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Tindakan ini tidak dapat dibatalkan. Apakah Anda yakin ingin menghapus rute ini?</p>
                </div>
                <div class="mt-6 flex gap-3">
                    <button @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Batal
                    </button>
                    <button @click="deleteRoute()" :disabled="loading"
                        class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 disabled:opacity-50 transition">
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        {{-- Skip Modal (Khusus Sales: Alasan Wajib + Foto Bukti Wajib) --}}
        <div x-show="showSkipModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="closeSkipModal()">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="showSkipModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeSkipModal()"></div>
                
                <div x-show="showSkipModal" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-md w-full p-5 sm:p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Lewati Kunjungan</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Lengkapi alasan dan foto bukti sebelum melewati toko.</p>
                            </div>
                        </div>
                        <button type="button" @click="closeSkipModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mb-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl p-3 border border-gray-100 dark:border-gray-700/60 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-400 dark:text-gray-500">Toko:</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100" x-text="activeSkipStop?.store?.name || '-'"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-400 dark:text-gray-500">Urutan:</span>
                            <span class="font-bold text-[#0DA4CE]" x-text="'Kunjungan #' + (activeSkipStop?.sequence || '-')"></span>
                        </div>
                    </div>

                    <form @submit.prevent="confirmSkip()" class="space-y-4">
                        {{-- Alasan / Catatan --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                Alasan / Catatan <span class="text-red-500">*</span>
                            </label>
                            <textarea x-model="skipForm.notes" rows="3" required
                                class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white p-3 outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition resize-none font-normal leading-relaxed"
                                placeholder="Contoh: Toko tutup / Pemilik toko tidak berada di lokasi / Alamat tidak ditemukan"></textarea>
                        </div>

                        {{-- Foto Bukti (Hanya Ambil Foto Kamera) --}}
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200">
                                Foto Bukti <span class="text-red-500">*</span>
                            </label>

                            {{-- Area Video Kamera Saat Aktif --}}
                            <div x-show="skipCameraActive" class="space-y-2">
                                <div class="relative bg-gray-900 rounded-xl overflow-hidden aspect-[4/3] w-full border border-gray-300 dark:border-gray-700">
                                    <video id="skip-camera" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"></video>
                                    
                                    {{-- Tombol Balik Kamera di Pojok Kanan Atas --}}
                                    <button type="button" @click="flipSkipCamera()"
                                        class="absolute top-3 right-3 z-20 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-black/60 hover:bg-black/80 text-white text-xs font-semibold backdrop-blur-xs transition active:scale-95 border border-white/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span x-text="skipFacing === 'user' ? 'Kamera Depan' : 'Kamera Belakang'"></span>
                                    </button>

                                    <div class="absolute bottom-3 inset-x-0 flex items-center justify-center gap-4 z-10 px-4">
                                        <button type="button" @click="stopSkipCamera()" class="px-4 py-2 rounded-xl bg-black/60 hover:bg-black/80 text-white text-xs font-semibold backdrop-blur-xs transition">
                                            Batal
                                        </button>
                                        <button type="button" @click="captureSkipPhoto()" aria-label="Ambil Foto" class="w-14 h-14 rounded-full bg-white border-4 border-amber-500 flex items-center justify-center shadow-lg active:scale-95 transition hover:scale-105"></button>
                                    </div>
                                </div>
                            </div>

                            {{-- Preview Foto Terambil --}}
                            <div x-show="skipForm.photo && !skipCameraActive" class="space-y-2">
                                <div class="relative rounded-xl overflow-hidden aspect-[4/3] w-full bg-black border border-gray-200 dark:border-gray-700">
                                    <img :src="skipForm.photo" class="w-full h-full object-cover" alt="Foto Bukti Dilewati">
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <button type="button" @click="retakeSkipPhoto()"
                                        class="flex-1 py-2 px-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 text-gray-700 dark:text-gray-200 text-xs font-semibold flex items-center justify-center gap-1.5 transition active:scale-95">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>Ambil Ulang</span>
                                    </button>
                                    <span class="text-[11px] font-medium text-green-600 dark:text-green-400 flex items-center gap-1 px-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Foto Siap
                                    </span>
                                </div>
                            </div>

                            {{-- Tombol Buka Kamera (Hanya Ambil Foto - Tanpa File Picker) --}}
                            <div x-show="!skipForm.photo && !skipCameraActive">
                                <button type="button" @click="startSkipCamera()"
                                    class="w-full py-3.5 px-4 rounded-xl border-2 border-dashed border-amber-500/50 bg-amber-500/5 hover:bg-amber-500/10 text-amber-700 dark:text-amber-400 text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 transition active:scale-95 shadow-2xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Ambil Foto</span>
                                </button>
                            </div>
                        </div>

                        <canvas id="skip-canvas" class="hidden"></canvas>

                        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="closeSkipModal()"
                                class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs sm:text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="loading || !skipForm.notes || !skipForm.photo"
                                class="px-5 py-2.5 bg-amber-500 text-white rounded-xl text-xs sm:text-sm font-semibold hover:bg-amber-600 disabled:opacity-50 transition shadow-xs">
                                Konfirmasi Lewati
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Toko Kunjungan --}}
        <div x-show="showAddStopModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="closeAddStopModal()">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="showAddStopModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeAddStopModal()"></div>
                
                <div x-show="showAddStopModal" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-lg w-full p-5 sm:p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $isDriver ? 'Tambah Tujuan Pengiriman' : 'Tambah Toko Kunjungan' }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $isDriver ? 'Tambahkan tujuan pengiriman baru ke rute ini.' : 'Tambahkan tujuan toko baru ke rute ini.' }}</p>
                            </div>
                        </div>
                        <button type="button" @click="closeAddStopModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitAddStop()" class="space-y-4">
                        {{-- Pilih Toko --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                Pilih {{ $isDriver ? 'Tujuan' : 'Toko' }} <span class="text-red-500">*</span>
                            </label>
                            <select x-model="addStopForm.store_id" required
                                class="w-full h-11 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE] transition px-3 outline-none">
                                <option value="">{{ $isDriver ? '-- Pilih Tujuan --' : '-- Pilih Toko Aktif --' }}</option>
                                <template x-for="st in availableStores" :key="st.id">
                                    <option :value="st.id" x-text="st.name + (st.city ? ' (' + st.city + ')' : (st.code ? ' (' + st.code + ')' : ''))"></option>
                                </template>
                            </select>
                            <template x-if="availableStores.length === 0">
                                <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">{{ $isDriver ? 'Semua tujuan aktif sudah terdaftar dalam rute ini.' : 'Semua toko aktif sudah terdaftar dalam rute ini.' }}</p>
                            </template>
                        </div>

                        {{-- Estimasi Durasi & Urutan --}}
                        <div class="grid grid-cols-1 {{ $isDriver ? '' : 'sm:grid-cols-2' }} gap-3">
                            @if(!$isDriver)
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                    Estimasi Durasi (Menit) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" x-model.number="addStopForm.estimated_duration_minutes" min="1" max="480" required placeholder="Contoh: 30"
                                    class="w-full h-11 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE] transition px-3.5 outline-none font-medium">
                            </div>
                            @endif
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                    {{ $isDriver ? 'Urutan Pengiriman' : 'Urutan Kunjungan' }}
                                </label>
                                <input type="text" :value="'Urutan ke-' + (currentStopsCount + 1) + ' (Terakhir)'" disabled
                                    class="w-full h-11 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 px-3.5 outline-none font-medium cursor-not-allowed">
                            </div>
                        </div>

                        {{-- Catatan Opsional --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                Catatan Rencana Toko (Opsional)
                            </label>
                            <input type="text" x-model="addStopForm.notes" placeholder="Contoh: Cek display produk, tagih faktur"
                                class="w-full h-11 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE] transition px-3.5 outline-none">
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-2">
                            <button type="button" @click="closeAddStopModal()" class="px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="loading || !addStopForm.store_id"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-[#0DA4CE] hover:bg-[#097A99] disabled:opacity-50 transition shadow-xs">
                                <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span>Tambah ke Rute</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Edit Rute (Sebelum Rute Dimulai) --}}
        <div x-show="showEditRouteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="closeEditRouteModal()">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="showEditRouteModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeEditRouteModal()"></div>
                
                <div x-show="showEditRouteModal" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-2xl w-full p-5 sm:p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit Rencana Kunjungan</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ubah toko, urutan kunjungan, atau estimasi durasi sebelum rute dimulai.</p>
                            </div>
                        </div>
                        <button type="button" @click="closeEditRouteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitEditRoute()" class="space-y-4">
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            <template x-for="(stop, idx) in editRouteStops" :key="idx">
                                <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono" x-text="'Kunjungan #' + (idx + 1)"></span>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="moveStopUp(idx)" :disabled="idx === 0"
                                                class="p-1 rounded-lg bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 disabled:opacity-30 border border-gray-200 dark:border-gray-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            </button>
                                            <button type="button" @click="moveStopDown(idx)" :disabled="idx === editRouteStops.length - 1"
                                                class="p-1 rounded-lg bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 disabled:opacity-30 border border-gray-200 dark:border-gray-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <button type="button" @click="removeEditStop(idx)" :disabled="editRouteStops.length <= 1"
                                                class="p-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 disabled:opacity-30 border border-red-200 dark:bg-red-950/40 dark:border-red-800 ml-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Ganti Toko --}}
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Pilih Toko <span class="text-red-500">*</span>
                                        </label>
                                        <select x-model="stop.store_id" required
                                            class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white p-2.5 outline-none focus:border-[#0DA4CE]">
                                            <option value="">-- Pilih Toko --</option>
                                            @foreach($stores as $st)
                                                <option value="{{ $st->id }}" :selected="String(stop.store_id) === '{{ $st->id }}'">
                                                    {{ $st->name }}{{ $st->city ? ' (' . $st->city . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                     <div class="grid grid-cols-2 gap-2.5">
                                         <div>
                                             <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                                 Urutan <span class="text-red-500">*</span>
                                             </label>
                                             <select x-model.number="stop.sequence" required
                                                 @change="onStopSequenceDropdownChanged(idx, stop.sequence)"
                                                 class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white p-2 outline-none font-mono">
                                             <template x-for="n in editRouteStops.length" :key="n">
                                                 <option :value="n" x-text="'Urutan ' + n" :selected="Number(stop.sequence) === n"></option>
                                             </template>
                                             </select>
                                         </div>
                                         <div>
                                             <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                                 Durasi (Menit) <span class="text-red-500">*</span>
                                             </label>
                                             <input type="number" x-model.number="stop.estimated_duration_minutes" min="1" max="480" required
                                                 class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white p-2 outline-none font-mono">
                                         </div>
                                     </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Catatan</label>
                                        <input type="text" x-model="stop.notes" placeholder="Catatan toko..."
                                            class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white p-2 outline-none">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="closeEditRouteModal()"
                                class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs sm:text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="loading"
                                class="px-5 py-2.5 bg-amber-500 text-white rounded-xl text-xs sm:text-sm font-semibold hover:bg-amber-600 disabled:opacity-50 transition shadow-xs">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="toast.show" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            class="fixed bottom-6 left-4 right-4 z-50 max-w-lg mx-auto">
            <div class="rounded-2xl px-6 py-4 shadow-xl border"
                :class="toast.type === 'success' ? 'bg-green-50 dark:bg-green-950/80 border-green-200 dark:border-green-800 text-green-800 dark:text-green-400' : 'bg-red-50 dark:bg-red-950/80 border-red-200 dark:border-red-800 text-red-800 dark:text-red-400'">
                <div class="flex items-center gap-3">
                    <svg x-show="toast.type === 'success'" class="w-6 h-6 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="toast.type === 'error'" class="w-6 h-6 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium text-sm" x-text="toast.message"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function routeDetail() {
            return {
                loading: false,
                showDeleteModal: false,
                showSkipModal: false,
                showAddStopModal: false,
                showEditRouteModal: false,
                skipStopId: null,
                skipCameraActive: false,
                skipStream: null,
                skipFacing: 'user',
                skipLat: null,
                skipLng: null,
                skipAddress: null,
                skipMapsUrl: null,
                skipForm: {
                    notes: '',
                    photo: null,
                },
                editRouteStops: [],
                toast: { show: false, message: '', type: 'success' },
                gpsTracking: false,
                gpsWatchId: null,

                isDriver: {{ $isDriver ? 'true' : 'false' }},
                allStores: @js($stores ?? []),
                existingStoreIds: @js($route->stops->pluck('store_id')->toArray() ?? []),
                currentStopsCount: {{ count($route->stops ?? []) }},
                stopErrors: {},
                stopErrorTimers: {},
                routeStops: @js($route->stops->map(function ($s) {
                    return [
                        'id' => (string) $s->id,
                        'store_id' => (string) $s->store_id,
                        'store_name' => $s->store?->name ?? 'Toko',
                        'sequence' => (int) $s->sequence,
                        'status' => (string) $s->status,
                        'computed_status' => (string) $s->computed_status,
                    ];
                }) ?? []),
                rawStops: @js($route->stops->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'store_id' => $s->store_id,
                        'sequence' => $s->sequence,
                        'estimated_duration_minutes' => $s->estimated_duration_minutes ?? 30,
                        'notes' => $s->notes ?? '',
                    ];
                }) ?? []),

                get activeSkipStop() {
                    return @js($route->stops ?? []).find(s => s.id === this.skipStopId) || null;
                },

                showStopError(stopId, title, message) {
                    if (this.stopErrorTimers[stopId]) {
                        clearTimeout(this.stopErrorTimers[stopId]);
                    }
                    this.stopErrors[stopId] = { title, message };
                    this.stopErrorTimers[stopId] = setTimeout(() => {
                        this.stopErrors[stopId] = null;
                        delete this.stopErrorTimers[stopId];
                    }, 5000);
                },

                clearStopError(stopId) {
                    if (this.stopErrorTimers[stopId]) {
                        clearTimeout(this.stopErrorTimers[stopId]);
                        delete this.stopErrorTimers[stopId];
                    }
                    this.stopErrors[stopId] = null;
                },

                attemptCheckIn(stopId, targetUrl) {
                    this.clearStopError(stopId);

                    const currentStop = this.routeStops.find(s => String(s.id) === String(stopId));
                    if (!currentStop) {
                        window.location.href = targetUrl;
                        return;
                    }

                        // 1. Check prior stops in this route (sequence < currentStop.sequence)
                        const priorPending = this.routeStops
                            .filter(s => Number(s.sequence) < Number(currentStop.sequence) && s.status !== 'skipped' && s.computed_status !== 'visited' && s.computed_status !== 'skipped')
                            .sort((a, b) => Number(a.sequence) - Number(b.sequence))[0];

                        if (priorPending) {
                            const isPriorInProgress = priorPending.computed_status === 'in_progress';
                            const msg = isPriorInProgress
                                ? priorPending.store_name + (this.isDriver ? ' masih dalam proses pengiriman. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke ' : ' masih dalam proses kunjungan. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke ') + currentStop.store_name + '.'
                                : (this.isDriver ? 'Silakan selesaikan pengiriman ' : 'Silakan selesaikan kunjungan ') + priorPending.store_name + (this.isDriver ? ' terlebih dahulu sebelum melakukan Check In ke ' : ' terlebih dahulu sebelum melakukan Check In ke ') + currentStop.store_name + '.';

                            this.showStopError(stopId, 'Belum dapat Check In', msg);
                            return;
                        }

                        // 2. Check active in_progress visit/delivery in this route
                        const activeInRoute = this.routeStops.find(s => s.computed_status === 'in_progress' && String(s.id) !== String(stopId));
                        if (activeInRoute) {
                            const msg = activeInRoute.store_name + (this.isDriver ? ' masih dalam proses pengiriman. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke ' : ' masih dalam proses kunjungan. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke ') + currentStop.store_name + '.';
                            this.showStopError(stopId, 'Belum dapat Check In', msg);
                            return;
                        }

                    window.location.href = targetUrl;
                },

                openEditRouteModal() {
                    this.editRouteStops = JSON.parse(JSON.stringify(this.rawStops)).sort((a, b) => a.sequence - b.sequence);
                    this.showEditRouteModal = true;
                },

                closeEditRouteModal() {
                    this.showEditRouteModal = false;
                },

                moveStopUp(idx) {
                    if (idx <= 0) return;
                    const temp = this.editRouteStops[idx];
                    this.editRouteStops[idx] = this.editRouteStops[idx - 1];
                    this.editRouteStops[idx - 1] = temp;
                    this.reassignEditSequences();
                },

                moveStopDown(idx) {
                    if (idx >= this.editRouteStops.length - 1) return;
                    const temp = this.editRouteStops[idx];
                    this.editRouteStops[idx] = this.editRouteStops[idx + 1];
                    this.editRouteStops[idx + 1] = temp;
                    this.reassignEditSequences();
                },

                removeEditStop(idx) {
                    if (this.editRouteStops.length <= 1) return;
                    this.editRouteStops.splice(idx, 1);
                    this.reassignEditSequences();
                },

                onStopSequenceDropdownChanged(fromIdx, targetSeq) {
                    const targetIdx = Number(targetSeq) - 1;
                    if (targetIdx < 0 || targetIdx >= this.editRouteStops.length || targetIdx === fromIdx) {
                        this.reassignEditSequences();
                        return;
                    }

                    const item = this.editRouteStops.splice(fromIdx, 1)[0];
                    this.editRouteStops.splice(targetIdx, 0, item);
                    this.reassignEditSequences();
                },

                onStopSequenceChanged(idx) {
                    this.editRouteStops.sort((a, b) => (Number(a.sequence) || 0) - (Number(b.sequence) || 0));
                    this.reassignEditSequences();
                },

                reassignEditSequences() {
                    this.editRouteStops.forEach((s, i) => {
                        s.sequence = i + 1;
                    });
                },

                async submitEditRoute() {
                    // Check duplicate store
                    const storeIds = this.editRouteStops.map(s => s.store_id);
                    if (new Set(storeIds).size !== storeIds.length) {
                        this.showToast('Toko dalam rute tidak boleh duplikat.', 'error');
                        return;
                    }

                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("route.sequence", $route->id) }}', {
                            method: 'PUT',
                            headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                 'Accept': 'application/json',
                             },
                             body: JSON.stringify({
                                 stops: this.editRouteStops.map(s => ({
                                     id: s.id || null,
                                     store_id: s.store_id,
                                     sequence: s.sequence,
                                     estimated_duration_minutes: Number(s.estimated_duration_minutes) || 30,
                                     notes: s.notes || null
                                 }))
                             }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal memperbarui rute');
                        this.showToast(data.message || 'Rute berhasil diperbarui.', 'success');
                        this.closeEditRouteModal();
                        setTimeout(() => window.location.reload(), 1000);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                addStopForm: {
                    store_id: '',
                    estimated_duration_minutes: 30,
                    notes: '',
                },

                get availableStores() {
                    return this.allStores.filter(s => !this.existingStoreIds.includes(s.id));
                },

                openAddStopModal() {
                    this.addStopForm = {
                        store_id: '',
                        estimated_duration_minutes: 30,
                        notes: '',
                    };
                    this.showAddStopModal = true;
                },

                closeAddStopModal() {
                    this.showAddStopModal = false;
                },

                async submitAddStop() {
                    if (!this.addStopForm.store_id) {
                        this.showToast('Silakan pilih tujuan.', 'error');
                        return;
                    }
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("route.stop.add", $route->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                store_id: this.addStopForm.store_id,
                                estimated_duration_minutes: {{ $isDriver ? 'null' : 'this.addStopForm.estimated_duration_minutes' }},
                                notes: this.addStopForm.notes || null
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal menambahkan tujuan ke rute');
                        this.showToast(data.message || 'Tujuan berhasil ditambahkan ke rute.', 'success');
                        this.closeAddStopModal();
                        setTimeout(() => window.location.reload(), 1000);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                init() {
                    const errorStopId = @js(session('error_stop_id'));
                    const errorMsg = @js(session('error'));

                    if (errorStopId && errorMsg) {
                        // Sequential check-in error: only display inline card alert, do not trigger global toast
                        this.showStopError(errorStopId, @js(session('error_title') ?? 'Belum dapat Check In'), errorMsg);
                        this.$nextTick(() => {
                            const el = document.getElementById('stop-' + errorStopId);
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    } else if (errorMsg) {
                        // Other general errors: display global toast
                        this.showToast(errorMsg, 'error');
                    }

                    @if(session('warning'))
                    this.showToast(@js(session('warning')), 'warning');
                    @endif
                    @if(session('success'))
                    this.showToast(@js(session('success')), 'success');
                    @endif

                    if (!errorStopId && window.location.hash && window.location.hash.startsWith('#stop-')) {
                        this.$nextTick(() => {
                            const el = document.querySelector(window.location.hash);
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    }
                },

                startGpsTracking() {
                    if (window.isSecureContext !== true || this.gpsTracking) return;
                    if (!navigator.geolocation) {
                        console.warn('Geolocation not supported');
                        return;
                    }
                    this.gpsTracking = true;
                    this.gpsWatchId = navigator.geolocation.watchPosition(
                        (position) => {
                            try { localStorage.setItem('sw-permission-location', '1'); } catch (e) {}
                            this.sendGpsLocation(position);
                        },
                        (error) => {
                            if (error && error.code === 1) { try { localStorage.removeItem('sw-permission-location'); } catch (e) {} }
                            console.warn('GPS error:', error.message);
                        },
                        { enableHighAccuracy: true, maximumAge: 30000, timeout: 15000 }
                    );
                },

                stopGpsTracking() {
                    if (this.gpsWatchId) {
                        navigator.geolocation.clearWatch(this.gpsWatchId);
                        this.gpsWatchId = null;
                    }
                    this.gpsTracking = false;
                },

                async sendGpsLocation(position) {
                    try {
                        await fetch('{{ route('route.gps.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                route_id: '{{ $route->id }}',
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                altitude: position.coords.altitude,
                                accuracy: position.coords.accuracy,
                                speed: position.coords.speed,
                                recorded_at: new Date(position.timestamp).toISOString(),
                            }),
                        });
                    } catch (e) {
                        console.warn('Failed to send GPS:', e);
                    }
                },

                async startRoute() {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("route.start", $route->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal memulai rute');
                        this.showToast(data.message || 'Rute berhasil dimulai', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async completeRoute() {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("route.complete", $route->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal menyelesaikan rute');
                        this.showToast(data.message || 'Rute berhasil diselesaikan', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async updateStop(stopId, status) {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("route.stop.update", [$route->id, ":id"]) }}'.replace(':id', stopId), {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ status }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal memperbarui status toko');
                        const msg = status === 'skipped' ? 'Kunjungan berhasil dilewati.' : (data.message || 'Status toko berhasil diperbarui');
                        this.showToast(msg, 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                openSkipModal(stopId) {
                    this.skipStopId = stopId;
                    this.skipFacing = 'user';
                    this.skipForm = {
                        notes: '',
                        photo: null,
                    };
                    this.skipCameraActive = false;
                    this.showSkipModal = true;
                    this.getRealtimeGps();
                },

                getRealtimeGps() {
                    if (!navigator.geolocation) return;
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.skipLat = position.coords.latitude;
                            this.skipLng = position.coords.longitude;
                            this.skipMapsUrl = `https://www.google.com/maps?q=${this.skipLat},${this.skipLng}`;
                            this.reverseGeocode(this.skipLat, this.skipLng);
                        },
                        (err) => {
                            console.warn('Skip GPS warning:', err.message);
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                async reverseGeocode(lat, lng) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`);
                        const data = await res.json();
                        this.skipAddress = data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    } catch {
                        this.skipAddress = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    }
                },

                closeSkipModal() {
                    this.stopSkipCamera();
                    this.showSkipModal = false;
                    this.skipStopId = null;
                },

                async startSkipCamera() {
                    if (window.isSecureContext !== true) {
                        this.showToast('Fitur kamera memerlukan koneksi aman (HTTPS).', 'warning');
                        return;
                    }
                    if (this.skipStream) {
                        this.stopSkipCamera();
                    }
                    this.skipCameraActive = true;
                    try {
                        const constraints = {
                            video: {
                                facingMode: { ideal: this.skipFacing },
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            },
                            audio: false
                        };
                        const stream = await navigator.mediaDevices.getUserMedia(constraints);
                        this.skipStream = stream;
                        this.$nextTick(() => {
                            const video = document.getElementById('skip-camera');
                            if (video) {
                                video.srcObject = stream;
                                // Mirror preview hanya untuk kamera depan (user)
                                video.style.transform = this.skipFacing === 'user' ? 'scaleX(-1)' : 'none';
                                video.play().catch(() => {});
                            }
                        });
                    } catch (e) {
                        try {
                            const fallbackStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                            this.skipStream = fallbackStream;
                            this.$nextTick(() => {
                                const video = document.getElementById('skip-camera');
                                if (video) {
                                    video.srcObject = fallbackStream;
                                    video.style.transform = this.skipFacing === 'user' ? 'scaleX(-1)' : 'none';
                                    video.play().catch(() => {});
                                }
                            });
                        } catch (e2) {
                            const msg = (e2.name === 'NotAllowedError' || e2.name === 'PermissionDeniedError')
                                ? 'Izin kamera ditolak. Silakan izinkan akses kamera di browser Anda.'
                                : 'Gagal mengakses kamera.';
                            this.showToast(msg, 'error');
                            this.skipCameraActive = false;
                        }
                    }
                },

                async flipSkipCamera() {
                    this.skipFacing = this.skipFacing === 'user' ? 'environment' : 'user';
                    await this.startSkipCamera();
                },

                stopSkipCamera() {
                    if (this.skipStream) {
                        this.skipStream.getTracks().forEach(t => t.stop());
                        this.skipStream = null;
                    }
                    const video = document.getElementById('skip-camera');
                    if (video) {
                        video.srcObject = null;
                    }
                    this.skipCameraActive = false;
                },

                captureSkipPhoto() {
                    const video = document.getElementById('skip-camera');
                    const canvas = document.getElementById('skip-canvas');
                    if (!video || !canvas) return;

                    const width = video.videoWidth || 640;
                    const height = video.videoHeight || 480;
                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    if (this.skipFacing === 'user') {
                        ctx.translate(width, 0);
                        ctx.scale(-1, 1);
                    } else {
                        ctx.setTransform(1, 0, 0, 1, 0, 0);
                    }
                    ctx.drawImage(video, 0, 0, width, height);
                    ctx.setTransform(1, 0, 0, 1, 0, 0);

                    this.skipForm.photo = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopSkipCamera();
                },

                retakeSkipPhoto() {
                    this.skipForm.photo = null;
                    this.startSkipCamera();
                },

                async confirmSkip() {
                    if (!this.skipStopId) return;
                    if (!this.skipForm.notes || this.skipForm.notes.trim() === '') {
                        this.showToast('Alasan / catatan wajib diisi.', 'error');
                        return;
                    }
                    if (!this.skipForm.photo) {
                        this.showToast('Foto bukti wajib diisi.', 'error');
                        return;
                    }

                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("route.stop.update", [$route->id, ":id"]) }}'.replace(':id', this.skipStopId), {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                status: 'skipped',
                                notes: this.skipForm.notes,
                                photo: this.skipForm.photo,
                                latitude: this.skipLat,
                                longitude: this.skipLng,
                                address: this.skipAddress,
                                maps_url: this.skipMapsUrl
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal melewati kunjungan.');
                        this.showToast(data.message || 'Kunjungan berhasil dilewati.', 'success');
                        this.closeSkipModal();
                        setTimeout(() => window.location.reload(), 1000);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                confirmDelete() {
                    this.showDeleteModal = true;
                },

                async deleteRoute() {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("route.destroy", $route->id) }}', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal menghapus rute');
                        this.showToast(data.message || 'Rute berhasil dihapus', 'success');
                        setTimeout(() => window.location.href = '{{ route("route.index") }}', 1200);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                        this.showDeleteModal = false;
                    } finally {
                        this.loading = false;
                    }
                },

                showToast(message, type) {
                    this.toast = { show: true, message, type };
                    setTimeout(() => this.toast.show = false, 4000);
                },
            };
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>