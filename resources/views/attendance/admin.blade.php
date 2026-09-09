<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="clock" title="Monitoring Presensi" subtitle="Pantau seluruh presensi Sales, Driver, dan Staff"></x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6" x-data="{
        cancelTarget: null,
        deleteTarget: null,
        detailTarget: null,
        openDetail(data) {
            this.detailTarget = null;
            this.$nextTick(() => {
                this.detailTarget = Object.assign({}, data);
            });
        },
        closeDetail() {
            this.detailTarget = null;
        }
    }">
        {{-- Flash Messages dengan Auto-dismiss --}}
        @if (session('success'))
            <div class="rounded-2xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 p-4 text-sm text-green-800 dark:text-green-200 shadow-sm"
                x-cloak x-data="{show:true}" x-show="show" x-init="setTimeout(() => show = false, 4500)" x-transition>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1 font-medium">{{ session('success') }}</span>
                    <button @click="show=false" class="text-green-500 hover:text-green-700 dark:hover:text-green-300 font-bold">&times;</button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 p-4 text-sm text-red-800 dark:text-red-200 shadow-sm"
                x-cloak x-data="{show:true}" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1 font-medium">{{ session('error') }}</span>
                    <button @click="show=false" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 font-bold">&times;</button>
                </div>
            </div>
        @endif

        {{-- Ringkasan Statistik Responsive --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-8 gap-3 sm:gap-4">
            {{-- Total Pengguna --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['total_users'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Total Pengguna</p>
                    </div>
                </div>
            </div>

            {{-- Sudah Presensi --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-1 flex-wrap">
                            <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['present'] ?? 0 }}</p>
                            <span class="text-[11px] font-bold text-[#0DA4CE]">({{ $stats['percentage'] ?? 0 }}%)</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Sudah Presensi</p>
                    </div>
                </div>
            </div>

            {{-- Sudah Check-In --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['checked_in'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Sudah Check-In</p>
                    </div>
                </div>
            </div>

            {{-- Sudah Check-Out --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['checked_out'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Sudah Check-Out</p>
                    </div>
                </div>
            </div>

            {{-- Izin --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['izin'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Izin</p>
                    </div>
                </div>
            </div>

            {{-- Sakit --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['sakit'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Sakit</p>
                    </div>
                </div>
            </div>

            {{-- Belum Presensi --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['not_present'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Belum Presensi</p>
                    </div>
                </div>
            </div>

            {{-- Dibatalkan --}}
            <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $stats['canceled'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight truncate">Dibatalkan</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Card --}}
        @php
            $filterInputClass = 'w-full border-2 border-gray-300 rounded-lg px-3 py-3 text-sm bg-white text-gray-900 placeholder-gray-400 transition focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/30 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200';
        @endphp
        <div id="sw-attendance-filter-card" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="flex flex-col lg:flex-row flex-wrap items-end gap-3">
                <div class="w-full lg:w-44">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Periode</label>
                    <select name="period" class="{{ $filterInputClass }}" onchange="this.form.submit()">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="date" {{ $period === 'date' ? 'selected' : '' }}>Tanggal Tertentu</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Mingguan</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulanan</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Rentang Tanggal</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[150px]" id="date-field" @if($period !== 'date') style="display:none" @endif>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}" class="{{ $filterInputClass }}">
                </div>

                <div class="flex-1 min-w-[150px]" id="from-field" @if($period !== 'custom') style="display:none" @endif>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dari</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="{{ $filterInputClass }}">
                </div>

                <div class="flex-1 min-w-[150px]" id="to-field" @if($period !== 'custom') style="display:none" @endif>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Sampai</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="{{ $filterInputClass }}">
                </div>

                <div class="sw-attendance-pair w-full min-w-0 lg:contents">
                    <div class="w-full lg:w-40">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Role</label>
                        <select name="role" class="{{ $filterInputClass }}">
                            <option value="">Semua Role</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full lg:w-44">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" class="{{ $filterInputClass }}">
                            <option value="">Semua Status</option>
                            <option value="hadir" {{ request('status') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Check In</option>
                            <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Check Out</option>
                            <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="not_present" {{ in_array(request('status'), ['not_present', 'belum_presensi'], true) ? 'selected' : '' }}>Belum Presensi</option>
                            <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Cari Nama</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama pengguna..." class="{{ $filterInputClass }} pl-10">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    @if(request()->anyFilled(['period', 'search', 'role', 'status', 'date', 'from_date', 'to_date']) && (request('period') !== 'today' || request()->filled('search') || request()->filled('role') || request()->filled('status')))
                    <a href="{{ route('admin.attendance.index') }}"
                        class="inline-flex items-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Mobile Cards --}}
        <div class="sm:hidden space-y-4">
            @forelse($attendances as $attendance)
            @php
                $cancelMeta = $attendance->cancelled_meta;
                $status = $attendance->status_presensi;
                $statusLabel = $attendance->status_presensi_label;
                $roleLabel = collect($attendance->user?->roles ?? [])
                    ->pluck('name')
                    ->map(fn ($r) => ucfirst(str_replace('-', ' ', $r)))
                    ->implode(', ');
                $badgeClass = match($status) {
                    'canceled' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
                    'checked_out' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
                    'checked_in' => 'bg-[#0DA4CE]/10 text-[#0DA4CE]',
                    'izin' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                    'sakit' => 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300',
                    default => 'bg-gray-100/70 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400',
                };
            @endphp
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 dark:text-white truncate">{{ $attendance->user->name ?? '-' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $attendance->date?->format('d M Y') ?? '-' }} &middot; <span class="font-medium text-gray-600 dark:text-gray-300">{{ $roleLabel }}</span></p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0 {{ $badgeClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                @if($attendance->is_canceled && $cancelMeta)
                    <div class="text-xs bg-red-50 dark:bg-red-950/30 p-2.5 rounded-xl border border-red-200/60 dark:border-red-800/40">
                        <p class="font-semibold text-red-700 dark:text-red-300">Catatan Pembatalan:</p>
                        <p class="truncate text-red-600 dark:text-red-400 mt-0.5">{{ $cancelMeta['reason'] ?? '-' }}</p>
                    </div>
                @elseif($status === 'izin' || $status === 'sakit')
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800">
                        <span>Status: <strong class="text-gray-900 dark:text-white">{{ $statusLabel }}</strong></span>
                        <span class="text-gray-400">Lihat di Detail</span>
                    </div>
                @else
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800">
                        <span>Masuk: <strong class="text-gray-900 dark:text-white">{{ $attendance->clock_in?->format('H:i') ?? '-' }}</strong></span>
                        <span>Keluar: <strong class="text-gray-900 dark:text-white">{{ $attendance->clock_out?->format('H:i') ?? '-' }}</strong></span>
                    </div>
                @endif

                @if($attendance->id)
                <div class="flex items-center gap-2 pt-1">
                    <button type="button" @click="openDetail({
                        id: '{{ $attendance->id }}',
                        name: '{{ e($attendance->user->name ?? '-') }}',
                        username: '{{ e($attendance->user->username ?? '-') }}',
                        role: '{{ e($roleLabel) }}',
                        date: '{{ $attendance->date?->format('d M Y') ?? '-' }}',
                        status: '{{ $status }}',
                        statusLabel: '{{ $statusLabel }}',
                        badgeClass: '{{ $badgeClass }}',
                        clockIn: '{{ $attendance->clock_in?->format('H:i') ?? '-' }}',
                        clockOut: '{{ $attendance->clock_out?->format('H:i') ?? '-' }}',
                        absenceNote: '{{ in_array($status, ['izin', 'sakit']) ? e($attendance->absence_note ?? '') : '' }}',
                        notes: '{{ !$attendance->is_canceled ? e($attendance->notes ?? '') : '' }}',
                        address: '{{ e($attendance->clock_in_address ?? '-') }}',
                        mapsUrl: '{{ $attendance->clock_in_maps_url ?? '' }}',
                        lat: '{{ $attendance->clock_in_lat ?? '' }}',
                        lng: '{{ $attendance->clock_in_lng ?? '' }}',
                        photoUrl: '{{ $attendance->clock_in_selfie ? Storage::url($attendance->clock_in_selfie) : '' }}',
                        outPhotoUrl: '{{ $attendance->clock_out_selfie ? Storage::url($attendance->clock_out_selfie) : '' }}',
                        isCanceled: {{ $attendance->is_canceled ? 'true' : 'false' }},
                        prevStatus: '{{ $cancelMeta['previous_status'] ?? '' }}',
                        cancelReason: '{{ e($cancelMeta['reason'] ?? '') }}',
                        cancellerName: '{{ e($cancelMeta ? ($cancellers[$cancelMeta['cancelled_by']]?->name ?? '-') : '') }}',
                        cancelTime: '{{ $cancelMeta ? \Carbon\Carbon::parse($cancelMeta['cancelled_at'])->format('d M Y H:i') : '' }}'
                    })" class="flex-1 py-2 text-xs font-semibold text-[#0DA4CE] bg-[#0DA4CE]/10 rounded-lg hover:bg-[#0DA4CE]/20 transition text-center">
                        Detail
                    </button>

                    @if($status !== 'canceled')
                    <button type="button" @click="cancelTarget = {
                        id: '{{ $attendance->id }}',
                        name: '{{ e($attendance->user->name ?? '-') }}',
                        role: '{{ e($roleLabel) }}',
                        date: '{{ $attendance->date?->format('d M Y') ?? '-' }}',
                        clockin: '{{ $attendance->clock_in?->format('H:i') ?? '-' }}',
                        clockout: '{{ $attendance->clock_out?->format('H:i') ?? '-' }}',
                        url: '{{ route('admin.attendance.cancel', $attendance->id) }}'
                    }" class="flex-1 py-2 text-xs font-semibold text-amber-600 bg-amber-50 dark:bg-amber-900/30 rounded-lg hover:bg-amber-100 transition text-center">
                        Batal
                    </button>
                    @endif

                    <button type="button" @click="deleteTarget = {
                        id: '{{ $attendance->id }}',
                        name: '{{ e($attendance->user->name ?? '-') }}',
                        date: '{{ $attendance->date?->format('d M Y') ?? '-' }}',
                        clockin: '{{ $attendance->clock_in?->format('H:i') ?? '-' }}',
                        clockout: '{{ $attendance->clock_out?->format('H:i') ?? '-' }}',
                        url: '{{ route('admin.attendance.destroy', $attendance->id) }}'
                    }" class="py-2 px-3 text-xs font-semibold text-red-600 bg-red-50 dark:bg-red-900/30 rounded-lg hover:bg-red-100 transition text-center">
                        Hapus
                    </button>
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-12 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada data presensi</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop Table --}}
        <div class="hidden sm:block bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check In</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check Out</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Presensi</th>
                            {{-- Kolom Catatan Pembatalan Murni --}}
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Catatan Pembatalan</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($attendances as $attendance)
                        @php
                            $cancelMeta = $attendance->cancelled_meta;
                            $status = $attendance->status_presensi;
                            $statusLabel = $attendance->status_presensi_label;
                            $roleLabel = collect($attendance->user?->roles ?? [])
                                ->pluck('name')
                                ->map(fn ($r) => ucfirst(str_replace('-', ' ', $r)))
                                ->implode(', ');
                            $badgeClass = match($status) {
                                'canceled' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
                                'checked_out' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
                                'checked_in' => 'bg-[#0DA4CE]/10 text-[#0DA4CE]',
                                'izin' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                                'sakit' => 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300',
                                default => 'bg-gray-100/70 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-xs font-bold text-[#0DA4CE]">
                                        {{ strtoupper(substr($attendance->user->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $attendance->user->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-400">{{ $attendance->user->username ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @foreach($attendance->user?->roles ?? [] as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendance->date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($status === 'izin' || $status === 'sakit') - @else {{ $attendance->clock_in?->format('H:i') ?? '-' }} @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($status === 'izin' || $status === 'sakit') - @else {{ $attendance->clock_out?->format('H:i') ?? '-' }} @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            {{-- Kolom Catatan Pembatalan (Hanya berisi alasan pembatalan) --}}
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[240px]">
                                @if($attendance->is_canceled && $cancelMeta)
                                    <p class="truncate font-medium text-red-600 dark:text-red-400" title="{{ $cancelMeta['reason'] ?? '' }}">
                                        {{ $cancelMeta['reason'] ?? '-' }}
                                    </p>
                                    <p class="text-[11px] text-gray-400 truncate">Oleh: {{ $cancellers[$cancelMeta['cancelled_by']]?->name ?? '-' }}</p>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                @if($attendance->id)
                                <div class="inline-flex items-center gap-1.5">
                                    {{-- Tombol Detail --}}
                                    <button type="button" @click="openDetail({
                                        id: '{{ $attendance->id }}',
                                        name: '{{ e($attendance->user->name ?? '-') }}',
                                        username: '{{ e($attendance->user->username ?? '-') }}',
                                        role: '{{ e($roleLabel) }}',
                                        date: '{{ $attendance->date?->format('d M Y') ?? '-' }}',
                                        status: '{{ $status }}',
                                        statusLabel: '{{ $statusLabel }}',
                                        badgeClass: '{{ $badgeClass }}',
                                        clockIn: '{{ $attendance->clock_in?->format('H:i') ?? '-' }}',
                                        clockOut: '{{ $attendance->clock_out?->format('H:i') ?? '-' }}',
                                        absenceNote: '{{ in_array($status, ['izin', 'sakit']) ? e($attendance->absence_note ?? '') : '' }}',
                                        notes: '{{ !$attendance->is_canceled ? e($attendance->notes ?? '') : '' }}',
                                        address: '{{ e($attendance->clock_in_address ?? '-') }}',
                                        mapsUrl: '{{ $attendance->clock_in_maps_url ?? '' }}',
                                        lat: '{{ $attendance->clock_in_lat ?? '' }}',
                                        lng: '{{ $attendance->clock_in_lng ?? '' }}',
                                        photoUrl: '{{ $attendance->clock_in_selfie ? Storage::url($attendance->clock_in_selfie) : '' }}',
                                        outPhotoUrl: '{{ $attendance->clock_out_selfie ? Storage::url($attendance->clock_out_selfie) : '' }}',
                                        isCanceled: {{ $attendance->is_canceled ? 'true' : 'false' }},
                                        prevStatus: '{{ $cancelMeta['previous_status'] ?? '' }}',
                                        cancelReason: '{{ e($cancelMeta['reason'] ?? '') }}',
                                        cancellerName: '{{ e($cancelMeta ? ($cancellers[$cancelMeta['cancelled_by']]?->name ?? '-') : '') }}',
                                        cancelTime: '{{ $cancelMeta ? \Carbon\Carbon::parse($cancelMeta['cancelled_at'])->format('d M Y H:i') : '' }}'
                                    })" class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-[#0DA4CE] bg-[#0DA4CE]/10 rounded-lg hover:bg-[#0DA4CE]/20 transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Detail
                                    </button>

                                    @if($status !== 'canceled')
                                    <button type="button" @click="cancelTarget = {
                                        id: $el.dataset.id,
                                        name: $el.dataset.name,
                                        role: $el.dataset.role,
                                        date: $el.dataset.date,
                                        clockin: $el.dataset.clockin,
                                        clockout: $el.dataset.clockout,
                                        url: $el.dataset.url
                                    }"
                                        data-id="{{ $attendance->id }}"
                                        data-name="{{ e($attendance->user->name ?? '-') }}"
                                        data-role="{{ e($roleLabel) }}"
                                        data-date="{{ $attendance->date?->format('d M Y') ?? '-' }}"
                                        data-clockin="{{ $attendance->clock_in?->format('H:i') ?? '-' }}"
                                        data-clockout="{{ $attendance->clock_out?->format('H:i') ?? '-' }}"
                                        data-url="{{ route('admin.attendance.cancel', $attendance->id) }}"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-amber-600 bg-amber-50 dark:bg-amber-900/40 rounded-lg hover:bg-amber-100 transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Batal
                                    </button>
                                    @endif

                                    <button type="button" @click="deleteTarget = {
                                        id: $el.dataset.id,
                                        name: $el.dataset.name,
                                        date: $el.dataset.date,
                                        clockin: $el.dataset.clockin,
                                        clockout: $el.dataset.clockout,
                                        url: $el.dataset.url
                                    }"
                                        data-id="{{ $attendance->id }}"
                                        data-name="{{ e($attendance->user->name ?? '-') }}"
                                        data-date="{{ $attendance->date?->format('d M Y') ?? '-' }}"
                                        data-clockin="{{ $attendance->clock_in?->format('H:i') ?? '-' }}"
                                        data-clockout="{{ $attendance->clock_out?->format('H:i') ?? '-' }}"
                                        data-url="{{ route('admin.attendance.destroy', $attendance->id) }}"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-red-600 bg-red-50 dark:bg-red-900/40 rounded-lg hover:bg-red-100 transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Hapus
                                    </button>
                                </div>
                                @else
                                <span class="text-xs text-gray-400 dark:text-gray-600 font-medium">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data presensi</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($attendances->hasPages())
        <div class="mt-6">{{ $attendances->links() }}</div>
        @endif

        {{-- Modal Detail Presensi Lengkap (Fixed viewport & non-clipping header) --}}
        <div x-show="detailTarget" x-cloak x-transition.opacity
            class="fixed inset-0 z-[70] bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-sm overflow-y-auto p-4 sm:p-6 flex justify-center items-start min-h-screen"
            @click.self="closeDetail()" @keydown.escape.window="closeDetail()">
            <div x-show="detailTarget" x-cloak x-transition.scale.origin.top
                class="relative w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 my-auto sm:my-8 overflow-hidden flex flex-col max-h-[calc(100vh-4rem)]">
                
                {{-- Modal Header (Fixed at top) --}}
                <div class="px-6 py-4.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0 bg-white dark:bg-gray-900">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center text-[#0DA4CE] font-extrabold text-sm shrink-0">
                            <span x-text="detailTarget ? detailTarget.name.substring(0,2).toUpperCase() : 'PR'"></span>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white truncate" x-text="detailTarget ? detailTarget.name : ''"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><span x-text="detailTarget ? detailTarget.role : ''"></span> &middot; <span x-text="detailTarget ? detailTarget.username : ''"></span></p>
                        </div>
                    </div>
                    <button type="button" @click="closeDetail()" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Content Scrollable Area --}}
                <div class="overflow-y-auto px-6 py-5 space-y-4 text-sm scrollbar-thin flex-1">
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500 dark:text-gray-400">Tanggal</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="detailTarget ? detailTarget.date : ''"></span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500 dark:text-gray-400">Status Presensi</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                            :class="detailTarget ? detailTarget.badgeClass : ''"
                            x-text="detailTarget ? detailTarget.statusLabel : ''"></span>
                    </div>

                    {{-- Informasi Hadir (Jam Check In & Check Out) --}}
                    <template x-if="detailTarget && (detailTarget.status === 'checked_in' || detailTarget.status === 'checked_out' || detailTarget.status === 'present')">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">Jam Check In</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="detailTarget.clockIn"></span>
                            </div>
                            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">Jam Check Out</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="detailTarget.clockOut"></span>
                            </div>
                            <template x-if="detailTarget.notes">
                                <div class="py-2.5 bg-gray-50 dark:bg-gray-800/60 p-3.5 rounded-xl border border-gray-200/70 dark:border-gray-700/60">
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Keterangan:</p>
                                    <p class="text-xs text-gray-800 dark:text-gray-200 leading-relaxed" x-text="detailTarget.notes"></p>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Catatan Izin (Hanya untuk Izin) --}}
                    <template x-if="detailTarget && detailTarget.status === 'izin' && detailTarget.absenceNote">
                        <div class="py-2.5 bg-amber-50/70 dark:bg-amber-950/30 p-3.5 rounded-xl border border-amber-200/70 dark:border-amber-800/40">
                            <p class="text-xs font-bold text-amber-800 dark:text-amber-300 mb-1">Catatan / Alasan Izin:</p>
                            <p class="text-xs text-gray-800 dark:text-gray-200 leading-relaxed" x-text="detailTarget.absenceNote"></p>
                        </div>
                    </template>

                    {{-- Catatan Sakit (Hanya untuk Sakit) --}}
                    <template x-if="detailTarget && detailTarget.status === 'sakit' && detailTarget.absenceNote">
                        <div class="py-2.5 bg-pink-50/70 dark:bg-pink-950/30 p-3.5 rounded-xl border border-pink-200/70 dark:border-pink-800/40">
                            <p class="text-xs font-bold text-pink-800 dark:text-pink-300 mb-1">Keterangan Sakit:</p>
                            <p class="text-xs text-gray-800 dark:text-gray-200 leading-relaxed" x-text="detailTarget.absenceNote"></p>
                        </div>
                    </template>

                    {{-- Info Lokasi & Maps --}}
                    <div class="py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Lokasi Presensi</p>
                        <p class="text-xs font-medium text-gray-900 dark:text-white leading-relaxed" x-text="detailTarget ? detailTarget.address : '-'"></p>
                        <template x-if="detailTarget && detailTarget.lat && detailTarget.lng">
                            <p class="text-[11px] text-gray-400 mt-0.5">Koordinat: <span x-text="detailTarget.lat + ', ' + detailTarget.lng"></span></p>
                        </template>
                        <template x-if="detailTarget && detailTarget.mapsUrl">
                            <a :href="detailTarget.mapsUrl" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-green-600 dark:text-green-400 hover:underline mt-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Buka di Google Maps
                            </a>
                        </template>
                    </div>

                    {{-- Foto Presensi / Dokumentasi --}}
                    <template x-if="detailTarget && detailTarget.photoUrl">
                        <div class="pt-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Foto Presensi / Dokumentasi</p>
                            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 max-h-56 bg-black flex items-center justify-center">
                                <img :src="detailTarget.photoUrl" class="w-full h-full object-contain max-h-56" alt="Foto Presensi">
                            </div>
                        </div>
                    </template>

                    {{-- Metadata Pembatalan jika record dibatalkan --}}
                    <template x-if="detailTarget && detailTarget.isCanceled">
                        <div class="p-3.5 bg-red-50 dark:bg-red-950/40 rounded-xl border border-red-200 dark:border-red-800 text-xs space-y-1.5">
                            <p class="font-bold text-red-800 dark:text-red-300">Presensi Ini Telah Dibatalkan</p>
                            <template x-if="detailTarget.prevStatus">
                                <p class="text-gray-700 dark:text-gray-300">Status Awal: <span class="font-semibold uppercase" x-text="detailTarget.prevStatus"></span></p>
                            </template>
                            <p class="text-gray-700 dark:text-gray-300">Dibatalkan oleh: <span class="font-semibold" x-text="detailTarget.cancellerName"></span> (<span x-text="detailTarget.cancelTime"></span>)</p>
                            <p class="text-gray-700 dark:text-gray-300">Alasan: <span class="font-semibold" x-text="detailTarget.cancelReason"></span></p>
                        </div>
                    </template>
                </div>

                {{-- Modal Footer (Fixed at bottom) --}}
                <div class="px-6 py-3.5 border-t border-gray-100 dark:border-gray-800 flex justify-end shrink-0 bg-gray-50/50 dark:bg-gray-800/50">
                    <button type="button" @click="closeDetail()" class="w-full sm:w-auto px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl text-sm font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Batal Presensi --}}
        <div x-show="cancelTarget" x-cloak x-transition.opacity
            class="fixed inset-0 z-[60] bg-gray-900/50 dark:bg-gray-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="cancelTarget = null" @keydown.escape.window="cancelTarget = null">
            <div x-show="cancelTarget" x-cloak x-transition.scale.origin.center
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Batal Presensi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Yakin ingin membatalkan presensi ini?</p>
                        </div>
                        <button type="button" @click="cancelTarget = null"
                            class="p-1.5 -m-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4 space-y-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center text-[11px] font-bold text-white shrink-0">!</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="cancelTarget ? cancelTarget.name : ''"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="cancelTarget ? cancelTarget.role : ''"></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Tanggal</span>
                            <span class="text-gray-900 dark:text-white font-medium" x-text="cancelTarget ? cancelTarget.date : ''"></span>
                        </div>
                    </div>

                    <form method="POST" :action="cancelTarget ? cancelTarget.url : '#'" class="mt-5">
                        @csrf
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Alasan Pembatalan <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="3" required minlength="3" maxlength="1000"
                            placeholder="Tuliskan alasan pembatalan presensi..."
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white text-gray-900 placeholder-gray-400 transition focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/30 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"></textarea>

                        <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                            <button type="button" @click="cancelTarget = null"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                Tutup
                            </button>
                            <button type="submit"
                                class="inline-flex items-center justify-center w-full px-5 py-2.5 bg-amber-600 text-white rounded-xl text-sm font-semibold hover:bg-amber-700 transition shadow-sm shadow-amber-600/20">
                                Batalkan Presensi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Hapus Presensi --}}
        <div x-show="deleteTarget" x-cloak x-transition.opacity
            class="fixed inset-0 z-[60] bg-gray-900/50 dark:bg-gray-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="deleteTarget = null" @keydown.escape.window="deleteTarget = null">
            <div x-show="deleteTarget" x-cloak x-transition.scale.origin.center
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Presensi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Yakin ingin menghapus presensi ini? Tindakan ini permanen.</p>
                        </div>
                        <button type="button" @click="deleteTarget = null"
                            class="p-1.5 -m-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button" @click="deleteTarget = null"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Batal
                        </button>
                        <form method="POST" :action="deleteTarget ? deleteTarget.url : '#'" class="sm:order-2">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center justify-center w-full px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition shadow-sm shadow-red-600/20">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const period = document.querySelector('select[name="period"]');
            const toggle = () => {
                const v = period.value;
                document.getElementById('date-field').style.display = v === 'date' ? '' : 'none';
                document.getElementById('from-field').style.display = v === 'custom' ? '' : 'none';
                document.getElementById('to-field').style.display = v === 'custom' ? '' : 'none';
            };
            period.addEventListener('change', toggle);
            toggle();
        });
    </script>
</x-app-layout>
