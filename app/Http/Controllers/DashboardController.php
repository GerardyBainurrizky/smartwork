<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return $this->superAdminDashboard();
        }

        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        if ($user->hasRole('staff')) {
            return $this->staffDashboard();
        }

        if ($user->hasRole('driver')) {
            return $this->driverDashboard();
        }

        return $this->salesDashboard();
    }

    private function superAdminDashboard(): View
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $weekStart = $now->copy()->startOfWeek()->toDateString();

        // Kumpulan ID pengguna operasional — Admin & Super Admin dikecualikan.
        $salesUserIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->pluck('id');
        $driverUserIds = User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))->pluck('id');
        $fieldUserIds = $salesUserIds->merge($driverUserIds);
        $staffUserIds = User::whereHas('roles', fn ($q) => $q->where('name', 'staff'))->pluck('id');

        // 1. Total Pengguna (dari tabel user)
        $totalUsers = User::count();
        $totalSales = $salesUserIds->count();
        $totalDriver = $driverUserIds->count();
        $totalStaff = $staffUserIds->count();
        $totalAdminOnly = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->count();
        $totalSuperAdmin = User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->count();
        $totalAdmin = $totalAdminOnly + $totalSuperAdmin;

        // 2. Presensi Hari Ini (tanpa Admin & Super Admin) — terpisah Sales/Driver/Staff
        $todayAttendances = Attendance::with('user')
            ->whereDate('date', $today)
            ->where('status', '!=', 'canceled')
            ->get();

        $salesPresent = $todayAttendances->filter(fn ($a) => $salesUserIds->contains($a->user_id) && $a->clock_in)->count();
        $salesIzin = $todayAttendances->filter(fn ($a) => $salesUserIds->contains($a->user_id) && $a->status === 'izin')->count();
        $salesSakit = $todayAttendances->filter(fn ($a) => $salesUserIds->contains($a->user_id) && $a->status === 'sakit')->count();
        $salesSudahPresensi = $salesPresent + $salesIzin + $salesSakit;

        $driverPresent = $todayAttendances->filter(fn ($a) => $driverUserIds->contains($a->user_id) && $a->clock_in)->count();
        $driverIzin = $todayAttendances->filter(fn ($a) => $driverUserIds->contains($a->user_id) && $a->status === 'izin')->count();
        $driverSakit = $todayAttendances->filter(fn ($a) => $driverUserIds->contains($a->user_id) && $a->status === 'sakit')->count();
        $driverSudahPresensi = $driverPresent + $driverIzin + $driverSakit;

        $staffPresent = $todayAttendances->filter(fn ($a) => $staffUserIds->contains($a->user_id) && $a->clock_in)->count();
        $staffIzin = $todayAttendances->filter(fn ($a) => $staffUserIds->contains($a->user_id) && $a->status === 'izin')->count();
        $staffSakit = $todayAttendances->filter(fn ($a) => $staffUserIds->contains($a->user_id) && $a->status === 'sakit')->count();
        $staffSudahPresensi = $staffPresent + $staffIzin + $staffSakit;

        $hadirCount = $salesPresent + $driverPresent + $staffPresent;
        $izinCount = $salesIzin + $driverIzin + $staffIzin;
        $sakitCount = $salesSakit + $driverSakit + $staffSakit;
        $presentCount = $salesSudahPresensi + $driverSudahPresensi + $staffSudahPresensi;

        $operationalUserCount = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'staff', 'field-supervisor', 'driver']))
            ->where('status', 'active')
            ->count();

        // Sales Aktif Hari Ini: berdasarkan presensi atau aktivitas rute/kunjungan hari ini
        $salesActiveTodayIds = collect();
        foreach (Attendance::whereDate('date', $today)->whereIn('user_id', $salesUserIds)->where('status', '!=', 'canceled')->pluck('user_id') as $uid) {
            $salesActiveTodayIds->push($uid);
        }
        foreach (Route::whereDate('started_at', $today)->whereIn('user_id', $salesUserIds)->pluck('user_id') as $uid) {
            $salesActiveTodayIds->push($uid);
        }
        foreach (Visit::whereDate('check_in_at', $today)->whereIn('user_id', $salesUserIds)->pluck('user_id') as $uid) {
            $salesActiveTodayIds->push($uid);
        }
        $salesActiveCount = $salesActiveTodayIds->unique()->count();

        // Driver Aktif Hari Ini: berdasarkan presensi atau aktivitas rute/kunjungan hari ini
        $driverActiveTodayIds = collect();
        foreach (Attendance::whereDate('date', $today)->whereIn('user_id', $driverUserIds)->where('status', '!=', 'canceled')->pluck('user_id') as $uid) {
            $driverActiveTodayIds->push($uid);
        }
        foreach (Route::whereDate('started_at', $today)->whereIn('user_id', $driverUserIds)->pluck('user_id') as $uid) {
            $driverActiveTodayIds->push($uid);
        }
        foreach (Visit::whereDate('check_in_at', $today)->whereIn('user_id', $driverUserIds)->pluck('user_id') as $uid) {
            $driverActiveTodayIds->push($uid);
        }
        $driverActiveCount = $driverActiveTodayIds->unique()->count();

        $staffActiveCount = $staffUserIds->count();

        $totalSalesUsers = $salesUserIds->count();
        $totalDriverUsers = $driverUserIds->count();

        $salesAbsent = max(0, $totalSalesUsers - $salesSudahPresensi);
        $driverAbsent = max(0, $totalDriverUsers - $driverSudahPresensi);
        $staffAbsent = max(0, $staffActiveCount - $staffSudahPresensi);
        $absentCount = max(0, $operationalUserCount - $presentCount);

        $attendanceRate = $operationalUserCount > 0 ? round(($presentCount / $operationalUserCount) * 100) : 0;

        // 3. Aktivitas lapangan hari ini — Sales vs Driver terpisah
        $salesStops = RouteStop::with(['visit'])
            ->whereHas('route', fn ($q) => $q->whereDate('date', $today)->whereIn('user_id', $salesUserIds))
            ->get();

        $driverStopsToday = RouteStop::with(['visit'])
            ->whereHas('route', fn ($q) => $q->whereDate('date', $today)->whereIn('user_id', $driverUserIds))
            ->get();

        $visitCompleted = $salesStops->filter(fn ($s) => $s->computed_status === 'visited')->count();
        $visitInProgress = $salesStops->filter(fn ($s) => $s->computed_status === 'in_progress')->count();
        $visitSkipped = $salesStops->filter(fn ($s) => $s->computed_status === 'skipped')->count();
        $visitTotal = $salesStops->count();

        $deliveryCompleted = $driverStopsToday->filter(fn ($s) => $s->computed_status === 'visited')->count();
        $deliveryInProgress = $driverStopsToday->filter(fn ($s) => $s->computed_status === 'in_progress')->count();
        $deliverySkipped = $driverStopsToday->filter(fn ($s) => $s->computed_status === 'skipped')->count();
        $deliveryTotal = $driverStopsToday->count();
        $deliveryPending = max(0, $deliveryTotal - $deliveryCompleted - $deliveryInProgress - $deliverySkipped);

        // 4. Route Hari Ini — Sales vs Driver terpisah
        $todayRoutes = Route::with('stops')->whereDate('date', $today)->get();
        $salesTodayRoutes = $todayRoutes->filter(fn ($r) => $salesUserIds->contains($r->user_id));
        $driverTodayRoutes = $todayRoutes->filter(fn ($r) => $driverUserIds->contains($r->user_id));

        $routeActive = $salesTodayRoutes->filter(fn ($r) => $r->computed_status === 'active')->count();
        $routeCompleted = $salesTodayRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count();
        $routeDraft = $salesTodayRoutes->filter(fn ($r) => $r->computed_status === 'draft')->count();
        $routeCancelled = $salesTodayRoutes->filter(fn ($r) => $r->computed_status === 'cancelled')->count();
        $routeTotal = $salesTodayRoutes->count();

        $deliveryRouteActive = $driverTodayRoutes->filter(fn ($r) => $r->computed_status === 'active')->count();
        $deliveryRouteCompleted = $driverTodayRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count();
        $deliveryRouteDraft = $driverTodayRoutes->filter(fn ($r) => $r->computed_status === 'draft')->count();
        $deliveryRouteCancelled = $driverTodayRoutes->filter(fn ($r) => $r->computed_status === 'cancelled')->count();
        $deliveryRouteTotal = $driverTodayRoutes->count();

        // 5. Total Toko
        $totalStores = Store::withTrashed()->count();
        $activeStores = Store::where('status', 'active')->count();
        $unassignedStoresCount = Store::where('status', 'active')->whereNull('sales_penanggung_jawab_id')->count();
        $deliveryStoresCount = Store::where('status', 'active')->where('is_delivery_destination', true)->count();

        // 6. Aktivitas Hari Ini
        $todayActivities = $this->getTodayActivities($today);

        // Grafik — Sales vs Driver dipisah
        $attendanceTrend = $this->getAttendanceTrend(7);
        $visitTrend = $this->getVisitTrendForIds($salesUserIds, 7);
        $deliveryTrend = $this->getVisitTrendForIds($driverUserIds, 7);
        $topSalesWeek = $this->getTopSalesWeek($weekStart, $salesUserIds);
        $topDriversWeek = $this->getTopDriversWeek($weekStart);
        $financialTrend = $this->getFinancialTrend(7);
        $routeTrend = $this->getRouteTrendForIds($salesUserIds, 7);
        $deliveryRouteTrend = $this->getRouteTrendForIds($driverUserIds, 7);

        // 7. DATA KEUANGAN GLOBAL (Super Admin)
        $salesUsersList = User::whereIn('id', $salesUserIds)->orderBy('name')->get(['id', 'name']);
        $salesCashInSummary = [];
        $totalSalesCashInToday = 0.0;
        $salesReceivableSummary = [];
        $totalSalesReceivable = 0.0;

        foreach ($salesUsersList as $salesUser) {
            $userStoreIds = Store::where('sales_penanggung_jawab_id', $salesUser->id)
                ->where('status', 'active')
                ->pluck('id');

            // A. Pemasukan Transaksi Baru hari ini untuk toko Sales ini
            $userNewTxCashIn = (float) StoreTransactionPayment::whereHas('transaction', function ($q) use ($userStoreIds) {
                    $q->whereIn('store_id', $userStoreIds)
                      ->where(function ($qq) {
                          $qq->whereNull('reference_type')
                             ->orWhere('reference_type', '!=', 'opening_balance');
                      });
                })
                ->whereDate('payment_date', $today)
                ->where(function ($q) {
                    $q->where('source', 'initial_payment')
                      ->orWhere('notes', 'like', '%Pembayaran awal%');
                })
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->sum('amount');

            // B. Pembayaran Piutang Lama hari ini untuk toko Sales ini & Uang Pelunasan Piutang
            $userOldDebtPayments = StoreTransactionPayment::with(['transaction.payments'])
                ->whereHas('transaction', function ($q) use ($userStoreIds) {
                    $q->whereIn('store_id', $userStoreIds);
                })
                ->whereDate('payment_date', $today)
                ->where('source', '!=', 'initial_payment')
                ->where(function ($q) {
                    $q->where('notes', 'not like', '%Pembayaran awal%')
                      ->orWhereNull('notes');
                })
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->get();

            $userOldDebtCashIn = (float) $userOldDebtPayments->sum('amount');

            $userDebtSettlement = 0.0;
            foreach ($userOldDebtPayments as $p) {
                $tx = $p->transaction;
                if (! $tx) continue;
                $txAmount = (float) $tx->transaction_amount;
                if ($txAmount <= 0) continue;

                $allPayments = $tx->payments->filter(function ($otherP) use ($today) {
                    if ($otherP->source === 'adjustment') return false;
                    $otherPDate = $otherP->payment_date ? $otherP->payment_date->toDateString() : null;
                    return $otherPDate && $otherPDate <= $today;
                });

                $priorPayments = $allPayments->filter(function ($otherP) use ($today) {
                    $otherPDate = $otherP->payment_date ? $otherP->payment_date->toDateString() : null;
                    return $otherPDate && $otherPDate < $today;
                });

                $sumAll = (float) $allPayments->sum('amount');
                $sumPrior = (float) $priorPayments->sum('amount');

                if ($sumAll >= $txAmount && $sumPrior < $txAmount) {
                    $userDebtSettlement += (float) $p->amount;
                }
            }

            $userTotalCashIn = $userNewTxCashIn + $userOldDebtCashIn;
            $totalSalesCashInToday += $userTotalCashIn;

            $salesCashInSummary[] = [
                'id'         => $salesUser->id,
                'name'       => $salesUser->name,
                'new_tx'     => $userNewTxCashIn,
                'old_debt'   => $userOldDebtCashIn,
                'settlement' => $userDebtSettlement,
                'amount'     => $userTotalCashIn,
            ];

            // Piutang saat ini menggunakan StoreReceivableService (ledger yang sama dengan modul Piutang)
            $userReceivable = 0.0;
            foreach ($userStoreIds as $stId) {
                $userReceivable += (float) StoreReceivableService::balanceForStore($stId);
            }

            $totalSalesReceivable += $userReceivable;
            $salesReceivableSummary[] = [
                'id'     => $salesUser->id,
                'name'   => $salesUser->name,
                'amount' => $userReceivable,
            ];
        }

        // Uang Masuk Driver Hari Ini & Breakdown per Driver: transaksi Visit Driver yang completed + paid hari ini
        $driverUsersList = User::whereIn('id', $driverUserIds)->orderBy('name')->get(['id', 'name']);
        $driverCashInSummary = [];
        $totalDriverCashInToday = 0.0;

        foreach ($driverUsersList as $driverUser) {
            $driverUserAmount = (float) Visit::where('user_id', $driverUser->id)
                ->whereDate('check_in_at', $today)
                ->where('status', 'completed')
                ->where('transaction_status', 'paid')
                ->sum('transaction_amount');

            $totalDriverCashInToday += $driverUserAmount;
            $driverCashInSummary[] = [
                'id'     => $driverUser->id,
                'name'   => $driverUser->name,
                'amount' => $driverUserAmount,
            ];
        }

        // Piutang total perusahaan (semua toko aktif) — konsisten dengan modul Piutang
        $allActiveStoreIds = Store::where('status', 'active')->pluck('id');
        $totalCurrentReceivable = 0.0;
        foreach ($allActiveStoreIds as $stId) {
            $totalCurrentReceivable += (float) StoreReceivableService::balanceForStore($stId);
        }

        // Card Uang Masuk Sales Hari Ini (Sales-owned stores only)
        $totalNewTxCashInToday = (float) array_sum(array_column($salesCashInSummary, 'new_tx'));
        $totalOldDebtPaymentToday = (float) array_sum(array_column($salesCashInSummary, 'old_debt'));
        $totalDebtSettlementToday = (float) array_sum(array_column($salesCashInSummary, 'settlement'));

        // Monitoring — performa terpisah
        $salesPerformanceToday = $this->paginateDashboardSection(
            $this->getSalesPerformanceToday($today, $salesUserIds),
            10,
            'sales_performance_page'
        );
        $driverPerformanceRows = $this->getDriverPerformanceToday($today, $driverUserIds);
        $driverPerformanceTotalRoutes = collect($driverPerformanceRows)->sum('routes');
        $driverPerformanceTotalCompleted = collect($driverPerformanceRows)->sum('completed');
        $driverPerformanceToday = $this->paginateDashboardSection(
            $driverPerformanceRows,
            10,
            'driver_performance_page'
        );
        $usersTodayPaginated = $this->getOperationalUsersPaginated($todayAttendances, $salesUserIds, $driverUserIds, $staffUserIds);

        return view('dashboard.super-admin', compact(
            'totalUsers', 'totalSales', 'totalDriver', 'totalStaff', 'totalAdmin', 'totalAdminOnly', 'totalSuperAdmin',
            'salesPresent', 'salesIzin', 'salesSakit', 'salesSudahPresensi',
            'driverPresent', 'driverIzin', 'driverSakit', 'driverSudahPresensi',
            'staffPresent', 'staffIzin', 'staffSakit', 'staffSudahPresensi',
            'hadirCount', 'izinCount', 'sakitCount', 'presentCount',
            'salesActiveCount', 'driverActiveCount', 'staffActiveCount',
            'totalSalesUsers', 'totalDriverUsers',
            'salesAbsent', 'driverAbsent', 'staffAbsent', 'absentCount', 'operationalUserCount', 'attendanceRate',
            'visitTotal', 'visitInProgress', 'visitCompleted', 'visitSkipped',
            'deliveryTotal', 'deliveryInProgress', 'deliveryCompleted', 'deliverySkipped', 'deliveryPending',
            'routeActive', 'routeCompleted', 'routeDraft', 'routeCancelled', 'routeTotal',
            'deliveryRouteActive', 'deliveryRouteCompleted', 'deliveryRouteDraft', 'deliveryRouteCancelled', 'deliveryRouteTotal',
            'totalStores', 'activeStores', 'unassignedStoresCount', 'deliveryStoresCount',
            'todayActivities',
            'attendanceTrend', 'visitTrend', 'deliveryTrend', 'topSalesWeek', 'topDriversWeek', 'financialTrend', 'routeTrend', 'deliveryRouteTrend',
            'salesPerformanceToday', 'driverPerformanceToday', 'driverPerformanceTotalRoutes', 'driverPerformanceTotalCompleted', 'usersTodayPaginated',
            'totalSalesCashInToday', 'salesCashInSummary',
            'totalDriverCashInToday', 'driverCashInSummary',
            'totalSalesReceivable', 'salesReceivableSummary',
            'totalCurrentReceivable',
            'totalNewTxCashInToday', 'totalOldDebtPaymentToday', 'totalDebtSettlementToday'
        ));
    }

    private function adminDashboard(): View
    {
        $today = now()->toDateString();

        $salesOnlyIds = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->pluck('id');
        $driverOnlyIds = User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))->pluck('id');
        $staffUserIds = User::whereHas('roles', fn ($q) => $q->where('name', 'staff'))->pluck('id');
        $operationalUserIds = $salesOnlyIds->merge($staffUserIds)->merge($driverOnlyIds);

        $totalSales = $salesOnlyIds->count();
        $totalDriver = $driverOnlyIds->count();
        $totalStaff = $staffUserIds->count();

        // CARD 3 - Presensi Hari Ini (Sales + Driver + Staff)
        $todayAttendanceRecords = Attendance::whereDate('date', $today)
            ->whereIn('user_id', $operationalUserIds)
            ->where('status', '!=', 'canceled')
            ->get();

        $activeSalesUsers = User::whereIn('id', $salesOnlyIds)->where('status', 'active')->pluck('id');
        $activeDriverUsers = User::whereIn('id', $driverOnlyIds)->where('status', 'active')->pluck('id');
        $activeStaffUsers = User::whereIn('id', $staffUserIds)->where('status', 'active')->pluck('id');

        $salesTotal = $activeSalesUsers->count();
        $driverTotal = $activeDriverUsers->count();
        $staffTotal = $activeStaffUsers->count();

        $salesPresent = $todayAttendanceRecords->filter(fn ($a) => $activeSalesUsers->contains($a->user_id) && $a->clock_in)->count();
        $salesIzin = $todayAttendanceRecords->filter(fn ($a) => $activeSalesUsers->contains($a->user_id) && $a->status === 'izin')->count();
        $salesSakit = $todayAttendanceRecords->filter(fn ($a) => $activeSalesUsers->contains($a->user_id) && $a->status === 'sakit')->count();
        $salesSudahPresensi = $salesPresent + $salesIzin + $salesSakit;

        $driverPresent = $todayAttendanceRecords->filter(fn ($a) => $activeDriverUsers->contains($a->user_id) && $a->clock_in)->count();
        $driverIzin = $todayAttendanceRecords->filter(fn ($a) => $activeDriverUsers->contains($a->user_id) && $a->status === 'izin')->count();
        $driverSakit = $todayAttendanceRecords->filter(fn ($a) => $activeDriverUsers->contains($a->user_id) && $a->status === 'sakit')->count();
        $driverSudahPresensi = $driverPresent + $driverIzin + $driverSakit;

        $staffPresent = $todayAttendanceRecords->filter(fn ($a) => $activeStaffUsers->contains($a->user_id) && $a->clock_in)->count();
        $staffIzin = $todayAttendanceRecords->filter(fn ($a) => $activeStaffUsers->contains($a->user_id) && $a->status === 'izin')->count();
        $staffSakit = $todayAttendanceRecords->filter(fn ($a) => $activeStaffUsers->contains($a->user_id) && $a->status === 'sakit')->count();
        $staffSudahPresensi = $staffPresent + $staffIzin + $staffSakit;

        $hadirCount = $salesPresent + $driverPresent + $staffPresent;
        $izinCount = $salesIzin + $driverIzin + $staffIzin;
        $sakitCount = $salesSakit + $driverSakit + $staffSakit;
        $todayAttendance = $salesSudahPresensi + $driverSudahPresensi + $staffSudahPresensi;

        $salesAbsent = max(0, $salesTotal - $salesSudahPresensi);
        $driverAbsent = max(0, $driverTotal - $driverSudahPresensi);
        $staffAbsent = max(0, $staffTotal - $staffSudahPresensi);

        $attendanceBase = $salesTotal + $driverTotal + $staffTotal;
        $attendanceRate = $attendanceBase > 0 ? round(($todayAttendance / $attendanceBase) * 100) : 0;
        $totalAbsent = max(0, $attendanceBase - $todayAttendance);

        // CARD 4 - Kunjungan Hari Ini (Sales)
        // Total = kunjungan Sales hari ini (data kunjungan sebenarnya)
        $salesUserIds = $salesOnlyIds;

        $todayVisits = Visit::with(['user', 'store'])
            ->whereDate('check_in_at', $today)
            ->whereIn('user_id', $salesUserIds)
            ->get();

        // Status per toko (per route stop) pada route Sales hari ini
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

        // CARD 5 - Route Aktif (sudah ditekan "Mulai Rute", belum selesai)
        $activeRoutesCount = Route::with('stops')
            ->whereIn('user_id', $salesOnlyIds)
            ->whereDate('date', $today)
            ->get()
            ->filter(fn ($r) => $r->computed_status === 'active')
            ->count();

        // CARD 6 - Route Selesai (status = completed, hanya route hari ini yang semua tokonya selesai/dilewati)
        $completedRoutesCount = Route::with('stops')
            ->whereIn('user_id', $salesOnlyIds)
            ->whereDate('date', $today)
            ->get()
            ->filter(fn ($r) => $r->computed_status === 'completed')
            ->count();

        $driverStops = RouteStop::with(['visit', 'store'])
            ->whereHas('route', function ($q) use ($today, $driverOnlyIds) {
                $q->whereDate('date', $today)->whereIn('user_id', $driverOnlyIds);
            })
            ->get();
        $activeDeliveries = $driverStops->filter(function ($stop) {
            return $stop->status !== 'skipped'
                && $stop->visit
                && $stop->visit->check_in_at
                && ! $stop->visit->check_out_at;
        })->count();
        $completedDeliveries = $driverStops->filter(function ($stop) {
            return $stop->status !== 'skipped'
                && $stop->visit
                && $stop->visit->check_in_at
                && $stop->visit->check_out_at;
        })->count();
        $skippedDeliveries = $driverStops->filter(fn ($stop) => $stop->status === 'skipped')->count();
        $pendingDeliveries = $driverStops->filter(function ($stop) {
            return $stop->status !== 'skipped'
                && (! $stop->visit || ! $stop->visit->check_in_at);
        })->count();
        $totalDeliveriesCount = $driverStops->count();

        $activeDeliveryRoutesCount = Route::with('stops')->whereIn('user_id', $driverOnlyIds)->whereDate('date', $today)->get()->filter(fn ($route) => $route->computed_status === 'active')->count();
        $completedDeliveryRoutesCount = Route::with('stops')->whereIn('user_id', $driverOnlyIds)->whereDate('date', $today)->get()->filter(fn ($route) => $route->computed_status === 'completed')->count();

        $totalStores = Store::where('status', 'active')->count();
        $unassignedStoresCount = Store::where('status', 'active')->whereNull('sales_penanggung_jawab_id')->count();
        $deliveryStoresCount = Store::where('status', 'active')->where('is_delivery_destination', true)->count();
        $activeSalesToday = $this->getActiveSalesToday($today, $salesUserIds);
        $activeDriversToday = $this->getActiveSalesToday($today, $driverOnlyIds);
        $activeSalesCount = $activeSalesToday->count();
        $activeDriverCount = $activeDriversToday->count();

        $recentRoutes = Route::with(['user', 'stops', 'stops.visit'])->whereIn('user_id', $salesOnlyIds)->whereDate('date', $today)->orderBy('created_at', 'desc')->take(5)->get();
        $recentDeliveryRoutes = Route::with(['user', 'stops', 'stops.visit'])->whereIn('user_id', $driverOnlyIds)->whereDate('date', $today)->orderBy('created_at', 'desc')->take(5)->get();
        $recentVisits = Visit::with(['user', 'store'])->whereIn('user_id', $salesOnlyIds)->whereDate('check_in_at', $today)->orderBy('check_in_at', 'desc')->take(5)->get();
        $recentDeliveries = Visit::with(['user', 'store'])->whereIn('user_id', $driverOnlyIds)->whereDate('check_in_at', $today)->orderBy('check_in_at', 'desc')->take(5)->get();

        // =========================================================================
        // RINGKASAN KEUANGAN SALES & DRIVER (REVISI 15)
        // =========================================================================

        // 1. Uang Masuk Sales Hari Ini & Breakdown per Sales
        // Definisi: Pembayaran yang masuk HARI INI dari transaksi/piutang toko yang menjadi scope Sales
        $salesUsersList = User::whereIn('id', $salesOnlyIds)->orderBy('name')->get(['id', 'name']);
        $salesCashInSummary = [];
        $totalSalesCashInToday = 0.0;

        // 2. Piutang Sales & Breakdown per Sales
        // Definisi: Saldo piutang berjalan (remaining_amount > 0) dari seluruh toko Sales
        $salesReceivableSummary = [];
        $totalSalesReceivable = 0.0;

        foreach ($salesUsersList as $salesUser) {
            $userStoreIds = Store::where('sales_penanggung_jawab_id', $salesUser->id)
                ->where('status', 'active')
                ->pluck('id');

            // Cash in today for this sales
            $userCashInToday = (float) \App\Models\StoreTransactionPayment::whereHas('transaction', function ($q) use ($userStoreIds) {
                    $q->whereIn('store_id', $userStoreIds);
                })
                ->whereDate('payment_date', $today)
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->sum('amount');

            $totalSalesCashInToday += $userCashInToday;
            $salesCashInSummary[] = [
                'id' => $salesUser->id,
                'name' => $salesUser->name,
                'amount' => $userCashInToday,
            ];

            // Receivable balance for this sales
            $userReceivable = 0.0;
            foreach ($userStoreIds as $stId) {
                $userReceivable += (float) \App\Services\StoreReceivableService::balanceForStore($stId);
            }

            $totalSalesReceivable += $userReceivable;
            $salesReceivableSummary[] = [
                'id' => $salesUser->id,
                'name' => $salesUser->name,
                'amount' => $userReceivable,
            ];
        }

        // 3. Uang Masuk Driver Hari Ini & Breakdown per Driver
        // Definisi: Pembayaran/transaksi Driver yang selesai & paid pada hari ini
        $driverUsersList = User::whereIn('id', $driverOnlyIds)->orderBy('name')->get(['id', 'name']);
        $driverCashInSummary = [];
        $totalDriverCashInToday = 0.0;

        foreach ($driverUsersList as $driverUser) {
            $driverVisitsToday = Visit::where('user_id', $driverUser->id)
                ->whereDate('check_in_at', $today)
                ->where('status', 'completed')
                ->where('transaction_status', 'paid')
                ->get();

            $userDriverCashIn = (float) $driverVisitsToday->sum('transaction_amount');
            $totalDriverCashInToday += $userDriverCashIn;

            $driverCashInSummary[] = [
                'id' => $driverUser->id,
                'name' => $driverUser->name,
                'amount' => $userDriverCashIn,
            ];
        }

        return view('dashboard.admin', compact(
            'todayAttendance', 'hadirCount', 'izinCount', 'sakitCount', 'attendanceRate', 'attendanceBase', 'totalAbsent',
            'salesTotal', 'salesPresent', 'salesIzin', 'salesSakit', 'salesSudahPresensi', 'salesAbsent',
            'staffTotal', 'staffPresent', 'staffIzin', 'staffSakit', 'staffSudahPresensi', 'staffAbsent',
            'driverTotal', 'driverPresent', 'driverIzin', 'driverSakit', 'driverSudahPresensi', 'driverAbsent',
            'totalSales', 'totalDriver', 'totalStaff', 'totalStores', 'unassignedStoresCount', 'deliveryStoresCount',
            'todayVisits', 'completedVisits', 'activeVisits', 'pendingVisits', 'skippedVisits',
            'activeRoutesCount', 'completedRoutesCount', 'totalDeliveriesCount', 'activeDeliveries', 'completedDeliveries', 'pendingDeliveries', 'skippedDeliveries', 'activeDeliveryRoutesCount', 'completedDeliveryRoutesCount',
            'activeSalesToday', 'activeSalesCount', 'activeDriversToday', 'activeDriverCount',
            'recentRoutes', 'recentDeliveryRoutes', 'recentVisits', 'recentDeliveries',
            'totalSalesCashInToday', 'salesCashInSummary',
            'totalSalesReceivable', 'salesReceivableSummary',
            'totalDriverCashInToday', 'driverCashInSummary'
        ));
    }

    private function getActiveSalesToday(string $today, $salesUserIds)
    {
        $active = collect();

        foreach (Attendance::with('user')
            ->whereDate('date', $today)
            ->whereIn('user_id', $salesUserIds)
            ->get() as $att) {
            $active->put($att->user_id, [
                'name' => $att->user->name ?? 'Pengguna',
                'time' => $att->clock_in?->format('H:i') ?? '',
                'activity' => 'Presensi masuk',
            ]);
        }

        foreach (Route::with('user')
            ->whereDate('started_at', $today)
            ->whereIn('user_id', $salesUserIds)
            ->get() as $route) {
            $time = $route->started_at?->format('H:i') ?? '';
            $current = $active->get($route->user_id);
            if (! $current || $time > $current['time']) {
                $active->put($route->user_id, [
                    'name' => $route->user->name ?? 'Pengguna',
                    'time' => $time,
                    'activity' => 'Memulai rute',
                ]);
            }
        }

        foreach (Visit::with('user')
            ->whereDate('check_in_at', $today)
            ->whereIn('user_id', $salesUserIds)
            ->get() as $visit) {
            $time = $visit->check_in_at?->format('H:i') ?? '';
            $current = $active->get($visit->user_id);
            if (! $current || $time > $current['time']) {
                $active->put($visit->user_id, [
                    'name' => $visit->user->name ?? 'Pengguna',
                    'time' => $time,
                    'activity' => 'Kunjungan',
                ]);
            }
        }

        return $active->sortByDesc('time')->values();
    }

    private function staffDashboard(): View
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        $attendanceStatus = $this->getAttendanceStatus($todayAttendance);

        return view('dashboard.staff', compact(
            'todayAttendance', 'attendanceStatus'
        ));
    }

    private function salesDashboard(): View
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        $attendanceStatus = $this->getAttendanceStatus($todayAttendance);

        $todayPlan = Route::todayPlanFor($user->id, $today);
        $todayRoute = $todayPlan['routes']->first();
        $todayStops = $todayPlan['stops'];
        $todayPlanStats = $todayPlan['stats'];

        $todayVisits = Visit::with(['store'])
            ->where('user_id', $user->id)
            ->whereDate('check_in_at', $today)
            ->orderBy('check_in_at', 'desc')
            ->get();

        $salesStores = Store::where('sales_penanggung_jawab_id', $user->id)
            ->where('status', 'active')
            ->pluck('id');

        // 1. Transaksi Baru Hari Ini: Total nilai TRANSAKSI BARU yang dibuat hari ini untuk seluruh toko tanggung jawab Sales ini
        $todayNewTransactionsAmount = (float) \App\Models\StoreTransaction::whereIn('store_id', $salesStores)
            ->whereDate('transaction_date', $today)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', 'opening_balance');
            })
            ->sum('transaction_amount');

        // Fallback untuk kunjungan legacy jika ada
        $legacyNewTxAmount = (float) Visit::where('user_id', $user->id)
            ->whereDate('check_in_at', $today)
            ->where('status', 'completed')
            ->doesntHave('transactions')
            ->where('transaction_amount', '>', 0)
            ->whereIn('transaction_status', ['paid', 'mixed'])
            ->sum('transaction_amount');
        $todayNewTransactionsAmount += $legacyNewTxAmount;

        // 2. Uang Masuk Transaksi Baru Hari Ini: Uang yang BENAR-BENAR DITERIMA dan dialokasikan untuk TRANSAKSI BARU hari ini
        $todayCashInAmount = (float) \App\Models\StoreTransactionPayment::whereHas('transaction', function ($q) use ($salesStores, $today) {
                $q->whereIn('store_id', $salesStores)
                  ->whereDate('transaction_date', $today)
                  ->where(function ($qq) {
                      $qq->whereNull('reference_type')
                         ->orWhere('reference_type', '!=', 'opening_balance');
                  });
            })
            ->whereDate('payment_date', $today)
            ->where(function ($q) {
                $q->where('source', 'initial_payment')
                  ->orWhere('notes', 'like', '%Pembayaran awal%')
                  ->orWhere('notes', 'like', '%pembayaran awal%');
            })
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->sum('amount');

        $legacyNewTxPaid = (float) Visit::where('user_id', $user->id)
            ->whereDate('check_in_at', $today)
            ->where('status', 'completed')
            ->doesntHave('transactions')
            ->where('transaction_amount', '>', 0)
            ->where('transaction_status', 'paid')
            ->get()
            ->sum(fn ($v) => (float) ($v->cash_received ?: $v->transaction_amount));
        $todayCashInAmount += $legacyNewTxPaid;

        // 3. Total Saldo Piutang: SELURUH SALDO PIUTANG YANG MASIH OUTSTANDING untuk seluruh toko tanggung jawab Sales ini (TIDAK reset bulanan)
        $currentReceivableAmount = 0.0;
        foreach ($salesStores as $storeId) {
            $currentReceivableAmount += (float) \App\Services\StoreReceivableService::balanceForStore($storeId);
        }

        // 4. Pembayaran Piutang Lama Hari Ini: Uang yang diterima HARI INI untuk membayar PIUTANG YANG SUDAH ADA SEBELUMNYA (bukan transaksi baru hari ini)
        $todayDebtPaymentAmount = (float) \App\Models\StoreTransactionPayment::whereHas('transaction', function ($q) use ($salesStores, $today) {
                $q->whereIn('store_id', $salesStores)
                  ->where(function ($qq) use ($today) {
                      $qq->whereDate('transaction_date', '<', $today)
                         ->orWhere('reference_type', 'opening_balance');
                  });
            })
            ->whereDate('payment_date', $today)
            ->where('source', '!=', 'initial_payment')
            ->where(function ($q) {
                $q->where('notes', 'not like', '%Pembayaran awal%')
                  ->where('notes', 'not like', '%pembayaran awal%')
                  ->orWhereNull('notes');
            })
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->sum('amount');

        $legacyOldDebtPaid = (float) Visit::where('user_id', $user->id)
            ->whereDate('check_in_at', $today)
            ->where('status', 'completed')
            ->doesntHave('payments')
            ->where('cash_received', '>', 0)
            ->whereIn('transaction_status', ['piutang', 'mixed'])
            ->sum('cash_received');
        $todayDebtPaymentAmount += $legacyOldDebtPaid;

        $todayVisitStats = [
            'total' => $todayVisits->count(),
            'completed' => $todayVisits->where('status', 'completed')->count(),
            'in_progress' => $todayVisits->where('status', 'in_progress')->count(),
            'transaction' => $todayNewTransactionsAmount,
            'cash_in' => $todayCashInAmount,
            'current_receivable' => $currentReceivableAmount,
            'debt_payment_today' => $todayDebtPaymentAmount,
        ];

        $plannedVisitCount = Route::where('user_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        $visitedCount = $todayPlanStats['dikunjungi'];

        $startOfMonth = now()->startOfMonth()->toDateString();
        $todayStr = now()->toDateString();

        // 1. Rencana Bulan Ini: jumlah rencana/rute Sales pada bulan kalender berjalan
        $monthlyRoutes = Route::with(['stops.visit'])
            ->where('user_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get();

        $monthlyCompletedRoutes = $monthlyRoutes->filter(fn ($route) => $route->computed_status === 'completed')->count();

        // 2. Kunjungan Bulan Ini: jumlah kunjungan Sales pada bulan berjalan
        $monthlyVisitsCount = Visit::where('user_id', $user->id)
            ->whereMonth('check_in_at', now()->month)
            ->whereYear('check_in_at', now()->year)
            ->count();

        // 3. Total Transaksi Baru Bulan Ini: nilai transaksi baru yang dibuat di bulan kalender berjalan untuk seluruh toko tanggung jawab Sales ini
        $monthlyTransactionAmount = (float) \App\Models\StoreTransaction::whereIn('store_id', $salesStores)
            ->whereDate('transaction_date', '>=', $startOfMonth)
            ->whereDate('transaction_date', '<=', $todayStr)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', 'opening_balance');
            })
            ->sum('transaction_amount');

        $legacyMonthlyNewTx = (float) Visit::where('user_id', $user->id)
            ->whereBetween('check_in_at', [$startOfMonth . ' 00:00:00', $todayStr . ' 23:59:59'])
            ->where('status', 'completed')
            ->doesntHave('transactions')
            ->where('transaction_amount', '>', 0)
            ->whereIn('transaction_status', ['paid', 'mixed'])
            ->sum('transaction_amount');
        $monthlyTransactionAmount += $legacyMonthlyNewTx;

        // 4. Tingkat Penyelesaian Bulan Ini:
        // Numerator: Kunjungan bulan berjalan yang berhasil Check Out (completed).
        // Denominator: Kunjungan yang SUDAH JATUH TEMPO sampai hari ini (date <= today).
        // Kunjungan masa depan (date > today) TIDAK dimasukkan ke denominator.
        // Visit yang dilewati (skipped) TIDAK dihitung sebagai check out berhasil.
        $dueStopsMonth = RouteStop::with(['visit', 'route'])
            ->whereHas('route', function ($q) use ($user, $startOfMonth, $todayStr) {
                $q->where('user_id', $user->id)
                  ->whereDate('date', '>=', $startOfMonth)
                  ->whereDate('date', '<=', $todayStr);
            })
            ->get();

        $dueVisitsCount = $dueStopsMonth->count();

        $completedVisitsMonth = $dueStopsMonth->filter(function ($stop) {
            return $stop->status !== 'skipped'
                && $stop->visit
                && $stop->visit->check_in_at
                && $stop->visit->check_out_at
                && $stop->visit->status === 'completed';
        })->count();

        $completionRate = $dueVisitsCount > 0 ? (int) round(($completedVisitsMonth / $dueVisitsCount) * 100) : 0;

        $totalStops = $monthlyRoutes->sum(fn ($route) => $route->stops->count());
        $monthlyProgress = $dueVisitsCount > 0 ? $completionRate : ($totalStops > 0 ? round(($completedVisitsMonth / $totalStops) * 100) : 0);
        $monthlyStatus = $totalStops === 0
            ? 'menunggu'
            : ($monthlyCompletedRoutes === $monthlyRoutes->count() && $monthlyRoutes->count() > 0 ? 'selesai' : ($monthlyProgress > 0 ? 'berjalan' : 'menunggu'));

        $monthlyStats = [
            'routes' => $monthlyRoutes->count(),
            'completed_routes' => $monthlyCompletedRoutes,
            'in_progress_routes' => $monthlyRoutes->filter(fn ($route) => $route->computed_status === 'active')->count(),
            'pending_routes' => $monthlyRoutes->filter(fn ($route) => $route->computed_status === 'draft')->count(),
            'visits' => $monthlyVisitsCount,
            'transaction' => $monthlyTransactionAmount,
            'completion_rate' => $completionRate,
            'due_visits' => $dueVisitsCount,
            'completed_visits' => $completedVisitsMonth,
            'progress' => $monthlyProgress,
            'status' => $monthlyStatus,
        ];

        return view('dashboard.sales', compact(
            'todayAttendance', 'todayRoute', 'todayStops', 'todayPlanStats',
            'todayVisits', 'todayVisitStats',
            'plannedVisitCount', 'visitedCount', 'monthlyStats', 'attendanceStatus'
        ));
    }

    private function driverDashboard(): View
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        $attendanceStatus = $this->getAttendanceStatus($todayAttendance);

        $todayPlan = Route::todayPlanFor($user->id, $today);
        $todayRoute = $todayPlan['routes']->first();
        $todayStops = $todayPlan['stops'];
        $todayPlanStats = $todayPlan['stats'];

        $todayVisits = Visit::with(['store'])
            ->where('user_id', $user->id)
            ->whereDate('check_in_at', $today)
            ->orderBy('check_in_at', 'desc')
            ->get();

        $todayCompletedVisits = $todayVisits->where('status', 'completed');
        $todayDriverTx = (float) $todayCompletedVisits->where('transaction_status', 'paid')->sum('transaction_amount');

        $todayVisitStats = [
            'total' => $todayVisits->count(),
            'completed' => $todayCompletedVisits->count(),
            'in_progress' => $todayVisits->where('status', 'in_progress')->count(),
            'transaction' => $todayDriverTx,
            'cash_in' => $todayDriverTx, // For driver, cash received from driver transaction equals the transaction value
        ];

        $plannedVisitCount = Route::where('user_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        $visitedCount = $todayPlanStats['dikunjungi'];

        $monthlyRoutes = Route::with(['stops.visit'])
            ->where('user_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get();

        $monthlyCompletedRoutes = $monthlyRoutes->filter(fn ($route) => $route->computed_status === 'completed')->count();

        $totalStops = $monthlyRoutes->sum(fn ($route) => $route->stops->count());
        $doneStops = $monthlyRoutes->sum(function ($route) {
            return $route->stops->filter(function ($stop) {
                if ($stop->status === 'skipped') {
                    return true;
                }

                return $stop->status === 'visited'
                    && $stop->visit
                    && $stop->visit->status === 'completed';
            })->count();
        });

        $monthlyProgress = $totalStops > 0 ? round(($doneStops / $totalStops) * 100) : 0;
        $monthlyStatus = $totalStops === 0
            ? 'menunggu'
            : ($monthlyProgress >= 100 ? 'selesai' : ($monthlyProgress > 0 ? 'berjalan' : 'menunggu'));

        $monthlyCompletedDriverVisits = Visit::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereMonth('check_in_at', now()->month)
            ->whereYear('check_in_at', now()->year)
            ->get();
        $monthlyDriverTx = (float) $monthlyCompletedDriverVisits->where('transaction_status', 'paid')->sum('transaction_amount');

        $monthlyStats = [
            'routes' => $monthlyRoutes->count(),
            'completed_routes' => $monthlyCompletedRoutes,
            'in_progress_routes' => $monthlyRoutes->filter(fn ($route) => $route->computed_status === 'active')->count(),
            'pending_routes' => $monthlyRoutes->filter(fn ($route) => $route->computed_status === 'draft')->count(),
            'visits' => Visit::where('user_id', $user->id)
                ->whereMonth('check_in_at', now()->month)
                ->whereYear('check_in_at', now()->year)
                ->count(),
            'transaction' => $monthlyDriverTx,
            'cash_in' => $monthlyDriverTx,
            'progress' => $monthlyProgress,
            'status' => $monthlyStatus,
        ];

        return view('dashboard.driver', compact(
            'todayAttendance', 'todayRoute', 'todayStops', 'todayPlanStats',
            'todayVisits', 'todayVisitStats',
            'plannedVisitCount', 'visitedCount', 'monthlyStats', 'attendanceStatus'
        ));
    }

    private function getAttendanceStatus(?Attendance $attendance): string
    {
        if (! $attendance) {
            return 'absent';
        }

        if ($attendance->is_canceled) {
            return 'canceled';
        }

        if ($attendance->status === 'izin') {
            return 'izin';
        }

        if ($attendance->status === 'sakit') {
            return 'sakit';
        }

        if ($attendance->clock_in) {
            return $attendance->clock_out ? 'done' : 'working';
        }

        return 'absent';
    }

    private function getAttendanceTrend(int $days): array
    {
        $trend = [];
        $roles = ['sales', 'staff', 'field-supervisor', 'driver'];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            $attendances = Attendance::whereDate('date', $date)
                ->where('status', '!=', 'canceled')
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', $roles))
                ->get();

            $hadir = $attendances->filter(fn ($a) => !empty($a->clock_in))->count();
            $izin = $attendances->filter(fn ($a) => $a->status === 'izin')->count();
            $sakit = $attendances->filter(fn ($a) => $a->status === 'sakit')->count();
            $total = $hadir + $izin + $sakit;

            $trend[] = [
                'date' => Carbon::parse($date)->format('d M'),
                'full_date' => Carbon::parse($date)->isoFormat('D MMMM YYYY'),
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'count' => $hadir,
                'total' => $total,
            ];
        }
        return $trend;
    }

    private function getRouteTrend(int $days): array
    {
        $allSalesIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->pluck('id');
        return $this->getRouteTrendForIds($allSalesIds, $days);
    }

    private function getRouteTrendForIds($userIds, int $days): array
    {
        $trend = [];
        $fromDate = now()->subDays($days - 1)->toDateString();
        $toDate = now()->toDateString();

        $counts = Route::selectRaw('DATE(date) as date_key, count(*) as total')
            ->whereBetween('date', [$fromDate, $toDate])
            ->whereIn('user_id', $userIds)
            ->groupBy('date_key')
            ->pluck('total', 'date_key');

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $trend[] = [
                'date' => Carbon::parse($date)->format('d M'),
                'full_date' => Carbon::parse($date)->isoFormat('D MMMM YYYY'),
                'total' => (int) ($counts[$date] ?? 0),
            ];
        }
        return $trend;
    }

    private function getVisitTrend(int $days): array
    {
        $allSalesIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->pluck('id');
        return $this->getVisitTrendForIds($allSalesIds, $days);
    }

    private function getVisitTrendForIds($userIds, int $days): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();

            $total = Visit::whereDate('check_in_at', $date)->whereIn('user_id', $userIds)->count();
            $completed = Visit::whereDate('check_in_at', $date)->where('status', 'completed')->whereIn('user_id', $userIds)->count();
            $skipped = RouteStop::where('status', 'skipped')
                ->whereHas('route', fn ($q) => $q->whereDate('date', $date)->whereIn('user_id', $userIds))
                ->count();

            $trend[] = [
                'date' => Carbon::parse($date)->format('d M'),
                'full_date' => Carbon::parse($date)->isoFormat('D MMMM YYYY'),
                'total' => $total,
                'completed' => $completed,
                'skipped' => $skipped,
            ];
        }
        return $trend;
    }

    private function getMonthlyTrend(int $months): array
    {
        $trend = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $monthName = $month->translatedFormat('M');
            $routes = Route::whereMonth('date', $month->month)->whereYear('date', $month->year)->count();
            $visits = Visit::whereMonth('check_in_at', $month->month)->whereYear('check_in_at', $month->year)->count();
            $transaction = Visit::whereMonth('check_in_at', $month->month)
                ->whereYear('check_in_at', $month->year)
                ->sum('transaction_amount') ?? 0;
            $trend[] = [
                'month' => $monthName,
                'routes' => $routes,
                'visits' => $visits,
                'transaction' => $transaction,
            ];
        }
        return $trend;
    }

    private function getTodayActivities(string $today): array
    {
        $activities = collect();

        foreach (Attendance::with('user')
            ->whereDate('date', $today)
            ->get() as $att) {
            if ($att->clock_in) {
                $activities->push([
                    'waktu' => $att->clock_in,
                    'label' => 'Presensi Masuk',
                    'user' => $att->user->name ?? '-',
                    'detail' => $att->clock_in->format('H:i'),
                    'kategori' => 'presensi',
                ]);
            }
            if ($att->clock_out) {
                $activities->push([
                    'waktu' => $att->clock_out,
                    'label' => 'Presensi Keluar',
                    'user' => $att->user->name ?? '-',
                    'detail' => $att->clock_out->format('H:i'),
                    'kategori' => 'presensi',
                ]);
            }
        }

        foreach (Route::with('user')
            ->whereDate('created_at', $today)
            ->get() as $route) {
            $activities->push([
                'waktu' => $route->created_at,
                'label' => 'Pembuatan Rute',
                'user' => $route->user->name ?? '-',
                'detail' => $route->name,
                'kategori' => 'route',
            ]);
        }

        foreach (Visit::with(['user', 'store'])
            ->whereDate('check_in_at', $today)
            ->get() as $visit) {
            $activities->push([
                'waktu' => $visit->check_in_at,
                'label' => 'Check In',
                'user' => $visit->user->name ?? '-',
                'detail' => 'Toko: '.($visit->store->name ?? '-'),
                'kategori' => 'check-in',
            ]);
        }

        foreach (Visit::with(['user', 'store'])
            ->whereDate('check_out_at', $today)
            ->get() as $visit) {
            $activities->push([
                'waktu' => $visit->check_out_at,
                'label' => 'Check Out',
                'user' => $visit->user->name ?? '-',
                'detail' => 'Toko: '.($visit->store->name ?? '-'),
                'kategori' => 'check-out',
            ]);
        }

        return $activities
            ->sortByDesc('waktu')
            ->take(12)
            ->values()
            ->all();
    }

    private function getTopDriversWeek(string $weekStart): array
    {
        $driverIds = User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))->pluck('id');
        return Visit::with('user')
            ->whereIn('user_id', $driverIds)
            ->where('status', 'completed')
            ->whereBetween('check_in_at', [$weekStart.' 00:00:00', now()->toDateString().' 23:59:59'])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($visits) => [
                'name' => $visits->first()->user->name ?? '-',
                'total' => $visits->count(),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->all();
    }

    private function getDriverPerformanceToday(string $today, $driverUserIds): array
    {
        return $this->getSalesPerformanceToday($today, $driverUserIds);
    }

    private function getOperationalUsersPaginated($todayAttendances, $salesUserIds, $driverUserIds, $staffUserIds)
    {
        $operationalUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'driver', 'staff', 'field-supervisor']))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $attendanceByUserId = $todayAttendances->keyBy('user_id');

        $rows = $operationalUsers->map(function (User $user) use ($attendanceByUserId, $salesUserIds, $driverUserIds) {
            $att = $attendanceByUserId->get($user->id);
            $roleName = $user->hasRole('sales') || $user->hasRole('field-supervisor') ? 'Sales' : ($user->hasRole('driver') ? 'Driver' : 'Staff');

            $status = 'Belum Hadir';
            $isPresent = false;

            if ($att) {
                if ($att->status === 'izin') {
                    $status = 'Izin';
                    $isPresent = true;
                } elseif ($att->status === 'sakit') {
                    $status = 'Sakit';
                    $isPresent = true;
                } elseif ($att->clock_in) {
                    $status = 'Hadir';
                    $isPresent = true;
                }
            }

            return [
                'name' => $user->name,
                'role' => $roleName,
                'status' => $status,
                'present' => $isPresent,
                'clock_in' => $att?->clock_in?->format('H:i') ?? ($att?->isAbsence() ? '-' : '-'),
                'clock_out' => $att?->clock_out?->format('H:i') ?? ($att?->isAbsence() ? '-' : '-'),
                'absence_note' => $att?->absence_note,
            ];
        })->sortBy(fn ($row) => $row['present'] ? 0 : 1)->values();

        return $this->paginateDashboardSection($rows, 10, 'users_page');
    }

    private function paginateDashboardSection($items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $items = collect($items);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }

    private function getTopSalesWeek(string $weekStart, $salesUserIds = null): array
    {
        if ($salesUserIds === null) {
            $salesUserIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->pluck('id');
        }
        return Visit::with('user')
            ->whereIn('user_id', $salesUserIds)
            ->where('status', 'completed')
            ->whereBetween('check_in_at', [$weekStart.' 00:00:00', now()->toDateString().' 23:59:59'])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($visits) => [
                'name'  => $visits->first()->user->name ?? '-',
                'total' => $visits->count(),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->all();
    }

    private function getSalesPerformanceToday(string $today, $salesUserIds): array
    {
        $salesUsers = User::whereIn('id', $salesUserIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $stops = RouteStop::with(['visit', 'route'])
            ->whereHas('route', fn ($q) => $q->whereDate('date', $today)->whereIn('user_id', $salesUserIds))
            ->get();

        $performance = [];
        foreach ($salesUsers as $sales) {
            $myStops = $stops->filter(fn ($s) => (string) $s->route->user_id === (string) $sales->id);

            $routes = Route::where('user_id', $sales->id)->whereDate('date', $today)->count();
            $completed = $myStops->filter(fn ($s) => $s->computed_status === 'visited')->count();
            $inProgress = $myStops->filter(fn ($s) => $s->computed_status === 'in_progress')->count();
            $skipped = $myStops->filter(fn ($s) => $s->computed_status === 'skipped')->count();

            $performance[] = [
                'name' => $sales->name,
                'routes' => $routes,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'skipped' => $skipped,
                'score' => ($completed * 3) + $inProgress,
            ];
        }

        return collect($performance)
            ->sortByDesc(fn ($p) => [$p['completed'], $p['in_progress']])
            ->values()
            ->all();
    }

    private function getStaffAttendanceToday($todayAttendances, $staffUserIds): array
    {
        return $todayAttendances
            ->filter(fn ($a) => $staffUserIds->contains($a->user_id))
            ->map(fn ($a) => [
                'name' => $a->user->name ?? '-',
                'status' => $a->clock_in ? 'Hadir' : 'Belum',
                'clock_in' => $a->clock_in?->format('H:i') ?? '-',
                'clock_out' => $a->clock_out?->format('H:i') ?? '-',
            ])
            ->values()
            ->all();
    }

    private function getSalesPerformance($salesUsers, string $monthStart, string $monthEnd): array
    {
        $performance = [];
        foreach ($salesUsers as $sales) {
            $routes = Route::where('user_id', $sales->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->count();
            $completedRoutes = Route::with(['stops.visit'])
                ->where('user_id', $sales->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get()
                ->filter(fn ($r) => $r->computed_status === 'completed')
                ->count();
            $visits = Visit::where('user_id', $sales->id)
                ->whereBetween('check_in_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])
                ->count();
            $transaction = Visit::where('user_id', $sales->id)
                ->whereBetween('check_in_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])
                ->sum('transaction_amount') ?? 0;
            $performance[] = [
                'name' => $sales->name,
                'routes' => $routes,
                'completed_routes' => $completedRoutes,
                'visits' => $visits,
                'transaction' => $transaction,
                'completion' => $routes > 0 ? round(($completedRoutes / $routes) * 100) : 0,
            ];
        }
        return $performance;
    }

    private function getFinancialTrend(int $days): array
    {
        // Scope: HANYA toko yang memiliki Sales Penanggung Jawab untuk section Performa Keuangan Sales
        $salesStoreIds = Store::where('status', 'active')->whereNotNull('sales_penanggung_jawab_id')->pluck('id');

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->toDateString();

            // Pemasukan Transaksi Baru: initial payment hari itu untuk transaksi baru (bukan opening_balance) toko Sales
            $newTxCashIn = (float) StoreTransactionPayment::whereHas('transaction', function ($q) use ($salesStoreIds) {
                    $q->whereIn('store_id', $salesStoreIds)
                      ->where(function ($qq) {
                          $qq->whereNull('reference_type')
                             ->orWhere('reference_type', '!=', 'opening_balance');
                      });
                })
                ->whereDate('payment_date', $dateStr)
                ->where(function ($q) {
                    $q->where('source', 'initial_payment')
                      ->orWhere('notes', 'like', '%Pembayaran awal%');
                })
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->sum('amount');

            // Pembayaran Piutang Lama: payment hari itu bukan initial_payment dan bukan adjustment toko Sales
            $oldDebtPayments = StoreTransactionPayment::with(['transaction.payments'])
                ->whereHas('transaction', function ($q) use ($salesStoreIds) {
                    $q->whereIn('store_id', $salesStoreIds);
                })
                ->whereDate('payment_date', $dateStr)
                ->where('source', '!=', 'initial_payment')
                ->where(function ($q) {
                    $q->where('notes', 'not like', '%Pembayaran awal%')
                      ->orWhereNull('notes');
                })
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->get();

            $oldDebtPayment = (float) $oldDebtPayments->sum('amount');

            // Uang Pelunasan Piutang: bagian dari Pembayaran Piutang Lama yang melunasi transaksi menjadi LUNAS (saldo Rp0)
            $debtSettlementCash = 0.0;
            foreach ($oldDebtPayments as $p) {
                $tx = $p->transaction;
                if (! $tx) continue;
                $txAmount = (float) $tx->transaction_amount;
                if ($txAmount <= 0) continue;

                $allPayments = $tx->payments->filter(function ($otherP) use ($dateStr) {
                    if ($otherP->source === 'adjustment') return false;
                    $otherPDate = $otherP->payment_date ? $otherP->payment_date->toDateString() : null;
                    return $otherPDate && $otherPDate <= $dateStr;
                });

                $priorPayments = $allPayments->filter(function ($otherP) use ($dateStr) {
                    $otherPDate = $otherP->payment_date ? $otherP->payment_date->toDateString() : null;
                    return $otherPDate && $otherPDate < $dateStr;
                });

                $sumAll = (float) $allPayments->sum('amount');
                $sumPrior = (float) $priorPayments->sum('amount');

                if ($sumAll >= $txAmount && $sumPrior < $txAmount) {
                    $debtSettlementCash += (float) $p->amount;
                }
            }

            // Total cash masuk driver hari itu (tetap terpisah)
            $totalDriverCashIn = (float) Visit::whereDate('check_in_at', $dateStr)
                ->where('status', 'completed')
                ->where('transaction_status', 'paid')
                ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                ->sum('transaction_amount');

            // Saldo Piutang Akhir Hari untuk toko-toko milik Sales (Historical Cutoff 23:59:59 dari ledger)
            $outstandingAtDate = 0.0;
            foreach ($salesStoreIds as $stId) {
                $outstandingAtDate += (float) StoreReceivableService::historicalBalanceForStore($stId, $dateStr, '23:59:59');
            }

            $trend[] = [
                'date'                 => $date->isoFormat('D MMM'),
                'full_date'            => $date->isoFormat('D MMMM YYYY'),
                'new_tx_cash_in'       => $newTxCashIn,
                'old_debt_payment'     => $oldDebtPayment,
                'debt_settlement_cash' => $debtSettlementCash,
                'driver_cash_in'       => $totalDriverCashIn,
                'total_cash_in'        => $newTxCashIn + $oldDebtPayment,
                'outstanding_balance'  => $outstandingAtDate,
            ];
        }

        $sumNewTxCashIn       = array_sum(array_column($trend, 'new_tx_cash_in'));
        $sumOldDebt           = array_sum(array_column($trend, 'old_debt_payment'));
        $sumDebtSettlement    = array_sum(array_column($trend, 'debt_settlement_cash'));
        $sumDriverCashIn      = array_sum(array_column($trend, 'driver_cash_in'));
        $maxNewTx             = count($trend) > 0 ? max(array_column($trend, 'new_tx_cash_in')) : 0;
        $maxOldDebt           = count($trend) > 0 ? max(array_column($trend, 'old_debt_payment')) : 0;
        $maxOutstanding       = count($trend) > 0 ? max(array_column($trend, 'outstanding_balance')) : 0;

        return [
            'days'    => $days,
            'summary' => [
                'new_tx_cash_in'       => $sumNewTxCashIn,
                'old_debt_payment'     => $sumOldDebt,
                'debt_settlement_cash' => $sumDebtSettlement,
                'driver_cash_in'       => $sumDriverCashIn,
                'total_cash_in'        => $sumNewTxCashIn + $sumOldDebt,
                'max_new_tx'           => $maxNewTx,
                'max_old_debt'         => $maxOldDebt,
                'max_outstanding'      => $maxOutstanding,
                // backward-compat alias
                'total_amount'         => $sumNewTxCashIn + $sumOldDebt,
                'total_count'          => 0,
                'max_amount'           => max($maxNewTx, $maxOldDebt, $maxOutstanding),
            ],
            'points'  => $trend,
        ];
    }

    private function getTopStores(int $limit): array
    {
        return Visit::selectRaw('store_id, count(*) as total_visits, sum(transaction_amount) as total_transaction')
            ->where('status', 'completed')
            ->groupBy('store_id')
            ->orderByDesc('total_visits')
            ->take($limit)
            ->with('store')
            ->get()
            ->map(fn ($v) => [
                'name' => $v->store->name ?? 'Toko',
                'visits' => $v->total_visits,
                'transaction' => $v->total_transaction ?? 0,
            ])
            ->toArray();
    }

    private function formatRupiah($amount): string
    {
        if ($amount >= 1000000) {
            return round($amount / 1000000, 1) . 'M';
        }
        if ($amount >= 1000) {
            return round($amount / 1000, 0) . 'rb';
        }
        return (string) $amount;
    }
}