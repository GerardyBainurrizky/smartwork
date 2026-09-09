<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Exports\DriverRoutesExport;
use App\Exports\DriverVisitsExport;
use App\Exports\RoutesExport;
use App\Exports\SalesActivitiesExport;
use App\Exports\StoresExport;
use App\Exports\TransactionsExport;
use App\Exports\UsersExport;
use App\Exports\VisitsExport;
use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreReceivable;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    private function getDateRange(Request $request): array
    {
        $now = Carbon::now();
        $period = $request->get('period', 'this_week');

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $labelMap = [
            'today' => 'Hari Ini',
            'yesterday' => 'Kemarin',
            'this_week' => 'Minggu Ini',
            'last_week' => 'Minggu Lalu',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lalu',
            'custom' => 'Kustom',
        ];

        if (! $fromDate || ! $toDate) {
            [$fromDate, $toDate] = match ($period) {
                'today' => [$now->toDateString(), $now->toDateString()],
                'yesterday' => [$now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString()],
                'this_week' => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
                'last_week' => [$now->copy()->subWeek()->startOfWeek()->toDateString(), $now->copy()->subWeek()->endOfWeek()->toDateString()],
                'this_month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
                'last_month' => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
                default => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            };
        }

        $periodLabel = $labelMap[$period] ?? 'Kustom';

        if ($fromDate === $toDate) {
            $periodLabel .= ' (' . Carbon::parse($fromDate)->format('d M Y') . ')';
        } else {
            $periodLabel .= ' (' . Carbon::parse($fromDate)->format('d M Y') . ' - ' . Carbon::parse($toDate)->format('d M Y') . ')';
        }

        return [$fromDate, $toDate, $periodLabel];
    }

    private function getFieldUsers(): \Illuminate\Support\Collection
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor', 'staff', 'driver']))
            ->orderBy('name')->get();
    }

    private function getSalesUsers(): \Illuminate\Support\Collection
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->orderBy('name')->get();
    }

    private function getDriverUsers(): \Illuminate\Support\Collection
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))
            ->orderBy('name')->get();
    }

    public function index(Request $request): View
    {
        $today = Carbon::now()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // 1. Roles & User scope
        $salesUserIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->pluck('id');
        $driverUserIds = User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))->pluck('id');
        $staffUserIds = User::whereHas('roles', fn ($q) => $q->where('name', 'staff'))->pluck('id');
        $operationalUserIds = $salesUserIds->merge($staffUserIds)->merge($driverUserIds);

        // SECTION A - Operasional Hari Ini
        // 1. Presensi Hari Ini (Hadir / clock_in tercatat & status bukan canceled)
        $todayAttendance = Attendance::whereDate('date', $today)
            ->where('status', '!=', Attendance::STATUS_CANCELED)
            ->whereNotNull('clock_in')
            ->count();

        // 2. Izin Hari Ini
        $todayIzin = Attendance::whereDate('date', $today)
            ->where('status', Attendance::STATUS_IZIN)
            ->count();

        // 3. Sakit Hari Ini
        $todaySakit = Attendance::whereDate('date', $today)
            ->where('status', Attendance::STATUS_SAKIT)
            ->count();

        // 4. Rencana Kunjungan Sales Hari Ini (Route stops Sales)
        $salesTodayRoutes = Route::with(['stops.visit'])
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereDate('date', $today)
            ->get();
        $salesTodayRouteIds = $salesTodayRoutes->pluck('id');
        $todayPlannedSalesStops = RouteStop::whereIn('route_id', $salesTodayRouteIds)->count();
        $todaySalesRoutesCount = $salesTodayRoutes->count();

        // 5. Kunjungan Sales Hari Ini (Check-in kunjungan Sales)
        $todaySalesVisitsList = Visit::whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereDate('check_in_at', $today)
            ->get();
        $todaySalesVisits = $todaySalesVisitsList->count();
        $todaySalesVisitsCompleted = $todaySalesVisitsList->where('status', 'completed')->count();
        $todaySalesVisitsSkipped = RouteStop::whereIn('route_id', $salesTodayRouteIds)->where('status', 'skipped')->count();

        // 6. Sales Aktif (Master user sales dengan status active)
        $activeSalesCount = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->where('status', 'active')
            ->count();

        // 7. Total Pengguna (Master user aktif terdaftar)
        $totalUsers = User::count();

        // 8. Rencana Pengiriman Driver Hari Ini (Route stops Driver)
        $driverTodayRoutes = Route::with(['stops.visit'])
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereDate('date', $today)
            ->get();
        $driverTodayRouteIds = $driverTodayRoutes->pluck('id');
        $todayPlannedDriverStops = RouteStop::whereIn('route_id', $driverTodayRouteIds)->count();
        $todayDriverRoutesCount = $driverTodayRoutes->count();

        // 9. Pengiriman Driver Hari Ini (Check-in delivery Driver)
        $todayDriverDeliveriesList = Visit::whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereDate('check_in_at', $today)
            ->get();
        $todayDriverDeliveries = $todayDriverDeliveriesList->count();
        $todayDriverDeliveriesCompleted = $todayDriverDeliveriesList->where('status', 'completed')->count();
        $todayDriverDeliveriesSkipped = RouteStop::whereIn('route_id', $driverTodayRouteIds)->where('status', 'skipped')->count();

        // 10. Transaksi Masuk Sales Hari Ini (Total Uang Masuk Sales = Uang Masuk Trx Baru Hari Ini + Pembayaran Piutang Lama Hari Ini)
        $todaySalesNewTxPaid = (float) StoreTransactionPayment::whereDate('payment_date', $today)
            ->whereHas('transaction', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('reference_type')
                       ->orWhere('reference_type', '!=', 'opening_balance');
                });
            })
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

        $todaySalesOldDebtPaid = (float) StoreTransactionPayment::whereDate('payment_date', $today)
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

        $todaySalesCashIn = $todaySalesNewTxPaid + $todaySalesOldDebtPaid;

        $todaySalesNewTransactionsAmount = (float) StoreTransaction::whereDate('transaction_date', $today)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', 'opening_balance');
            })
            ->whereHas('store', fn ($q) => $q->whereNotNull('sales_penanggung_jawab_id'))
            ->sum('transaction_amount');

        // 11. Uang Masuk Driver Hari Ini (COD / full payment dari pengiriman selesai Driver hari ini)
        $todayDriverCashIn = (float) Visit::whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereDate('check_in_at', $today)
            ->where('status', 'completed')
            ->where('transaction_status', 'paid')
            ->sum('transaction_amount');

        // SECTION B - Statistik Tambahan / Ledger Piutang & Keuangan
        // Gunakan source-of-truth yang sama dengan ReceivableController
        $allActiveStores = Store::with(['salesPenanggungJawab:id,name', 'transactions.payments'])
            ->where('status', 'active')
            ->get();

        $totalReceivable = 0.0;
        $storesWithReceivable = 0;
        foreach ($allActiveStores as $st) {
            $bal = (float) StoreReceivableService::balanceForStore($st->id);
            $st->receivable_balance = $bal;
            if ($bal > 0.005) {
                $storesWithReceivable++;
                $totalReceivable += $bal;
            }
        }

        // Transaksi Terbuka (remaining_amount > 0)
        $allTransactions = StoreTransaction::with('payments')->get();
        $openTransactionsList = $allTransactions->filter(fn ($t) => $t->remaining_amount > 0.005);
        $totalOpenTransactions = $openTransactionsList->count();

        // Transaksi Lunas (remaining_amount <= 0 && transaction_amount > 0)
        $paidTransactionsList = $allTransactions->filter(fn ($t) => $t->remaining_amount <= 0.005 && (float) $t->transaction_amount > 0);
        $totalPaidTransactions = $paidTransactionsList->count();

        // Total Transaksi Baru Bulan Ini (Sales)
        $newTransactionsThisMonth = (float) StoreTransaction::whereDate('transaction_date', '>=', $startOfMonth)
            ->whereDate('transaction_date', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', 'opening_balance');
            })
            ->sum('transaction_amount');

        // Uang Masuk Transaksi Baru Bulan Ini (Sales - pembayaran awal yang dialokasikan ke transaksi baru bulan ini)
        $newTransactionsCashInThisMonth = (float) StoreTransactionPayment::whereHas('transaction', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereDate('transaction_date', '>=', $startOfMonth)
                  ->whereDate('transaction_date', '<=', $endOfMonth)
                  ->where(function ($qq) {
                      $qq->whereNull('reference_type')
                         ->orWhere('reference_type', '!=', 'opening_balance');
                  });
            })
            ->whereDate('payment_date', '>=', $startOfMonth)
            ->whereDate('payment_date', '<=', $endOfMonth)
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

        // Total Pembayaran Piutang Lama (Bulan Ini)
        $debtPaymentsThisMonth = (float) StoreTransactionPayment::whereDate('payment_date', '>=', $startOfMonth)
            ->whereDate('payment_date', '<=', $endOfMonth)
            ->where('source', '!=', 'initial_payment')
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->where(function ($q) {
                $q->where('notes', 'not like', '%Pembayaran awal%')
                  ->orWhereNull('notes');
            })
            ->sum('amount');

        // SECTION C - Laporan Sales (Grouping per Sales)
        $salesGroups = $allActiveStores->groupBy(fn ($st) => $st->sales_penanggung_jawab_id ?? 'unassigned');
        $salesList = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $salesReceivablesSummary = [];
        foreach ($salesList as $sUser) {
            $userStores = $salesGroups->get($sUser->id, collect());
            $sBal = 0.0;
            $sIndebtedStoresCount = 0;
            $sOpenTrxCount = 0;

            foreach ($userStores as $ust) {
                if ($ust->receivable_balance > 0.005) {
                    $sBal += $ust->receivable_balance;
                    $sIndebtedStoresCount++;
                }
                $sOpenTrxCount += $ust->transactions->filter(fn ($trx) => $trx->remaining_amount > 0.005)->count();
            }

            $salesReceivablesSummary[] = [
                'user' => $sUser,
                'total_receivable' => $sBal,
                'indebted_stores_count' => $sIndebtedStoresCount,
                'open_transactions_count' => $sOpenTrxCount,
                'total_stores' => $userStores->count(),
            ];
        }

        // Urutkan sales dengan piutang terbesar
        usort($salesReceivablesSummary, fn ($a, $b) => $b['total_receivable'] <=> $a['total_receivable']);
        $topIndebtedSales = $salesReceivablesSummary[0] ?? null;

        // SECTION D - Laporan Transaksi Driver
        $driverDeliveriesMonth = Visit::whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereDate('check_in_at', '>=', $startOfMonth)
            ->whereDate('check_in_at', '<=', $endOfMonth)
            ->get();

        $driverStats = [
            'total_deliveries_today' => $todayDriverDeliveries,
            'completed_deliveries_today' => $todayDriverDeliveriesCompleted,
            'planned_deliveries_today' => $todayPlannedDriverStops,
            'total_deliveries_month' => $driverDeliveriesMonth->count(),
            'completed_deliveries_month' => $driverDeliveriesMonth->where('status', 'completed')->count(),
            'pending_deliveries_month' => $driverDeliveriesMonth->where('status', 'in_progress')->count(),
            'deliveries_with_trx_month' => $driverDeliveriesMonth->where('transaction_amount', '>', 0)->count(),
            'deliveries_without_trx_month' => $driverDeliveriesMonth->where('transaction_amount', '<=', 0)->count(),
            'total_amount_month' => (float) $driverDeliveriesMonth->where('transaction_status', 'paid')->sum('transaction_amount'),
            'today_cash_in' => $todayDriverCashIn,
        ];

        return view('reports.index', compact(
            'todayAttendance', 'todayIzin', 'todaySakit',
            'todaySalesRoutesCount', 'todayPlannedSalesStops', 'todaySalesVisits', 'todaySalesVisitsCompleted', 'todaySalesVisitsSkipped',
            'activeSalesCount', 'totalUsers',
            'todayDriverRoutesCount', 'todayPlannedDriverStops', 'todayDriverDeliveries', 'todayDriverDeliveriesCompleted', 'todayDriverDeliveriesSkipped',
            'todaySalesCashIn', 'todaySalesNewTxPaid', 'todaySalesOldDebtPaid', 'todaySalesNewTransactionsAmount', 'todayDriverCashIn',
            'totalReceivable', 'storesWithReceivable', 'totalOpenTransactions', 'totalPaidTransactions',
            'newTransactionsThisMonth', 'newTransactionsCashInThisMonth', 'debtPaymentsThisMonth',
            'salesReceivablesSummary', 'topIndebtedSales', 'driverStats'
        ));
    }

    public function users(Request $request): View
    {
        $search = $request->get('search');
        $role = $request->get('role');
        $status = $request->get('status');

        $query = User::withTrashed()->with('roles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role) {
            if ($role === 'admin') {
                $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']));
            } else {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        $baseStatsQuery = clone $query;

        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'super_admin' => (clone $baseStatsQuery)->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->count(),
            'admin' => (clone $baseStatsQuery)->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->count(),
            'sales' => (clone $baseStatsQuery)->whereHas('roles', fn ($q) => $q->where('name', 'sales'))->count(),
            'driver' => (clone $baseStatsQuery)->whereHas('roles', fn ($q) => $q->where('name', 'driver'))->count(),
            'staff' => (clone $baseStatsQuery)->whereHas('roles', fn ($q) => $q->where('name', 'staff'))->count(),
        ];

        $users = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('reports.users', compact(
            'users', 'roles', 'role', 'status', 'search', 'stats'
        ));
    }

    public function stores(Request $request): View
    {
        $search = $request->get('search');
        $province = $request->get('province');
        $city = $request->get('city');
        $kecamatan = $request->get('kecamatan');
        $status = $request->get('status');
        $salesId = $request->get('sales_id');
        $delivery = $request->get('delivery');

        $query = Store::withTrashed()->with(['salesPenanggungJawab']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('owner', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($province) {
            $query->where('province', $province);
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($kecamatan) {
            $query->where('kecamatan', $kecamatan);
        }

        if ($salesId === 'unassigned') {
            $query->whereNull('sales_penanggung_jawab_id');
        } elseif ($salesId) {
            $query->where('sales_penanggung_jawab_id', $salesId);
        }

        if ($delivery === 'yes') {
            $query->where('is_delivery_destination', true);
        } elseif ($delivery === 'no') {
            $query->where('is_delivery_destination', false);
        }

        if ($status) {
            if ($status === 'active') {
                $query->where('status', 'active')->whereNull('deleted_at');
            } elseif ($status === 'inactive') {
                $query->where(function ($q) {
                    $q->where('status', 'inactive')->orWhereNotNull('deleted_at');
                });
            }
        }

        $stores = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $provinces = Store::withTrashed()
            ->whereNotNull('province')->where('province', '!=', '')
            ->distinct()->orderBy('province')->pluck('province');

        $cities = Store::withTrashed()
            ->whereNotNull('city')->where('city', '!=', '')
            ->when($province, fn ($q) => $q->where('province', $province))
            ->distinct()->orderBy('city')->pluck('city');

        $kecamatans = Store::withTrashed()
            ->whereNotNull('kecamatan')->where('kecamatan', '!=', '')
            ->when($province, fn ($q) => $q->where('province', $province))
            ->when($city, fn ($q) => $q->where('city', $city))
            ->distinct()->orderBy('kecamatan')->pluck('kecamatan');

        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('reports.stores', compact(
            'stores', 'search', 'province', 'city', 'kecamatan', 'status',
            'provinces', 'cities', 'kecamatans', 'salesUsers', 'salesId', 'delivery'
        ));
    }

    public function attendance(Request $request): View
    {
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');
        $statusFilter = $request->get('status');
        $roleFilter = $request->get('role');
        $search = $request->get('search');

        if ($roleFilter === 'sales') {
            $scopedRoleNames = ['sales', 'field-supervisor'];
        } elseif ($roleFilter === 'driver') {
            $scopedRoleNames = ['driver'];
        } elseif ($roleFilter === 'staff') {
            $scopedRoleNames = ['staff'];
        } else {
            $scopedRoleNames = ['sales', 'field-supervisor', 'driver', 'staff'];
            $roleFilter = null;
        }

        $scopedUsersQuery = User::whereHas('roles', fn ($q) => $q->whereIn('name', $scopedRoleNames));
        if ($userId) {
            $scopedUsersQuery->where('id', $userId);
        }
        if ($search) {
            $scopedUsersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }
        $scopedUsers = $scopedUsersQuery->orderBy('name')->get();
        $totalUsers = $scopedUsers->count();
        $scopedUserIds = $scopedUsers->pluck('id');
        $effectiveIds = $scopedUserIds->isNotEmpty() ? $scopedUserIds : collect([null]);

        $allAttendancesForStats = Attendance::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereIn('user_id', $effectiveIds)
            ->get();
        $activeAttendances = $allAttendancesForStats->where('status', '!=', 'canceled');
        $presentUserIds = $activeAttendances->pluck('user_id')->unique();
        $presentCount = $presentUserIds->count();
        $checkedInCount = $activeAttendances->whereNotNull('clock_in')->whereNull('clock_out')->filter(fn ($a) => ! $a->isAbsence())->pluck('user_id')->unique()->count();
        $checkedOutCount = $activeAttendances->whereNotNull('clock_out')->pluck('user_id')->unique()->count();
        $izinCount = $activeAttendances->filter(fn ($a) => $a->status === 'izin')->count();
        $sakitCount = $activeAttendances->filter(fn ($a) => $a->status === 'sakit')->count();
        $canceledCount = $allAttendancesForStats->where('status', 'canceled')->count();
        $notPresent = max(0, $totalUsers - $presentCount);
        $lateCount = $allAttendancesForStats->where('status', 'late')->count();
        $onTimeRate = $this->calcRate($allAttendancesForStats->whereIn('status', ['present', 'late'])->count(), $activeAttendances->count());
        $percentage = $totalUsers > 0 ? (int) round(($presentCount / $totalUsers) * 100) : 0;

        $stats = [
            'total_users' => $totalUsers,
            'total' => $presentCount,
            'present' => $presentCount,
            'late' => $lateCount,
            'checked_in' => $checkedInCount,
            'checked_out' => $checkedOutCount,
            'izin' => $izinCount,
            'sakit' => $sakitCount,
            'not_present' => $notPresent,
            'canceled' => $canceledCount,
            'on_time_rate' => $onTimeRate,
            'percentage' => $percentage,
        ];

        $query = Attendance::with(['user', 'user.roles'])
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereIn('user_id', $effectiveIds)
            ->orderBy('date', 'desc');

        if ($statusFilter === 'canceled') {
            $query->where('status', 'canceled');
        } elseif ($statusFilter === 'checked_out') {
            $query->where('status', '!=', 'canceled')->whereNotNull('clock_out');
        } elseif ($statusFilter === 'checked_in') {
            $query->where('status', '!=', 'canceled')->whereNotNull('clock_in')->whereNull('clock_out')->whereNotIn('status', ['izin', 'sakit']);
        } elseif ($statusFilter === 'izin') {
            $query->where('status', 'izin');
        } elseif ($statusFilter === 'sakit') {
            $query->where('status', 'sakit');
        } elseif (in_array($statusFilter, ['not_present', 'belum_presensi'], true)) {
            $query->whereRaw('1=0');
        } else {
            $query->where('status', '!=', 'canceled');
        }

        $attendances = $query->paginate(10)->withQueryString();

        $roles = Role::whereIn('name', ['sales', 'staff', 'driver'])->get();
        $filterUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', $scopedRoleNames))->orderBy('name')->get();
        if ($roleFilter === null) {
            $filterUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor', 'driver', 'staff']))->orderBy('name')->get();
        }
        $salesUsers = $filterUsers;

        return view('reports.attendance', compact(
            'attendances', 'stats', 'fromDate', 'toDate', 'periodLabel', 'userId', 'statusFilter', 'salesUsers', 'roles', 'roleFilter', 'filterUsers'
        ));
    }

    public function routes(Request $request): View
    {
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');
        $storeId = $request->get('store_id');
        $statusFilter = $request->get('status');
        $search = $request->get('search');

        $query = Route::with(['user', 'stops.store', 'stops.visit', 'creator'])
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($storeId) {
            $query->whereHas('stops', fn ($q) => $q->where('store_id', $storeId));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('stops.store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $routes = $query->paginate(10)->withQueryString();

        $allRoutes = Route::with(['stops.visit'])
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereBetween('date', [$fromDate, $toDate])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($storeId, fn ($q) => $q->whereHas('stops', fn ($sq) => $sq->where('store_id', $storeId)))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('stops.store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->get();

        if ($statusFilter) {
            $allRoutes = $allRoutes->filter(fn ($r) => $r->computed_status === $statusFilter);
        }

        $stats = [
            'total' => $allRoutes->count(),
            'completed' => $allRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count(),
            'active' => $allRoutes->filter(fn ($r) => $r->computed_status === 'active')->count(),
            'draft' => $allRoutes->filter(fn ($r) => $r->computed_status === 'draft')->count(),
            'completion_rate' => $this->calcRate($allRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count(), $allRoutes->count()),
            'total_stops' => RouteStop::whereIn('route_id', $allRoutes->pluck('id'))->count(),
            'visited_stops' => RouteStop::whereIn('route_id', $allRoutes->pluck('id'))->where('status', 'visited')->count(),
        ];

        $salesUsers = $this->getSalesUsers();
        $storesList = Store::orderBy('name')->get();

        return view('reports.routes', compact(
            'routes', 'stats', 'fromDate', 'toDate', 'periodLabel', 'userId', 'storeId', 'statusFilter', 'search', 'salesUsers', 'storesList'
        ));
    }

    public function driverRoutes(Request $request): View
    {
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');
        $storeId = $request->get('store_id');
        $statusFilter = $request->get('status');
        $search = $request->get('search');

        $query = Route::with(['user', 'stops.store', 'stops.visit', 'creator'])
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($storeId) {
            $query->whereHas('stops', fn ($q) => $q->where('store_id', $storeId));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('stops.store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $routes = $query->paginate(10)->withQueryString();

        $allRoutes = Route::with(['stops.visit'])
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereBetween('date', [$fromDate, $toDate])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($storeId, fn ($q) => $q->whereHas('stops', fn ($sq) => $sq->where('store_id', $storeId)))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('stops.store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->get();

        if ($statusFilter) {
            $allRoutes = $allRoutes->filter(fn ($r) => $r->computed_status === $statusFilter);
        }

        $stats = [
            'total' => $allRoutes->count(),
            'completed' => $allRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count(),
            'active' => $allRoutes->filter(fn ($r) => $r->computed_status === 'active')->count(),
            'draft' => $allRoutes->filter(fn ($r) => $r->computed_status === 'draft')->count(),
            'completion_rate' => $this->calcRate($allRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count(), $allRoutes->count()),
            'total_stops' => RouteStop::whereIn('route_id', $allRoutes->pluck('id'))->count(),
            'visited_stops' => RouteStop::whereIn('route_id', $allRoutes->pluck('id'))->where('status', 'visited')->count(),
        ];

        $driverUsers = $this->getDriverUsers();
        $storesList = Store::where('is_delivery_destination', true)->orWhereHas('routeStops.route.user.roles', fn ($q) => $q->where('name', 'driver'))->orderBy('name')->get();

        return view('reports.driver-routes', compact(
            'routes', 'stats', 'fromDate', 'toDate', 'periodLabel', 'userId', 'storeId', 'statusFilter', 'search', 'driverUsers', 'storesList'
        ));
    }

    public function visits(Request $request): View
    {
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');
        $storeId = $request->get('store_id');
        $statusFilter = $request->get('status');
        $search = $request->get('search');

        $query = Visit::with(['user', 'store', 'route', 'routeStop', 'payments.transaction', 'transactions.payments', 'photos', 'checkoutPhotos'])
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->orderBy('check_in_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('store', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                })->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('route', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($statusFilter === 'in_progress') {
            $query->where('status', 'in_progress');
        }

        $visits = $query->paginate(10)->withQueryString();

        $allVisits = Visit::whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('store', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    })->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('route', function ($rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($statusFilter === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->when($statusFilter === 'in_progress', fn ($q) => $q->where('status', 'in_progress'))
            ->get();

        $stats = [
            'total' => $allVisits->count(),
            'completed' => $allVisits->where('status', 'completed')->count(),
            'in_progress' => $allVisits->where('status', 'in_progress')->count(),
            'completion_rate' => $this->calcRate($allVisits->where('status', 'completed')->count(), $allVisits->count()),
            'total_transaction' => $allVisits->sum('transaction_amount') ?? 0,
            'total_cash' => $allVisits->sum('cash_received') ?? 0,
        ];

        $skippedStops = collect();
        if (! in_array($statusFilter, ['completed', 'in_progress'], true)) {
            $skippedQuery = RouteStop::with(['store', 'route.user', 'route'])
                ->where('status', 'skipped')
                ->whereHas('route.user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->whereHas('route', function ($q) use ($fromDate, $toDate, $userId) {
                    $q->whereBetween('date', [$fromDate, $toDate]);
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                });

            if ($storeId) {
                $skippedQuery->where('store_id', $storeId);
            }

            if ($search) {
                $skippedQuery->where(function ($q) use ($search) {
                    $q->whereHas('store', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    })->orWhereHas('route.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('route', function ($rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $skippedStops = $skippedQuery->orderBy('route_id')->get();
        }

        $salesUsers = $this->getSalesUsers();
        $storesList = Store::orderBy('name')->get();

        return view('reports.visits', compact(
            'visits', 'stats', 'skippedStops', 'fromDate', 'toDate', 'periodLabel', 'userId', 'storeId', 'statusFilter', 'search', 'salesUsers', 'storesList'
        ));
    }

    public function driverVisits(Request $request): View
    {
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');
        $storeId = $request->get('store_id');
        $statusFilter = $request->get('status');
        $trxStatus = $request->get('transaction_status');
        $search = $request->get('search');

        $query = Visit::with(['user', 'store', 'route', 'routeStop', 'photos', 'checkoutPhotos'])
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->orderBy('check_in_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('store', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                })->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('route', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($statusFilter === 'in_progress') {
            $query->where('status', 'in_progress');
        }

        if ($trxStatus === 'paid') {
            $query->where('transaction_status', 'paid')->where('transaction_amount', '>', 0);
        } elseif ($trxStatus === 'none') {
            $query->where(function ($q) {
                $q->whereNull('transaction_status')
                    ->orWhere('transaction_status', 'none')
                    ->orWhere('transaction_amount', '<=', 0);
            });
        }

        $deliveries = $query->paginate(10)->withQueryString();

        $allDeliveries = Visit::whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('store', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    })->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('route', function ($rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($statusFilter === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->when($statusFilter === 'in_progress', fn ($q) => $q->where('status', 'in_progress'))
            ->when($trxStatus === 'paid', fn ($q) => $q->where('transaction_status', 'paid')->where('transaction_amount', '>', 0))
            ->when($trxStatus === 'none', fn ($q) => $q->where(function ($sq) {
                $sq->whereNull('transaction_status')->orWhere('transaction_status', 'none')->orWhere('transaction_amount', '<=', 0);
            }))
            ->get();

        $stats = [
            'total' => $allDeliveries->count(),
            'completed' => $allDeliveries->where('status', 'completed')->count(),
            'in_progress' => $allDeliveries->where('status', 'in_progress')->count(),
            'completion_rate' => $this->calcRate($allDeliveries->where('status', 'completed')->count(), $allDeliveries->count()),
            'total_transactions_count' => $allDeliveries->where('transaction_status', 'paid')->where('transaction_amount', '>', 0)->count(),
            'total_transactions_amount' => (float) $allDeliveries->where('transaction_status', 'paid')->sum('transaction_amount'),
        ];

        $skippedStops = collect();
        if (! in_array($statusFilter, ['completed', 'in_progress'], true)) {
            $skippedQuery = RouteStop::with(['store', 'route.user', 'route'])
                ->where('status', 'skipped')
                ->whereHas('route.user.roles', fn ($q) => $q->where('name', 'driver'))
                ->whereHas('route', function ($q) use ($fromDate, $toDate, $userId) {
                    $q->whereBetween('date', [$fromDate, $toDate]);
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                });

            if ($storeId) {
                $skippedQuery->where('store_id', $storeId);
            }

            if ($search) {
                $skippedQuery->where(function ($q) use ($search) {
                    $q->whereHas('store', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    })->orWhereHas('route.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('route', function ($rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $skippedStops = $skippedQuery->orderBy('route_id')->get();
        }

        $driverUsers = $this->getDriverUsers();
        $storesList = Store::where('is_delivery_destination', true)->orWhereHas('routeStops.route.user.roles', fn ($q) => $q->where('name', 'driver'))->orderBy('name')->get();

        return view('reports.driver-visits', compact(
            'deliveries', 'stats', 'skippedStops', 'fromDate', 'toDate', 'periodLabel', 'userId', 'storeId', 'statusFilter', 'trxStatus', 'search', 'driverUsers', 'storesList'
        ));
    }

    public function transactions(Request $request): View
    {
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');
        $storeId = $request->get('store_id');
        $search = $request->get('search');
        $txStatusFilter = $request->get('transaction_status');

        $baseQuery = Visit::with([
            'user.roles',
            'store',
            'route',
            'routeStop',
            'photos',
            'transactions.payments',
            'payments.transaction',
        ])
        ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
        ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
        ->where('status', 'completed');

        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }

        if ($storeId) {
            $baseQuery->where('store_id', $storeId);
        }

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->whereHas('store', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                })
                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('transactions', fn ($tq) => $tq->where('transaction_code', 'like', "%{$search}%"));
            });
        }

        if ($txStatusFilter) {
            if ($txStatusFilter === 'has_transaction') {
                $baseQuery->where(function ($q) {
                    $q->whereHas('transactions')
                        ->orWhere(function ($qq) {
                            $qq->where('transaction_amount', '>', 0)
                               ->whereIn('transaction_status', ['paid', 'mixed']);
                        });
                });
            } elseif ($txStatusFilter === 'has_old_debt_payment') {
                $baseQuery->where(function ($q) {
                    $q->whereHas('payments', fn ($pq) => $pq->where('source', '!=', 'initial_payment')->where('notes', 'not like', '%Pembayaran awal%'))
                        ->orWhere(function ($qq) {
                            $qq->whereIn('transaction_status', ['piutang', 'mixed'])
                               ->where('cash_received', '>', 0);
                        });
                });
            } else {
                $baseQuery->where('transaction_status', $txStatusFilter);
            }
        }

        $allVisits = (clone $baseQuery)->orderBy('check_in_at', 'desc')->get();

        $totalTransactionsCount = 0;
        $totalNilaiTransaksi = 0.0;
        $totalPembayaranBaru = 0.0;
        $totalPembayaranLama = 0.0;
        $totalSisaPiutang = 0.0;

        foreach ($allVisits as $v) {
            $summary = StoreReceivableService::getVisitReceivableSummary($v);
            $vTxCount = $v->transactions->count() ?: (($v->transaction_amount > 0 && in_array($v->transaction_status, ['paid', 'mixed'], true)) ? 1 : 0);

            $totalTransactionsCount += $vTxCount;
            $totalNilaiTransaksi += $summary['new_tx_total'];
            $totalPembayaranBaru += $summary['new_tx_initial_paid'];
            $totalPembayaranLama += $summary['old_debt_paid'];
            $totalSisaPiutang += $summary['new_tx_remaining'];
        }

        $stats = [
            'total_transactions' => $totalTransactionsCount,
            'total_amount' => $totalNilaiTransaksi,
            'total_paid_new' => $totalPembayaranBaru,
            'total_paid_old' => $totalPembayaranLama,
            'total_outstanding' => $totalSisaPiutang,
        ];

        $transactions = $baseQuery->orderBy('check_in_at', 'desc')->paginate(20)->withQueryString();

        // Attach computed financial attributes to paginated collection items
        foreach ($transactions as $v) {
            $summary = StoreReceivableService::getVisitReceivableSummary($v);
            $vTxCount = $v->transactions->count() ?: (($v->transaction_amount > 0 && in_array($v->transaction_status, ['paid', 'mixed'], true)) ? 1 : 0);

            $v->computed_tx_count = $vTxCount;
            $v->computed_nilai_tx = $summary['new_tx_total'];
            $v->computed_bayar_baru = $summary['new_tx_initial_paid'];
            $v->computed_sisa = $summary['new_tx_remaining'];
            $v->computed_saldo_sebelum = $summary['balance_before'];
            $v->computed_bayar_lama = $summary['old_debt_paid'];
            $v->computed_saldo_setelah = $summary['balance_after'];
        }

        $salesUsers = $this->getSalesUsers();
        $stores = Store::orderBy('name')->get();

        return view('reports.transactions', compact(
            'transactions', 'stats', 'fromDate', 'toDate', 'periodLabel', 'userId', 'salesUsers', 'stores', 'storeId', 'search', 'txStatusFilter'
        ));
    }

    public function salesActivities(Request $request): View
    {
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');

        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']));
        if ($userId) {
            $salesUsers->where('id', $userId);
        }
        $salesUsers = $salesUsers->orderBy('name')->get();

        $salesActivitiesData = StoreReceivableService::getSalesActivitiesPerformanceData($salesUsers, $fromDate, $toDate);
        $performance = $salesActivitiesData['performance'];
        $summary = $salesActivitiesData['summary'];

        $salesFilterUsers = $this->getSalesUsers();

        return view('reports.sales-activities', compact(
            'performance', 'summary', 'fromDate', 'toDate', 'periodLabel', 'userId', 'salesFilterUsers'
        ));
    }

    public function exportPdf(Request $request): Response
    {
        $type = $request->get('type', 'attendance');
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');

        if (in_array($type, ['users', 'stores'], true) && ! ($request->filled('from_date') && $request->filled('to_date'))) {
            $fromDate = null;
            $toDate = null;
            $periodLabel = 'Semua Periode';
        }

        $data = $this->getExportData($type, $fromDate, $toDate, $userId, $request);
        $data['periodLabel'] = $periodLabel;
        $data['fromDate'] = $fromDate;
        $data['toDate'] = $toDate;
        $data['generatedAt'] = now()->locale('id')->translatedFormat('d F Y, H:i') . ' WIB';
        $data['printedBy'] = auth()->user()->name ?? 'Administrator';
        $data['printedByRole'] = auth()->user()?->getRoleNames()->first() === 'super-admin' ? 'Super Admin' : 'Admin';
        $data['companyName'] = 'PT ISA Tri Selaras Gemilang';
        $data['companyFullName'] = 'PT ISA TRI SELARAS GEMILANG';
        $data['logoPath'] = public_path('assets/images/logo-isa-smartwork.png');

        // Filter labels untuk driver-visits
        if ($type === 'driver-visits') {
            $data['selectedUserLabel'] = $data['selectedUserLabel'] ?? 'Semua Driver';
            $data['statusLabel'] = $data['statusLabel'] ?? 'Semua Status';
            $data['trxStatusLabel'] = $data['trxStatusLabel'] ?? 'Semua';
        }

        // Filter labels untuk driver-routes
        if ($type === 'driver-routes') {
            if ($userId) {
                $user = User::find($userId);
                $data['selectedUserLabel'] = $user ? $user->name : 'Driver #' . $userId;
            } else {
                $data['selectedUserLabel'] = 'Semua Driver';
            }

            if ($request->filled('status')) {
                $st = $request->get('status');
                $data['statusFilterLabel'] = match ($st) {
                    'completed' => 'Selesai',
                    'active' => 'Sedang Berjalan',
                    'draft' => 'Draft',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst($st),
                };
            } else {
                $data['statusFilterLabel'] = 'Semua Status';
            }

            if ($request->filled('store_id')) {
                $store = Store::find($request->get('store_id'));
                $data['selectedStoreLabel'] = $store ? $store->name : null;
            } else {
                $data['selectedStoreLabel'] = null;
            }
        }

        // Filter labels untuk visits
        if ($type === 'visits') {
            $data['selectedUserLabel'] = $data['selectedUserLabel'] ?? 'Semua Sales';
            $data['statusLabel'] = $data['statusLabel'] ?? 'Semua Status';
        }

        // Filter labels untuk transactions
        if ($type === 'transactions') {
            if ($userId) {
                $user = User::find($userId);
                $data['selectedUserLabel'] = $user ? $user->name : 'Sales #' . $userId;
            } else {
                $data['selectedUserLabel'] = 'Semua Sales';
            }

            if ($request->filled('store_id')) {
                $store = Store::find($request->get('store_id'));
                $data['selectedStoreLabel'] = $store ? $store->name . ($store->code ? ' (' . $store->code . ')' : '') : null;
            } else {
                $data['selectedStoreLabel'] = null;
            }

            $txStatus = $request->get('transaction_status');
            $data['txStatusLabel'] = match ($txStatus) {
                'has_transaction' => 'Ada Transaksi Baru',
                'has_old_debt_payment' => 'Ada Pembayaran Piutang Lama',
                'paid' => 'Transaksi Baru (Paid)',
                'piutang' => 'Bayar Piutang',
                'mixed' => 'Transaksi + Piutang',
                'none' => 'Tanpa Transaksi',
                default => 'Semua Status',
            };
        }

        // Filter labels untuk routes (Sales)
        if ($type === 'routes') {
            if ($userId) {
                $user = User::find($userId);
                $data['selectedUserLabel'] = $user ? $user->name : 'Sales #' . $userId;
            } else {
                $data['selectedUserLabel'] = 'Semua Sales';
            }

            if ($request->filled('status')) {
                $st = $request->get('status');
                $data['statusFilterLabel'] = match ($st) {
                    'completed' => 'Selesai',
                    'active' => 'Sedang Berjalan',
                    'draft' => 'Draft',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst($st),
                };
            } else {
                $data['statusFilterLabel'] = 'Semua Status';
            }

            if ($request->filled('store_id')) {
                $store = Store::find($request->get('store_id'));
                $data['selectedStoreLabel'] = $store ? $store->name : null;
            } else {
                $data['selectedStoreLabel'] = null;
            }
        }

        $viewMap = [
            'users' => 'reports.pdf.users',
            'stores' => 'reports.pdf.stores',
            'attendance' => 'reports.pdf.attendance',
            'routes' => 'reports.pdf.routes',
            'driver-routes' => 'reports.pdf.driver-routes',
            'visits' => 'reports.pdf.visits',
            'driver-visits' => 'reports.pdf.driver-visits',
            'transactions' => 'reports.pdf.transactions',
            'sales-activities' => 'reports.pdf.sales-activities',
        ];

        $view = $viewMap[$type] ?? 'reports.pdf.attendance';
        $landscape = in_array($type, ['stores', 'routes', 'driver-routes', 'transactions', 'sales-activities'], true);

        $data['landscape'] = $landscape;

        $pdf = Pdf::loadView($view, $data)->setPaper('a4', $landscape ? 'landscape' : 'portrait');

        $fileDates = ($fromDate && $toDate) ? "{$fromDate}-{$toDate}" : 'semua-periode';

        $downloadName = $this->downloadName($request->get('filename'), "laporan-{$type}-{$fileDates}", 'pdf');

        return $pdf->download($downloadName);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $type = $request->get('type', 'attendance');
        [$fromDate, $toDate, $periodLabel] = $this->getDateRange($request);
        $userId = $request->get('user_id');

        if (in_array($type, ['users', 'stores'], true) && ! ($request->filled('from_date') && $request->filled('to_date'))) {
            $fromDate = null;
            $toDate = null;
        }

        $exportMap = [
            'users' => UsersExport::class,
            'stores' => StoresExport::class,
            'attendance' => AttendanceExport::class,
            'routes' => RoutesExport::class,
            'driver-routes' => DriverRoutesExport::class,
            'visits' => VisitsExport::class,
            'driver-visits' => DriverVisitsExport::class,
            'transactions' => TransactionsExport::class,
            'sales-activities' => SalesActivitiesExport::class,
        ];

        $exportClass = $exportMap[$type] ?? AttendanceExport::class;

        $params = [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'userId' => $userId,
            'role' => $request->get('role'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'store_id' => $request->get('store_id'),
            'transaction_status' => $request->get('transaction_status'),
            'province' => $request->get('province'),
            'city' => $request->get('city'),
            'kecamatan' => $request->get('kecamatan'),
            'salesId' => $request->get('sales_id'),
            'delivery' => $request->get('delivery'),
        ];

        $fileDates = ($fromDate && $toDate) ? "{$fromDate}-{$toDate}" : 'semua-periode';

        $downloadName = $this->downloadName($request->get('filename'), "laporan-{$type}-{$fileDates}", 'xlsx');

        return Excel::download(new $exportClass($params), $downloadName);
    }

    private function downloadName(?string $filename, string $default, string $extension): string
    {
        if ($filename) {
            $name = preg_replace('/\.(pdf|xlsx?)$/i', '', trim($filename));
            $name = preg_replace('/[\\\\\/:*?"<>|\r\n]+/', ' ', (string) $name);
            $name = preg_replace('/\s+/', ' ', trim($name));

            if ($name !== '') {
                return mb_substr($name, 0, 120) . '.' . $extension;
            }
        }

        return $default . '.' . $extension;
    }

    private function getExportData(string $type, ?string $fromDate, ?string $toDate, ?string $userId, Request $request): array
    {
        return match ($type) {
            'users' => [
                'title' => 'LAPORAN PENGGUNA',
                'items' => User::withTrashed()->with('roles')
                    ->when($request->get('search'), function ($q) use ($request) {
                        $search = $request->get('search');
                        $q->where(function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    })
                    ->when($request->get('role'), function ($q) use ($request) {
                        if ($request->get('role') === 'admin') {
                            return $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'super-admin']));
                        }

                        return $q->whereHas('roles', fn ($r) => $r->where('name', $request->get('role')));
                    })
                    ->when($request->get('status'), fn ($q) => $q->where('status', $request->get('status')))
                    ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']))
                    ->orderBy('created_at', 'desc')->get(),
            ],
            'stores' => [
                'title' => 'LAPORAN MASTER TOKO',
                'items' => Store::withTrashed()->with(['salesPenanggungJawab'])
                    ->when($request->get('search'), function ($q) use ($request) {
                        $search = $request->get('search');
                        $q->where(function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('owner', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                    })
                    ->when($request->get('province'), fn ($q) => $q->where('province', $request->get('province')))
                    ->when($request->get('city'), fn ($q) => $q->where('city', $request->get('city')))
                    ->when($request->get('kecamatan'), fn ($q) => $q->where('kecamatan', $request->get('kecamatan')))
                    ->when($request->get('sales_id'), function ($q, $salesId) {
                        if ($salesId === 'unassigned') {
                            $q->whereNull('sales_penanggung_jawab_id');
                        } else {
                            $q->where('sales_penanggung_jawab_id', $salesId);
                        }
                    })
                    ->when($request->get('delivery'), function ($q, $delivery) {
                        if ($delivery === 'yes') {
                            $q->where('is_delivery_destination', true);
                        } elseif ($delivery === 'no') {
                            $q->where('is_delivery_destination', false);
                        }
                    })
                    ->when($request->get('status'), function ($q, $status) {
                        if ($status === 'active') {
                            $q->where('status', 'active')->whereNull('deleted_at');
                        } elseif ($status === 'inactive') {
                            $q->where(function ($sub) {
                                $sub->where('status', 'inactive')->orWhereNotNull('deleted_at');
                            });
                        }
                    })
                    ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']))
                    ->orderBy('created_at', 'desc')->get(),
            ],
            'attendance' => [
                'title' => 'LAPORAN PRESENSI',
                'items' => Attendance::with(['user', 'user.roles'])
                    ->whereDate('date', '>=', $fromDate)
                    ->whereDate('date', '<=', $toDate)
                    ->when($userId, fn ($q) => $q->where('user_id', $userId))
                    ->when($request->filled('role') && in_array($request->get('role'), ['sales', 'staff', 'driver'], true), function ($q) use ($request) {
                        $role = $request->get('role');
                        if ($role === 'sales') {
                            $q->whereHas('user.roles', fn ($r) => $r->whereIn('name', ['sales', 'field-supervisor']));
                        } else {
                            $q->whereHas('user.roles', fn ($r) => $r->where('name', $role));
                        }
                    })
                    ->when($request->filled('search'), function ($q) use ($request) {
                        $s = $request->get('search');
                        $q->whereHas('user', fn ($r) => $r->where('name', 'like', "%{$s}%")->orWhere('username', 'like', "%{$s}%"));
                    })
                    ->when($request->get('status') === 'canceled', fn ($q) => $q->where('status', 'canceled'))
                    ->when($request->get('status') === 'checked_out', fn ($q) => $q->where('status', '!=', 'canceled')->whereNotNull('clock_out'))
                    ->when($request->get('status') === 'checked_in', fn ($q) => $q->where('status', '!=', 'canceled')->whereNotNull('clock_in')->whereNull('clock_out')->whereNotIn('status', ['izin', 'sakit']))
                    ->when($request->get('status') === 'izin', fn ($q) => $q->where('status', 'izin'))
                    ->when($request->get('status') === 'sakit', fn ($q) => $q->where('status', 'sakit'))
                    ->when(empty($request->get('status')), fn ($q) => $q->where('status', '!=', 'canceled'))
                    ->orderBy('date', 'desc')->get(),
            ],
            'routes' => [
                'title' => 'LAPORAN RENCANA KUNJUNGAN SALES',
                'items' => Route::with(['user.roles', 'stops.store', 'stops.visit', 'creator'])
                    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->when($userId, fn ($q) => $q->where('user_id', $userId))
                    ->when($request->get('store_id'), fn ($q, $sid) => $q->whereHas('stops', fn ($sq) => $sq->where('store_id', $sid)))
                    ->when($request->get('search'), function ($q, $search) {
                        $q->where(function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('stops.store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->orderBy('date', 'desc')->get()
                    ->when($request->get('status'), function ($collection, $status) {
                        return $collection->filter(fn ($r) => $r->computed_status === $status);
                    }),
            ],
            'driver-routes' => [
                'title' => 'LAPORAN RENCANA PENGIRIMAN DRIVER',
                'items' => Route::with(['user.roles', 'stops.store', 'stops.visit', 'creator'])
                    ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->when($userId, fn ($q) => $q->where('user_id', $userId))
                    ->when($request->get('store_id'), fn ($q, $sid) => $q->whereHas('stops', fn ($sq) => $sq->where('store_id', $sid)))
                    ->when($request->get('search'), function ($q, $search) {
                        $q->where(function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('stops.store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->orderBy('date', 'desc')->get()
                    ->when($request->get('status'), function ($collection, $status) {
                        return $collection->filter(fn ($r) => $r->computed_status === $status);
                    }),
            ],
            'visits' => $this->buildVisitExportData($fromDate, $toDate, $userId, $request),
            'driver-visits' => $this->buildDriverVisitExportData($fromDate, $toDate, $userId, $request),
            'transactions' => [
                'title' => 'LAPORAN TRANSAKSI KUNJUNGAN SALES',
                'items' => Visit::with([
                    'user.roles',
                    'store',
                    'route',
                    'routeStop',
                    'photos',
                    'transactions.payments',
                    'payments.transaction.payments',
                ])
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                ->where('status', 'completed')
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->when($request->get('store_id'), fn ($q, $sid) => $q->where('store_id', $sid))
                ->when($request->get('search'), function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->whereHas('store', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('transactions', fn ($tq) => $tq->where('transaction_code', 'like', "%{$search}%"));
                    });
                })
                ->when($request->get('transaction_status'), function ($q, $st) {
                    if ($st === 'has_transaction') {
                        $q->where(function ($sub) {
                            $sub->whereHas('transactions')
                                ->orWhere(function ($qq) {
                                    $qq->where('transaction_amount', '>', 0)
                                       ->whereIn('transaction_status', ['paid', 'mixed']);
                                });
                        });
                    } elseif ($st === 'has_old_debt_payment') {
                        $q->where(function ($sub) {
                            $sub->whereHas('payments', fn ($pq) => $pq->where('source', '!=', 'initial_payment')->where('notes', 'not like', '%Pembayaran awal%'))
                                ->orWhere(function ($qq) {
                                    $qq->whereIn('transaction_status', ['piutang', 'mixed'])
                                       ->where('cash_received', '>', 0);
                                });
                        });
                    } else {
                        $q->where('transaction_status', $st);
                    }
                })
                ->orderBy('check_in_at', 'desc')->get(),
            ],
            'sales-activities' => [
                'title' => 'LAPORAN PERFORMA AKTIVITAS SALES',
                'items' => $this->getSalesUsers()->when($userId, fn ($q) => $q->where('id', $userId)),
                'salesActivitiesData' => StoreReceivableService::getSalesActivitiesPerformanceData($this->getSalesUsers()->when($userId, fn ($q) => $q->where('id', $userId)), $fromDate, $toDate),
                'salesData' => $this->getSalesPerformanceData($this->getSalesUsers()->when($userId, fn ($q) => $q->where('id', $userId)), $fromDate, $toDate),
            ],
            default => ['title' => '', 'items' => collect()],
        };
    }

    private function buildVisitExportData(?string $fromDate, ?string $toDate, ?string $userId, ?Request $request = null): array
    {
        $statusFilter = $request?->get('status');
        $storeId = $request?->get('store_id');
        $search = $request?->get('search');

        $visits = Visit::with(['user', 'store', 'route', 'routeStop', 'payments.transaction.payments', 'transactions.payments', 'photos', 'checkoutPhotos'])
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereDoesntHave('routeStop', fn ($rs) => $rs->where('status', 'skipped'))
            ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($statusFilter === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->when($statusFilter === 'in_progress', fn ($q) => $q->where('status', 'in_progress'))
            ->orderBy('check_in_at', 'desc')->get();

        $skipped = collect();
        if (! in_array($statusFilter, ['completed', 'in_progress'], true)) {
            $skipped = RouteStop::with(['store', 'route.user', 'visit', 'anyVisit'])
                ->where('status', 'skipped')
                ->whereHas('route.user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->whereHas('route', function ($q) use ($fromDate, $toDate, $userId) {
                    if ($fromDate && $toDate) {
                        $q->whereDate('date', '>=', $fromDate)
                          ->whereDate('date', '<=', $toDate);
                    } elseif ($fromDate) {
                        $q->whereDate('date', '>=', $fromDate);
                    } elseif ($toDate) {
                        $q->whereDate('date', '<=', $toDate);
                    }
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                })
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->whereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
                            ->orWhereHas('route.user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('route', fn ($rq) => $rq->where('name', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('route_id')
                ->orderBy('sequence')->get();
        }

        $selectedUser = $userId ? User::find($userId) : null;
        $selectedUserLabel = $selectedUser ? $selectedUser->name : 'Semua Sales';
        $statusLabel = match ($statusFilter) {
            'completed' => 'Selesai',
            'in_progress' => 'Sedang Berjalan',
            default => 'Semua Status',
        };

        return [
            'title' => 'LAPORAN KUNJUNGAN SALES',
            'items' => $visits,
            'skipped' => $skipped,
            'selectedUserLabel' => $selectedUserLabel,
            'statusLabel' => $statusLabel,
        ];
    }

    private function buildDriverVisitExportData(?string $fromDate, ?string $toDate, ?string $userId, ?Request $request = null): array
    {
        $statusFilter = $request?->get('status');
        $trxStatus = $request?->get('transaction_status');
        $storeId = $request?->get('store_id');
        $search = $request?->get('search');

        $query = Visit::with(['user', 'store', 'route', 'routeStop', 'photos', 'checkoutPhotos'])
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($statusFilter === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->when($statusFilter === 'in_progress', fn ($q) => $q->where('status', 'in_progress'))
            ->when($trxStatus === 'paid', fn ($q) => $q->where('transaction_status', 'paid')->where('transaction_amount', '>', 0))
            ->when($trxStatus === 'none', fn ($q) => $q->where(function ($sq) {
                $sq->whereNull('transaction_status')->orWhere('transaction_status', 'none')->orWhere('transaction_amount', '<=', 0);
            }))
            ->orderBy('check_in_at', 'desc');

        $deliveries = $query->get();

        $skipped = collect();
        if (! in_array($statusFilter, ['completed', 'in_progress'], true) && $trxStatus === null) {
            $skipped = RouteStop::with(['store', 'route.user', 'visit', 'anyVisit'])
                ->where('status', 'skipped')
                ->whereHas('route.user.roles', fn ($q) => $q->where('name', 'driver'))
                ->whereHas('route', function ($q) use ($fromDate, $toDate, $userId) {
                    $q->whereBetween('date', [$fromDate, $toDate]);
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                })
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->orderBy('route_id')->get();
        }

        $selectedUser = $userId ? User::find($userId) : null;
        $selectedUserLabel = $selectedUser ? $selectedUser->name : 'Semua Driver';
        $statusLabel = match ($statusFilter) {
            'completed' => 'Selesai',
            'in_progress' => 'Sedang Dikirim',
            default => 'Semua Status',
        };
        $trxStatusLabel = match ($trxStatus) {
            'paid' => 'COD/Dibayar',
            'none' => 'Tanpa Transaksi',
            default => 'Semua',
        };

        return [
            'title' => 'LAPORAN HASIL PENGIRIMAN DRIVER',
            'items' => $deliveries,
            'skipped' => $skipped,
            'selectedUserLabel' => $selectedUserLabel,
            'statusLabel' => $statusLabel,
            'trxStatusLabel' => $trxStatusLabel,
        ];
    }

    private function getSalesPerformanceData($salesUsers, $fromDate, $toDate): array
    {
        $res = StoreReceivableService::getSalesActivitiesPerformanceData($salesUsers, $fromDate, $toDate);
        $data = [];
        foreach ($res['performance'] as $p) {
            $data[$p['id']] = $p;
        }

        return $data;
    }

    private function calcRate(int $value, int $total): int
    {
        return $total > 0 ? round(($value / $total) * 100) : 0;
    }
}
