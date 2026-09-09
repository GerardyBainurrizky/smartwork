<?php

namespace Tests\Feature;

use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesRouteDetailStatusIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected Store $store1;
    protected Store $store2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'sales']);

        $this->sales = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store1 = Store::create(['code' => 'ST-001', 'name' => 'Toko 1', 'city' => 'Subang', 'status' => 'active']);
        $this->store2 = Store::create(['code' => 'ST-002', 'name' => 'Toko 2', 'city' => 'Subang', 'status' => 'active']);
    }

    public function test_draft_route_shows_start_button(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Pagi Draft',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.show', $route->id));
        $response->assertOk();
        $response->assertSee('Mulai Rute');
        $response->assertDontSee('Selesai Rute');
        $response->assertDontSee('Rute Sedang Berjalan');
    }

    public function test_active_route_shows_active_indicator_without_manual_complete_button(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Sedang Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.show', $route->id));
        $response->assertOk();
        $response->assertSee('Rute Sedang Berjalan');
        $response->assertDontSee('Selesai Rute');
        $response->assertDontSee('Mulai Rute');
    }

    public function test_completed_route_shows_completed_indicator(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Sudah Selesai',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store1->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.show', $route->id));
        $response->assertOk();
        $response->assertSee('Rute Selesai');
        $response->assertDontSee('Selesai Rute');
        $response->assertDontSee('Rute Sedang Berjalan');
        $response->assertDontSee('Mulai Rute');
    }
}
