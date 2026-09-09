<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Pengguna') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Semua Periode</p>
            </div>
            @include('reports.partials.export-buttons', [
                'exportType' => 'users',
                'exportFilename' => 'Laporan Pengguna',
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="mb-6">@include('reports.partials.back')</div>

        <div id="sw-filter-card" class="sw-report-filter sw-report-filter-users bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.users') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
                    <div class="lg:col-span-5">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cari Pengguna</label>
                        <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Cari nama / username / email / no. telepon..."
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Role</label>
                        <select name="role" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Role</option>
                            <option value="super-admin" {{ request('role') === 'super-admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="sales" {{ request('role') === 'sales' ? 'selected' : '' }}>Sales</option>
                            <option value="driver" {{ request('role') === 'driver' ? 'selected' : '' }}>Driver</option>
                            <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status</label>
                        <select name="status" onchange="this.form.submit()"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2 flex flex-col sm:flex-row sm:items-end justify-end gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                            Filter
                        </button>
                        @if(request()->anyFilled(['search', 'role', 'status']))
                        <a href="{{ route('admin.reports.users') }}"
                            class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Reset
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Pengguna</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Super Admin</p>
                <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['super_admin'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Admin</p>
                <p class="text-xl font-bold text-[#0DA4CE]">{{ $stats['admin'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Sales</p>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['sales'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Driver</p>
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['driver'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Staff</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['staff'] }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-12">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama Pengguna</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Username</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No. Telepon</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Role</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($users as $index => $user)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ $users->firstItem() + $index }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-xs font-bold text-[#0DA4CE]">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-700 dark:text-gray-300">{{ $user->username ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $user->email ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $user->phone ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @forelse($user->roles as $r)
                                    @php
                                        $roleName = $r->name;
                                        $roleLabel = match($roleName) {
                                            'super-admin' => 'Super Admin',
                                            'admin' => 'Admin',
                                            'sales' => 'Sales',
                                            'driver' => 'Driver',
                                            'staff' => 'Staff',
                                            default => ucfirst($roleName)
                                        };
                                        $badgeClass = match($roleName) {
                                            'super-admin' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
                                            'admin' => 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300',
                                            'sales' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
                                            'driver' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
                                            'staff' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                                            default => 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                        {{ $roleLabel }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400">-</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if($user->status === 'active') bg-[#90F022]/20 text-green-800 dark:text-green-300
                                    @else bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 @endif">
                                    {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $user->created_at?->format('d M Y, H:i') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-6 py-14 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data pengguna yang sesuai filter</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $users->links('vendor.pagination.reports') }}
    </div>
</x-app-layout>