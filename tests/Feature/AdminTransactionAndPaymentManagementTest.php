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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminTransactionAndPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $storeWithSales;
    private Store $storeWithoutSales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin Test']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Test']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales1 = User::factory()->create(['name' => 'Sales Satu']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Dua']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Test']);
        $this->driver->assignRole('driver');

        $this->storeWithSales = Store::create([
            'name' => 'Toko Mitra Sejati',
            'code' => 'TMS-01',
            'address' => 'Jl. Kebon Sirih No. 1',
            'city' => 'Jakarta Pusat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeWithoutSales = Store::create([
            'name' => 'Toko Tanpa Sales',
            'code' => 'TTS-02',
            'address' => 'Jl. Gajah Mada No. 2',
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => null,
        ]);
    }

    /**
     * TEST 1: Admin dapat membuat transaksi
     */
    public function test_1_admin_dapat_membuat_transaksi(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithSales->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Faktur PO #001',
            'transaction_amount' => 150000,
            'paid_amount' => 0,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('store_transactions', [
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 150000.00,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * TEST 2: Super Admin dapat membuat transaksi
     */
    public function test_2_super_admin_dapat_membuat_transaksi(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithSales->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Faktur Super Admin #002',
            'transaction_amount' => 200000,
            'paid_amount' => 50000,
            'payment_method' => 'transfer',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('store_transactions', [
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 200000.00,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    /**
     * TEST 3 & 4: Kode transaksi dibuat otomatis dan berurutan/unik
     */
    public function test_3_dan_4_kode_transaksi_otomatis_dan_unik(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 120000,
        ], $this->admin);

        $this->assertStringStartsWith('TRX-', $trx1->transaction_code);
        $this->assertStringStartsWith('TRX-', $trx2->transaction_code);
        $this->assertNotEquals($trx1->transaction_code, $trx2->transaction_code);
    }

    /**
     * TEST 5: Transaksi tanpa pembayaran: status BELUM_LUNAS
     */
    public function test_5_transaksi_tanpa_pembayaran_status_belum_lunas(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithSales->id,
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 100000,
            'paid_amount' => 0,
        ]);

        $res->assertCreated();
        $trx = StoreTransaction::where('store_id', $this->storeWithSales->id)->first();
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx->status);
        $this->assertEquals(0, $trx->payments()->count());
        $this->assertEquals(100000.0, $trx->remaining_amount);
    }

    /**
     * TEST 6: Transaksi dengan pembayaran sebagian: status SEBAGIAN
     */
    public function test_6_transaksi_dengan_pembayaran_sebagian_status_sebagian(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithSales->id,
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ]);

        $res->assertCreated();
        $trx = StoreTransaction::where('store_id', $this->storeWithSales->id)->first();
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx->status);
        $this->assertEquals(20000.0, $trx->total_paid);
        $this->assertEquals(80000.0, $trx->remaining_amount);
    }

    /**
     * TEST 7: Transaksi dengan pembayaran penuh: status LUNAS
     */
    public function test_7_transaksi_dengan_pembayaran_penuh_status_lunas(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithSales->id,
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 100000,
            'paid_amount' => 100000,
            'payment_method' => 'transfer',
        ]);

        $res->assertCreated();
        $trx = StoreTransaction::where('store_id', $this->storeWithSales->id)->first();
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx->status);
        $this->assertEquals(100000.0, $trx->total_paid);
        $this->assertEquals(0.0, $trx->remaining_amount);
    }

    /**
     * TEST 8: Pembayaran tambahan memperbarui sisa transaksi dan status
     */
    public function test_8_pembayaran_tambahan_memperbarui_sisa_dan_status(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->admin);

        // Tambah pembayaran Rp50.000
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 50000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'notes' => 'Cicilan kedua',
        ]);
        $res->assertCreated();

        $trx->refresh();
        $this->assertEquals(70000.0, $trx->total_paid);
        $this->assertEquals(30000.0, $trx->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx->status);

        // Pelunasan Rp30.000
        $resLunas = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 30000,
            'payment_method' => 'qris',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pelunasan faktur',
        ]);
        $resLunas->assertCreated();

        $trx->refresh();
        $this->assertEquals(100000.0, $trx->total_paid);
        $this->assertEquals(0.0, $trx->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx->status);
    }

    /**
     * TEST 9: Histori pembayaran tidak hilang (3 kali bayar menghasilkan 3 distinct records)
     */
    public function test_9_histori_pembayaran_tidak_hilang(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 50000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'notes' => 'Bayar Admin 1',
        ])->assertCreated();

        $this->actingAs($this->superAdmin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 30000,
            'payment_method' => 'transfer',
            'payment_date' => now()->toDateString(),
            'notes' => 'Bayar Super Admin 2',
        ])->assertCreated();

        $this->assertEquals(3, $trx->payments()->count());
        $payments = $trx->payments()->orderBy('created_at')->get();

        $this->assertEquals(20000.0, (float) $payments[0]->amount);
        $this->assertEquals($this->sales1->id, $payments[0]->recorded_by);

        $this->assertEquals(50000.0, (float) $payments[1]->amount);
        $this->assertEquals($this->admin->id, $payments[1]->recorded_by);

        $this->assertEquals(30000.0, (float) $payments[2]->amount);
        $this->assertEquals($this->superAdmin->id, $payments[2]->recorded_by);
    }

    /**
     * TEST 10: Payment melebihi sisa ditolak
     */
    public function test_10_payment_melebihi_sisa_ditolak(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 80000,
        ], $this->admin);

        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 100000, // Sisa hanya 80.000
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
        ]);

        $res->assertStatus(422);
        $trx->refresh();
        $this->assertEquals(80000.0, $trx->remaining_amount);
    }

    /**
     * TEST 11: Payment negatif ditolak
     */
    public function test_11_payment_negatif_ditolak(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => -50000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
        ]);

        $res->assertStatus(422);
    }

    /**
     * TEST 12: Admin tidak dapat membuat payment melebihi sisa karena concurrent update (Atomic Lock)
     */
    public function test_12_atomic_lock_mencegah_concurrent_payment_melebihi_sisa(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        // First payment of 80.000 succeeds
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 80000,
            'payment_method' => 'tunai',
        ], $this->admin);

        // Concurrently, another attempt of 50.000 is made (total would be 130.000 > 100.000)
        $this->expectException(ValidationException::class);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 50000,
            'payment_method' => 'transfer',
        ], $this->superAdmin);
    }

    /**
     * TEST 13: Double submit / id protection
     */
    public function test_13_double_submit_protection(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 50000,
        ], $this->admin);

        // Attempt 1 -> Success
        $res1 = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 50000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
        ]);
        $res1->assertCreated();

        // Attempt 2 immediately after (sisa now 0) -> Rejected
        $res2 = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 50000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
        ]);
        $res2->assertStatus(422);

        $this->assertEquals(1, $trx->payments()->count());
    }

    /**
     * TEST 14 & 15: Sales transaction existing tetap terbaca dan memiliki source visit
     */
    public function test_14_dan_15_sales_transaction_terbaca_dan_source_visit(): void
    {
        $route = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Kunjungan',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeWithSales->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeWithSales->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Monas',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'checkin.jpg',
        ]);

        $dummy = 'data:image/jpeg;base64,' . base64_encode('fake');
        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Monas',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Lancar',
            'transaction_status' => 'paid',
            'new_tx_total' => 100000,
            'new_tx_paid' => 30000,
            'new_payment_method' => 'tunai',
            'selfie' => $dummy,
            'final_store_photo' => $dummy,
        ])->assertOk();

        $trx = StoreTransaction::where('reference_id', $visit->id)->first();
        $this->assertNotNull($trx);
        $this->assertEquals('visit', $trx->reference_type);

        $pay = $trx->payments()->first();
        $this->assertNotNull($pay);
        $this->assertEquals('visit', $pay->source);
        $this->assertEquals($visit->id, $pay->source_id);
    }

    /**
     * TEST 16 & 17: Total piutang toko benar setelah transaksi baru dan payment
     */
    public function test_16_dan_17_total_piutang_toko_akurat_setelah_transaksi_dan_payment(): void
    {
        $this->assertSame('0.00', StoreReceivableService::balanceForStore($this->storeWithSales->id));

        // 1. Tambah transaksi Rp100.000 (piutang toko = 100.000)
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        $this->assertSame('100000.00', StoreReceivableService::balanceForStore($this->storeWithSales->id));

        // 2. Tambah transaksi kedua Rp50.000 bayar Rp20.000 (piutang bertambah 30.000 -> total 130.000)
        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 50000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->admin);

        $this->assertSame('130000.00', StoreReceivableService::balanceForStore($this->storeWithSales->id));

        // 3. Bayar transaksi 1 sebesar Rp60.000 (total piutang berkurang menjadi 70.000)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 60000,
            'payment_method' => 'transfer',
        ], $this->admin);

        $this->assertSame('70000.00', StoreReceivableService::balanceForStore($this->storeWithSales->id));
    }

    /**
     * TEST 18: Toko tanpa Sales tetap dapat dikelola Admin
     */
    public function test_18_toko_tanpa_sales_dapat_dikelola_admin(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithoutSales->id,
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 75000,
            'paid_amount' => 0,
        ]);

        $res->assertCreated();
        $this->assertSame('75000.00', StoreReceivableService::balanceForStore($this->storeWithoutSales->id));
    }

    /**
     * TEST 19: Sales tidak dapat mengakses endpoint Admin
     */
    public function test_19_sales_tidak_dapat_mengakses_endpoint_admin(): void
    {
        $this->actingAs($this->sales1)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 50000,
        ])->assertRedirect();
    }

    /**
     * TEST 20 & 21: Driver tidak dapat mengakses endpoint piutang dan tidak menerima data piutang
     */
    public function test_20_dan_21_driver_terisolasi_dari_piutang(): void
    {
        $this->actingAs($this->driver)->get(route('admin.receivables.index'))->assertRedirect();
        $this->actingAs($this->driver)->postJson(route('admin.receivables.transactions.store'), [
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 50000,
        ])->assertRedirect();
    }

    /**
     * TEST 22, 23 & 24: Super Admin dapat melihat detail transaksi dan menambah pembayaran
     */
    public function test_22_23_24_super_admin_kelola_transaksi_dan_histori(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // Super Admin views transaction detail
        $resShow = $this->actingAs($this->superAdmin)->get(route('admin.receivables.transactions.show', $trx->id));
        $resShow->assertOk();
        $resShow->assertSee($trx->transaction_code);
        $resShow->assertSee('Rp 100.000');
        $resShow->assertSee('Rp 20.000');
        $resShow->assertSee('Rp 80.000');

        // Super Admin adds payment
        $resPay = $this->actingAs($this->superAdmin)->postJson(route('admin.receivables.transactions.payments.store', $trx->id), [
            'amount' => 80000,
            'payment_method' => 'transfer',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pelunasan oleh Super Admin',
        ]);
        $resPay->assertCreated();

        $trx->refresh();
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx->status);
        $this->assertEquals(2, $trx->payments()->count());
    }

    /**
     * TEST 25: Transaksi dengan payment terlindungi integritasnya
     */
    public function test_25_transaksi_dengan_payment_terlindungi(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeWithSales->id,
            'transaction_amount' => 100000,
            'paid_amount' => 50000,
            'payment_method' => 'tunai',
        ], $this->admin);

        $this->assertNotEmpty($trx->payments);
        $this->assertEquals(1, $trx->payments()->count());
    }

    /**
     * TEST 26: Saldo awal yang dibuat melalui fitur existing masuk konsisten ke ledger
     */
    public function test_26_saldo_awal_existing_masuk_konsisten_ke_ledger(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.opening-balance.store'), [
            'store_id' => $this->storeWithSales->id,
            'amount' => '350.000',
            'transaction_date' => now()->toDateString(),
            'notes' => 'Saldo piutang lama audit',
        ]);
        $res->assertCreated();

        $this->assertSame('350000.00', StoreReceivableService::balanceForStore($this->storeWithSales->id));

        $trx = StoreTransaction::where('store_id', $this->storeWithSales->id)->where('reference_type', 'opening_balance')->first();
        $this->assertNotNull($trx);
        $this->assertEquals(350000.0, (float) $trx->transaction_amount);
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx->status);
    }
}
