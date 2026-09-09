<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReceivablesDashboardAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeUnassigned;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin Manager']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Director']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales1 = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Citra']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Joko']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko A',
            'code' => 'TKA-01',
            'address' => 'Jl. A',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko B',
            'code' => 'TKB-02',
            'address' => 'Jl. B',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeC = Store::create([
            'name' => 'Toko C',
            'code' => 'TKC-03',
            'address' => 'Jl. C',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);

        $this->storeUnassigned = Store::create([
            'name' => 'Toko Tanpa Sales',
            'code' => 'TKS-04',
            'address' => 'Jl. D',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'status' => 'active',
            'sales_penanggung_jawab_id' => null,
        ]);
    }

    /**
     * TEST 1: Admin dapat membuka dashboard
     */
    public function test_1_admin_dapat_membuka_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index'));
        $response->assertOk();
        $response->assertSee('Master Piutang Toko');
    }

    /**
     * TEST 2: Super Admin dapat membuka dashboard
     */
    public function test_2_super_admin_dapat_membuka_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.receivables.index'));
        $response->assertOk();
        $response->assertSee('Master Piutang Toko');
    }

    /**
     * TEST 3: Sales tidak dapat membuka dashboard Admin
     */
    public function test_3_sales_tidak_dapat_membuka_dashboard_admin(): void
    {
        $this->actingAs($this->sales1)->get(route('admin.receivables.index'))->assertRedirect();
    }

    /**
     * TEST 4: Driver tidak dapat membuka dashboard piutang
     */
    public function test_4_driver_tidak_dapat_membuka_dashboard_piutang(): void
    {
        $this->actingAs($this->driver)->get(route('admin.receivables.index'))->assertRedirect();
    }

    /**
     * TEST 5, 6, 7, 8, 9, 10: Verifikasi seluruh metrik kartu KPI:
     * Toko A:
     * TRX-1: Rp100.000, bayar Rp20.000 (sisa Rp80.000) -> SEBAGIAN
     * TRX-2: Rp50.000, bayar Rp50.000 (sisa Rp0) -> LUNAS
     * TRX-3: Rp200.000, bayar Rp0 (sisa Rp200.000) -> BELUM_LUNAS
     *
     * Toko B: Rp0
     * Toko C: Rp0
     *
     * Expected:
     * Total Piutang = Rp280.000
     * Toko Memiliki Piutang = 1
     * Transaksi Terbuka = 2 (TRX-1 dan TRX-3)
     * Total Pembayaran = Rp70.000 (Rp20.000 + Rp50.000)
     * Transaksi Lunas = 1 (TRX-2)
     */
    public function test_5_sampai_10_verifikasi_kpi_cards(): void
    {
        $t1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $t2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 50000,
            'paid_amount' => 50000,
            'payment_method' => 'transfer',
        ], $this->sales1);

        $t3 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 200000,
            'paid_amount' => 0,
        ], $this->sales1);

        // Record old debt payments
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $t1->id,
            'amount' => 30000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pembayaran cicilan piutang lama',
        ], $this->sales1);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $t3->id,
            'amount' => 40000,
            'payment_method' => 'transfer',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pembayaran cicilan piutang lama kedua',
        ], $this->sales1);

        $response = $this->actingAs($this->admin)->getJson(route('admin.receivables.index'));
        $response->assertOk();

        $summary = $response->json('summary');
        $this->assertEquals(210000.0, (float) $summary['total_receivable']);
        $this->assertEquals(1, $summary['stores_with']);
        $this->assertEquals(2, $summary['total_open_transactions']);
        $this->assertEquals(1, $summary['total_paid_transactions']);
        $this->assertEquals(70000.0, (float) $summary['total_payments']);
        $this->assertEquals(70000.0, (float) $summary['today_payments']);
        $this->assertEquals(350000.0, (float) $summary['new_transactions_this_month']);
        $this->assertEquals(70000.0, (float) $summary['new_transactions_cash_in_this_month']);
    }

    /**
     * TEST 11: Filter Sales (Sales 1: Toko A Rp100rb + Toko B Rp50rb = Rp150rb; Sales 2: Toko C Rp200rb)
     */
    public function test_11_filter_sales_menghasilkan_data_terisolasi(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 50000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_amount' => 200000,
        ], $this->sales2);

        // Filter Sales 1
        $resSales1 = $this->actingAs($this->admin)->getJson(route('admin.receivables.index', ['sales_id' => $this->sales1->id]));
        $resSales1->assertOk();
        $this->assertEquals(150000.0, (float) $resSales1->json('summary.total_receivable'));
        $this->assertEquals(2, $resSales1->json('summary.stores_with'));

        // Filter Sales 2
        $resSales2 = $this->actingAs($this->admin)->getJson(route('admin.receivables.index', ['sales_id' => $this->sales2->id]));
        $resSales2->assertOk();
        $this->assertEquals(200000.0, (float) $resSales2->json('summary.total_receivable'));
        $this->assertEquals(1, $resSales2->json('summary.stores_with'));
    }

    /**
     * TEST 12: Filter Toko
     */
    public function test_12_filter_toko(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 50000,
        ], $this->sales1);

        $res = $this->actingAs($this->admin)->getJson(route('admin.receivables.index', ['store_id' => $this->storeA->id]));
        $res->assertOk();
        $this->assertEquals(100000.0, (float) $res->json('summary.total_receivable'));
        $this->assertEquals(1, $res->json('summary.stores_with'));
    }

    /**
     * TEST 13: Filter Status (with vs without)
     */
    public function test_13_filter_status(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
        ], $this->sales1);

        // Filter 'with' -> Only storeA
        $resWith = $this->actingAs($this->admin)->getJson(route('admin.receivables.index', ['receivable_status' => 'with']));
        $this->assertCount(1, $resWith->json('stores'));

        // Filter 'without' -> 3 stores
        $resWithout = $this->actingAs($this->admin)->getJson(route('admin.receivables.index', ['receivable_status' => 'without']));
        $this->assertCount(3, $resWithout->json('stores'));
    }

    /**
     * TEST 14: Filter Periode
     */
    public function test_14_filter_periode(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
            'paid_amount' => 0,
            'payment_method' => 'tunai',
        ], $this->sales1);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 20000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pembayaran piutang hari ini',
        ], $this->sales1);

        $resToday = $this->actingAs($this->admin)->getJson(route('admin.receivables.index', ['period' => 'today']));
        $resToday->assertOk();
        $this->assertEquals(20000.0, (float) $resToday->json('summary.total_payments'));
    }

    /**
     * TEST 15 & 16: Grafik pembayaran menggunakan payment ledger dan grafik Sales
     */
    public function test_15_dan_16_grafik_pembayaran_dan_sales_menggunakan_ledger(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
            'paid_amount' => 0,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // Record payment against receivable
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 30000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pembayaran piutang',
        ], $this->sales1);

        $res = $this->actingAs($this->admin)->getJson(route('admin.receivables.index'));
        $res->assertOk();

        // 15. Grafik pembayaran per tanggal (Ringkasan Pembayaran Piutang)
        $chartPayments = $res->json('chart_payments');
        $this->assertNotEmpty($chartPayments);
        $this->assertEquals(30000.0, (float) $chartPayments[0]['total']);

        // 16. Distribusi piutang per Sales
        $salesReceivables = $res->json('sales_receivables');
        $this->assertNotEmpty($salesReceivables);
        $budiGroup = collect($salesReceivables)->firstWhere('name', 'Sales Budi');
        $this->assertEquals(70000.0, (float) $budiGroup['total']);
    }

    /**
     * TEST 17: Toko tanpa Sales tetap dapat muncul jika memiliki piutang
     */
    public function test_17_toko_tanpa_sales_muncul_di_distribusi_piutang(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeUnassigned->id,
            'transaction_amount' => 85000,
        ], $this->admin);

        $res = $this->actingAs($this->admin)->getJson(route('admin.receivables.index'));
        $res->assertOk();

        $salesReceivables = $res->json('sales_receivables');
        $unassignedGroup = collect($salesReceivables)->firstWhere('name', 'Belum Ada Sales');
        $this->assertNotNull($unassignedGroup);
        $this->assertEquals(85000.0, (float) $unassignedGroup['total']);
    }

    /**
     * TEST 18 & 19: Transaksi terbaru dan Pembayaran terbaru benar
     */
    public function test_18_dan_19_transaksi_dan_pembayaran_terbaru(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
            'paid_amount' => 0,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $pay = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 45000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pembayaran piutang',
        ], $this->sales1);

        $res = $this->actingAs($this->admin)->getJson(route('admin.receivables.index'));
        $res->assertOk();

        $recentTrx = $res->json('recent_transactions');
        $this->assertNotEmpty($recentTrx);
        $this->assertEquals($trx->transaction_code, $recentTrx[0]['transaction_code']);

        $recentPay = $res->json('recent_payments');
        $this->assertNotEmpty($recentPay);
        $this->assertEquals(45000.0, (float) $recentPay[0]['amount']);
    }

    /**
     * TEST 20 & 21: Drill down ke detail toko dan detail transaksi
     */
    public function test_20_dan_21_drill_down_ke_detail_toko_dan_transaksi(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        // Drill down to Store
        $resStore = $this->actingAs($this->admin)->get(route('admin.receivables.show', $this->storeA->id));
        $resStore->assertOk();
        $resStore->assertSee($this->storeA->name);

        // Drill down to Transaction
        $resTrx = $this->actingAs($this->admin)->get(route('admin.receivables.transactions.show', $trx->id));
        $resTrx->assertOk();
        $resTrx->assertSee($trx->transaction_code);
    }

    /**
     * TEST 22: Admin dan Super Admin menghasilkan data konsisten
     */
    public function test_22_admin_dan_super_admin_konsisten(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        $resAdmin = $this->actingAs($this->admin)->getJson(route('admin.receivables.index'));
        $resSuperAdmin = $this->actingAs($this->superAdmin)->getJson(route('admin.receivables.index'));

        $this->assertEquals($resAdmin->json('summary'), $resSuperAdmin->json('summary'));
    }

    /**
     * TEST 23 & 24: Tidak ada duplicate ledger dan tidak ada saldo manual
     */
    public function test_23_dan_24_single_ledger_dan_kebenaran_data(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
            'paid_amount' => 30000,
            'payment_method' => 'tunai',
        ], $this->admin);

        $dbBalance = StoreReceivableService::balanceForStore($this->storeA->id);
        $res = $this->actingAs($this->admin)->getJson(route('admin.receivables.index'));

        $this->assertEquals((float) $dbBalance, (float) $res->json('summary.total_receivable'));
        $this->assertEquals(1, StoreTransaction::count());
        $this->assertEquals(1, StoreTransactionPayment::count());
    }

    /**
     * TEST 25: Card Clicks menuju Halaman Detail Khusus Summary (Bukan modal/bukan tabel bawah).
     */
    public function test_25_card_clicks_and_summary_routes(): void
    {
        $t1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $t2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 50000,
            'paid_amount' => 50000,
            'payment_method' => 'transfer',
        ], $this->sales1);

        // 1. Total summary view
        $resTotal = $this->actingAs($this->admin)->get(route('admin.receivables.summary.total'));
        $resTotal->assertOk();
        $resTotal->assertSee('Detail Total Piutang');

        // 2. Indebted stores summary view
        $resStores = $this->actingAs($this->admin)->get(route('admin.receivables.summary.indebted-stores'));
        $resStores->assertOk();
        $resStores->assertSee('Daftar Toko Berpiutang');

        // 3. Open transactions view
        $resOpen = $this->actingAs($this->admin)->get(route('admin.receivables.summary.open-transactions'));
        $resOpen->assertOk();
        $resOpen->assertSee('Daftar Transaksi Terbuka');
        $resOpen->assertSee($t1->transaction_code);

        // 4. Paid transactions view
        $resPaid = $this->actingAs($this->admin)->get(route('admin.receivables.summary.paid-transactions'));
        $resPaid->assertOk();
        $resPaid->assertSee('Daftar Transaksi Lunas');
        $resPaid->assertSee($t2->transaction_code);

        // 5. Debt payments view
        $resDebt = $this->actingAs($this->admin)->get(route('admin.receivables.summary.payments'));
        $resDebt->assertOk();
        $resDebt->assertSee('Riwayat Pembayaran Piutang');

        // 6. Today payments view
        $resToday = $this->actingAs($this->admin)->get(route('admin.receivables.summary.today-payments'));
        $resToday->assertOk();
        $resToday->assertSee('Pembayaran Piutang Hari Ini');

        // 7. New transactions view
        $resNew = $this->actingAs($this->admin)->get(route('admin.receivables.summary.new-transactions'));
        $resNew->assertOk();
        $resNew->assertSee('Transaksi Baru Bulan Ini');

        // 8. New transaction payments view
        $resNewPay = $this->actingAs($this->admin)->get(route('admin.receivables.summary.new-transaction-payments'));
        $resNewPay->assertOk();
        $resNewPay->assertSee('Uang Masuk Transaksi Baru');

        // 9. Trends view (Riwayat Pembayaran Piutang)
        $resTrends = $this->actingAs($this->admin)->get(route('admin.receivables.summary.trends'));
        $resTrends->assertOk();
        $resTrends->assertSee('Riwayat Pembayaran Piutang');

        // 10. By Sales view
        $resSales = $this->actingAs($this->admin)->get(route('admin.receivables.summary.by-sales'));
        $resSales->assertOk();
        $resSales->assertSee('Piutang Berdasarkan Sales');
    }
}
