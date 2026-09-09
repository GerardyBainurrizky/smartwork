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

class SalesCheckoutPiutangIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $store1;
    private Store $store2;
    private Route $route1;
    private RouteStop $stop1;
    private string $dummyPhoto;

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

        $this->store1 = Store::create([
            'name' => 'Toko Barokah Jaya',
            'code' => 'TBJ-01',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Sentosa',
            'code' => 'STS-02',
            'address' => 'Jl. Asia Afrika No. 20',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);

        $this->route1 = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Kunjungan Harian',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stop1 = RouteStop::create([
            'route_id' => $this->route1->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('fake_image_content');
    }

    private function createActiveVisit(User $user, Store $store, ?RouteStop $stop = null): Visit
    {
        $route = $stop ? $stop->route : Route::create([
            'user_id' => $user->id,
            'name' => 'Rute ' . $user->name,
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        if (! $stop) {
            $stop = RouteStop::create([
                'route_id' => $route->id,
                'store_id' => $store->id,
                'sequence' => 1,
                'status' => 'visited',
            ]);
        }

        return Visit::create([
            'user_id' => $user->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.175392,
            'check_in_lng' => 106.827153,
            'check_in_address' => 'Monas, Gambir, Jakarta Pusat',
            'check_in_maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'selfie' => 'visits/checkin_sample.jpg',
        ]);
    }

    /**
     * TEST A: Tidak Ada Pembayaran
     * Expected: tidak ada payment, tidak ada StoreTransaction baru, sisa piutang lama tidak berubah.
     */
    public function test_a_tidak_ada_pembayaran(): void
    {
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Hanya survei toko, tidak ada pesanan ataupun pembayaran.',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertOk();
        $visit->refresh();
        $this->assertEquals('completed', $visit->status);
        $this->assertEquals('none', $visit->transaction_status);
        $this->assertNull($visit->transaction_amount);
        $this->assertNull($visit->payment_method);

        $this->assertEquals(0, StoreTransactionPayment::where('source_id', $visit->id)->count());
        $this->assertEquals(0, StoreTransaction::where('reference_id', $visit->id)->count());
    }

    /**
     * TEST B: Bayar Piutang Lama Satu Transaksi
     * Sisa Rp100.000, Bayar Rp50.000 => Sisa Rp50.000
     */
    public function test_b_bayar_piutang_lama_satu_transaksi(): void
    {
        // 1. Buat transaksi piutang lama
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
            'description' => 'Faktur Lama #1',
        ], $this->sales1);

        $this->assertEquals(100000.0, $trx->remaining_amount);

        // 2. Sales Check-out bayar Rp50.000
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Penagihan piutang lama separuh.',
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx->id, 'amount' => 50000],
            ],
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertOk();
        $trx->refresh();

        $this->assertEquals(50000.0, $trx->total_paid);
        $this->assertEquals(50000.0, $trx->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx->status);
        $this->assertSame('50000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * TEST C: Bayar Piutang Lama Beberapa Transaksi
     * TRX-001 bayar Rp50.000, TRX-002 bayar Rp100.000 => Masing-masing transaction_id benar
     */
    public function test_c_bayar_piutang_lama_beberapa_transaksi(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 80000,
            'description' => 'Faktur 1',
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 200000,
            'description' => 'Faktur 2',
        ], $this->sales1);

        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Bayar multi invoice piutang lama.',
            'transaction_status' => 'piutang',
            'old_payment_method' => 'transfer',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 50000],
                ['store_transaction_id' => $trx2->id, 'amount' => 100000],
            ],
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertOk();
        $trx1->refresh();
        $trx2->refresh();

        $this->assertEquals(30000.0, $trx1->remaining_amount);
        $this->assertEquals(100000.0, $trx2->remaining_amount);
        $this->assertSame('130000.00', StoreReceivableService::balanceForStore($this->store1->id));

        // Periksa payment records
        $pay1 = StoreTransactionPayment::where('store_transaction_id', $trx1->id)->where('source_id', $visit->id)->first();
        $pay2 = StoreTransactionPayment::where('store_transaction_id', $trx2->id)->where('source_id', $visit->id)->first();

        $this->assertNotNull($pay1);
        $this->assertNotNull($pay2);
        $this->assertEquals(50000.0, (float) $pay1->amount);
        $this->assertEquals(100000.0, (float) $pay2->amount);
        $this->assertEquals('transfer', $pay1->payment_method);
        $this->assertEquals('transfer', $pay2->payment_method);
    }

    /**
     * TEST D: Pembayaran melebihi sisa piutang transaksi
     * Expected: ditolak backend (422) dengan pesan yang sesuai.
     */
    public function test_d_pembayaran_melebihi_sisa_ditolak(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 80000,
        ], $this->sales1);

        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Bayar kelebihan',
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx->id, 'amount' => 100000],
            ],
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Nominal pembayaran melebihi sisa piutang transaksi.',
        ]);

        $trx->refresh();
        $this->assertEquals(80000.0, $trx->remaining_amount);
    }

    /**
     * TEST E: Transaksi Baru Rp10.000, Bayar Rp2.000
     * Expected: Sisa Rp8.000, status SEBAGIAN, kode TRX otomatis
     */
    public function test_e_transaksi_baru_bayar_sebagian(): void
    {
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Order baru dan DP.',
            'transaction_status' => 'paid',
            'new_tx_total' => 10000,
            'new_tx_paid' => 2000,
            'new_payment_method' => 'tunai',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertOk();

        $newTrx = StoreTransaction::where('reference_id', $visit->id)->first();
        $this->assertNotNull($newTrx);
        $this->assertStringStartsWith('TRX-', $newTrx->transaction_code);
        $this->assertEquals(10000.0, (float) $newTrx->transaction_amount);
        $this->assertEquals(2000.0, $newTrx->total_paid);
        $this->assertEquals(8000.0, $newTrx->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $newTrx->status);
        $this->assertSame('8000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * TEST F: Transaksi Baru Rp10.000, Bayar Rp10.000
     * Expected: Sisa Rp0, status LUNAS, tidak menambah total piutang toko
     */
    public function test_f_transaksi_baru_langsung_lunas(): void
    {
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Order tunai cash keras.',
            'transaction_status' => 'paid',
            'new_tx_total' => 10000,
            'new_tx_paid' => 10000,
            'new_payment_method' => 'tunai',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertOk();

        $newTrx = StoreTransaction::where('reference_id', $visit->id)->first();
        $this->assertNotNull($newTrx);
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $newTrx->status);
        $this->assertEquals(0.0, $newTrx->remaining_amount);
        $this->assertSame('0.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * TEST G: Transaksi Baru Rp10.000, Bayar Rp0
     * Expected: Sisa Rp10.000, status BELUM_LUNAS, piutang toko bertambah Rp10.000
     */
    public function test_g_transaksi_baru_tanpa_bayar_tempo(): void
    {
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Order tempo 100%.',
            'transaction_status' => 'paid',
            'new_tx_total' => 10000,
            'new_tx_paid' => 0,
            'new_payment_method' => 'tunai',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertOk();

        $newTrx = StoreTransaction::where('reference_id', $visit->id)->first();
        $this->assertNotNull($newTrx);
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $newTrx->status);
        $this->assertEquals(0.0, $newTrx->total_paid);
        $this->assertEquals(10000.0, $newTrx->remaining_amount);
        $this->assertSame('10000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * TEST H: Gabungan (Piutang Lama + Transaksi Baru)
     * Piutang lama: Rp150.000 dibayar (TRX-001: 50rb, TRX-002: 100rb)
     * Transaksi baru: Rp100.000, dibayar Rp30.000 -> Sisa baru: Rp70.000
     */
    public function test_h_gabungan_bayar_piutang_lama_dan_transaksi_baru(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 80000,
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 200000,
        ], $this->sales1);

        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Bayar piutang lama dan order baru sebagian.',
            'transaction_status' => 'mixed',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 50000],
                ['store_transaction_id' => $trx2->id, 'amount' => 100000],
            ],
            'new_tx_total' => 100000,
            'new_tx_paid' => 30000,
            'new_payment_method' => 'transfer',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertOk();
        $trx1->refresh();
        $trx2->refresh();

        $this->assertEquals(30000.0, $trx1->remaining_amount);
        $this->assertEquals(100000.0, $trx2->remaining_amount);

        $newTrx = StoreTransaction::where('reference_id', $visit->id)->first();
        $this->assertNotNull($newTrx);
        $this->assertEquals(100000.0, (float) $newTrx->transaction_amount);
        $this->assertEquals(30000.0, $newTrx->total_paid);
        $this->assertEquals(70000.0, $newTrx->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $newTrx->status);
    }

    /**
     * TEST I: Total saldo akhir toko benar sesuai rumus ledger
     * Sebelum: 280.000
     * Bayar piutang lama: 150.000
     * Transaksi baru: 100.000, bayar: 30.000 (sisa baru: 70.000)
     * Saldo akhir = 280.000 - 150.000 + 70.000 = 200.000
     */
    public function test_i_total_saldo_akhir_toko_benar(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 80000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 200000,
        ], $this->sales1);

        $this->assertSame('280000.00', StoreReceivableService::balanceForStore($this->store1->id));

        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $trx1 = StoreTransaction::where('store_id', $this->store1->id)->where('transaction_amount', 80000)->first();
        $trx2 = StoreTransaction::where('store_id', $this->store1->id)->where('transaction_amount', 200000)->first();

        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Gabungan piutang lama & transaksi baru',
            'transaction_status' => 'mixed',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 50000],
                ['store_transaction_id' => $trx2->id, 'amount' => 100000],
            ],
            'new_tx_total' => 100000,
            'new_tx_paid' => 30000,
            'new_payment_method' => 'transfer',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        $this->assertSame('200000.00', StoreReceivableService::balanceForStore($this->store1->id));
        $this->assertEquals(200000.0, $this->store1->fresh()->total_receivable);
    }

    /**
     * TEST J: Detail Visit membaca payment aktual
     * Tampilan show.blade.php membedakan Pembayaran Piutang Lama dan Transaksi Baru.
     */
    public function test_j_detail_visit_membaca_payment_aktual(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 80000,
        ], $this->sales1);

        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Bayar piutang dan order baru',
            'transaction_status' => 'mixed',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx->id, 'amount' => 50000],
            ],
            'new_tx_total' => 100000,
            'new_tx_paid' => 30000,
            'new_payment_method' => 'transfer',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        $resShow = $this->actingAs($this->sales1)->get(route('visit.show', $visit->id));
        $resShow->assertOk();
        $resShow->assertSee('Pembayaran Piutang Lama:');
        $resShow->assertSee('Rp 50.000');
        $resShow->assertSee('Transaksi Baru:');
        $resShow->assertSee('Rp 100.000');
        $resShow->assertSee('Rp 30.000');
        $resShow->assertSee('Rp 70.000');
    }

    /**
     * TEST K: Double checkout tidak menghasilkan double payment
     */
    public function test_k_double_checkout_tidak_menghasilkan_double_payment(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
        ], $this->sales1);

        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $payload = [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Bayar cicilan',
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx->id, 'amount' => 50000],
            ],
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ];

        // First attempt -> Success
        $res1 = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), $payload);
        $res1->assertOk();

        // Second attempt -> Rejected
        $res2 = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), $payload);
        $res2->assertStatus(422);

        $trx->refresh();
        $this->assertEquals(50000.0, $trx->remaining_amount);
        $this->assertEquals(1, StoreTransactionPayment::where('store_transaction_id', $trx->id)->count());
    }

    /**
     * TEST L: Sales tidak dapat mengakses transaksi toko Sales lain
     */
    public function test_l_sales_tidak_dapat_mengakses_transaksi_toko_sales_lain(): void
    {
        // Store 2 belongs to Sales 2
        $trxStore2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store2->id,
            'transaction_amount' => 100000,
        ], $this->sales2);

        // Sales 1 tries to pay transaction of Store 2 in their own visit
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $response = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Unauthorized payment attempt',
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trxStore2->id, 'amount' => 50000],
            ],
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ]);

        $response->assertStatus(403); // Access denied by authorization
        $trxStore2->refresh();
        $this->assertEquals(100000.0, $trxStore2->remaining_amount);
    }

    /**
     * TEST M: Admin tetap dapat melihat transaksi
     */
    public function test_m_admin_dapat_melihat_transaksi(): void
    {
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Pesanan baru',
            'transaction_status' => 'paid',
            'new_tx_total' => 150000,
            'new_tx_paid' => 50000,
            'new_payment_method' => 'tunai',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        // Admin checks receivables dashboard/detail
        $resAdmin = $this->actingAs($this->admin)->get(route('admin.receivables.show', $this->store1->id));
        $resAdmin->assertOk();
        $resAdmin->assertSee('100.000'); // Sisa piutang Rp100.000
    }

    /**
     * TEST N: Super Admin tetap dapat melihat transaksi
     */
    public function test_n_super_admin_dapat_melihat_transaksi(): void
    {
        $visit = $this->createActiveVisit($this->sales1, $this->store1, $this->stop1);

        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas, Gambir, Jakarta Pusat',
            'maps_url' => 'https://maps.google.com/?q=-6.175392,106.827153',
            'visit_result' => 'Pesanan baru',
            'transaction_status' => 'paid',
            'new_tx_total' => 250000,
            'new_tx_paid' => 100000,
            'new_payment_method' => 'transfer',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        $resSuperAdmin = $this->actingAs($this->superAdmin)->get(route('admin.receivables.show', $this->store1->id));
        $resSuperAdmin->assertOk();
        $resSuperAdmin->assertSee('150.000'); // Sisa piutang Rp150.000
    }

    /**
     * TEST O: Driver tetap tidak memiliki akses piutang
     */
    public function test_o_driver_tetap_tidak_memiliki_akses_piutang(): void
    {
        $driverRoute = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $driverStop = RouteStop::create([
            'route_id' => $driverRoute->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $driverVisit = Visit::create([
            'user_id' => $this->driver->id,
            'route_stop_id' => $driverStop->id,
            'route_id' => $driverRoute->id,
            'store_id' => $this->store1->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.175392,
            'check_in_lng' => 106.827153,
            'check_in_address' => 'Monas',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'driver_checkin.jpg',
        ]);

        // 1. Driver check out form does not expose openTransactions
        $resForm = $this->actingAs($this->driver)->get(route('visit.check-out-form', $driverVisit->id));
        $resForm->assertOk();
        $resForm->assertDontSee('Status Transaksi / Pembayaran');

        // 2. Driver check out ignores any transaction params
        $resCheckout = $this->actingAs($this->driver)->postJson(route('visit.check-out', $driverVisit->id), [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Monas',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Pengiriman barang beres',
            'photos' => [$this->dummyPhoto],
            'transaction_status' => 'paid',
            'transaction_amount' => 500000,
        ]);
        $resCheckout->assertOk();

        // 3. No receivable or store transactions created
        $this->assertEquals(0, StoreTransaction::where('reference_id', $driverVisit->id)->count());
        $this->assertEquals(0, StoreTransactionPayment::where('source_id', $driverVisit->id)->count());
    }
}
