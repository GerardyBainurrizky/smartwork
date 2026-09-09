<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getSuperAdminData(): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $cacheKey = "dashboard:super-admin:{$today}";
        return Cache::remember($cacheKey, 60, function () use ($now, $today, $monthStart, $monthEnd) {
            return [
                'kpi' => $this->buildKpi($today, $monthStart, $monthEnd),
                'attendanceTrend' => $this->getAttendanceTrend(7),
                'routeTrend' => $this->getRouteTrend(7),
                'visitTrend' => $this->getVisitTrend(7),
                'monthlyTrend' => $this->getMonthlyTrend(6),
                'salesPerformance' => $this->getSalesPerformance($monthStart, $monthEnd),
                'topStores' => $this->getTopStores(10),
            ];
        });
    }

    public function getAdminData(): array
    {
        $today = now()->toDateString();

        $salesOnlyIds = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->pluck('id');
        $driverOnlyIds = User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))->pluck('id');
        $staffUserIds = User::whereHas('roles', fn ($q) => $q->where('name', 'staff'))->pluck('id');
        $operationalUserIds = $salesOnlyIds->merge($staffUserIds)->merge($driverOnlyIds);

        $salesUserIds = $salesOnlyIds;
        $totalSales = $salesOnlyIds->count();
        $totalDriver = $driverOnlyIds->count();
        $totalStaff = $staffUserIds->count();
        $totalStores = \App\Models\Store::where('status', 'active')->count();

        $todayAttendance = Attendance::whereDate('date', $today)
            ->whereIn('user_id', $operationalUserIds)
            ->whereNotNull('clock_in')
            ->count();
        $attendanceBase = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'staff', 'driver']))
            ->where('status', 'active')
            ->count();
        $attendanceRate = $attendanceBase > 0 ? round(($todayAttendance / $attendanceBase) * 100) : 0;

        $todayVisits = Visit::with(['user', 'store'])
            ->whereDate('check_in_at', $today)
            ->whereIn('user_id', $salesUserIds)
            ->get();

        $todayStops = RouteStop::with(['visit', 'store'])
            ->whereHas('route', function ($q) use ($today, $salesUserIds) {
                $q->whereDate('date', $today)->whereIn('user_id', $salesUserIds);
            })
            ->get();

        $completedVisits = $todayStops->filter(function ($stop) {
            return $stop->status !== 'skipped'
                && $stop->visit
                && $stop->visit->check_in_at
                && $stop->visit->check_out_at;
        })->count();

        $activeVisits = $todayStops->filter(function ($stop) {
            return $stop->status !== 'skipped'
                && $stop->visit
                && $stop->visit->check_in_at
                && ! $stop->visit->check_out_at;
        })->count();

        $skippedVisits = $todayStops->filter(fn ($stop) => $stop->status === 'skipped')->count();

        $pendingVisits = $todayStops->filter(function ($stop) {
            return $stop->status !== 'skipped'
                && (! $stop->visit || ! $stop->visit->check_in_at);
        })->count();

        $activeRoutesCount = Route::where('status', 'active')->whereIn('user_id', $salesUserIds)->whereDate('date', $today)->count();
        $completedRoutesCount = Route::where('status', 'completed')->whereIn('user_id', $salesUserIds)->whereDate('date', $today)->count();

        return [
            'todayAttendance' => $todayAttendance,
            'attendanceBase' => $attendanceBase,
            'attendanceRate' => $attendanceRate,
            'totalSales' => $totalSales,
            'totalDriver' => $totalDriver,
            'totalStaff' => $totalStaff,
            'totalStores' => $totalStores,
            'todayVisits' => $todayVisits,
            'completedVisits' => $completedVisits,
            'activeVisits' => $activeVisits,
            'pendingVisits' => $pendingVisits,
            'skippedVisits' => $skippedVisits,
            'activeRoutesCount' => $activeRoutesCount,
            'completedRoutesCount' => $completedRoutesCount,
            'activeSalesCount' => $this->countActiveSalesToday($today, $salesUserIds),
            'recentRoutes' => Route::with(['user', 'stops', 'stops.visit'])->whereDate('date', $today)->orderBy('created_at', 'desc')->take(5)->get(),
            'recentVisits' => Visit::with(['user', 'store'])->whereDate('check_in_at', $today)->orderBy('check_in_at', 'desc')->take(5)->get(),
        ];
    }

    private function countActiveSalesToday(string $today, $salesUserIds): int
    {
        return collect()
            ->merge(Attendance::whereDate('date', $today)->whereIn('user_id', $salesUserIds)->pluck('user_id'))
            ->merge(Route::whereDate('started_at', $today)->whereIn('user_id', $salesUserIds)->pluck('user_id'))
            ->merge(Visit::whereDate('check_in_at', $today)->whereIn('user_id', $salesUserIds)->pluck('user_id'))
            ->unique()
            ->count();
    }

    public function getSalesData(): array
    {
        $user = auth()->user();
        $today = now()->toDateString();

        return [
            'todayAttendance' => Attendance::where('user_id', $user->id)->where('date', $today)->first(),
            'todayRoute' => Route::with(['stops.store'])->where('user_id', $user->id)->where('date', $today)->first(),
            'todayVisits' => Visit::with(['store'])->where('user_id', $user->id)->whereDate('check_in_at', $today)->orderBy('check_in_at', 'desc')->get(),
            'monthlyStats' => $this->getMonthlyStats($user->id),
        ];
    }

    private function buildKpi(string $today, string $monthStart, string $monthEnd): array
    {
        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor', 'driver']))->get();
        $salesCount = $salesUsers->count();

        $todayAttendance = Attendance::where('date', $today)->count();
        $todayRoutes = Route::where('date', $today)->count();
        $todayCompletedRoutes = Route::where('date', $today)->where('status', 'completed')->count();
        $todayVisits = Visit::whereDate('check_in_at', $today)->count();
        $todayCompletedVisits = Visit::whereDate('check_in_at', $today)->where('status', 'completed')->count();
        $monthlyTransaction = Visit::whereBetween('check_in_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])->sum('transaction_amount') ?? 0;
        $monthlyVisits = Visit::whereBetween('check_in_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])->count();

        return [
            ['label' => 'Total Pengguna', 'value' => User::count(), 'icon' => 'users', 'color' => '#0DA4CE', 'change' => User::where('status', 'active')->count() . ' aktif'],
            ['label' => 'Attendance Hari Ini', 'value' => $salesCount > 0 ? round(($todayAttendance / $salesCount) * 100) . '%' : '0%', 'icon' => 'clock', 'color' => '#097A99', 'change' => $todayAttendance . ' orang'],
            ['label' => 'Rute Selesai', 'value' => $todayRoutes > 0 ? round(($todayCompletedRoutes / $todayRoutes) * 100) . '%' : '0%', 'icon' => 'map', 'color' => '#6366F1', 'change' => $todayCompletedRoutes . '/' . $todayRoutes],
            ['label' => 'Kunjungan Selesai', 'value' => $todayVisits > 0 ? round(($todayCompletedVisits / $todayVisits) * 100) . '%' : '0%', 'icon' => 'store', 'color' => '#F59E0B', 'change' => $todayCompletedVisits . '/' . $todayVisits],
            ['label' => 'Transaksi (Bulan)', 'value' => 'Rp ' . $this->formatRupiah($monthlyTransaction), 'icon' => 'cash', 'color' => '#10B981', 'change' => $monthlyVisits . ' kunjungan'],
            ['label' => 'Sales Aktif', 'value' => Attendance::where('date', $today)->whereNotNull('clock_in')->whereNull('clock_out')->count(), 'icon' => 'user-check', 'color' => '#90F022', 'change' => 'dari ' . $salesCount . ' sales'],
        ];
    }

    private function getAttendanceTrend(int $days): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $trend[] = ['date' => Carbon::parse($date)->format('d M'), 'count' => Attendance::where('date', $date)->count()];
        }
        return $trend;
    }

    private function getRouteTrend(int $days): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $trend[] = ['date' => Carbon::parse($date)->format('d M'), 'total' => Route::where('date', $date)->count(), 'completed' => Route::where('date', $date)->where('status', 'completed')->count()];
        }
        return $trend;
    }

    private function getVisitTrend(int $days): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trend[] = ['date' => $date->format('d M'), 'total' => Visit::whereDate('check_in_at', $date->toDateString())->count(), 'completed' => Visit::whereDate('check_in_at', $date->toDateString())->where('status', 'completed')->count()];
        }
        return $trend;
    }

    private function getMonthlyTrend(int $months): array
    {
        $trend = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $trend[] = ['month' => $month->translatedFormat('M'), 'routes' => Route::whereMonth('date', $month->month)->whereYear('date', $month->year)->count(), 'visits' => Visit::whereMonth('check_in_at', $month->month)->whereYear('check_in_at', $month->year)->count(), 'transaction' => Visit::whereMonth('check_in_at', $month->month)->whereYear('check_in_at', $month->year)->sum('transaction_amount') ?? 0];
        }
        return $trend;
    }

    private function getSalesPerformance(string $monthStart, string $monthEnd): array
    {
        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor', 'driver']))->orderBy('name')->get();
        $performance = [];
        foreach ($salesUsers as $sales) {
            $routes = Route::where('user_id', $sales->id)->whereBetween('date', [$monthStart, $monthEnd])->count();
            $completedRoutes = Route::where('user_id', $sales->id)->whereBetween('date', [$monthStart, $monthEnd])->where('status', 'completed')->count();
            $visits = Visit::where('user_id', $sales->id)->whereBetween('check_in_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])->count();
            $transaction = Visit::where('user_id', $sales->id)->whereBetween('check_in_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])->sum('transaction_amount') ?? 0;
            $performance[] = ['name' => $sales->name, 'routes' => $routes, 'completed_routes' => $completedRoutes, 'visits' => $visits, 'transaction' => $transaction, 'completion' => $routes > 0 ? round(($completedRoutes / $routes) * 100) : 0];
        }
        return $performance;
    }

    private function getTopStores(int $limit): array
    {
        return Visit::selectRaw('store_id, count(*) as total_visits, sum(transaction_amount) as total_transaction')
            ->where('status', 'completed')->groupBy('store_id')->orderByDesc('total_visits')->take($limit)->with('store')
            ->get()->map(fn ($v) => ['name' => $v->store->name ?? 'Toko', 'visits' => $v->total_visits, 'transaction' => $v->total_transaction ?? 0])->toArray();
    }

    private function getMonthlyStats(string $userId): array
    {
        return [
            'routes' => Route::where('user_id', $userId)->whereMonth('date', now()->month)->whereYear('date', now()->year)->count(),
            'completed_routes' => Route::where('user_id', $userId)->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'completed')->count(),
            'visits' => Visit::where('user_id', $userId)->whereMonth('check_in_at', now()->month)->whereYear('check_in_at', now()->year)->count(),
            'transaction' => Visit::where('user_id', $userId)->whereMonth('check_in_at', now()->month)->whereYear('check_in_at', now()->year)->sum('transaction_amount') ?? 0,
        ];
    }

    private function formatRupiah($amount): string
    {
        if ($amount >= 1000000) return round($amount / 1000000, 1) . 'M';
        if ($amount >= 1000) return round($amount / 1000, 0) . 'rb';
        return (string) $amount;
    }
}