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

class AdminDriverVisitsAndFinancialReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $salesA;
    private User $salesB;
    private User $driverA;
    private User $driverB;
    private User $staff;
    private Store $storeA;
    private Store $storeB;
    private Store $storeDeliveryA;
    private Store $storeDeliveryB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->staff = User::where('username', 'staff')->first();

        $this->salesA = User::create([
            'username' => 'sales_a',
            'name' => 'Sales Anton',
            'email' => 'sales_a@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->salesA->syncRoles(['sales']);

        $this->salesB = User::create([
            'username' => 'sales_b',
            'name' => 'Sales Budi',
            'email' => 'sales_b@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->salesB->syncRoles(['sales']);

        $this->driverA = User::create([
            'username' => 'driver_a',
            'name' => 'Driver Andi',
            'email' => 'driver_a@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driverA->syncRoles(['driver']);

        $this->driverB = User::create([
            'username' => 'driver_b',
            'name' => 'Driver Bambang',
            'email' => 'driver_b@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driverB->syncRoles(['driver']);

        $this->storeA = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'TKO-001',
            'name' => 'Toko Anugerah',
            'owner' => 'Ko Anton',
            'address' => 'Jl. Sudirman 1',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'status' => 'active',
        ]);

        $this->storeB = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'TKO-002',
            'name' => 'Toko Berkah',
            'owner' => 'H. Bahrul',
            'address' => 'Jl. Thamrin 2',
            'sales_penanggung_jawab_id' => $this->salesB->id,
            'status' => 'active',
        ]);

        $this->storeDeliveryA = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'DLV-001',
            'name' => 'Toko Tujuan Kirim 1',
            'owner' => 'Pak Dedi',
            'address' => 'Jl. Gatot Subroto 10',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        $this->storeDeliveryB = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'DLV-002',
            'name' => 'Toko Tujuan Kirim 2',
            'owner' => 'Pak Eko',
            'address' => 'Jl. Gatot Subroto 20',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    /**
     * TEST 1: /admin/driver/visits does not have Uang Masuk card while Dashboard Admin has Uang Masuk Driver Hari Ini
     */
    public function test_driver_visits_card_cash_in_and_filtering(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // 1. Driver A route today: 2 stops (1 completed paid Rp500.000, 1 pending)
        $routeDriverA = RouteModel::create([
            'user_id' => $this->driverA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Driver A Hari Ini',
            'date' => $today,
            'status' => 'active',
        ]);

        $stopA1 = RouteStop::create([
            'route_id' => $routeDriverA->id,
            'store_id' => $this->storeDeliveryA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driverA->id,
            'route_stop_id' => $stopA1->id,
            'route_id' => $routeDriverA->id,
            'store_id' => $this->storeDeliveryA->id,
            'status' => 'completed',
            'check_in_at' => now()->startOfDay()->addHours(8),
            'check_out_at' => now()->startOfDay()->addHours(9),
            'transaction_amount' => 500000,
            'payment_method' => 'tunai',
            'transaction_status' => 'paid',
        ]);

        RouteStop::create([
            'route_id' => $routeDriverA->id,
            'store_id' => $this->storeDeliveryB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        // 2. Driver B route today: 1 stop completed paid Rp300.000
        $routeDriverB = RouteModel::create([
            'user_id' => $this->driverB->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Driver B Hari Ini',
            'date' => $today,
            'status' => 'completed',
        ]);

        $stopB1 = RouteStop::create([
            'route_id' => $routeDriverB->id,
            'store_id' => $this->storeDeliveryB->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driverB->id,
            'route_stop_id' => $stopB1->id,
            'route_id' => $routeDriverB->id,
            'store_id' => $this->storeDeliveryB->id,
            'status' => 'completed',
            'check_in_at' => now()->startOfDay()->addHours(10),
            'check_out_at' => now()->startOfDay()->addHours(11),
            'transaction_amount' => 300000,
            'payment_method' => 'transfer',
            'transaction_status' => 'paid',
        ]);

        // Scenario A: Admin views /admin/driver/visits (Card Uang Masuk Driver has been removed from this page)
        $this->actingAs($this->admin);
        $respAll = $this->get(route('admin.driver.visits.index', ['date' => $today]));
        $respAll->assertOk();
        $respAll->assertDontSee('Uang Masuk Driver');
        $respAll->assertSee('Total Pengiriman');
        $respAll->assertSee('Selesai');
        $respAll->assertSee('Sedang Berjalan');
        $respAll->assertSee('Belum Dikirim');
        $respAll->assertSee('Dilewati');

        // Scenario B: Dashboard Admin keeps "Uang Masuk Driver Hari Ini" for comprehensive monitoring
        $respDashboard = $this->get(route('dashboard'));
        $respDashboard->assertOk();
        $respDashboard->assertViewHas('totalDriverCashInToday', 800000.0);
        $respDashboard->assertSee('Uang Masuk Driver Hari Ini');
        $respDashboard->assertSee('Rp 800.000');
        $respDashboard->assertSee('Total pembayaran transaksi pengiriman Driver hari ini');

        // Scenario C: Super Admin on /admin/driver/visits
        $this->actingAs($this->superAdmin);
        $respSuperAdmin = $this->get(route('admin.driver.visits.index', ['date' => $today]));
        $respSuperAdmin->assertOk();
        $respSuperAdmin->assertDontSee('Uang Masuk Driver');
        $respSuperAdmin->assertSee('Total Pengiriman');
    }

    /**
     * TEST 2: /admin/receivables KPI Cards, Store Status, Recent Transactions & Recent Payments
     */
    public function test_admin_receivables_financial_metrics_and_labels(): void
    {
        $this->actingAs($this->admin);
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();

        // 1. Toko A: Saldo Awal (Bulan lalu) Rp5.000.000
        $trxOld = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
            'paid_amount' => 0,
            'transaction_date' => now()->subMonth()->toDateString(),
            'description' => 'Faktur Lama Toko A',
        ], $this->salesA);

        // Pembayaran piutang lama hari ini: Rp2.000.000
        $payOld = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOld->id,
            'amount' => 2000000,
            'payment_date' => $today,
            'payment_method' => 'tunai',
            'notes' => 'Pembayaran piutang transaksi ' . $trxOld->transaction_code,
        ], $this->salesA);

        // Sisa piutang Toko A sekarang = Rp3.000.000

        // 2. Toko B: Transaksi Baru Bulan Ini Rp10.000.000, Uang Masuk DP Rp8.000.000
        $trxNew = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 10000000,
            'paid_amount' => 8000000,
            'transaction_date' => $today,
            'description' => 'Faktur Baru Toko B',
        ], $this->salesB);

        // Sisa piutang Toko B sekarang = Rp2.000.000

        $response = $this->get(route('admin.receivables.index'));
        $response->assertOk();

        // Check KPI variables
        $response->assertViewHas('totalReceivable', 5000000.0);
        $response->assertViewHas('totalPayments', 2000000.0);
        $response->assertViewHas('todayPayments', 2000000.0);
        $response->assertViewHas('newTransactionsThisMonth', 10000000.0);
        $response->assertViewHas('newTransactionsCashInThisMonth', 8000000.0);
        $response->assertViewHas('storesWithReceivable', 2);

        // Check HTML labels & subtitles
        $response->assertSee('Total saldo piutang yang masih outstanding');
        $response->assertSee('Total Pembayaran Piutang Lama');
        $response->assertSee('Akumulasi pembayaran untuk piutang yang sudah ada');
        $response->assertSee('Pembayaran piutang lama yang diterima hari ini');
        $response->assertSee('Total nilai transaksi baru yang dibuat bulan ini');
        $response->assertSee('Pembayaran yang diterima untuk transaksi baru bulan ini');

        // Check Store status in table
        $response->assertSee('Berpiutang');

        // Check Recent Transactions title
        $response->assertSee('Transaksi Baru Terbaru');
        $response->assertSee('Transaksi/faktur baru yang terakhir dibuat');

        // Check Recent Payments title and classification
        $response->assertSee('Pembayaran Terbaru');
        $response->assertSee('Pembayaran terakhir, dengan jenis pembayaran ditampilkan');
        $response->assertSee('PEMBAYARAN TRANSAKSI BARU');
        $response->assertSee('PEMBAYARAN PIUTANG LAMA');

        // Verify Super Admin consistency
        $this->actingAs($this->superAdmin);
        $respSuper = $this->get(route('admin.receivables.index'));
        $respSuper->assertOk();
        $respSuper->assertViewHas('totalReceivable', 5000000.0);
        $respSuper->assertViewHas('totalPayments', 2000000.0);
        $respSuper->assertViewHas('newTransactionsThisMonth', 10000000.0);
        $respSuper->assertViewHas('newTransactionsCashInThisMonth', 8000000.0);
    }

    /**
     * TEST 3: /admin/reports Transaksi Masuk Sales Hari Ini & Kondisi Piutang Perusahaan
     */
    public function test_admin_reports_sales_inflow_today_and_receivables(): void
    {
        $this->actingAs($this->admin);
        $today = now()->toDateString();

        // Hari Ini:
        // 1. Transaksi Baru Toko A: Rp10.000.000, Dibayar Rp8.000.000 -> Uang Masuk Trx Baru: Rp8.000.000, Piutang Baru: Rp2.000.000
        $trxA = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 10000000,
            'paid_amount' => 8000000,
            'transaction_date' => $today,
            'description' => 'Faktur A Hari Ini',
        ], $this->salesA);

        // 2. Piutang Lama Toko B (dibuat bulan lalu) Rp300.000
        $trxOldB = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 300000,
            'paid_amount' => 0,
            'transaction_date' => now()->subMonth()->toDateString(),
            'description' => 'Faktur Lama B',
        ], $this->salesB);

        // Dibayar hari ini: Rp300.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOldB->id,
            'amount' => 300000,
            'payment_date' => $today,
            'payment_method' => 'tunai',
            'notes' => 'Pembayaran piutang transaksi ' . $trxOldB->transaction_code,
        ], $this->salesB);

        $response = $this->get(route('admin.reports.index'));
        $response->assertOk();

        $response->assertViewHas('todaySalesCashIn', 8300000.0);
        $response->assertViewHas('todaySalesNewTxPaid', 8000000.0);
        $response->assertViewHas('todaySalesOldDebtPaid', 300000.0);
        $response->assertViewHas('totalReceivable', 2000000.0);
        $response->assertViewHas('newTransactionsThisMonth', 10000000.0);
        $response->assertViewHas('newTransactionsCashInThisMonth', 8000000.0);
        $response->assertViewHas('debtPaymentsThisMonth', 300000.0);

        // HTML checks
        $response->assertSee('Rp8.300.000');
        $response->assertSee('Rp8.000.000');
        $response->assertSee('Rp300.000');
        $response->assertSee('Total uang yang diterima dari transaksi baru dan pembayaran piutang lama hari ini');
    }

    /**
     * TEST 4: 5 Reconciliation Scenarios strictly verified
     */
    public function test_five_financial_reconciliation_scenarios(): void
    {
        $today = now()->toDateString();

        // SCENARIO 1: Transaksi baru lunas (Rp1.000.000, bayar Rp1.000.000)
        $s1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 1000000,
            'transaction_date' => $today,
        ], $this->salesA);
        $this->assertEquals(0.0, $s1->remaining_amount);
        $this->assertEquals('LUNAS', $s1->status);

        // SCENARIO 2: Transaksi baru sebagian (Rp2.000.000, bayar Rp500.000)
        $s2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 2000000,
            'paid_amount' => 500000,
            'transaction_date' => $today,
        ], $this->salesA);
        $this->assertEquals(1500000.0, $s2->remaining_amount);
        $this->assertEquals('SEBAGIAN', $s2->status);

        // SCENARIO 3: Transaksi baru belum dibayar (Rp3.000.000, bayar Rp0)
        $s3 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 300000,
            'paid_amount' => 0,
            'transaction_date' => $today,
        ], $this->salesA);
        $this->assertEquals(300000.0, $s3->remaining_amount);
        $this->assertEquals('BELUM_LUNAS', $s3->status);

        // SCENARIO 4: Pembayaran piutang lama (bayar Rp100.000 pada s3)
        $p4 = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $s3->id,
            'amount' => 100000,
            'payment_date' => $today,
            'notes' => 'Pembayaran piutang lama s3',
        ], $this->salesA);
        $s3->refresh();
        $this->assertEquals(200000.0, $s3->remaining_amount);
        $this->assertEquals('SEBAGIAN', $s3->status);
        $this->assertFalse($p4->isNewTransactionPayment());
        $this->assertEquals('PEMBAYARAN PIUTANG LAMA', $p4->payment_type_label);

        // SCENARIO 5: Outstanding Balance Check for Store A
        $balA = (float) StoreReceivableService::balanceForStore($this->storeA->id);
        $this->assertEquals(1700000.0, $balA);
    }

    /**
     * TEST 5: Masalah 3 - Transaksi baru sebagian dibayar lalu dicicil di hari/kunjungan berikutnya
     */
    public function test_subsequent_installment_payment_is_strictly_old_debt_not_new_tx_cash_in(): void
    {
        $day1 = now()->subDays(2)->toDateString();
        $day2 = now()->toDateString();

        // Day 1: Transaksi Baru Toko A Rp10.000.000, bayar DP Rp8.000.000
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 10000000,
            'paid_amount' => 8000000,
            'transaction_date' => $day1,
            'description' => 'Transaksi Baru Day 1',
        ], $this->salesA);

        $this->assertEquals(2000000.0, $trx->remaining_amount);
        $this->assertEquals('SEBAGIAN', $trx->status);

        // Day 2 (Hari Ini): Sales menerima cicilan pelunasan Rp1.000.000
        $payDay2 = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 1000000,
            'payment_date' => $day2,
            'notes' => 'Cicilan pelunasan piutang trx Day 1',
        ], $this->salesA);

        $trx->refresh();
        $this->assertEquals(1000000.0, $trx->remaining_amount);
        $this->assertEquals('SEBAGIAN', $trx->status);

        // Payment Day 2 harus berjenis PEMBAYARAN PIUTANG LAMA
        $this->assertFalse($payDay2->isNewTransactionPayment());
        $this->assertEquals('PEMBAYARAN PIUTANG LAMA', $payDay2->payment_type_label);

        // Pada /admin/reports Hari Ini (Day 2):
        $this->actingAs($this->admin);
        $resp = $this->get(route('admin.reports.index'));
        $resp->assertOk();
        $resp->assertViewHas('todaySalesNewTxPaid', 0.0);
        $resp->assertViewHas('todaySalesOldDebtPaid', 1000000.0);
        $resp->assertViewHas('todaySalesCashIn', 1000000.0);
        $resp->assertViewHas('totalReceivable', 1000000.0);

        // Pada /admin/receivables:
        $respRec = $this->get(route('admin.receivables.index'));
        $respRec->assertOk();
        $respRec->assertViewHas('newTransactionsThisMonth', 10000000.0);
        $respRec->assertViewHas('newTransactionsCashInThisMonth', 8000000.0);
        $respRec->assertViewHas('totalPayments', 1000000.0);
        $respRec->assertViewHas('totalReceivable', 1000000.0);
    }

    /**
     * TEST 6: Hapus Semua Notifikasi Admin
     */
    public function test_admin_notifications_destroy_all_deletes_only_logged_in_admin_notifications(): void
    {
        // 1. Create notification for Admin
        \App\Services\ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Presensi Sales Masuk',
            'message' => 'Sales Anton melakukan presensi',
            'actor_name' => 'Sales Anton',
            'actor_role' => 'Sales',
        ]);

        // 2. Create notification for Super Admin
        \App\Services\ActivityNotificationService::notifyUser($this->superAdmin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Presensi Sales Masuk Super',
            'message' => 'Sales Anton presensi untuk Super Admin',
            'actor_name' => 'Sales Anton',
            'actor_role' => 'Sales',
        ]);

        // 3. Create notification for Sales
        \App\Services\ActivityNotificationService::notifyUser($this->salesA, [
            'activity_type' => 'route_created',
            'title' => 'Rute Ditugaskan',
            'message' => 'Rute baru untuk Sales Anton',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
        ]);

        $this->assertGreaterThan(0, $this->admin->notifications()->count());
        $this->assertGreaterThan(0, $this->superAdmin->notifications()->count());
        $this->assertGreaterThan(0, $this->salesA->notifications()->count());

        $initialSuperAdminNotifs = $this->superAdmin->notifications()->count();
        $initialSalesNotifs = $this->salesA->notifications()->count();

        // Acting as Admin and perform Hapus Semua
        $this->actingAs($this->admin);
        $response = $this->delete(route('admin.notifications.destroy-all'));

        // Assert redirect stays on notifications page
        $response->assertRedirect(route('admin.notifications.index'));
        $response->assertSessionHas('success', 'Semua notifikasi berhasil dihapus.');

        // Refresh and check DB
        $this->assertEquals(0, $this->admin->notifications()->count());
        // Other users' notifications must NOT be deleted
        $this->assertEquals($initialSuperAdminNotifs, $this->superAdmin->notifications()->count());
        $this->assertEquals($initialSalesNotifs, $this->salesA->notifications()->count());
    }
}
