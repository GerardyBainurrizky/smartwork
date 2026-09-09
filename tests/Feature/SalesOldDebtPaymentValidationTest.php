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

class SalesOldDebtPaymentValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private Store $store;
    private Route $route;
    private RouteStop $stop;
    private Visit $visit;
    private string $dummyPhoto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('dummy_image_data_test');

        $this->sales = User::factory()->create(['name' => 'Sales Validation', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store = Store::create([
            'name' => 'Toko Barokah Jaya',
            'code' => 'TKO-BJ01',
            'address' => 'Jl. Barokah No. 1',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Test Validation',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stop = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'in_progress',
        ]);

        $this->visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $this->stop->id,
            'route_id' => $this->route->id,
            'store_id' => $this->store->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
        ]);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Barokah No. 1',
            'maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
            'visit_result' => 'Kunjungan sales selesai dengan baik',
            'final_store_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ], $overrides);
    }

    /**
     * TEST 1: Pilih 1 transaksi, Payment Rp0 -> REJECT
     */
    public function test_single_selected_transaction_with_zero_payment_is_rejected(): void
    {
        $trx1 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-001',
            'transaction_date' => now()->subDays(2)->toDateString(),
            'transaction_amount' => 500000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 0],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Transaksi TRX-001 dipilih tetapi nominal pembayarannya masih Rp0. Isi nominal pembayaran atau batalkan pilihan transaksi tersebut.',
            ]);

        $this->assertEquals(0, StoreTransactionPayment::where('store_transaction_id', $trx1->id)->count());
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx1->fresh()->status);
        $this->assertEquals('in_progress', $this->visit->fresh()->status);
    }

    /**
     * TEST 2: Pilih 3 transaksi, TRX-001 = 200k, TRX-002 = 500k, TRX-003 = 0 -> REJECT & Atomic rollback
     */
    public function test_multiple_selected_with_one_zero_payment_is_rejected_atomically(): void
    {
        $trx1 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-001',
            'transaction_date' => now()->subDays(3)->toDateString(),
            'transaction_amount' => 500000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);
        $trx2 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-002',
            'transaction_date' => now()->subDays(2)->toDateString(),
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 200000],
                ['store_transaction_id' => $trx2->id, 'amount' => 500000],
                ['store_transaction_id' => $trx3->id, 'amount' => 0],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Transaksi TRX-003 dipilih tetapi nominal pembayarannya masih Rp0. Isi nominal pembayaran atau batalkan pilihan transaksi tersebut.',
            ]);

        // Pastikan TIDAK ADA payment tersimpan dan status TIDAK berubah sama sekali (atomic)
        $this->assertEquals(0, StoreTransactionPayment::whereIn('store_transaction_id', [$trx1->id, $trx2->id, $trx3->id])->count());
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx1->fresh()->status);
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx2->fresh()->status);
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx3->fresh()->status);
        $this->assertEquals('in_progress', $this->visit->fresh()->status);
    }

    /**
     * TEST 3: Pilih 3 transaksi tapi TRX-003 di-uncheck (hanya TRX-001 & TRX-002 dikirim) -> SUCCESS
     */
    public function test_unchecking_zero_payment_transaction_allows_successful_checkout(): void
    {
        $trx1 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-001',
            'transaction_date' => now()->subDays(3)->toDateString(),
            'transaction_amount' => 500000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);
        $trx2 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-002',
            'transaction_date' => now()->subDays(2)->toDateString(),
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'transfer',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 200000],
                ['store_transaction_id' => $trx2->id, 'amount' => 500000],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(200);

        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx1->fresh()->status);
        $this->assertEquals(300000.0, $trx1->fresh()->remaining_amount);

        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx2->fresh()->status);
        $this->assertEquals(500000.0, $trx2->fresh()->remaining_amount);

        // TRX-003 tidak disentuh sama sekali
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx3->fresh()->status);
        $this->assertEquals(750000.0, $trx3->fresh()->remaining_amount);
        $this->assertEquals(0, StoreTransactionPayment::where('store_transaction_id', $trx3->id)->count());

        $this->assertEquals('completed', $this->visit->fresh()->status);
    }

    /**
     * TEST 4: Pilih 3 transaksi, ketiganya dibayar lunas -> Status menjadi LUNAS
     */
    public function test_all_transactions_fully_paid_become_lunas(): void
    {
        $trx1 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-001',
            'transaction_date' => now()->subDays(3)->toDateString(),
            'transaction_amount' => 200000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);
        $trx2 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-002',
            'transaction_date' => now()->subDays(2)->toDateString(),
            'transaction_amount' => 500000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'transfer',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 200000],
                ['store_transaction_id' => $trx2->id, 'amount' => 500000],
                ['store_transaction_id' => $trx3->id, 'amount' => 750000],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(200);

        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx1->fresh()->status);
        $this->assertEquals(0.0, $trx1->fresh()->remaining_amount);

        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx2->fresh()->status);
        $this->assertEquals(0.0, $trx2->fresh()->remaining_amount);

        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx3->fresh()->status);
        $this->assertEquals(0.0, $trx3->fresh()->remaining_amount);
    }

    /**
     * TEST 5: TRX-003 sisa 750k, bayar 250k -> status SEBAGIAN, BUKAN LUNAS
     */
    public function test_partial_payment_status_is_sebagian_not_lunas(): void
    {
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx3->id, 'amount' => 250000],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(200);

        $this->assertEquals(500000.0, $trx3->fresh()->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx3->fresh()->status);
        $this->assertNotEquals(StoreTransaction::STATUS_LUNAS, $trx3->fresh()->status);
    }

    /**
     * TEST 6: TRX-003 sisa 750k, bayar 750k -> status LUNAS
     */
    public function test_exact_remaining_payment_becomes_lunas(): void
    {
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'qris',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx3->id, 'amount' => 750000],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(200);

        $this->assertEquals(0.0, $trx3->fresh()->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx3->fresh()->status);
    }

    /**
     * TEST 7: Payment melebihi sisa piutang -> REJECT
     */
    public function test_payment_exceeding_remaining_is_rejected(): void
    {
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx3->id, 'amount' => 800000],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(422);
        $this->assertEquals(0, StoreTransactionPayment::where('store_transaction_id', $trx3->id)->count());
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx3->fresh()->status);
    }

    /**
     * TEST 8: Nominal negatif -> REJECT
     */
    public function test_negative_payment_amount_is_rejected(): void
    {
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx3->id, 'amount' => -100000],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(422);
        $this->assertEquals(0, StoreTransactionPayment::where('store_transaction_id', $trx3->id)->count());
    }

    /**
     * TEST 9: Payment null -> REJECT
     */
    public function test_null_payment_amount_is_rejected(): void
    {
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx3->id, 'amount' => null],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(422);
        $this->assertEquals(0, StoreTransactionPayment::where('store_transaction_id', $trx3->id)->count());
    }

    /**
     * TEST 10: Missing payment field in allocation item -> REJECT
     */
    public function test_missing_payment_amount_field_is_rejected(): void
    {
        $trx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-003',
            'transaction_date' => now()->subDays(1)->toDateString(),
            'transaction_amount' => 750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'piutang',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx3->id],
            ],
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(422);
        $this->assertEquals(0, StoreTransactionPayment::where('store_transaction_id', $trx3->id)->count());
    }

    /**
     * COMBINED TEST: Mode Piutang Lama + Transaksi Baru -> Jika salah satu old receivable payment Rp0 -> REJECT seluruhnya
     */
    public function test_combined_mode_with_zero_old_debt_payment_is_rejected_atomically(): void
    {
        $oldTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD',
            'transaction_date' => now()->subDays(2)->toDateString(),
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $payload = $this->basePayload([
            'transaction_status' => 'mixed',
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $oldTrx->id, 'amount' => 0],
            ],
            'new_tx_total' => 5000000,
            'new_tx_paid' => 1000000,
            'new_payment_method' => 'transfer',
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);

        $res->assertStatus(422);

        // Pastikan tidak ada transaksi baru dan tidak ada payment yang tersimpan
        $this->assertEquals(1, StoreTransaction::where('store_id', $this->store->id)->count());
        $this->assertEquals(0, StoreTransactionPayment::where('store_id', $this->store->id)->count());
        $this->assertEquals('in_progress', $this->visit->fresh()->status);
    }
}
