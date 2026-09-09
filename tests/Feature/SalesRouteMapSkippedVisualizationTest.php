<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesRouteMapSkippedVisualizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected Store $store1;
    protected Store $store2;
    protected Store $store3;
    protected Store $store4;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'sales']);

        $this->sales = User::factory()->create(['name' => 'Andi Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store1 = Store::create(['code' => 'ST-001', 'name' => 'Toko Barokah', 'latitude' => -6.551234, 'longitude' => 107.761234, 'city' => 'Subang', 'status' => 'active']);
        $this->store2 = Store::create(['code' => 'ST-002', 'name' => 'Toko Wulan', 'latitude' => -6.552234, 'longitude' => 107.762234, 'city' => 'Subang', 'status' => 'active']);
        $this->store3 = Store::create(['code' => 'ST-003', 'name' => 'Toko Sejahtera', 'latitude' => -6.553234, 'longitude' => 107.763234, 'city' => 'Subang', 'status' => 'active']);
        $this->store4 = Store::create(['code' => 'ST-004', 'name' => 'Toko Maju', 'latitude' => -6.554234, 'longitude' => 107.764234, 'city' => 'Subang', 'status' => 'active']);
    }

    public function test_route_map_renders_skipped_store_with_orange_amber_indicator(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Subang Kota',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'completed',
        ]);

        $stop2 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'sequence' => 2,
            'status' => 'skipped',
        ]);

        $stop3 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store3->id,
            'sequence' => 3,
            'status' => 'pending',
        ]);

        // Create visit for stop 1
        Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store1->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop1->id,
            'check_in_at' => now()->setTime(8, 30),
            'check_out_at' => now()->setTime(9, 0),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.map', $route->id));
        $response->assertOk();

        // Legend contains Dilewati
        $response->assertSee('Dilewati');
        // Orange/Amber color defined for skipped status
        $response->assertSee('#F97316');
        // Timeline renders skipped style (bg-orange, border-orange, text-orange)
        $response->assertSee('bg-orange-50');
        $response->assertSee('bg-orange-500');
        // Skipped status label
        $response->assertSee('Dilewati');
        // History chain includes visited and skipped stops
        $response->assertSee("s.status === 'visited' || s.status === 'skipped'", false);
        // Pending filter for active path excludes skipped
        $response->assertSee(".filter(s => s.status === 'pending')", false);
    }
}
