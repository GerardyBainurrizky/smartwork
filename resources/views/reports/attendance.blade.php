<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">{{ __('Laporan Presensi') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $periodLabel }}</p>
            </div>
            @php
                $reportFileName = 'Laporan Presensi';
                if ($fromDate && $toDate) {
                    $reportFileName .= ' ' . \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($toDate)->format('d M Y');
                }
            @endphp
            @include('reports.partials.export-buttons', [
                'exportType' => 'attendance',
                'exportFilename' => $reportFileName,
            ])
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="mb-6">@include('reports.partials.back')</div>

        @php
            $attendanceUsers = $filterUsers ?? $salesUsers;
            $attendanceRoleValue = $roleFilter ?? request('role', '');
            $attendanceUserValue = request('user_id', $userId ?? '');
        @endphp
        <div id="sw-filter-card" class="sw-report-filter bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('admin.reports.attendance') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Role</label>
                        <select name="role" class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition" onchange="this.form.submit()">
                            <option value="">Semua Role</option>
                            <option value="sales" {{ $attendanceRoleValue === 'sales' ? 'selected' : '' }}>Sales</option>
                            <option value="driver" {{ $attendanceRoleValue === 'driver' ? 'selected' : '' }}>Driver</option>
                            <option value="staff" {{ $attendanceRoleValue === 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Pengguna</label>
                        <select name="user_id" onchange="this.form.submit()" class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Pengguna</option>
                            @foreach($attendanceUsers as $u)
                                <option value="{{ $u->id }}" {{ (string) $attendanceUserValue === (string) $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->getRoleNames()->first() ?? '-') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status</label>
                        <select name="status" onchange="this.form.submit()" class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                            <option value="">Semua Status</option>
                            <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Check In</option>
                            <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Check Out (Selesai)</option>
                            <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cari Nama</label>
                        <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Cari nama pengguna..."
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>

                    <div class="lg:col-span-2 flex flex-col sm:flex-row sm:items-end justify-end gap-2">
                        <button type="submit" class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">Filter</button>
                        @if(request()->anyFilled(['from_date', 'to_date', 'role', 'user_id', 'status', 'search']))
                        <a href="{{ route('admin.reports.attendance') }}" class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">Reset</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="from_date" value="{{ request('from_date', $fromDate ?? '') }}" class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="to_date" value="{{ request('to_date', $toDate ?? '') }}" class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Total Pengguna</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total_users'] ?? $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Sudah Check In</p>
                <p class="text-xl font-bold text-[#0DA4CE]">{{ $stats['checked_in'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Sudah Check Out</p>
                <p class="text-xl font-bold text-green-700 dark:text-green-400">{{ $stats['checked_out'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Izin</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['izin'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Sakit</p>
                <p class="text-xl font-bold text-pink-600 dark:text-pink-400">{{ $stats['sakit'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Belum Presensi</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['not_present'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Dibatalkan</p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ $stats['canceled'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Persentase Hadir</p>
                <p class="text-xl font-bold text-[#0DA4CE]">{{ $stats['percentage'] ?? $stats['on_time_rate'] }}%</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama Karyawan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Role</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Jam Check In</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Jam Check Out</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Durasi Kerja</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Lokasi Check In / Out</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Keterangan / Alasan</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Foto Presensi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($attendances as $att)
                        @php
                            $attDuration = '-';
                            if ($att->clock_in && $att->clock_out) {
                                $attMinutes = max(0, (int) $att->clock_in->diffInMinutes($att->clock_out));
                                $attDuration = $attMinutes >= 60
                                    ? ((intdiv($attMinutes, 60)) . 'j ' . ($attMinutes % 60) . 'm')
                                    : $attMinutes . 'm';
                            }
                            $attNote = $att->absence_note ?? ($att->cancelled_meta['reason'] ?? $att->notes ?? null);
                            $attStatus = $att->status_presensi;
                            $attBadge = match($attStatus) {
                                'canceled' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
                                'checked_out' => 'bg-[#90F022]/20 text-green-800 dark:text-green-300',
                                'checked_in' => 'bg-[#0DA4CE]/10 text-[#0DA4CE]',
                                'izin' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                                'sakit' => 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300',
                                default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                            };
                            $roleLabel = match($att->user?->getRoleNames()->first()) {
                                'sales' => 'Sales',
                                'driver' => 'Driver',
                                'staff' => 'Staff',
                                'field-supervisor' => 'Field Supervisor',
                                'admin' => 'Admin',
                                'super-admin' => 'Super Admin',
                                default => ucfirst($att->user?->getRoleNames()->first() ?? '-'),
                            };
                            $inCoord = ($att->clock_in_lat && $att->clock_in_lng) ? round((float)$att->clock_in_lat, 4) . ', ' . round((float)$att->clock_in_lng, 4) : null;
                            $outCoord = ($att->clock_out_lat && $att->clock_out_lng) ? round((float)$att->clock_out_lat, 4) . ', ' . round((float)$att->clock_out_lng, 4) : null;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 align-top">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0DA4CE]/10 flex items-center justify-center text-xs font-bold text-[#0DA4CE]">{{ strtoupper(substr($att->user->name ?? 'U', 0, 2)) }}</div>
                                    <div>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $att->user->name ?? '-' }}</span>
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $att->user->username ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    {{ $roleLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400 font-medium">{{ $att->date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-gray-700 dark:text-gray-300">
                                @if($attStatus === 'izin' || $attStatus === 'sakit') - @else {{ $att->clock_in?->format('H:i') ?? '-' }} @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-gray-700 dark:text-gray-300">
                                @if($attStatus === 'izin' || $attStatus === 'sakit') - @else {{ $att->clock_out?->format('H:i') ?? '-' }} @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $attDuration }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $attBadge }}">
                                    {{ $att->status_presensi_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 max-w-[220px] space-y-1">
                                @if($att->clock_in_address || $inCoord)
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">In:</span>
                                        @if($inCoord)
                                            <a href="https://www.google.com/maps?q={{ $att->clock_in_lat }},{{ $att->clock_in_lng }}" target="_blank" rel="noopener noreferrer" class="text-[#0DA4CE] hover:underline font-medium">
                                                {{ $inCoord }}
                                            </a>
                                        @endif
                                        @if($att->clock_in_address)
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $att->clock_in_address }}</p>
                                        @endif
                                    </div>
                                @endif
                                @if($att->clock_out_address || $outCoord)
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Out:</span>
                                        @if($outCoord)
                                            <a href="https://www.google.com/maps?q={{ $att->clock_out_lat }},{{ $att->clock_out_lng }}" target="_blank" rel="noopener noreferrer" class="text-[#0DA4CE] hover:underline font-medium">
                                                {{ $outCoord }}
                                            </a>
                                        @endif
                                        @if($att->clock_out_address)
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $att->clock_out_address }}</p>
                                        @endif
                                    </div>
                                @endif
                                @if(!$att->clock_in_address && !$inCoord && !$att->clock_out_address && !$outCoord)
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 max-w-[200px] line-clamp-2">{{ $attNote ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($att->clock_in_selfie)
                                    <a href="{{ asset('storage/' . $att->clock_in_selfie) }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ asset('storage/' . $att->clock_in_selfie) }}" class="w-8 h-8 rounded object-cover border border-gray-200 dark:border-gray-700 hover:scale-105 transition" title="{{ ($attStatus === 'izin' || $attStatus === 'sakit') ? 'Foto Bukti ' . ucfirst($attStatus) : 'Selfie Check In' }}">
                                    </a>
                                    @endif
                                    @if($att->clock_out_selfie)
                                    <a href="{{ asset('storage/' . $att->clock_out_selfie) }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ asset('storage/' . $att->clock_out_selfie) }}" class="w-8 h-8 rounded object-cover border border-gray-200 dark:border-gray-700 hover:scale-105 transition" title="Selfie Check Out">
                                    </a>
                                    @endif
                                    @if(!$att->clock_in_selfie && !$att->clock_out_selfie)
                                    <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="px-6 py-14 text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada data presensi</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $attendances->links('vendor.pagination.reports') }}
    </div>
</x-app-layout>
