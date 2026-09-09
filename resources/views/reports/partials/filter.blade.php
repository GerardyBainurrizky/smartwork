@php
    $filterRoute = $filterRoute ?? null;
    $showDates = $showDates ?? true;
    $showSales = $showSales ?? true;
    $salesLabel = $salesLabel ?? 'Sales / Staff';
    $salesUsers = $salesUsers ?? collect();
    $showStatus = $showStatus ?? false;
    $statusOptions = $statusOptions ?? [];
    $showRole = $showRole ?? false;
    $roleOptions = $roleOptions ?? [];
    $filterClass = $filterClass ?? 'sw-report-filter';

    $fromValue = request('from_date', $fromDate ?? '');
    $toValue = request('to_date', $toDate ?? '');
    $activeFilters = array_filter([
        'user_id' => request('user_id'),
        'from_date' => $showDates ? request('from_date') : null,
        'to_date' => $showDates ? request('to_date') : null,
        'status' => request('status'),
        'role' => request('role'),
    ]);
@endphp

<div id="sw-filter-card" class="{{ $filterClass }} bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sm:p-5 mb-6">
    <form method="GET" action="{{ $filterRoute ? route($filterRoute) : request()->url() }}" class="space-y-4">
        <div class="sw-report-filter-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 gap-y-3 items-end">
            @if($showDates)
            <div class="sw-report-filter-dates grid grid-cols-2 gap-3 w-full sm:col-span-2 lg:col-span-4">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Mulai</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="sw-filter-from" type="date" name="from_date" value="{{ $fromValue }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Tanggal Selesai</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="sw-filter-to" type="date" name="to_date" value="{{ $toValue }}"
                            class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    </div>
                </div>
            </div>
            @endif

            @if($showSales)
            <div class="sw-report-filter-sales lg:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">{{ $salesLabel }}</label>
                <select name="user_id"
                    class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    <option value="">Semua {{ $salesLabel }}</option>
                    @foreach($salesUsers as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showRole)
            <div class="sw-report-filter-role lg:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Role</label>
                <select name="role"
                    class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    <option value="">Semua Role</option>
                    @foreach($roleOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showStatus)
            <div class="sw-report-filter-status lg:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Status</label>
                <select name="status"
                    class="w-full min-h-[3rem] border-2 border-gray-200 rounded-xl text-sm px-4 py-3 bg-white text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 focus:border-[#0DA4CE] focus:ring-[#0DA4CE] focus:ring-2 focus:outline-none transition">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="sw-report-filter-actions flex flex-col sm:flex-row sm:items-end justify-end gap-3 lg:col-span-3 lg:justify-end">
                <button type="submit"
                    class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm">
                    Filter
                </button>
                @if($activeFilters)
                <a href="{{ $filterRoute ? route($filterRoute) : request()->url() }}"
                    class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-3 min-h-[3rem] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    Reset
                </a>
                @endif
            </div>
        </div>
    </form>
</div>
