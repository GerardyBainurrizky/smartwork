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

class SalesCheckoutLocationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private Store $store;
    private Route $route;
    private RouteStop $stop;
    private string $dummyPhoto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Sales Lapangan']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Delivery']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Harapan Makmur',
            'code' => 'THM-01',
            'address' => 'Jl. Kebon Sirih No. 10',
            'latitude' => -6.182345,
            'longitude' => 106.829876,
            'city' => 'Jakarta Pusat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Harian',
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

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('fake_image_content');
    }

    private function createInProgressVisit(User $user, Store $store, RouteStop $stop): Visit
    {
        return Visit::create([
            'user_id' => $user->id,
            'route_stop_id' => $stop->id,
            'route_id' => $stop->route_id,
            'store_id' => $store->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.182340,
            'check_in_lng' => 106.829870,
            'check_in_address' => 'Jl. Kebon Sirih No. 10',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'checkin.jpg',
        ]);
    }

    /**
     * TEST A & B: Sales membuka checkout form dan merender elemen lokasi serta koordinat
     */
    public function test_a_b_sales_membuka_checkout_dan_memiliki_elemen_lokasi(): void
    {
        $visit = $this->createInProgressVisit($this->sales, $this->store, $this->stop);

        $response = $this->actingAs($this->sales)->get(route('visit.check-out-form', $visit->id));
        $response->assertOk();
        $response->assertSee('Check Out Kunjungan');
        $response->assertSee('Toko Harapan Makmur');
        $response->assertSee('x-text="gpsTitle"', false);
        $response->assertSee('x-text="gpsHint"', false);
        $response->assertSee('Koordinat GPS:', false);
        $response->assertSee('Jarak dari Toko:', false);
    }

    /**
     * TEST H: Submit tanpa latitude/longitude ditolak oleh backend validation
     */
    public function test_h_submit_tanpa_lokasi_ditolak_backend(): void
    {
        $visit = $this->createInProgressVisit($this->sales, $this->store, $this->stop);

        $response = $this->actingAs($this->sales)->postJson(route('visit.check-out', $visit->id), [
            'address' => 'Jl. Kebon Sirih No. 10',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Kunjungan tanpa koordinat',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
        ]);

        $response->assertStatus(422);
        $this->assertEquals('in_progress', $visit->fresh()->status);
    }

    /**
     * TEST I: Submit tanpa selfie ditolak
     */
    public function test_i_submit_tanpa_selfie_ditolak(): void
    {
        $visit = $this->createInProgressVisit($this->sales, $this->store, $this->stop);

        $response = $this->actingAs($this->sales)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.182350,
            'longitude' => 106.829880,
            'address' => 'Jl. Kebon Sirih No. 10',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Kunjungan tanpa selfie',
            'transaction_status' => 'none',
        ]);

        $response->assertStatus(422);
    }
}
