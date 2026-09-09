<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReceivablesSummaryPagesTest extends TestCase
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

        $this->admin = User::factory()->create(['name' => 'Admin Test', 'email' => 'admin@test.com']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Test', 'email' => 'superadmin@test.com']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales1 = User::factory()->create(['name' => 'Sales Budi', 'email' => 'budi@test.com']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Citra', 'email' => 'citra@test.com']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Doni', 'email' => 'doni@test.com']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko Sinar Abadi',
            'code' => 'TSA-01',
            'address' => 'Jl. Merdeka No. 1',
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko Berkah Jaya',
            'code' => 'TBJ-02',
            'address' => 'Jl. Sudirman No. 2',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeC = Store::create([
            'name' => 'Toko Cahaya Terang',
            'code' => 'TCT-03',
            'address' => 'Jl. Asia Afrika No. 3',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);

        $this->storeUnassigned = Store::create([
            'name' => 'Toko Mandiri Non-Sales',
            'code' => 'TMN-04',
            'address' => 'Jl. Pemuda No. 4',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'status' => 'active',
            'sales_penanggung_jawab_id' => null,
        ]);
    }

    /**
     * Helper list of all 10 summary route names
     */
    private function getSummaryRoutes(): array
    {
        return [
            'admin.receivables.summary.total',
            'admin.receivables.summary.indebted-stores',
            'admin.receivables.summary.open-transactions',
            'admin.receivables.summary.payments',
            'admin.receivables.summary.today-payments',
            'admin.receivables.summary.paid-transactions',
            'admin.receivables.summary.new-transactions',
            'admin.receivables.summary.new-transaction-payments',
            'admin.receivables.summary.trends',
            'admin.receivables.summary.by-sales',
        ];
    }

    /**
     * TEST 1: Akses Admin & Super Admin ke seluruh 10 route summary detail (HTTP 200 OK)
     */
    public function test_1_admin_and_super_admin_can_access_all_10_summary_routes(): void
    {
        foreach ($this->getSummaryRoutes() as $routeName) {
            $adminRes = $this->actingAs($this->admin)->get(route($routeName));
            $adminRes->assertOk();

            $superAdminRes = $this->actingAs($this->superAdmin)->get(route($routeName));
            $superAdminRes->assertOk();
        }
    }

    /**
     * TEST 2: Blokir akses untuk Sales dan Driver ke seluruh 10 route tersebut (harus redirect 302).
     */
    public function test_2_sales_and_driver_are_blocked_from_all_10_summary_routes(): void
    {
        foreach ($this->getSummaryRoutes() as $routeName) {
            $salesRes = $this->actingAs($this->sales1)->get(route($routeName));
            $salesRes->assertStatus(302);

            $driverRes = $this->actingAs($this->driver)->get(route($routeName));
            $driverRes->assertStatus(302);
        }
    }

    /**
     * TEST 3: Konsistensi data 100% antara KPI card / angka di index dan data di halaman summary detail
     */
    public function test_3_data_consistency_between_index_kpi_cards_and_summary_detail_pages(): void
    {
        // Setup initial transactions & payments
        // 1. Toko A: Saldo Awal (opening balance) Rp 1.000.000, bayar Rp 400.000 hari ini
        StoreReceivableService::addOpeningBalance([
            'store_id' => $this->storeA->id,
            'amount' => 1000000,
            'transaction_date' => now()->toDateString(),
        ], $this->admin);

        $opTrx = StoreTransaction::where('store_id', $this->storeA->id)->where('reference_type', 'opening_balance')->first();
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $opTrx->id,
            'amount' => 400000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'notes' => 'Cicilan saldo awal',
        ], $this->sales1);

        // 2. Toko B: Transaksi baru bulan ini Rp 500.000, bayar awal Rp 200.000 (hari ini) -> sisa 300.000 (open)
        $tNew1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 500000,
            'paid_amount' => 200000,
            'payment_method' => 'transfer',
            'transaction_date' => now()->toDateString(),
        ], $this->sales1);

        // 3. Toko C: Transaksi baru bulan ini Rp 300.000, bayar lunas Rp 300.000 (hari ini) -> sisa 0 (paid)
        $tNew2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_amount' => 300000,
            'paid_amount' => 300000,
            'payment_method' => 'qris',
            'transaction_date' => now()->toDateString(),
        ], $this->sales2);

        // Toko Unassigned: Tidak ada transaksi (saldo 0)

        // Request index page (JSON / View data)
        $indexRes = $this->actingAs($this->admin)->get(route('admin.receivables.index'));
        $indexRes->assertOk();

        // Ambil data view yang dikirim ke index
        $indexData = $indexRes->original->getData();
        $indexTotalReceivable = $indexData['totalReceivable'];
        $indexIndebtedStores = $indexData['storesWithReceivable'];
        $indexOpenTrx = $indexData['totalOpenTransactions'];
        $indexTotalPayments = $indexData['totalPayments'];
        $indexTodayPayments = $indexData['todayPayments'];
        $indexPaidTrx = $indexData['totalPaidTransactions'];
        $indexNewTrxAmount = $indexData['newTransactionsThisMonth'];
        $indexNewCashIn = $indexData['newTransactionsCashInThisMonth'];

        // 3.1 Total Piutang: card = summary total
        $resTotal = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.total'));
        $resTotal->assertOk();
        $this->assertEquals($indexTotalReceivable, $resTotal->json('total_receivable'));
        $this->assertEquals(900000, $resTotal->json('total_receivable')); // (1.000.000 - 400.000) + (500.000 - 200.000) = 600.000 + 300.000 = 900.000

        // 3.2 Toko Berpiutang: card count = count toko di indebted-stores
        $resIndebted = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.indebted-stores'));
        $resIndebted->assertOk();
        $this->assertEquals($indexIndebtedStores, $resIndebted->json('total_stores'));
        $this->assertEquals(2, $resIndebted->json('total_stores')); // Toko A & Toko B

        // 3.3 Transaksi Terbuka: card count = count di open-transactions
        $resOpen = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.open-transactions'));
        $resOpen->assertOk();
        $this->assertEquals($indexOpenTrx, $resOpen->json('total_count'));
        $this->assertEquals(2, $resOpen->json('total_count')); // opTrx & tNew1

        // 3.4 Transaksi Lunas: card count = count di paid-transactions
        $resPaid = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.paid-transactions'));
        $resPaid->assertOk();
        $this->assertEquals($indexPaidTrx, $resPaid->json('total_count'));
        $this->assertEquals(1, $resPaid->json('total_count')); // tNew2

        // 3.5 Total Pembayaran: card = total di payments summary
        $resPayments = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.payments'));
        $resPayments->assertOk();
        $this->assertEquals($indexTotalPayments, $resPayments->json('total_amount'));
        $this->assertEquals(400000, $resPayments->json('total_amount')); // Pembayaran piutang lama (400k cicilan Toko A)

        // 3.6 Pembayaran Hari Ini: card = total di today-payments summary
        $resToday = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.today-payments'));
        $resToday->assertOk();
        $this->assertEquals($indexTodayPayments, $resToday->json('total_amount'));
        $this->assertEquals(400000, $resToday->json('total_amount'));

        // 3.7 Transaksi Baru Bulan Ini: card = total di new-transactions
        $resNew = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.new-transactions'));
        $resNew->assertOk();
        $this->assertEquals($indexNewTrxAmount, $resNew->json('total_transaction_amount'));
        $this->assertEquals(800000, $resNew->json('total_transaction_amount')); // tNew1 (500k) + tNew2 (300k)

        // 3.8 Uang Masuk Transaksi Baru: card = total di new-transaction-payments
        $resNewPay = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.new-transaction-payments'));
        $resNewPay->assertOk();
        $this->assertEquals($indexNewCashIn, $resNewPay->json('total_amount'));
        $this->assertEquals(500000, $resNewPay->json('total_amount')); // 200k + 300k

        // 3.9 Tren Pembayaran Total: card / chart total = total di trends
        $resTrends = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.trends'));
        $resTrends->assertOk();
        $this->assertEquals($indexTotalPayments, $resTrends->json('total_amount'));
        $this->assertEquals(400000, $resTrends->json('total_amount'));

        // 3.10 Piutang By Sales Total: total di by-sales = total piutang
        $resBySales = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.by-sales'));
        $resBySales->assertOk();
        $this->assertEquals($indexTotalReceivable, $resBySales->json('total_receivable'));
        $this->assertEquals(900000, $resBySales->json('total_receivable'));
    }

    /**
     * TEST 3.b: Empty State Today Payments saat belum ada pembayaran hari ini (0 rupiah & 0 data)
     */
    public function test_3b_today_payments_empty_state(): void
    {
        // Hanya ada transaksi tanpa pembayaran
        $this->storeA->update(['created_at' => now()]);
        StoreReceivableService::addOpeningBalance([
            'store_id' => $this->storeA->id,
            'amount' => 500000,
            'transaction_date' => now()->toDateString(),
        ], $this->admin);

        $resTodayHtml = $this->actingAs($this->admin)->get(route('admin.receivables.summary.today-payments'));
        $resTodayHtml->assertOk();
        $resTodayHtml->assertSee('Belum ada pembayaran piutang hari ini');
        $resTodayHtml->assertSee('Rp 0');

        $resTodayJson = $this->actingAs($this->admin)->getJson(route('admin.receivables.summary.today-payments'));
        $resTodayJson->assertOk();
        $this->assertEquals(0, $resTodayJson->json('total_amount'));
        $this->assertCount(0, $resTodayJson->json('payments.data'));
    }

    /**
     * TEST 4: Pagination di halaman detail bekerja normal (page=2 tetap berada di halaman detail summary dan tidak me-redirect)
     */
    public function test_4_pagination_on_summary_detail_pages_works_properly(): void
    {
        // Buat 20 toko berpiutang dan 20 transaksi untuk menguji pagination (perPage = 15)
        for ($i = 1; $i <= 20; $i++) {
            $st = Store::create([
                'name' => "Toko Bulk {$i}",
                'code' => "TBK-{$i}",
                'address' => "Jl. Bulk {$i}",
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'status' => 'active',
                'sales_penanggung_jawab_id' => $this->sales1->id,
            ]);

            $trx = StoreReceivableService::createTransaction([
                'store_id' => $st->id,
                'transaction_amount' => 100000 + ($i * 1000),
                'paid_amount' => 0,
                'payment_method' => 'tunai',
                'transaction_date' => now()->toDateString(),
            ], $this->sales1);

            // Record a payment for each
            StoreReceivableService::recordTransactionPayment([
                'store_transaction_id' => $trx->id,
                'amount' => 10000,
                'payment_method' => 'tunai',
                'payment_date' => now()->toDateString(),
                'notes' => "Cicilan {$i}",
            ], $this->sales1);
        }

        $summaryRoutesToPaginate = [
            'admin.receivables.summary.total',
            'admin.receivables.summary.indebted-stores',
            'admin.receivables.summary.open-transactions',
            'admin.receivables.summary.payments',
            'admin.receivables.summary.today-payments',
            'admin.receivables.summary.new-transactions',
            'admin.receivables.summary.new-transaction-payments',
            'admin.receivables.summary.trends',
            'admin.receivables.summary.by-sales',
        ];

        foreach ($summaryRoutesToPaginate as $routeName) {
            $resPage2 = $this->actingAs($this->admin)->get(route($routeName, ['page' => 2]));
            $resPage2->assertOk(); // Tidak 302 redirect
        }
    }

    /**
     * TEST 5: Filter di halaman detail (sales_id, search, payment_method, dll) bekerja dengan benar.
     */
    public function test_5_filters_on_summary_detail_pages_work_properly(): void
    {
        // Setup Transaksi Toko A (Sales 1) dan Toko C (Sales 2)
        $t1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-FILTER-A',
            'transaction_amount' => 1000000,
            'paid_amount' => 200000,
            'payment_method' => 'transfer',
            'transaction_date' => now()->toDateString(),
        ], $this->sales1);

        $t2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_code' => 'TRX-FILTER-C',
            'transaction_amount' => 500000,
            'paid_amount' => 100000,
            'payment_method' => 'tunai',
            'transaction_date' => now()->toDateString(),
        ], $this->sales2);

        // 5.1 Filter Sales ID di open-transactions
        $resSales1 = $this->actingAs($this->admin)->get(route('admin.receivables.summary.open-transactions', [
            'sales_id' => $this->sales1->id,
        ]));
        $resSales1->assertOk();
        $resSales1->assertSee($t1->transaction_code);
        $resSales1->assertDontSee($t2->transaction_code);

        // 5.2 Search di open-transactions
        $resSearch = $this->actingAs($this->admin)->get(route('admin.receivables.summary.open-transactions', [
            'search' => 'TRX-FILTER-C',
        ]));
        $resSearch->assertOk();
        $resSearch->assertSee($t2->transaction_code);
        $resSearch->assertDontSee($t1->transaction_code);

        // 5.3 Filter Sales ID di summary indebted-stores
        $resIndebtedSales2 = $this->actingAs($this->admin)->get(route('admin.receivables.summary.indebted-stores', [
            'sales_id' => $this->sales2->id,
        ]));
        $resIndebtedSales2->assertOk();
        $resIndebtedSales2->assertSee('Toko Cahaya Terang');
        $resIndebtedSales2->assertDontSee('Toko Sinar Abadi');

        // 5.4 Search Toko di summary total
        $resTotalSearch = $this->actingAs($this->admin)->get(route('admin.receivables.summary.total', [
            'search' => 'Sinar Abadi',
        ]));
        $resTotalSearch->assertOk();
        $resTotalSearch->assertSee('Toko Sinar Abadi');
        $resTotalSearch->assertDontSee('Toko Cahaya Terang');

        // 5.5 Filter Payment Method di new-transaction-payments
        $resPayMethodTransfer = $this->actingAs($this->admin)->get(route('admin.receivables.summary.new-transaction-payments', [
            'payment_method' => 'transfer',
        ]));
        $resPayMethodTransfer->assertOk();
        $resPayMethodTransfer->assertSee($t1->transaction_code);
        $resPayMethodTransfer->assertDontSee($t2->transaction_code);
    }

    /**
     * TEST 6: Tidak ada data Driver yang muncul di transaksi atau summary.
     */
    public function test_6_no_driver_data_appears_in_summary_or_sales_lists(): void
    {
        // Pastikan driver terdaftar di sistem dengan role driver
        $this->assertTrue($this->driver->hasRole('driver'));
        $this->assertFalse($this->driver->hasRole('sales'));

        // Cek halaman By Sales Summary
        $resBySales = $this->actingAs($this->admin)->get(route('admin.receivables.summary.by-sales'));
        $resBySales->assertOk();
        $resBySales->assertDontSee($this->driver->name);
        $resBySales->assertSee($this->sales1->name);
        $resBySales->assertSee($this->sales2->name);

        // Cek dropdown filter sales di halaman Open Transactions
        $resOpen = $this->actingAs($this->admin)->get(route('admin.receivables.summary.open-transactions'));
        $resOpen->assertOk();
        $resOpen->assertDontSee($this->driver->name);
        $resOpen->assertSee($this->sales1->name);
        $resOpen->assertSee($this->sales2->name);

        // Cek dropdown filter sales di halaman Total Summary
        $resTotal = $this->actingAs($this->admin)->get(route('admin.receivables.summary.total'));
        $resTotal->assertOk();
        $resTotal->assertDontSee($this->driver->name);
        $resTotal->assertSee($this->sales1->name);
        $resTotal->assertSee($this->sales2->name);

        // Cek dropdown filter sales di halaman Payments Summary
        $resPayments = $this->actingAs($this->admin)->get(route('admin.receivables.summary.payments'));
        $resPayments->assertOk();
        $resPayments->assertDontSee($this->driver->name);
        $resPayments->assertSee($this->sales1->name);
        $resPayments->assertSee($this->sales2->name);
    }
}
