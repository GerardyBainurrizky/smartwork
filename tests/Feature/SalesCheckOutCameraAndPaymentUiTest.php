<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCheckOutCameraAndPaymentUiTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private Store $store;
    private Route $route;
    private RouteStop $stop;
    private Visit $visit;
    private StoreTransaction $trx1;
    private StoreTransaction $trx2;
    private string $dummyPhoto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Doni']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Subur Makmur',
            'code' => 'TSM-01',
            'address' => 'Jl. Raya No. 88, Jakarta',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Toko',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stop = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('sample_valid_image');

        $this->visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $this->stop->id,
            'route_id' => $this->route->id,
            'store_id' => $this->store->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.200000,
            'check_in_lng' => 106.816666,
            'check_in_address' => 'Jl. Raya No. 88, Jakarta',
            'check_in_maps_url' => 'https://maps.google.com',
            'check_in_selfie' => 'checkin_selfie.jpg',
        ]);

        // Buat 2 transaksi piutang lama
        $this->trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 500000,
            'description' => 'Faktur A1',
        ], $this->sales);

        $this->trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 300000,
            'description' => 'Faktur A2',
        ], $this->sales);
    }

    /**
     * TEST 1: Check out view renders Foto Etalase UI, Selfie, and Payment input groups
     */
    public function test_check_out_view_renders_camera_and_payment_elements(): void
    {
        $res = $this->actingAs($this->sales)->get(route('visit.check-out-form', $this->visit->id));
        $res->assertOk();
        $res->assertSee('Foto Etalase');
        $res->assertSee('Foto Selfie');
        $res->assertSee('Buka Kamera Foto Etalase');
        $res->assertSee('Buka Kamera Selfie');
        $res->assertSee('Ambil Foto');
        $res->assertSee('storefront-camera');
        $res->assertSee('selfie-camera');
        $res->assertSee('Nominal Pembayaran');
        $res->assertSee($this->trx1->transaction_code);
        $res->assertSee($this->trx2->transaction_code);
    }

    /**
     * TEST 2: Sales check out with Storefront Photo + Selfie + Bayar Piutang Lama (Multi-transaction)
     */
    public function test_sales_checkout_with_storefront_photo_and_multi_piutang_payment(): void
    {
        $payload = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Raya No. 88, Jakarta',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'visit_result' => 'Toko ramai, bayar sebagian piutang',
            'final_notes' => 'Catatan etalase tertata rapi',
            'transaction_status' => 'piutang',
            'old_payment_amount' => 450000,
            'old_payment_method' => 'transfer',
            'old_payment_allocations' => [
                ['store_transaction_id' => $this->trx1->id, 'amount' => 300000],
                ['store_transaction_id' => $this->trx2->id, 'amount' => 150000],
            ],
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ];

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);
        $res->assertOk();

        $this->visit->refresh();
        $this->assertEquals('completed', $this->visit->status);
        $this->assertNotNull($this->visit->final_store_photo);
        $this->assertNotNull($this->visit->check_out_selfie);
        $this->assertEquals('Toko ramai, bayar sebagian piutang', $this->visit->visit_result);

        // Verifikasi piutang berkurang: 800.000 - 450.000 = 350.000
        $currentBalance = StoreReceivableService::balanceForStore($this->store->id);
        $this->assertEquals(350000, (float) $currentBalance);
    }

    /**
     * TEST 3: Sales check out without storefront photo is rejected (Required Validation)
     */
    public function test_sales_checkout_without_storefront_photo_is_rejected(): void
    {
        $payload = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Raya No. 88, Jakarta',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'visit_result' => 'Kunjungan rutin tanpa foto etalase',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
            // final_store_photo is missing
        ];

        $res = $this->actingAs($this->sales)->postJson(route('visit.check-out', $this->visit->id), $payload);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['final_store_photo']);
        $this->assertStringContainsString('Foto Etalase wajib diambil sebelum menyelesaikan Check Out.', $res->json('message') ?? json_encode($res->json('errors')));

        $this->visit->refresh();
        $this->assertEquals('in_progress', $this->visit->status);
    }
}
