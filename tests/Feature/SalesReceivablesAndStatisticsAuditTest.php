<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReceivablesAndStatisticsAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $salesA;
    private User $salesB;
    private User $driver;
    private User $admin;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeOtherSales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->salesA = User::factory()->create(['name' => 'Sales Alpha', 'status' => 'active']);
        $this->salesA->assignRole('sales');

        $this->salesB = User::factory()->create(['name' => 'Sales Beta', 'status' => 'active']);
        $this->salesB->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver One', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->admin = User::factory()->create(['name' => 'Admin User', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->storeA = Store::create([
            'name' => 'Toko Lengkong berkah',
            'code' => 'LB-001',
            'address' => 'Jl. Lengkong 1',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->salesA->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'PUSAKA TANI',
            'code' => 'PT-002',
            'address' => 'Jl. Pusaka 2',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->salesA->id,
        ]);

        $this->storeC = Store::create([
            'name' => 'RIZKY MANDIRI',
            'code' => 'RM-003',
            'address' => 'Jl. Rizky 3',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->salesA->id,
        ]);

        $this->storeOtherSales = Store::create([
            'name' => 'Toko Beta Jaya',
            'code' => 'BJ-999',
            'address' => 'Jl. Beta 99',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->salesB->id,
        ]);
    }

    /**
     * TEST 1: Transaksi hari ini masuk Transaksi Hari Ini.
     * TEST 2: Transaksi kemarin tidak masuk Transaksi Hari Ini.
     * TEST 3: Transaksi kemarin tetap tersimpan dan tetap dapat dilihat.
     */
    public function test_transaksi_hari_ini_dan_kemarin_isolation_and_persistence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Transaksi kemarin (2026-09-01) di Toko A: Rp 1.500.000
        $yesterdayTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1500000,
            'transaction_date' => '2026-09-01',
            'description' => 'Faktur Kemarin',
        ], $this->salesA);

        // Transaksi hari ini (2026-09-02) di Toko B: Rp 2.500.000
        $todayTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 2500000,
            'transaction_date' => '2026-09-02',
            'description' => 'Faktur Hari Ini',
        ], $this->salesA);

        $res = $this->actingAs($this->salesA)->get(route('dashboard'));
        $res->assertOk();
        // Transaksi Hari Ini harus Rp 2.500.000
        $res->assertSee('Rp 2.500.000');
        // Total Transaksi Bulan Ini menggabungkan kemarin (1.5jt) + hari ini (2.5jt) = Rp 4.000.000
        $res->assertSee('Total Transaksi Bulan Ini');
        $res->assertSee('Rp 4.000.000');

        // Test 3: Transaksi kemarin tetap tersimpan di database dan tampil di riwayat faktur toko
        $this->assertDatabaseHas('store_transactions', ['id' => $yesterdayTrx->id]);
        $storeRes = $this->actingAs($this->salesA)->get(route('sales.stores.show', $this->storeA->id));
        $storeRes->assertOk();
        $storeRes->assertSee('Faktur Kemarin');
        $storeRes->assertSee('Rp 1.500.000');
    }

    /**
     * TEST 4: Pembayaran hari ini masuk Pembayaran Piutang Hari Ini.
     * TEST 5: Pembayaran kemarin tidak masuk Pembayaran Piutang Hari Ini.
     * TEST 6: Pembayaran kemarin tetap tersimpan.
     */
    public function test_pembayaran_hari_ini_dan_kemarin_isolation_and_persistence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 3000000,
            'transaction_date' => '2026-08-25',
            'description' => 'Trx Lama',
        ], $this->salesA);

        // Pembayaran kemarin (2026-09-01): Rp 500.000
        $payYesterday = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 500000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'tunai',
        ], $this->salesA);

        // Pembayaran hari ini (2026-09-02): Rp 1.000.000
        $payToday = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 1000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'transfer',
        ], $this->salesA);

        $res = $this->actingAs($this->salesA)->get(route('dashboard'));
        $res->assertOk();
        // Pembayaran Piutang Hari Ini = Rp 1.000.000
        $res->assertSee('Pembayaran Piutang Hari Ini');
        $res->assertSee('Rp 1.000.000');

        // Test 6: Pembayaran kemarin tetap tersimpan
        $this->assertDatabaseHas('store_transaction_payments', ['id' => $payYesterday->id]);
        $this->assertDatabaseHas('store_transaction_payments', ['id' => $payToday->id]);
    }

    /**
     * TEST 7: Piutang Saat Ini tidak reset ketika hari berganti.
     * TEST 8: Piutang Saat Ini berkurang ketika pembayaran benar-benar terjadi.
     */
    public function test_piutang_saat_ini_no_reset_on_day_change_and_decreases_on_payment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
            'description' => 'Faktur Piutang',
        ], $this->salesA);

        // Pada 2 September: Piutang Saat Ini = Rp 5.000.000
        $resDay2 = $this->actingAs($this->salesA)->get(route('dashboard'));
        $resDay2->assertSee('Rp 5.000.000');

        // Berganti ke 3 September tanpa transaksi / pembayaran baru
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00'));
        $resDay3 = $this->actingAs($this->salesA)->get(route('dashboard'));
        // Piutang Saat Ini TETAP Rp 5.000.000
        $resDay3->assertSee('Rp 5.000.000');
        // Transaksi Hari Ini = Rp 0
        $resDay3->assertSee('Rp 0');

        // Test 8: Lakukan pembayaran Rp 2.000.000 pada 3 September
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 2000000,
            'payment_date' => '2026-09-03',
            'payment_method' => 'tunai',
        ], $this->salesA);

        $resAfterPayment = $this->actingAs($this->salesA)->get(route('dashboard'));
        // Piutang Saat Ini sekarang menjadi Rp 3.000.000
        $resAfterPayment->assertSee('Rp 3.000.000');
        $resAfterPayment->assertSee('Rp 2.000.000'); // Pembayaran Hari Ini
    }

    /**
     * TEST 15: Total Piutang di Toko & Piutang sama dengan Piutang Saat Ini untuk Sales yang sama.
     * TEST 16: Transaksi Terbuka menghitung transaksi berdasarkan remaining balance > 0.
     * TEST 17: Transaksi dengan remaining = 0 tidak dihitung sebagai transaksi terbuka.
     * TEST 18: Partial payment tetap dihitung sebagai transaksi terbuka.
     * TEST 19: Satu transaksi dengan multiple payment tidak double count.
     */
    public function test_sales_stores_receivable_and_open_transactions_exact_calculation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Toko A (Lengkong berkah): 2 Transaksi Terbuka
        // TRX A1: Rp 1.000.000, bayar Rp 400.000, bayar lagi Rp 100.000 -> sisa Rp 500.000 (Partial, multiple payments)
        $trxA1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-01',
            'description' => 'Trx A1',
        ], $this->salesA);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxA1->id,
            'amount' => 400000,
            'payment_date' => '2026-09-01',
        ], $this->salesA);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxA1->id,
            'amount' => 100000,
            'payment_date' => '2026-09-02',
        ], $this->salesA);

        // TRX A2: Rp 500.000, bayar 0 -> sisa Rp 500.000 (Belum lunas)
        $trxA2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 500000,
            'transaction_date' => '2026-09-02',
            'description' => 'Trx A2',
        ], $this->salesA);

        // TRX A3: Rp 700.000, bayar Rp 700.000 -> sisa Rp 0 (LUNAS -> TIDAK DIHITUNG)
        $trxA3 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 700000,
            'paid_amount' => 700000,
            'transaction_date' => '2026-09-02',
            'description' => 'Trx A3 Lunas',
        ], $this->salesA);

        // Toko B (PUSAKA TANI): 3 Transaksi Terbuka
        // TRX B1: Rp 2.000.000, sisa Rp 2.000.000
        $trxB1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 2000000,
            'transaction_date' => '2026-08-20',
        ], $this->salesA);
        // TRX B2: Rp 1.500.000, sisa Rp 1.500.000
        $trxB2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 1500000,
            'transaction_date' => '2026-08-25',
        ], $this->salesA);
        // TRX B3: Rp 1.000.000, bayar Rp 300.000 -> sisa Rp 700.000
        $trxB3 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 300000,
            'transaction_date' => '2026-09-01',
        ], $this->salesA);

        // Toko C (RIZKY MANDIRI): 1 Transaksi Terbuka
        // TRX C1: Rp 3.000.000, sisa Rp 3.000.000
        $trxC1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_amount' => 3000000,
            'transaction_date' => '2026-08-10',
        ], $this->salesA);

        // Total Piutang Toko A = 500k + 500k = 1.000.000 (2 open trx)
        // Total Piutang Toko B = 2jt + 1.5jt + 700k = 4.200.000 (3 open trx)
        // Total Piutang Toko C = 3.000.000 (1 open trx)
        // TOTAL PIUTANG SEMUA TOKO = 1.000.000 + 4.200.000 + 3.000.000 = Rp 8.200.000
        // TOTAL TRANSAKSI TERBUKA = 2 + 3 + 1 = 6 Trx

        // 1. Cek di /sales/stores
        $storesRes = $this->actingAs($this->salesA)->get(route('sales.stores.index'));
        $storesRes->assertOk();
        $storesRes->assertSee('Rp 8.200.000'); // Total Piutang
        $storesRes->assertSee('6'); // Transaksi Terbuka = 6 Trx
        $storesRes->assertSee('2 Trx'); // Open trx Toko A
        $storesRes->assertSee('3 Trx'); // Open trx Toko B
        $storesRes->assertSee('1 Trx'); // Open trx Toko C

        // 2. Cek di /dashboard (Harus sama persis: Rp 8.200.000)
        $dashRes = $this->actingAs($this->salesA)->get(route('dashboard'));
        $dashRes->assertOk();
        $dashRes->assertSee('Rp 8.200.000');
    }

    /**
     * TEST 20: Sales hanya melihat transaksi/piutang toko miliknya sendiri.
     * TEST 21: Sales tidak melihat piutang Sales lain.
     */
    public function test_sales_isolation_cannot_see_other_sales_receivables(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Toko Sales A: Rp 2.000.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 2000000,
            'transaction_date' => '2026-09-02',
        ], $this->salesA);

        // Toko Sales B: Rp 50.000.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeOtherSales->id,
            'transaction_amount' => 50000000,
            'transaction_date' => '2026-09-02',
        ], $this->salesB);

        // Sales A melihat dashboard: hanya Rp 2.000.000
        $dashA = $this->actingAs($this->salesA)->get(route('dashboard'));
        $dashA->assertSee('Rp 2.000.000');
        $dashA->assertDontSee('Rp 50.000.000');
        $dashA->assertDontSee('Rp 52.000.000');

        // Sales A melihat stores index: hanya tokonya, tidak ada Toko Beta Jaya
        $storesA = $this->actingAs($this->salesA)->get(route('sales.stores.index'));
        $storesA->assertSee($this->storeA->name);
        $storesA->assertDontSee($this->storeOtherSales->name);

        // Sales A mencoba akses detail toko Sales B -> 403 / 404
        $detailB = $this->actingAs($this->salesA)->get(route('sales.stores.show', $this->storeOtherSales->id));
        $this->assertTrue(in_array($detailB->status(), [403, 404]));
    }

    /**
     * TEST 22: Pergantian tahun tidak mencampurkan transaksi bulan/tahun yang sama (e.g. Sep 2025 vs Sep 2026).
     */
    public function test_year_boundary_does_not_mix_months_across_years(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Transaksi September 2025: Rp 10.000.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 10000000,
            'transaction_date' => '2025-09-02',
            'description' => 'Trx Sep 2025',
        ], $this->salesA);

        // Transaksi September 2026: Rp 1.000.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-02',
            'description' => 'Trx Sep 2026',
        ], $this->salesA);

        $res = $this->actingAs($this->salesA)->get(route('visit.history'));
        $res->assertOk();
        // Total Transaksi Bulan Ini untuk September 2026 harus Rp 1.000.000, bukan Rp 11.000.000
        $res->assertSee('Rp 1.000.000');
        $res->assertDontSee('Rp 11.000.000');
    }

    /**
     * TEST 23: Tidak ada proses yang menghapus transaksi/payment lama ketika periode berganti.
     */
    public function test_no_data_is_deleted_when_period_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-02',
        ], $this->salesA);

        $pay = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 300000,
            'payment_date' => '2026-09-02',
        ], $this->salesA);

        // Pindah ke Oktober 2026
        Carbon::setTestNow(Carbon::parse('2026-10-01 10:00:00'));

        $res = $this->actingAs($this->salesA)->get(route('dashboard'));
        $res->assertOk();

        // Data transaksi dan payment September tetap utuh di database
        $this->assertDatabaseHas('store_transactions', ['id' => $trx->id, 'transaction_amount' => '1000000.00']);
        $this->assertDatabaseHas('store_transaction_payments', ['id' => $pay->id, 'amount' => '300000.00']);
    }

    /**
     * TEST 24: Driver tidak terpengaruh oleh perubahan ini.
     */
    public function test_driver_workflow_and_views_unaffected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $res = $this->actingAs($this->driver)->get(route('dashboard'));
        $res->assertOk();
        // Driver dashboard tidak menampilkan ringkasan piutang sales
        $res->assertDontSee('Piutang Saat Ini');
        $res->assertDontSee('Pembayaran Piutang Hari Ini');

        // Driver tidak dapat membuka sales stores menu (redirect ke dashboard oleh CheckRole middleware)
        $storesRes = $this->actingAs($this->driver)->get(route('sales.stores.index'));
        $storesRes->assertRedirect(route('dashboard'));
    }

    /**
     * TEST 25: Transaksi dan Pembayaran yang dicatat Admin untuk toko Sales A tetap masuk ke statistik Sales A.
     */
    public function test_admin_created_transaction_and_payment_for_sales_store_reflects_in_sales_statistics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Admin membuat transaksi baru untuk Toko Lengkong berkah (toko milik Sales A)
        $adminTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 4000000,
            'transaction_date' => '2026-09-02',
            'description' => 'Faktur Dibuat Admin',
        ], $this->admin);

        // Admin mencatat pembayaran piutang Rp 1.500.000 untuk transaksi tersebut
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $adminTrx->id,
            'amount' => 1500000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'transfer',
            'source' => 'admin',
        ], $this->admin);

        // Sales A melihat Dashboard:
        // Transaksi Hari Ini = Rp 4.000.000 (meskipun created_by = admin)
        // Pembayaran Piutang Hari Ini = Rp 1.500.000 (meskipun recorded_by = admin)
        // Piutang Saat Ini = Rp 2.500.000
        $dash = $this->actingAs($this->salesA)->get(route('dashboard'));
        $dash->assertOk();
        $dash->assertSee('Rp 4.000.000');
        $dash->assertSee('Rp 1.500.000');
        $dash->assertSee('Rp 2.500.000');

        // Sales A melihat Riwayat Kunjungan /visit/history:
        // Total Transaksi Bulan Ini = Rp 4.000.000
        // Pembayaran Piutang Bulan Ini = Rp 1.500.000
        $hist = $this->actingAs($this->salesA)->get(route('visit.history'));
        $hist->assertOk();
        $hist->assertSee('Rp 4.000.000');
        $hist->assertSee('Rp 1.500.000');

        // Sales B tidak melihat transaksi dan pembayaran tersebut (isolasi toko)
        $dashB = $this->actingAs($this->salesB)->get(route('dashboard'));
        $dashB->assertOk();
        $dashB->assertSee('Rp 0');
    }
}
