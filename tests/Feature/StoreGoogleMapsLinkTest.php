<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreGoogleMapsLinkTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected Store $storeWithCoords;
    protected Store $storeWithAddressOnly;
    protected Store $storeEmpty;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->sales = User::factory()->create(['status' => 'active']);
        $this->sales->assignRole('sales');

        $this->storeWithCoords = Store::create([
            'code' => 'ST-001',
            'name' => 'Toko Subang Satu',
            'address' => 'Jl. Otista No. 12',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'latitude' => -6.565432,
            'longitude' => 107.765432,
            'status' => 'active',
        ]);

        $this->storeWithAddressOnly = Store::create([
            'code' => 'ST-002',
            'name' => 'Toko Subang Dua',
            'address' => 'Jl. Pejuang No. 45, Subang',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $this->storeEmpty = Store::create([
            'code' => 'ST-003',
            'name' => '',
            'address' => null,
            'city' => null,
            'province' => null,
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);
    }

    public function test_store_model_generates_correct_google_maps_urls(): void
    {
        // 1. Toko dengan koordinat valid
        $this->assertEquals(
            'https://www.google.com/maps/search/?api=1&query=-6.565432,107.765432',
            $this->storeWithCoords->google_maps_url
        );

        // 2. Toko tanpa koordinat fallback ke query nama & alamat ter-encode
        $this->assertNotNull($this->storeWithAddressOnly->google_maps_url);
        $this->assertStringContainsString('https://www.google.com/maps/search/?api=1&query=', $this->storeWithAddressOnly->google_maps_url);
        $this->assertStringContainsString(urlencode('Toko Subang Dua'), $this->storeWithAddressOnly->google_maps_url);

        // 3. Toko tanpa data lokasi menghasilkan null
        $this->assertNull($this->storeEmpty->google_maps_url);
    }

    public function test_route_show_page_displays_google_maps_link(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Test Maps',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeWithCoords->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.show', $route->id));
        $response->assertOk();
        $response->assertSee('Buka Maps');
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-6.565432,107.765432');
    }

    public function test_route_index_page_displays_google_maps_link(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeWithCoords->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.index'));
        $response->assertOk();
        $response->assertSee('Buka Maps');
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-6.565432,107.765432');
    }

    public function test_visit_index_page_displays_google_maps_link(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeWithCoords->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('visit.index'));
        $response->assertOk();
        $response->assertSee('Buka Maps');
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-6.565432,107.765432');
    }

    public function test_visit_show_page_displays_google_maps_link(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeWithCoords->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeWithCoords->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
        ]);

        $response = $this->actingAs($this->sales)->get(route('visit.show', $visit->id));
        $response->assertOk();
        $response->assertSee('Buka Maps');
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-6.565432,107.765432');
    }
}
