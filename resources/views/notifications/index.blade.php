<x-app-layout>
    @section('breadcrumbs')
        <span>Notifikasi</span>
    @endsection

    @php
        $isAdminOrSuperAdmin = Auth::user()->hasAnyRole(['admin', 'super-admin']);
        $isSuperAdmin = Auth::user()->hasRole('super-admin');
        $readAllRoute = $isAdminOrSuperAdmin ? route('admin.notifications.read-all') : route('notifications.read-all');
        $destroyAllRoute = $isAdminOrSuperAdmin ? route('admin.notifications.destroy-all') : route('notifications.destroy-all');
        $indexRoute = $isAdminOrSuperAdmin ? route('admin.notifications.index') : route('notifications.index');
    @endphp

    <div class="space-y-3 sm:space-y-5 max-w-5xl mx-auto w-full px-0 sm:px-2" x-data="{ showDeleteAllModal: false }">
        {{-- Session Flash Toast / Alerts --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                 class="p-3 sm:p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs sm:text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-0.5" aria-label="Tutup notifikasi sukses">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if (session('info'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                 class="p-3 sm:p-4 rounded-xl bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800 text-cyan-800 dark:text-cyan-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#0DA4CE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs sm:text-sm font-medium">{{ session('info') }}</span>
                </div>
                <button @click="show = false" class="text-[#0DA4CE] hover:text-[#097A99] p-0.5" aria-label="Tutup info">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                 class="p-3 sm:p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs sm:text-sm font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-700 p-0.5" aria-label="Tutup notifikasi error">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Notifikasi</h1>
                    @if($unreadCount > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-cyan-100 dark:bg-cyan-950/60 text-[#097A99] dark:text-[#0DA4CE] border border-cyan-200/80 dark:border-cyan-800">
                            {{ $unreadCount }} Belum Dibaca
                        </span>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 leading-normal">
                    @if($isAdminOrSuperAdmin)
                        Pantau riwayat aktivitas operasional Sales dan Staff.
                    @else
                        Informasi dan aktivitas terbaru akun Anda dari Admin & Super Admin.
                    @endif
                </p>
            </div>
            
            @if($unreadCount > 0 || $notifications->total() > 0)
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto shrink-0">
                    @if($unreadCount > 0)
                        <form action="{{ $readAllRoute }}" method="POST" class="w-full sm:w-auto col-span-1">
                            @csrf
                            <button type="submit" class="sw-btn sw-btn-ghost w-full justify-center text-xs py-2 px-2.5 sm:px-3.5 flex items-center gap-1.5 min-h-[38px]">
                                <svg class="w-3.5 h-3.5 text-[#0DA4CE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="truncate">Tandai Dibaca</span>
                            </button>
                        </form>
                    @endif

                    @if($notifications->total() > 0 && $isAdminOrSuperAdmin)
                        <button type="button" @click="showDeleteAllModal = true"
                                class="sw-btn bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-950/30 dark:hover:bg-red-900/40 dark:text-red-400 border border-red-200 dark:border-red-800/60 w-full sm:w-auto justify-center text-xs py-2 px-2.5 sm:px-3.5 flex items-center gap-1.5 min-h-[38px] transition {{ $unreadCount === 0 ? 'col-span-2' : 'col-span-1' }}">
                            <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span class="truncate">Hapus Semua</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Filter Container (Mobile-Optimized & Responsive) --}}
        <div class="sw-card p-3 sm:p-4">
            <form method="GET" action="{{ $indexRoute }}" class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
                {{-- Category Tabs: Smooth Touch Horizontal Scroll on Mobile --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden w-full md:w-auto">
                    @php
                        $currentType = request('type', 'all');
                        $currentStatus = request('status', '');
                    @endphp
                    <a href="{{ $indexRoute . '?' . http_build_query(array_merge(request()->except('page', 'type'), ['type' => 'all'])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors shrink-0 {{ $currentType === 'all' || !$currentType ? 'bg-[#0DA4CE] text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                        Semua
                    </a>
                    @hasanyrole('super-admin|admin|staff')
                        <a href="{{ $indexRoute . '?' . http_build_query(array_merge(request()->except('page', 'type'), ['type' => 'attendance'])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors shrink-0 {{ $currentType === 'attendance' ? 'bg-[#0DA4CE] text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                            Presensi
                        </a>
                    @endhasanyrole
                    @hasanyrole('super-admin|admin|sales')
                    <a href="{{ $indexRoute . '?' . http_build_query(array_merge(request()->except('page', 'type'), ['type' => 'route'])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors shrink-0 {{ $currentType === 'route' ? 'bg-[#0DA4CE] text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                        Rute Kunjungan
                    </a>
                    @endhasanyrole
                    @if($isAdminOrSuperAdmin)
                        <a href="{{ $indexRoute . '?' . http_build_query(array_merge(request()->except('page', 'type'), ['type' => 'visit'])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors shrink-0 {{ $currentType === 'visit' ? 'bg-[#0DA4CE] text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                            Kunjungan Toko
                        </a>
                    @endif
                </div>

                {{-- Status Filter: Full Width on Mobile, Inline on Desktop --}}
                <div class="flex flex-col sm:flex-row sm:items-center gap-1.5 w-full md:w-auto pt-2.5 md:pt-0 border-t md:border-t-0 border-gray-100 dark:border-gray-800">
                    <input type="hidden" name="type" value="{{ request('type', 'all') }}">
                    <label for="status" class="text-[11px] sm:text-xs font-semibold text-gray-500 dark:text-gray-400 shrink-0">Status Notifikasi:</label>
                    <div class="relative w-full md:w-44">
                        <select name="status" id="status" onchange="this.form.submit()"
                                class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-base sm:text-xs py-2 pl-3 pr-8 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-[#0DA4CE] focus:border-[#0DA4CE] appearance-none cursor-pointer transition min-h-[36px]">
                            <option value="" {{ request('status') === '' || !request()->has('status') ? 'selected' : '' }}>Semua Status</option>
                            <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Belum Dibaca</option>
                            <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Sudah Dibaca</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Notifications List --}}
        <div class="space-y-3">
            @forelse($notifications as $notification)
                @php
                    $data = is_array($notification->data) ? $notification->data : [];
                    $isUnread = is_null($notification->read_at);
                    $activityType = $data['activity_type'] ?? 'default';
                    $actorName = $data['actor_name'] ?? 'User';
                    $actorRole = $data['actor_role'] ?? 'Admin';
                    $storeName = $data['store_name'] ?? null;
                    $routeName = $data['route_name'] ?? null;
                    $reasonText = $data['reason'] ?? null;

                    $readUrl = $isAdminOrSuperAdmin
                        ? route('admin.notifications.read', $notification->id)
                        : route('notifications.read', $notification->id);
                @endphp
                <div class="sw-card p-3.5 sm:p-4 transition-all duration-150 relative overflow-hidden {{ $isUnread ? 'bg-cyan-50/40 dark:bg-cyan-950/20 border-l-[4px] border-l-[#0DA4CE] border-cyan-200/80 dark:border-cyan-800/70 shadow-xs' : 'bg-white dark:bg-gray-900 border-gray-200/70 dark:border-gray-800 hover:bg-gray-50/60 dark:hover:bg-gray-800/40' }}">
                    <div class="flex items-start gap-3 sm:gap-3.5">
                        {{-- Icon by Activity Type --}}
                        <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center
                            @if($activityType === 'attendance_canceled' || $activityType === 'store_submission_rejected')
                                bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400
                            @elseif(str_starts_with($activityType, 'attendance'))
                                bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400
                            @elseif(in_array($activityType, ['route_stop_skipped', 'admin_route_deleted']))
                                bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400
                            @elseif(in_array($activityType, ['route_stop_added', 'admin_stop_added']))
                                bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400
                            @elseif(in_array($activityType, ['route_created', 'delivery_route_created', 'admin_route_created', 'route_started']))
                                bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400
                            @elseif(str_starts_with($activityType, 'visit') || str_starts_with($activityType, 'delivery'))
                                bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400
                            @elseif(str_starts_with($activityType, 'store_submission'))
                                bg-cyan-100 text-cyan-600 dark:bg-cyan-900/40 dark:text-cyan-400
                            @else
                                bg-cyan-100 text-cyan-600 dark:bg-cyan-900/40 dark:text-cyan-400
                            @endif
                        ">
                            @if($activityType === 'attendance_canceled' || $activityType === 'store_submission_rejected')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif(str_starts_with($activityType, 'attendance'))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif($activityType === 'admin_route_deleted')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            @elseif($activityType === 'route_stop_skipped')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                            @elseif(in_array($activityType, ['route_stop_added', 'admin_stop_added']))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif(in_array($activityType, ['route_created', 'delivery_route_created', 'admin_route_created']))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            @elseif($activityType === 'route_started')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            @elseif(str_starts_with($activityType, 'visit') || str_starts_with($activityType, 'delivery'))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            @elseif(str_starts_with($activityType, 'store_submission'))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @endif
                        </div>

                        {{-- Content Body --}}
                        <div class="flex-1 min-w-0">
                            {{-- Title & Time --}}
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                    <h3 class="text-xs sm:text-sm {{ $isUnread ? 'font-bold text-gray-900 dark:text-white' : 'font-semibold text-gray-800 dark:text-gray-200' }} leading-snug break-words">
                                        {{ $data['title'] ?? 'Notifikasi Aktivitas' }}
                                    </h3>
                                    @if($isUnread)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-[#0DA4CE] text-white uppercase tracking-wider shrink-0">
                                            Baru
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap shrink-0">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>

                            {{-- Main Message Text --}}
                            <p class="text-[11px] sm:text-xs {{ $isUnread ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }} leading-relaxed mb-2 break-words">
                                @if($activityType === 'admin_stop_added')
                                    {{ $actorName }} menambahkan <span class="font-semibold text-gray-900 dark:text-white">{{ $storeName ?? 'Toko' }}</span> ke rute <span class="font-semibold text-[#097A99] dark:text-[#0DA4CE]">{{ $routeName ?? 'Rute' }}</span>.
                                @elseif($activityType === 'admin_route_created')
                                    {{ $actorName }} membuat rute <span class="font-semibold text-[#097A99] dark:text-[#0DA4CE]">{{ $routeName ?? 'Rute' }}</span> untuk Anda.
                                @elseif($activityType === 'admin_route_deleted')
                                    {{ $actorName }} telah menghapus rute <span class="font-semibold text-[#097A99] dark:text-[#0DA4CE]">{{ $routeName ?? 'Rute' }}</span>.
                                @elseif($activityType === 'attendance_canceled')
                                    {{ $data['message'] ?? '' }}
                                @else
                                    {{ $data['message'] ?? '' }}
                                @endif
                            </p>

                            {{-- Cancellation Reason Box (If Available & Not redundant) --}}
                            @if(!empty($reasonText) && $activityType === 'attendance_canceled')
                                <div class="mb-2 p-2 sm:p-2.5 rounded-lg bg-red-50/70 dark:bg-red-950/30 border border-red-100 dark:border-red-900/40 text-red-900 dark:text-red-200">
                                    <p class="text-[10px] sm:text-[11px] font-bold text-red-700 dark:text-red-400 uppercase tracking-wide mb-0.5">Alasan Pembatalan:</p>
                                    <p class="text-[11px] sm:text-xs leading-relaxed break-words">{{ $reasonText }}</p>
                                </div>
                            @endif

                            {{-- Footer Info & Action Buttons --}}
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-2 border-t {{ $isUnread ? 'border-cyan-100 dark:border-cyan-900/40' : 'border-gray-100 dark:border-gray-800' }}">
                                <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-gray-400 dark:text-gray-500 truncate">
                                    <span class="font-medium text-gray-600 dark:text-gray-300 truncate">{{ $actorName }}</span>
                                    <span>&bull;</span>
                                    <span class="truncate">{{ $data['info'] ?? '' }}</span>
                                </div>

                                <div class="flex items-center justify-end gap-1.5 sm:gap-2 shrink-0 pt-1 sm:pt-0">
                                    @if($isUnread)
                                        <form action="{{ $isAdminOrSuperAdmin ? route('admin.notifications.mark-read', $notification->id) : route('notifications.mark-read', $notification->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs text-gray-600 hover:text-[#0DA4CE] dark:text-gray-300 dark:hover:text-cyan-300 bg-gray-100/80 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 font-medium transition min-h-[32px] flex items-center">
                                                Tandai Dibaca
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ $isAdminOrSuperAdmin ? route('admin.notifications.destroy', $notification->id) : route('notifications.destroy', $notification->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus notifikasi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs text-red-600 hover:text-red-700 dark:text-red-400 bg-red-50/70 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-900/50 font-medium transition min-h-[32px] flex items-center" title="Hapus Notifikasi">
                                            Hapus
                                        </button>
                                    </form>

                                    <a href="{{ $readUrl }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-[#0DA4CE] hover:bg-[#097A99] shadow-xs transition min-h-[32px] active:scale-95">
                                        <span>Detail</span>
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="sw-card sw-empty py-8 sm:py-12">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 mb-2 sm:mb-3">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Notifikasi</h3>
                    <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5 max-w-sm px-4">Tidak ada aktivitas baru untuk ditampilkan.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination: Laravel Paginator Component (Responsive on Mobile & Desktop) --}}
        @if($notifications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $notifications->hasPages())
            <div class="pt-2 sm:pt-4 pb-2 w-full">
                {{ $notifications->links('vendor.pagination.tailwind') }}
            </div>
        @endif

        @if($isAdminOrSuperAdmin)
        {{-- Delete All Confirmation Modal --}}
        <div x-show="showDeleteAllModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDeleteAllModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="showDeleteAllModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDeleteAllModal" x-transition.scale.origin.center
                     class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-700">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="w-9 h-9 sm:w-11 sm:h-11 shrink-0 rounded-xl bg-red-100 dark:bg-red-950/50 flex items-center justify-center text-red-600 dark:text-red-400">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white" id="modal-title">
                                Hapus Semua Notifikasi?
                            </h3>
                            <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                Semua riwayat notifikasi Anda akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 sm:mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-2.5">
                        <button type="button" @click="showDeleteAllModal = false" class="sw-btn sw-btn-ghost w-full sm:w-auto text-xs sm:text-sm py-2 px-3.5">
                            Batal
                        </button>
                        <form action="{{ $destroyAllRoute }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sw-btn bg-red-600 hover:bg-red-700 text-white shadow-xs w-full sm:w-auto text-xs sm:text-sm py-2 px-3.5">
                                Hapus Semua
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>
</x-app-layout>
