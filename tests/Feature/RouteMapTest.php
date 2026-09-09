<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RouteMapTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;
    protected User $otherSales;
    protected Store $store1;
    protected Store $store2;
    protected Store $storeNoCoords;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['status' => 'active']);
        $this->sales->assignRole('sales');

        $this->otherSales = User::factory()->create(['status' => 'active']);
        $this->otherSales->assignRole('sales');

        $this->store1 = Store::create([
            'code' => 'ST-001',
            'name' => 'Toko Subang Satu',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'latitude' => -6.565432,
            'longitude' => 107.765432,
            'status' => 'active',
        ]);

        $this->store2 = Store::create([
            'code' => 'ST-002',
            'name' => 'Toko Subang Dua',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'latitude' => -6.575432,
            'longitude' => 107.775432,
            'status' => 'active',
        ]);

        $this->storeNoCoords = Store::create([
            'code' => 'ST-003',
            'name' => 'Toko Tanpa Koordinat',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);
    }

    public function test_route_map_renders_with_sales_check_in_location_as_starting_point(): void
    {
        $date = '2026-08-25';

        // 1. Buat presensi check in sales pada tanggal route
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $date,
            'clock_in' => "{$date} 08:00:00",
            'clock_in_lat' => -6.550000,
            'clock_in_lng' => 107.750000,
            'clock_in_address' => 'Kantor Cabang Subang',
            'status' => 'present',
        ]);

        // 2. Buat route
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Harian Sales',
            'date' => $date,
            'status' => 'active',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'visited',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'sequence' => 2,
            'estimated_duration_minutes' => 20,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.map', $route->id));
        $response->assertOk();
        $response->assertSee('Peta Rute');
        $response->assertSee('Toko Subang Satu');
        $response->assertSee('Toko Subang Dua');
        $response->assertSee('Mulai: Presensi');
        $response->assertSee('Sudah Ditempuh');
        $response->assertSee('Rute Berikutnya');
        $response->assertSee('08:00');
    }

    public function test_route_map_displays_in_progress_and_progress_legend(): void
    {
        $date = '2026-08-25';

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Sedang Berjalan',
            'date' => $date,
            'status' => 'active',
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        \App\Models\Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => "{$date} 09:00:00",
            'check_out_at' => "{$date} 10:00:00",
        ]);

        $stop2 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'sequence' => 2,
            'status' => 'visited',
        ]);

        \App\Models\Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'status' => 'in_progress',
            'check_in_at' => "{$date} 10:30:00",
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.map', $route->id));
        $response->assertOk();
        $response->assertSee('Sedang Dikunjungi');
        $response->assertSee('Sudah Ditempuh');
    }

    public function test_route_map_handles_out_of_order_actual_visits_gracefully(): void
    {
        $date = '2026-08-25';

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Kunjungan Melompat',
            'date' => $date,
            'status' => 'active',
        ]);

        $stop1 = RouteStop::create(['route_id' => $route->id, 'store_id' => $this->store1->id, 'sequence' => 1, 'status' => 'visited']);
        $stop2 = RouteStop::create(['route_id' => $route->id, 'store_id' => $this->store2->id, 'sequence' => 2, 'status' => 'visited']);

        // Kunjungan stop 2 selesai terlebih dahulu daripada stop 1 (melompat)
        \App\Models\Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => "{$date} 08:30:00",
            'check_out_at' => "{$date} 09:15:00",
        ]);

        \App\Models\Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => "{$date} 09:45:00",
            'check_out_at' => "{$date} 10:30:00",
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.map', $route->id));
        $response->assertOk();
        $response->assertSee('2/2 Toko Selesai');
    }

    public function test_route_map_renders_gracefully_when_sales_has_not_checked_in(): void
    {
        $date = '2026-08-25';

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Belum Presensi',
            'date' => $date,
            'status' => 'draft',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.map', $route->id));
        $response->assertOk();
        $response->assertSee('Lokasi awal belum tersedia karena Sales belum melakukan Check In presensi');
        $response->assertSee('Toko Subang Satu');
    }

    public function test_route_map_handles_stores_with_missing_coordinates(): void
    {
        $date = '2026-08-25';

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Toko Tanpa Koordinat',
            'date' => $date,
            'status' => 'draft',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeNoCoords->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 15,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.map', $route->id));
        $response->assertOk();
        $response->assertSee('Toko Tanpa Koordinat');
    }

    public function test_other_sales_cannot_access_route_map_but_admin_can(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Privat',
            'date' => '2026-08-25',
            'status' => 'draft',
        ]);

        // Sales lain harus ditolak 403
        $forbiddenResponse = $this->actingAs($this->otherSales)->get(route('route.map', $route->id));
        $forbiddenResponse->assertStatus(403);

        // Admin dapat melihat map rute
        $adminResponse = $this->actingAs($this->admin)->get(route('route.map', $route->id));
        $adminResponse->assertOk();
    }
}
