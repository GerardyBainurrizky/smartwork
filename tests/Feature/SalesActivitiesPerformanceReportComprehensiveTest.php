<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesActivitiesPerformanceReportComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $salesA;
    private User $salesB;
    private User $driver;
    private User $staff;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->staff = User::where('username', 'staff')->first();

        $this->salesA = User::create([
            'username' => 'sales_a',
            'name' => 'Sales Ahmad',
            'email' => 'sales_a@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->salesA->syncRoles(['sales']);

        $this->salesB = User::create([
            'username' => 'sales_b',
            'name' => 'Sales Budi',
            'email' => 'sales_b@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->salesB->syncRoles(['sales']);

        $this->driver = User::create([
            'username' => 'driver_deni',
            'name' => 'Driver Deni',
            'email' => 'driver_deni@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);

        // Stores
        $this->storeA = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-A01',
            'name' => 'Toko Amanah A',
            'owner' => 'Owner A',
            'address' => 'Jl. A No. 1',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'status' => 'active',
        ]);

        $this->storeB = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-B01',
            'name' => 'Toko Berkah B',
            'owner' => 'Owner B',
            'address' => 'Jl. B No. 2',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'status' => 'active',
        ]);

        $this->storeC = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-C01',
            'name' => 'Toko Cantik C',
            'owner' => 'Owner C',
            'address' => 'Jl. C No. 3',
            'sales_penanggung_jawab_id' => $this->salesB->id,
            'status' => 'active',
        ]);

        $this->storeDriver = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-D01',
            'name' => 'Toko Driver D',
            'owner' => 'Owner D',
            'address' => 'Jl. D No. 4',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    public function test_authorization_admin_superadmin_allowed_sales_driver_denied(): void
    {
        // Admin & Super Admin allowed
        $this->actingAs($this->admin)->get(route('admin.reports.sales-activities'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('admin.reports.sales-activities'))->assertOk();

        // Sales, Driver, Staff redirected (middleware role:super-admin,admin redirects unauthorized users)
        $this->actingAs($this->salesA)->get(route('admin.reports.sales-activities'))->assertRedirect();
        $this->actingAs($this->driver)->get(route('admin.reports.sales-activities'))->assertRedirect();
        $this->actingAs($this->staff)->get(route('admin.reports.sales-activities'))->assertRedirect();
    }

    public function test_sales_activities_calculations_filtering_and_cross_role_isolation(): void
    {
        $this->actingAs($this->admin);

        // Delete any default seeded users with role sales so we have exactly our 2 sales users
        User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereNotIn('id', [$this->salesA->id, $this->salesB->id])
            ->forceDelete();

        $d1 = '2026-09-01';
        $d2 = '2026-09-05';

        // 1. Sales A Attendance: 2 days
        Attendance::create(['user_id' => $this->salesA->id, 'date' => '2026-09-01', 'status' => 'present']);
        Attendance::create(['user_id' => $this->salesA->id, 'date' => '2026-09-02', 'status' => 'present']);
        // Canceled attendance should NOT count
        Attendance::create(['user_id' => $this->salesA->id, 'date' => '2026-09-03', 'status' => 'canceled']);

        // 2. Sales B Attendance: 1 day
        Attendance::create(['user_id' => $this->salesB->id, 'date' => '2026-09-01', 'status' => 'present']);

        // 3. Driver Attendance (MUST NOT COUNT in Sales report)
        Attendance::create(['user_id' => $this->driver->id, 'date' => '2026-09-01', 'status' => 'present']);

        // 4. Sales A Routes:
        // Route 1: Completed
        $routeA1 = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute A1',
            'date' => '2026-09-01',
            'status' => 'completed',
        ]);
        $stopA1 = RouteStop::create(['route_id' => $routeA1->id, 'store_id' => $this->storeA->id, 'sequence' => 1, 'status' => 'visited']);
        $visitA1 = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->salesA->id,
            'route_stop_id' => $stopA1->id,
            'route_id' => $routeA1->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-01 08:00:00',
            'check_out_at' => '2026-09-01 09:00:00',
        ]);

        // Route 2: Draft / Not completed
        $routeA2 = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute A2',
            'date' => '2026-09-02',
            'status' => 'draft',
        ]);

        // 5. Driver Route (MUST NOT COUNT in Sales report)
        $routeDriver = RouteModel::create([
            'user_id' => $this->driver->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Driver',
            'date' => '2026-09-01',
            'status' => 'completed',
        ]);
        $stopDriver = RouteStop::create(['route_id' => $routeDriver->id, 'store_id' => $this->storeDriver->id, 'sequence' => 1, 'status' => 'visited']);
        Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'route_stop_id' => $stopDriver->id,
            'route_id' => $routeDriver->id,
            'store_id' => $this->storeDriver->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-01 08:00:00',
            'check_out_at' => '2026-09-01 09:00:00',
            'transaction_amount' => 500000,
        ]);

        // 6. Transactions:
        // Toko A (Sales A) TRX = Rp1.000.000 (created by Sales A)
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 500000,
            'transaction_date' => '2026-09-01',
            'description' => 'Faktur 1 Toko A',
        ], $this->salesA);

        // Toko B (Sales A) TRX = Rp2.000.000 (created by Admin for Store B owned by Sales A)
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 2000000,
            'paid_amount' => 2000000,
            'transaction_date' => '2026-09-02',
            'description' => 'Faktur 2 Toko B',
        ], $this->admin);

        // Toko C (Sales B) TRX = Rp1.500.000 (created by Sales B)
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_amount' => 1500000,
            'paid_amount' => 0,
            'transaction_date' => '2026-09-03',
            'description' => 'Faktur 3 Toko C',
        ], $this->salesB);

        // Test 1: All Sales view
        $response = $this->get(route('admin.reports.sales-activities', [
            'from_date' => $d1,
            'to_date' => $d2,
        ]));
        $response->assertOk();

        // Check HTML contains page title and subtitle exactly as requested
        $response->assertSee('Laporan Performa Aktivitas Sales');
        $response->assertSee('Ringkasan aktivitas Sales berdasarkan periode dan filter yang dipilih');

        $summary = $response->viewData('summary');
        $performance = $response->viewData('performance');

        // Total Sales count = 2 (Sales Ahmad, Sales Budi) - Driver and Staff excluded
        $this->assertEquals(2, $summary['total_sales']);
        // Total Attendance = 2 (Sales A) + 1 (Sales B) = 3
        $this->assertEquals(3, $summary['total_attendance']);
        // Total Routes = 2 (Sales A: 2 routes, Sales B: 0) = 2
        $this->assertEquals(2, $summary['total_routes']);
        // Total Visits = 1 (Sales A)
        $this->assertEquals(1, $summary['total_visits']);
        // Total Transaction = 1.000.000 (Store A) + 2.000.000 (Store B) + 1.500.000 (Store C) = 4.500.000
        $this->assertEquals(4500000.0, $summary['total_transaction']);

        // Individual Sales A:
        $perfSalesA = collect($performance)->firstWhere('id', $this->salesA->id);
        $this->assertEquals(2, $perfSalesA['attendance']);
        $this->assertEquals(2, $perfSalesA['routes']);
        $this->assertEquals(1, $perfSalesA['completed_routes']);
        $this->assertEquals(50, $perfSalesA['completion']); // 1 / 2 = 50%
        $this->assertEquals(1, $perfSalesA['visits']);
        $this->assertEquals(1, $perfSalesA['completed_visits']);
        $this->assertEquals(3000000.0, $perfSalesA['transaction']);

        // Individual Sales B:
        $perfSalesB = collect($performance)->firstWhere('id', $this->salesB->id);
        $this->assertEquals(1, $perfSalesB['attendance']);
        $this->assertEquals(0, $perfSalesB['routes']);
        $this->assertEquals(0, $perfSalesB['completed_routes']);
        $this->assertEquals(0, $perfSalesB['completion']); // 0 routes -> 0% (no division by zero error)
        $this->assertEquals(0, $perfSalesB['visits']);
        $this->assertEquals(0, $perfSalesB['completed_visits']);
        $this->assertEquals(1500000.0, $perfSalesB['transaction']);

        // Test 2: Filter by Sales A only
        $responseFilterA = $this->get(route('admin.reports.sales-activities', [
            'from_date' => $d1,
            'to_date' => $d2,
            'user_id' => $this->salesA->id,
        ]));
        $responseFilterA->assertOk();

        $summaryA = $responseFilterA->viewData('summary');
        $this->assertEquals(1, $summaryA['total_sales']);
        $this->assertEquals(2, $summaryA['total_attendance']);
        $this->assertEquals(2, $summaryA['total_routes']);
        $this->assertEquals(1, $summaryA['total_visits']);
        $this->assertEquals(3000000.0, $summaryA['total_transaction']);

        // Test 3: Export PDF & Excel consistency
        $pdfResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'sales-activities',
            'from_date' => $d1,
            'to_date' => $d2,
            'user_id' => $this->salesA->id,
        ]));
        $pdfResp->assertOk();
        $this->assertEquals('application/pdf', $pdfResp->headers->get('content-type'));

        $excelResp = $this->get(route('admin.reports.export-excel', [
            'type' => 'sales-activities',
            'from_date' => $d1,
            'to_date' => $d2,
            'user_id' => $this->salesA->id,
        ]));
        $excelResp->assertOk();
    }

    public function test_completion_calculation_with_various_route_scenarios(): void
    {
        $this->actingAs($this->admin);

        // Sales A with 10 routes, 7 completed -> 70%
        // Sales B with 0 routes -> 0%
        $d1 = '2026-09-10';
        $d2 = '2026-09-20';

        for ($i = 1; $i <= 7; $i++) {
            $r = RouteModel::create([
                'user_id' => $this->salesA->id,
                'created_by' => $this->admin->id,
                'name' => "Rute $i",
                'date' => '2026-09-10',
                'status' => 'completed',
            ]);
            $st = RouteStop::create(['route_id' => $r->id, 'store_id' => $this->storeA->id, 'sequence' => 1, 'status' => 'visited']);
            Visit::create([
                'id' => (string) Str::uuid(),
                'user_id' => $this->salesA->id,
                'route_stop_id' => $st->id,
                'route_id' => $r->id,
                'store_id' => $this->storeA->id,
                'status' => 'completed',
                'check_in_at' => '2026-09-10 08:00:00',
                'check_out_at' => '2026-09-10 09:00:00',
            ]);
        }

        for ($i = 8; $i <= 10; $i++) {
            RouteModel::create([
                'user_id' => $this->salesA->id,
                'created_by' => $this->admin->id,
                'name' => "Rute $i",
                'date' => '2026-09-11',
                'status' => 'draft',
            ]);
        }

        $response = $this->get(route('admin.reports.sales-activities', [
            'from_date' => $d1,
            'to_date' => $d2,
            'user_id' => $this->salesA->id,
        ]));
        $response->assertOk();

        $performance = $response->viewData('performance');
        $perfA = collect($performance)->firstWhere('id', $this->salesA->id);
        $this->assertEquals(10, $perfA['routes']);
        $this->assertEquals(7, $perfA['completed_routes']);
        $this->assertEquals(70, $perfA['completion']);
    }

    /**
     * TEST: Financial breakdown accuracy:
     * - Nilai Transaksi Baru (invoice)
     * - Uang Masuk Transaksi Baru (initial payments)
     * - Pembayaran Piutang Lama (debt collections)
     * - Total Uang Masuk = Uang Masuk Transaksi Baru + Pembayaran Piutang Lama
     * - No double counting
     * - Sync between Web, PDF, and Excel
     */
    public function test_financial_breakdown_distinguishes_new_tx_cash_in_old_debt_and_total_cash_in(): void
    {
        $this->actingAs($this->admin);

        // Clear existing sales users to have clean test state
        User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereNotIn('id', [$this->salesA->id, $this->salesB->id])
            ->forceDelete();

        $d1 = '2026-09-01';
        $d2 = '2026-09-05';

        // Toko A (Sales A):
        // 1. Transaksi Lama (Old Debt from August): Rp 2.000.000
        $oldTrxA = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-OLD-A',
            'transaction_amount' => 2000000,
            'transaction_date' => '2026-08-25',
        ], $this->salesA);

        // 2. Transaksi Baru Toko A in period: Rp 1.000.000 with initial payment Rp 500.000
        $newTrxA = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-NEW-A',
            'transaction_amount' => 1000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'transaction_date' => '2026-09-01',
        ], $this->salesA);

        // 3. Pembayaran Piutang Lama Toko A in period: Rp 1.250.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrxA->id,
            'amount' => 1250000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'source' => 'visit',
        ], $this->salesA);

        // Toko C (Sales B):
        // 4. Transaksi Baru Toko C in period: Rp 3.000.000 lunas (paid Rp 3.000.000)
        $newTrxC = StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_code' => 'TRX-NEW-C',
            'transaction_amount' => 3000000,
            'paid_amount' => 3000000,
            'payment_method' => 'tunai',
            'transaction_date' => '2026-09-03',
        ], $this->salesB);

        // 1. Web view check
        $res = $this->get(route('admin.reports.sales-activities', [
            'from_date' => $d1,
            'to_date' => $d2,
        ]));
        $res->assertOk();

        $summary = $res->viewData('summary');
        $performance = $res->viewData('performance');

        // Total Nilai Transaksi Baru = 1.000.000 (A) + 3.000.000 (C) = 4.000.000 (NOT including 2.000.000 old debt from August)
        $this->assertEquals(4000000.0, $summary['total_new_tx_amount']);
        // Uang Masuk Transaksi Baru = 500.000 (A) + 3.000.000 (C) = 3.500.000
        $this->assertEquals(3500000.0, $summary['total_new_tx_paid']);
        // Pembayaran Piutang Lama = 1.250.000 (A)
        $this->assertEquals(1250000.0, $summary['total_old_debt_paid']);
        // Total Uang Masuk = 3.500.000 + 1.250.000 = 4.750.000
        $this->assertEquals(4750000.0, $summary['total_cash_in']);

        // Individual Sales A breakdown:
        $perfA = collect($performance)->firstWhere('id', $this->salesA->id);
        $this->assertEquals(1000000.0, $perfA['new_tx_amount']);
        $this->assertEquals(500000.0, $perfA['new_tx_paid']);
        $this->assertEquals(1250000.0, $perfA['old_debt_paid']);
        $this->assertEquals(1750000.0, $perfA['total_cash_in']);

        // Individual Sales B breakdown:
        $perfB = collect($performance)->firstWhere('id', $this->salesB->id);
        $this->assertEquals(3000000.0, $perfB['new_tx_amount']);
        $this->assertEquals(3000000.0, $perfB['new_tx_paid']);
        $this->assertEquals(0.0, $perfB['old_debt_paid']);
        $this->assertEquals(3000000.0, $perfB['total_cash_in']);

        // 2. Filter by Sales A only:
        $resA = $this->get(route('admin.reports.sales-activities', [
            'from_date' => $d1,
            'to_date' => $d2,
            'user_id' => $this->salesA->id,
        ]));
        $resA->assertOk();
        $summaryA = $resA->viewData('summary');
        $this->assertEquals(1000000.0, $summaryA['total_new_tx_amount']);
        $this->assertEquals(500000.0, $summaryA['total_new_tx_paid']);
        $this->assertEquals(1250000.0, $summaryA['total_old_debt_paid']);
        $this->assertEquals(1750000.0, $summaryA['total_cash_in']);

        // 3. Export Excel check:
        $export = new \App\Exports\SalesActivitiesExport([
            'fromDate' => $d1,
            'toDate' => $d2,
            'userId' => null,
        ]);
        $exportRows = $export->dataRows();
        $this->assertCount(2, $exportRows);

        $rowA = collect($exportRows)->firstWhere('1', $this->salesA->name);
        $this->assertEquals(1000000.0, $rowA[8]);  // Total Nilai Transaksi Baru
        $this->assertEquals(500000.0, $rowA[9]);   // Uang Masuk Transaksi Baru
        $this->assertEquals(1250000.0, $rowA[10]); // Pembayaran Piutang Lama
        $this->assertEquals(1750000.0, $rowA[11]); // Total Uang Masuk

        // 4. Export PDF check:
        $pdfResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'sales-activities',
            'from_date' => $d1,
            'to_date' => $d2,
        ]));
        $pdfResp->assertOk();
    }
}
