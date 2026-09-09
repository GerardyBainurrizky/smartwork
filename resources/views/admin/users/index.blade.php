<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="users" title="Manajemen Pengguna" subtitle="Kelola akun pengguna, role, dan status akses sistem">
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0DA4CE] border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-[#0b8fb5] focus:outline-none focus:ring-2 focus:ring-[#0DA4CE] focus:ring-offset-2 transition-all duration-200 shadow-sm shadow-[#0DA4CE]/20">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Pengguna
            </a>
        </x-page-header>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6" x-data="{ deleteUser: null }">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-2xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 p-4 text-sm text-green-800 dark:text-green-200" x-data="{show:true}" x-show="show" x-transition>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button @click="show=false" class="text-green-500 hover:text-green-700">&times;</button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 p-4 text-sm text-red-800 dark:text-red-200" x-data="{show:true}" x-show="show" x-transition>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button @click="show=false" class="text-red-500 hover:text-red-700">&times;</button>
                </div>
            </div>
        @endif

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['total'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">Total Pengguna</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['admin'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">Administrator</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-700 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['sales'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">Sales</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['driver'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">Total Driver</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['staff'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">Staff</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        @php
            $filterInputClass = 'w-full min-w-0 border-2 border-gray-300 rounded-lg px-3 py-3 text-sm bg-white text-gray-900 placeholder-gray-400 transition focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/30 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200';
        @endphp
        <div id="sw-user-filter-card" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="w-full flex flex-col lg:flex-row lg:flex-wrap lg:items-end gap-4 lg:gap-3">
                <div class="sw-filter-field w-full min-w-0 lg:flex-1 lg:min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Cari Pengguna</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau username..."
                            class="{{ $filterInputClass }} pl-10">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 w-full min-w-0 lg:flex lg:flex-row lg:items-end lg:gap-3 lg:w-auto">
                    <div class="sw-filter-field w-full min-w-0 lg:w-48">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Role</label>
                        <select name="role" class="{{ $filterInputClass }} sw-user-filter-select">
                            <option value="">Semua Role</option>
                            <option value="super-admin" {{ request('role') === 'super-admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="sales" {{ request('role') === 'sales' ? 'selected' : '' }}>Sales</option>
                            <option value="driver" {{ request('role') === 'driver' ? 'selected' : '' }}>Driver</option>
                            <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    </div>
                    <div class="sw-filter-field w-full min-w-0 lg:w-44">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" class="{{ $filterInputClass }} sw-user-filter-select">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <button type="submit"
                        class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'role', 'status']))
                    <a href="{{ route('admin.users.index') }}"
                        class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-4">
            @forelse($users ?? [] as $user)
            @php
                $role = $user->getRoleNames()->first();
                $roleLabel = in_array($role, ['admin', 'super-admin'])
                    ? 'Administrator'
                    : ($role ? ucfirst(str_replace('-', ' ', $role)) : '-');
            @endphp
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center text-sm font-bold text-white shrink-0">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ '@'.$user->username }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0
                        {{ $user->status === 'active' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }}">
                        {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ $user->email }}</p>
                <div class="flex items-center justify-between mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if(in_array($role, ['admin', 'super-admin'])) bg-blue-100 text-blue-700
                        @elseif($role === 'sales') bg-green-100 text-green-700
                        @elseif($role === 'staff') bg-orange-100 text-orange-700
                        @else bg-gray-100 text-gray-700
                        @endif">
                        @if(in_array($role, ['admin', 'super-admin'])) Administrator
                        @else {{ $role ? ucfirst(str_replace('-', ' ', $role)) : '-' }}
                        @endif
                    </span>
                    <span class="text-xs text-gray-400">{{ $user->created_at?->format('d M Y') }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-3">
                    @if($user->trashed())
                    <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" class="col-span-2">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center w-full px-3 py-2 text-xs font-semibold text-green-700 bg-green-50 dark:bg-green-900/40 rounded-lg hover:bg-green-100 transition">Aktifkan Kembali</button>
                    </form>
                    @else
                    <a href="{{ route('admin.users.edit', $user->id) }}"
                        class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-[#0DA4CE] bg-[#0DA4CE]/10 rounded-lg hover:bg-[#0DA4CE]/20 transition">Edit</a>
                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                        onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan pengguna ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center w-full px-3 py-2 text-xs font-semibold text-amber-600 bg-amber-50 dark:bg-amber-900/40 rounded-lg hover:bg-amber-100 transition">Nonaktifkan</button>
                    </form>
                    @endif
                    <button type="button" @click="deleteUser = {
                        id: $el.dataset.id,
                        name: $el.dataset.name,
                        email: $el.dataset.email,
                        role: $el.dataset.role,
                        url: $el.dataset.url
                    }"
                        data-id="{{ $user->id }}"
                        data-name="{{ e($user->name) }}"
                        data-email="{{ e($user->email) }}"
                        data-role="{{ e($roleLabel) }}"
                        data-url="{{ route('admin.users.force-destroy', $user->id) }}"
                        class="inline-flex items-center justify-center w-full px-3 py-2 text-xs font-semibold text-red-600 bg-red-50 dark:bg-red-900/40 rounded-lg hover:bg-red-100 transition">
                        Hapus
                    </button>
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500">Tidak ada pengguna ditemukan</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden sm:block bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Foto</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Role</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Dibuat Pada</th>
                        <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($users ?? [] as $user)
                    @php
                        $role = $user->getRoleNames()->first();
                        $roleLabel = in_array($role, ['admin', 'super-admin'])
                            ? 'Administrator'
                            : ($role ? ucfirst(str_replace('-', ' ', $role)) : '-');
                    @endphp
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition">
                        <td class="px-4 py-4 whitespace-nowrap">
                            @if($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" alt="Foto {{ $user->name }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center text-sm font-bold text-white">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ '@'.$user->username }}</p>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                @if(in_array($role, ['admin', 'super-admin'])) bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300
                                @elseif($role === 'sales') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300
                                @elseif($role === 'staff') bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300
                                @else bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                @endif">
                                @if(in_array($role, ['admin', 'super-admin'])) Administrator
                                @else {{ $role ? ucfirst(str_replace('-', ' ', $role)) : '-' }}
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                @if($user->status === 'active') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300
                                @else bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400
                                @endif">
                                <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->created_at?->format('d M Y') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-2">
                            @if($user->trashed())
                            <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-green-700 bg-green-50 dark:bg-green-900/40 rounded-lg hover:bg-green-100 transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Aktifkan
                                </button>
                            </form>
                            @else
                            <a href="{{ route('admin.users.edit', $user->id) }}"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0DA4CE] bg-[#0DA4CE]/10 rounded-lg hover:bg-[#0DA4CE]/20 transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan pengguna ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-amber-600 bg-amber-50 dark:bg-amber-900/40 rounded-lg hover:bg-amber-100 transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Nonaktifkan
                                </button>
                            </form>
                            @endif
                            <button type="button" @click="deleteUser = {
                                id: $el.dataset.id,
                                name: $el.dataset.name,
                                email: $el.dataset.email,
                                role: $el.dataset.role,
                                url: $el.dataset.url
                            }"
                                data-id="{{ $user->id }}"
                                data-name="{{ e($user->name) }}"
                                data-email="{{ e($user->email) }}"
                                data-role="{{ e($roleLabel) }}"
                                data-url="{{ route('admin.users.force-destroy', $user->id) }}"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 dark:bg-red-900/40 rounded-lg hover:bg-red-100 transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus
                            </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="mt-3 text-sm text-gray-500">Tidak ada pengguna ditemukan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $users->links('vendor.pagination.reports') }}

        {{-- Modal Konfirmasi Hapus Pengguna --}}
        <div x-show="deleteUser" x-cloak x-transition.opacity
            class="fixed inset-0 z-[60] bg-gray-900/50 dark:bg-gray-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="deleteUser = null" @keydown.escape.window="deleteUser = null">
            <div x-show="deleteUser" x-transition.scale.origin.center
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Pengguna</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Yakin ingin menghapus pengguna ini? Tindakan ini <span class="font-semibold text-red-600 dark:text-red-400">permanen</span> dan tidak dapat dibatalkan.</p>
                        </div>
                        <button type="button" @click="deleteUser = null"
                            class="p-1.5 -m-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4 space-y-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#097A99] to-[#0DA4CE] flex items-center justify-center text-[11px] font-bold text-white shrink-0">!</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="deleteUser ? deleteUser.name : ''"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="deleteUser ? deleteUser.email : ''"></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Role</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300" x-text="deleteUser ? deleteUser.role : ''"></span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button" @click="deleteUser = null"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Batal
                        </button>
                        <form method="POST" :action="deleteUser ? deleteUser.url : '#'" class="sm:order-2">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center justify-center w-full px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition shadow-sm shadow-red-600/20">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
