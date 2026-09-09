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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRevisionSpecificRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $storeA;
    private Store $storeB;
    private Route $route;
    private RouteStop $stopA;
    private RouteStop $stopB;

    private string $dummySelfie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->dummySelfie = 'data:image/jpeg;base64,' . base64_encode('dummy_image_data_binary');

        $this->sales1 = User::factory()->create(['name' => 'Sales Satu', 'status' => 'active']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Dua', 'status' => 'active']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Satu', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko Barokah A',
            'code' => 'TKO-A',
            'address' => 'Jl. Raya A No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko Barokah B',
            'code' => 'TKO-B',
            'address' => 'Jl. Raya B No. 2',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->route = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Kunjungan Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stopA = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $this->stopB = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST DASHBOARD 1: Definisi 3 Card Sales Dashboard
     * 1. Transaksi Hari Ini: transaksi baru hari ini
     * 2. Piutang Saat Ini: total remaining balance semua transaksi terbuka
     * 3. Pembayaran Piutang Hari Ini: uang yang diterima hari ini untuk mengurangi piutang lama
     */
    public function test_dashboard_sales_3_cards_behavior(): void
    {
        // 1. Transaksi Lama di Toko A: Rp 2.000.000, bayar Rp 500.000 -> sisa Rp 1.500.000
        $oldTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 2000000,
            'transaction_date' => now()->subDays(5)->toDateString(),
            'description' => 'Faktur Lama',
        ], $this->sales1);

        // Sebelum transaksi baru atau bayar hari ini:
        // Transaksi Hari Ini = 0
        // Piutang Saat Ini = 2.000.000
        // Pembayaran Piutang Hari Ini = 0
        $res = $this->actingAs($this->sales1)->get(route('dashboard'));
        $res->assertOk();
        $res->assertSee('Transaksi Hari Ini');
        $res->assertSee('Piutang Saat Ini');
        $res->assertSee('Pembayaran Piutang Hari Ini');
        $res->assertSee('Rp 2.000.000'); // Piutang Saat Ini

        // 2. Hari ini toko A membayar piutang lama Rp 500.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'tunai',
            'source' => 'visit',
        ], $this->sales1);

        // 3. Hari ini Sales 1 membuat transaksi baru Rp 5.000.000 di Toko B dengan bayar baru Rp 3.000.000 (sisa Rp 2.000.000)
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 5000000,
            'paid_amount' => 3000000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Penjualan Baru Hari Ini',
        ], $this->sales1);

        // Hasil seharusnya:
        // Transaksi Hari Ini: Rp 5.000.000
        // Pembayaran Piutang Hari Ini: Rp 500.000
        // Piutang Saat Ini: sisa faktur lama (1.500.000) + sisa faktur baru (2.000.000) = Rp 3.500.000
        $res2 = $this->actingAs($this->sales1)->get(route('dashboard'));
        $res2->assertOk();
        $res2->assertSee('Rp 5.000.000'); // Transaksi Hari Ini
        $res2->assertSee('Rp 500.000');   // Pembayaran Piutang Hari Ini
        $res2->assertSee('Rp 3.500.000'); // Piutang Saat Ini
    }

    /**
     * TEST CHECK IN: Lokasi, Foto Selfie, Catatan Kunjungan
     */
    public function test_check_in_sales_with_selfie_and_notes(): void
    {
        $res = $this->actingAs($this->sales1)->get(route('visit.check-in-form', $this->stopA->id));
        $res->assertOk();
        $res->assertSee('Check In');
        $res->assertSee('Foto Selfie');
        $res->assertSee('Catatan Kunjungan');

        $postRes = $this->actingAs($this->sales1)->postJson(route('visit.store'), [
            'route_stop_id' => $this->stopA->id,
            'route_id' => $this->route->id,
            'store_id' => $this->storeA->id,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Sudirman No. 1 Jakarta',
            'maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
            'selfie' => $this->dummySelfie,
            'initial_notes' => 'Datang terlambat karena ban kendaraan bocor.',
        ]);

        $postRes->assertOk();
        $visit = Visit::where('route_stop_id', $this->stopA->id)->first();
        $this->assertNotNull($visit);
        $this->assertSame('Datang terlambat karena ban kendaraan bocor.', $visit->initial_notes);
        $this->assertNotNull($visit->check_in_selfie);
    }

    /**
     * TEST CHECK OUT & BAYAR PIUTANG LAMA UI ELEMENTS
     */
    public function test_check_out_sales_field_order_and_payment_ui(): void
    {
        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $this->stopA->id,
            'route_id' => $this->route->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.200000,
            'check_in_lng' => 106.816666,
            'check_in_address' => 'Jl. Sudirman No. 1',
            'check_in_maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
            'check_in_selfie' => 'dummy_path.jpg',
        ]);

        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 250000,
            'description' => 'Faktur 001',
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('visit.check-out-form', $visit->id));
        $res->assertOk();
        $res->assertSee('Piutang Toko Saat Ini');
        $res->assertSee('Status Transaksi / Pembayaran');
        $res->assertSee('Hasil Kunjungan');
        $res->assertSee('Catatan Tambahan');
        $res->assertSee('Foto Etalase');
        $res->assertSee('Foto Selfie');

        // Check Out submission with old debt payment
        $postRes = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Sudirman No. 1 Jakarta',
            'maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
            'visit_result' => 'Toko ramai dan pemilik membayar piutang.',
            'transaction_status' => 'piutang',
            'old_payment_amount' => 250000,
            'old_payment_method' => 'tunai',
            'old_payment_allocations' => [
                ['store_transaction_id' => $trx1->id, 'amount' => 250000],
            ],
            'final_notes' => 'Catatan penutup kunjungan.',
            'selfie' => $this->dummySelfie,
            'final_store_photo' => $this->dummySelfie,
        ]);

        $postRes->assertOk();
        $this->assertSame(500000.0, $trx1->fresh()->remaining_amount);
        $this->assertSame('completed', $visit->fresh()->status);
    }

    /**
     * TEST SALES STORES FILTER ORDER & RESET
     */
    public function test_sales_stores_filter_and_reset(): void
    {
        // Store A has receivable
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 500000,
            'description' => 'Faktur A',
        ], $this->sales1);

        // Store B balance is 0
        $res = $this->actingAs($this->sales1)->get(route('sales.stores.index'));
        $res->assertOk();
        $res->assertSee('Status Piutang');
        $res->assertSee('Cari Toko');
        $res->assertSee('Reset');

        // Filter Ada Piutang
        $resWith = $this->actingAs($this->sales1)->get(route('sales.stores.index', ['status' => 'with']));
        $resWith->assertOk();
        $resWith->assertSee('Toko Barokah A');
        $resWith->assertDontSee('Toko Barokah B');

        // Filter Tidak Ada Piutang
        $resWithout = $this->actingAs($this->sales1)->get(route('sales.stores.index', ['status' => 'without']));
        $resWithout->assertOk();
        $resWithout->assertSee('Toko Barokah B');
        $resWithout->assertDontSee('Toko Barokah A');
    }
}
