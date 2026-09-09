<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesRouteDashboardSyncTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function salesUser(): User
    {
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('sales');

        return $user;
    }

    private function store(string $name): Store
    {
        return Store::create([
            'code' => 'ST-'.strtoupper(Str::random(4)),
            'name' => $name,
            'address' => 'Jl. Test No. 1',
            'city' => 'Jakarta Timur',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);
    }

    public function test_dashboard_stop_statuses_follow_real_visits(): void
    {
        $sales = $this->salesUser();
        $doneStore = $this->store('Toko Selesai');
        $progressStore = $this->store('Toko Berproses');
        $waitStore = $this->store('Toko Menunggu');
        $skipStore = $this->store('Toko Dilewati');

        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Status',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stopDone = RouteStop::create(['route_id' => $route->id, 'store_id' => $doneStore->id, 'sequence' => 1, 'status' => 'visited']);
        $stopProgress = RouteStop::create(['route_id' => $route->id, 'store_id' => $progressStore->id, 'sequence' => 2, 'status' => 'visited']);
        $stopWait = RouteStop::create(['route_id' => $route->id, 'store_id' => $waitStore->id, 'sequence' => 3, 'status' => 'pending']);
        RouteStop::create(['route_id' => $route->id, 'store_id' => $skipStore->id, 'sequence' => 4, 'status' => 'skipped']);

        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopDone->id,
            'store_id' => $doneStore->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
        ]);
        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopProgress->id,
            'store_id' => $progressStore->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($sales)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Jadwal Kunjungan Hari Ini');
        $response->assertSee('Toko Selesai')->assertSee('Dikunjungi');
        $response->assertSee('Toko Berproses')->assertSee('Proses');
        $response->assertSee('Toko Menunggu')->assertSee('Menunggu');
        $response->assertSee('Toko Dilewati')->assertSee('Dilewati');
    }

    public function test_route_index_stop_statuses_follow_real_visits(): void
    {
        $sales = $this->salesUser();
        $doneStore = $this->store('Toko Rute Selesai');
        $progressStore = $this->store('Toko Rute Proses');
        $waitStore = $this->store('Toko Rute Menunggu');

        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Index Status',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stopDone = RouteStop::create(['route_id' => $route->id, 'store_id' => $doneStore->id, 'sequence' => 1, 'status' => 'visited']);
        $stopProgress = RouteStop::create(['route_id' => $route->id, 'store_id' => $progressStore->id, 'sequence' => 2, 'status' => 'visited']);
        RouteStop::create(['route_id' => $route->id, 'store_id' => $waitStore->id, 'sequence' => 3, 'status' => 'pending']);

        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopDone->id,
            'store_id' => $doneStore->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
        ]);
        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopProgress->id,
            'store_id' => $progressStore->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($sales)->get(route('route.index'));

        $response->assertOk();
        $response->assertSee('Rute Hari Ini');
        $response->assertSee('Toko Rute Selesai')->assertSee('Dikunjungi');
        $response->assertSee('Toko Rute Proses')->assertSee('Proses');
        $response->assertSee('Toko Rute Menunggu')->assertSee('Menunggu');
    }

    public function test_upcoming_routes_include_today_and_all_later_dates(): void
    {
        $sales = $this->salesUser();
        $store = $this->store('Toko Upcoming');

        $routeToday = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Hari Ini Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Besok',
            'date' => now()->addDay()->toDateString(),
            'status' => 'draft',
        ]);
        Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Lusa',
            'date' => now()->addDays(2)->toDateString(),
            'status' => 'draft',
        ]);
        RouteStop::create(['route_id' => $routeToday->id, 'store_id' => $store->id, 'sequence' => 1, 'status' => 'pending']);

        $response = $this->actingAs($sales)->get(route('route.index'));

        $response->assertOk();
        $upcoming = $response->viewData('upcomingRoutes');
        $this->assertSame(3, $upcoming->count(), 'Rute Mendatang harus memuat tanggal >= hari ini.');
        $this->assertTrue($upcoming->pluck('id')->contains($routeToday->id), 'Rute hari ini yang aktif tetap tampil pada Rute Mendatang.');
    }

    public function test_visit_page_renders_all_today_route_stops_like_route_page(): void
    {
        $sales = $this->salesUser();
        $storeA = $this->store('Toko Visit A');
        $storeB = $this->store('Toko Visit B');
        $storeC = $this->store('Toko Visit C');

        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        RouteStop::create(['route_id' => $route->id, 'store_id' => $storeA->id, 'sequence' => 1, 'status' => 'pending']);
        RouteStop::create(['route_id' => $route->id, 'store_id' => $storeB->id, 'sequence' => 2, 'status' => 'pending']);
        RouteStop::create(['route_id' => $route->id, 'store_id' => $storeC->id, 'sequence' => 3, 'status' => 'pending']);

        $visitPage = $this->actingAs($sales)->get(route('visit.index'));
        $visitPage->assertOk();

        $routeDetail = $this->actingAs($sales)->get(route('route.index'));
        $routeDetail->assertOk();

        $this->assertSame(
            $routeDetail->viewData('todayStops')->pluck('id')->sort()->values()->all(),
            $visitPage->viewData('todayStops')->pluck('id')->sort()->values()->all(),
            'Route stops hari ini di /visit harus identik dengan /route.'
        );

        $this->assertSame(3, $visitPage->viewData('todayStops')->count());
        $this->assertSame(3, $visitPage->viewData('todayPlanStats')['total']);
        $visitPage->assertSee('Toko Visit A');
        $visitPage->assertSee('Toko Visit B');
        $visitPage->assertSee('Toko Visit C');
        $visitPage->assertSee('Belum Dikunjungi');
    }

    public function test_visit_page_shows_real_time_stop_status(): void
    {
        $sales = $this->salesUser();
        $doneStore = $this->store('Visit Toko Selesai');
        $progressStore = $this->store('Visit Toko Berjalan');
        $waitStore = $this->store('Visit Toko Menunggu');

        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Status Visit',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stopDone = RouteStop::create(['route_id' => $route->id, 'store_id' => $doneStore->id, 'sequence' => 1, 'status' => 'visited']);
        $stopProgress = RouteStop::create(['route_id' => $route->id, 'store_id' => $progressStore->id, 'sequence' => 2, 'status' => 'visited']);
        RouteStop::create(['route_id' => $route->id, 'store_id' => $waitStore->id, 'sequence' => 3, 'status' => 'pending']);

        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopDone->id,
            'store_id' => $doneStore->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
        ]);
        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopProgress->id,
            'store_id' => $progressStore->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($sales)->get(route('visit.index'));

        $response->assertOk();
        $response->assertSee('Visit Toko Selesai')->assertSee('Selesai');
        $response->assertSee('Visit Toko Berjalan')->assertSee('Sedang Berjalan');
        $response->assertSee('Visit Toko Menunggu')->assertSee('Belum Dikunjungi');
    }
}
