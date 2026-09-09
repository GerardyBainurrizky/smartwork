<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportsDashboardComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private User $staff;
    private Store $store1;
    private Store $store2;
    private Store $storeDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->staff = User::where('username', 'staff')->first();

        $this->sales1 = User::create([
            'username' => 'sales1_test',
            'name' => 'Sales Satu Test',
            'email' => 'sales1_test@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->sales1->syncRoles(['sales']);

        $this->sales2 = User::create([
            'username' => 'sales2',
            'name' => 'Sales Dua',
            'email' => 'sales2@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->sales2->syncRoles(['sales']);

        $this->driver = User::create([
            'username' => 'driver1',
            'name' => 'Budi Driver',
            'email' => 'driver1@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);

        $this->store1 = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-001',
            'name' => 'Toko Barokah Jaya',
            'owner' => 'H. Ahmad',
            'address' => 'Jl. Soekarno Hatta No. 10',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'status' => 'active',
        ]);

        $this->store2 = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-002',
            'name' => 'Toko Mitra Sejahtera',
            'owner' => 'Ibu Lina',
            'address' => 'Jl. Asia Afrika No. 25',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'status' => 'active',
        ]);

        $this->storeDriver = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-003',
            'name' => 'Toko Pengiriman Khusus',
            'owner' => 'Pak Agus',
            'address' => 'Jl. Gatot Subroto No. 5',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    public function test_admin_reports_dashboard_metrics_and_financial_accuracy(): void
    {
        $this->actingAs($this->admin);

        $today = now()->toDateString();

        // 1. Presensi: Sales1 Hadir, Sales2 Izin, Staff Sakit
        Attendance::create([
            'user_id' => $this->sales1->id,
            'date' => $today,
            'clock_in' => now()->subHours(4),
            'status' => Attendance::STATUS_PRESENT,
        ]);

        Attendance::create([
            'user_id' => $this->sales2->id,
            'date' => $today,
            'status' => Attendance::STATUS_IZIN,
            'absence_note' => 'Izin keperluan keluarga',
        ]);

        Attendance::create([
            'user_id' => $this->staff->id,
            'date' => $today,
            'status' => Attendance::STATUS_SAKIT,
            'absence_note' => 'Demam flu',
        ]);

        // 2. Route Sales: 1 Rute, 2 Stop (1 visited-completed, 1 skipped)
        $routeSales = RouteModel::create([
            'user_id' => $this->sales1->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Kunjungan Bandung',
            'date' => $today,
            'status' => 'active',
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $routeSales->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $stop2 = RouteStop::create([
            'route_id' => $routeSales->id,
            'store_id' => $this->store2->id,
            'sequence' => 2,
            'status' => 'skipped',
        ]);

        $visitSales = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $routeSales->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => now()->startOfDay()->addHours(9),
            'check_out_at' => now()->startOfDay()->addHours(10),
        ]);

        // 3. Route Driver: 1 Rute, 1 Stop (1 delivery completed with COD payment)
        $routeDriver = RouteModel::create([
            'user_id' => $this->driver->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Pengiriman Driver Bandung',
            'date' => $today,
            'status' => 'completed',
        ]);

        $stopDriver = RouteStop::create([
            'route_id' => $routeDriver->id,
            'store_id' => $this->storeDriver->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visitDriver = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'route_stop_id' => $stopDriver->id,
            'route_id' => $routeDriver->id,
            'store_id' => $this->storeDriver->id,
            'status' => 'completed',
            'check_in_at' => now()->startOfDay()->addHours(11),
            'check_out_at' => now()->startOfDay()->addHours(12),
            'delivered_goods' => 'Barang A 10 Dus',
            'cash_received' => 750000,
            'transaction_amount' => 750000,
            'payment_method' => 'cash',
            'transaction_status' => 'paid',
        ]);

        // 4. Financial Ledger Toko 1 (Sales 1):
        // Transaksi baru Hari Ini: Rp1.000.000, bayar awal: Rp400.000, sisa piutang: Rp600.000
        $trxSales1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 400000,
            'transaction_date' => $today,
            'description' => 'Invoice Pesanan Baru Toko 1',
        ], $this->sales1);

        // 5. Financial Ledger Toko 2 (Sales 2):
        // Transaksi Lunas dibuat bulan ini (kemarin): Rp500.000, bayar penuh Rp500.000
        $yesterday = now()->startOfMonth()->toDateString();
        $trxSales2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store2->id,
            'transaction_amount' => 500000,
            'paid_amount' => 500000,
            'transaction_date' => $yesterday,
            'description' => 'Invoice Lunas Toko 2',
        ], $this->sales2);

        // Hitung ekspektasi:
        // Presensi: 1 Hadir, 1 Izin, 1 Sakit
        // Sales Route Stops: 2 (1 visited, 1 skipped), Route Count: 1
        // Sales Visits: 1 checkin, 1 completed, 1 skipped stop
        // Driver Route Stops: 1 (1 visited), Route Count: 1
        // Driver Deliveries: 1 checkin, 1 completed
        // Uang Masuk Driver Hari Ini: Rp750.000
        // Transaksi Masuk Sales Hari Ini: Rp1.000.000
        // Total Piutang Toko: Rp600.000 (Toko 1)
        // Toko Berpiutang: 1 (Toko 1)
        // Transaksi Terbuka: 1 (trxSales1)
        // Transaksi Lunas: 1 (trxSales2)
        // Total Transaksi Baru Bulan Ini: Rp1.000.000 + Rp500.000 = Rp1.500.000
        // Uang Masuk Transaksi Baru Bulan Ini: Rp400.000 + Rp500.000 = Rp900.000

        $response = $this->get(route('admin.reports.index'));
        $response->assertOk();

        // Verify Section A variables
        $response->assertViewHas('todayAttendance', 1);
        $response->assertViewHas('todayIzin', 1);
        $response->assertViewHas('todaySakit', 1);
        $response->assertViewHas('todayPlannedSalesStops', 2);
        $response->assertViewHas('todaySalesVisits', 1);
        $response->assertViewHas('todaySalesVisitsCompleted', 1);
        $response->assertViewHas('todaySalesVisitsSkipped', 1);
        $response->assertViewHas('todayPlannedDriverStops', 1);
        $response->assertViewHas('todayDriverDeliveries', 1);
        $response->assertViewHas('todayDriverDeliveriesCompleted', 1);
        $response->assertViewHas('todaySalesCashIn', 400000.0);
        $response->assertViewHas('todaySalesNewTxPaid', 400000.0);
        $response->assertViewHas('todaySalesOldDebtPaid', 0.0);
        $response->assertViewHas('todaySalesNewTransactionsAmount', 1000000.0);
        $response->assertViewHas('todayDriverCashIn', 750000.0);

        // Verify Section B variables
        $response->assertViewHas('totalReceivable', 600000.0);
        $response->assertViewHas('storesWithReceivable', 1);
        $response->assertViewHas('totalOpenTransactions', 1);
        $response->assertViewHas('totalPaidTransactions', 1);
        $response->assertViewHas('newTransactionsThisMonth', 1500000.0);
        $response->assertViewHas('newTransactionsCashInThisMonth', 900000.0);

        // Verify HTML contents
        $response->assertSee('Ringkasan Operasional Hari Ini');
        $response->assertSee('Kondisi Piutang &amp; Transaksi Perusahaan', false);
        $response->assertSee('Laporan Sales &amp; Piutang', false);
        $response->assertSee('Laporan Transaksi &amp; Pengiriman Driver', false);
        $response->assertSee('Rp400.000');
        $response->assertSee('Rp750.000');
        $response->assertSee('Rp600.000');
        $response->assertSee('Rp1.500.000');
    }

    public function test_authorization_only_admin_and_superadmin_can_access(): void
    {
        $this->actingAs($this->superAdmin);
        $this->get(route('admin.reports.index'))->assertOk();

        $this->actingAs($this->admin);
        $this->get(route('admin.reports.index'))->assertOk();

        $this->actingAs($this->sales1);
        $this->get(route('admin.reports.index'))->assertRedirect();

        $this->actingAs($this->driver);
        $this->get(route('admin.reports.index'))->assertRedirect();

        $this->actingAs($this->staff);
        $this->get(route('admin.reports.index'))->assertRedirect();
    }
}
