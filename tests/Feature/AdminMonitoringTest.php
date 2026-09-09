<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
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

    private function store(?User $sales = null): Store
    {
        return Store::create([
            'code' => 'ST-'.strtoupper(Str::random(4)),
            'name' => 'Toko Sumber Tani',
            'sales_penanggung_jawab_id' => $sales?->id,
            'address' => 'Jl. Raya Bogor No. 45',
            'city' => 'Jakarta Timur',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_attendance_monitoring(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $response->assertSee('Presensi');
        $response->assertSee($sales->name);
        $this->assertDatabaseHas('attendances', ['user_id' => $sales->id]);
    }

    public function test_admin_is_blocked_from_sales_attendance(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('attendance.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($admin)->post(route('attendance.check-in'), [])->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_route_monitoring(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Monitoring',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.routes.index'));

        $response->assertOk();
        $response->assertSee('Rute Monitoring');
    }

    public function test_admin_route_create_page_renders(): void
    {
        $admin = $this->adminUser();
        $this->salesUser();
        $this->store();

        $response = $this->actingAs($admin)->get(route('admin.routes.create'));

        $response->assertOk();
        $response->assertSee('Buat Rencana Kunjungan');
    }

    public function test_admin_can_create_route(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store($sales);

        $response = $this->actingAs($admin)->postJson(route('admin.routes.store'), [
            'user_id' => $sales->id,
            'name' => 'Rute Baru Admin',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 45, 'notes' => 'Kunjungan utama'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('routes', ['user_id' => $sales->id, 'name' => 'Rute Baru Admin', 'status' => 'draft']);
        $this->assertDatabaseHas('route_stops', ['store_id' => $store->id, 'sequence' => 1]);
    }

    public function test_admin_route_requires_sales_owner(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store($sales);

        $response = $this->actingAs($admin)->postJson(route('admin.routes.store'), [
            'name' => 'Rute Tanpa Sales',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 45],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('routes', ['name' => 'Rute Tanpa Sales']);
    }

    public function test_admin_route_assigned_to_sales_only_appears_for_that_sales(): void
    {
        $admin = $this->adminUser();
        $salesA = $this->salesUser();
        $salesB = $this->salesUser();
        $store = $this->store($salesA);

        $this->actingAs($admin)->postJson(route('admin.routes.store'), [
            'user_id' => $salesA->id,
            'name' => 'Rute Milik Sales A',
            'date' => now()->addDays(2)->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ])->assertOk();

        $route = Route::where('name', 'Rute Milik Sales A')->first();
        $this->assertNotNull($route);
        $this->assertEquals($salesA->id, $route->user_id);

        $responseA = $this->actingAs($salesA)->get(route('route.index'));
        $responseA->assertSee('Rute Milik Sales A');

        $responseB = $this->actingAs($salesB)->get(route('route.index'));
        $responseB->assertDontSee('Rute Milik Sales A');
    }

    public function test_admin_route_page_shows_upcoming_and_today_routes_with_sales(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();

        $upcoming = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Mendatang',
            'date' => now()->addDays(3)->toDateString(),
            'status' => 'draft',
        ]);
        $todayRoute = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.routes.index'));

        $response->assertOk();
        $response->assertSee('Rute Mendatang');
        $response->assertSee('Rute Hari Ini');
        $response->assertSee($sales->name);
        $response->assertSee(strtoupper(substr($sales->name, 0, 2)));
        $this->assertNotNull($upcoming);
        $this->assertNotNull($todayRoute);
    }

    public function test_admin_route_page_filters_by_sales_dropdown(): void
    {
        $admin = $this->adminUser();
        $salesA = $this->salesUser();
        $salesB = $this->salesUser();

        Route::create([
            'user_id' => $salesA->id,
            'name' => 'Rute Alpha',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        Route::create([
            'user_id' => $salesB->id,
            'name' => 'Rute Zebra',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.routes.index', ['user_id' => $salesB->id]));

        $response->assertOk();
        $response->assertSee('Rute Zebra');
        $response->assertDontSee('Rute Alpha');
    }

    public function test_admin_route_page_shows_realtime_progress_from_real_visits(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $storeA = $this->store();
        $storeB = $this->store();
        $storeC = $this->store();

        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Monitoring Progress',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stopA = RouteStop::create(['route_id' => $route->id, 'store_id' => $storeA->id, 'sequence' => 1, 'status' => 'visited']);
        $stopB = RouteStop::create(['route_id' => $route->id, 'store_id' => $storeB->id, 'sequence' => 2, 'status' => 'visited']);
        $stopC = RouteStop::create(['route_id' => $route->id, 'store_id' => $storeC->id, 'sequence' => 3, 'status' => 'pending']);

        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopA->id,
            'store_id' => $storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHours(1),
            'transaction_amount' => 100000,
        ]);
        Visit::create([
            'user_id' => $sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stopB->id,
            'store_id' => $storeB->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.routes.index'));

        $response->assertOk();
        $response->assertSee('Rute Monitoring Progress');
        $response->assertSee('1 selesai');
        $response->assertSee('1 berjalan');
        $response->assertSee('1 belum');
    }

    public function test_admin_route_stats_cards_follow_filter_date_and_sales(): void
    {
        $admin = $this->adminUser();
        $salesA = $this->salesUser();
        $salesB = $this->salesUser();

        foreach (['draft', 'active', 'completed', 'cancelled'] as $status) {
            Route::create([
                'user_id' => $salesA->id,
                'name' => "Rute A {$status}",
                'date' => now()->toDateString(),
                'status' => $status,
            ]);
        }
        Route::create([
            'user_id' => $salesB->id,
            'name' => 'Rute B Selesai',
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        Route::create([
            'user_id' => $salesA->id,
            'name' => 'Rute A Hari Lain',
            'date' => now()->addDays(1)->toDateString(),
            'status' => 'active',
        ]);

        $all = $this->actingAs($admin)->get(route('admin.routes.index'));
        $all->assertOk();
        $this->assertSame(['total' => 5, 'active' => 1, 'completed' => 2], $all->viewData('stats'));

        $filteredA = $this->actingAs($admin)->get(route('admin.routes.index', ['user_id' => $salesA->id]));
        $filteredA->assertOk();
        $this->assertSame(['total' => 4, 'active' => 1, 'completed' => 1], $filteredA->viewData('stats'));

        $filteredB = $this->actingAs($admin)->get(route('admin.routes.index', ['user_id' => $salesB->id]));
        $filteredB->assertOk();
        $this->assertSame(['total' => 1, 'active' => 0, 'completed' => 1], $filteredB->viewData('stats'));
    }

    public function test_admin_route_created_by_tracks_admin_and_sales(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store($sales);

        $this->actingAs($admin)->postJson(route('admin.routes.store'), [
            'user_id' => $sales->id,
            'name' => 'Rute Dibuat Admin',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 30, 'notes' => null],
            ],
        ])->assertOk();

        // Tanggal berbeda agar tidak tergabung ke rute tanggal yang sama (aturan merge).
        $this->actingAs($sales)->postJson(route('route.store'), [
            'name' => 'Rute Dibuat Sales',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 30, 'notes' => null],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('routes', ['name' => 'Rute Dibuat Admin', 'created_by' => $admin->id]);
        $this->assertDatabaseHas('routes', ['name' => 'Rute Dibuat Sales', 'created_by' => $sales->id]);

        $response = $this->actingAs($admin)->get(route('admin.routes.index'));
        $response->assertOk();
        $response->assertSee('Dibuat oleh Admin');

        $responseSales = $this->actingAs($admin)->get(route('admin.routes.index', ['date' => now()->addDay()->toDateString()]));
        $responseSales->assertOk();
        $responseSales->assertSee('Dibuat oleh Sales');
    }

    public function test_admin_route_update_is_visible_to_assigned_sales(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store($sales);
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Lama',
            'date' => now()->addDays(1)->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->putJson(route('admin.routes.update', $route->id), [
            'user_id' => $sales->id,
            'name' => 'Rute Diubah Admin',
            'date' => now()->addDays(2)->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 30, 'notes' => null],
            ],
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('routes', ['id' => $route->id, 'name' => 'Rute Diubah Admin']);

        $salesResponse = $this->actingAs($sales)->get(route('route.index'));
        $salesResponse->assertOk();
        $salesResponse->assertSee('Rute Diubah Admin');
        $salesResponse->assertDontSee('Rute Lama');
    }

    public function test_admin_route_delete_removes_route_from_sales(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Akan Dihapus',
            'date' => now()->addDays(1)->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.routes.destroy', $route->id));

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertSoftDeleted('routes', ['id' => $route->id]);

        $salesResponse = $this->actingAs($sales)->get(route('route.index'));
        $salesResponse->assertOk();
        $salesResponse->assertDontSee('Rute Akan Dihapus');
    }

    public function test_admin_route_page_shows_routes_for_selected_date(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();

        Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Tanggal Dipilih',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Tanggal Lain',
            'date' => now()->subDays(1)->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.routes.index', ['date' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('Rute Tanggal Dipilih');
        $response->assertDontSee('Rute Tanggal Lain');
    }

    public function test_admin_can_edit_and_update_draft_route(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store($sales);
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Lama',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->get(route('admin.routes.edit', $route->id))->assertOk();

        $response = $this->actingAs($admin)->putJson(route('admin.routes.update', $route->id), [
            'user_id' => $sales->id,
            'name' => 'Rute Diperbarui',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 30, 'notes' => null],
            ],
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('routes', ['id' => $route->id, 'name' => 'Rute Diperbarui']);
    }

    public function test_admin_cannot_update_non_draft_route(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store();
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->putJson(route('admin.routes.update', $route->id), [
            'user_id' => $sales->id,
            'name' => 'Tidak Boleh',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 30, 'notes' => null],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('routes', ['id' => $route->id, 'name' => 'Rute Aktif']);
    }

    public function test_admin_can_delete_draft_route(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Dihapus',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.routes.destroy', $route->id));

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertSoftDeleted('routes', ['id' => $route->id]);
    }

    public function test_admin_cannot_delete_active_route(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.routes.destroy', $route->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('routes', ['id' => $route->id]);
    }

    public function test_admin_can_view_visit_monitoring(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store();
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Monitoring Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $sales->id,
            'store_id' => $store->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.visits.index'));

        $response->assertOk();
        $response->assertSee('Monitoring Kunjungan Sales');
        $response->assertSee('Rute Monitoring Hari Ini');
        $response->assertSee('Toko Sumber Tani');
        $response->assertSee($sales->name);
        $response->assertSee('Selesai');
    }

    public function test_admin_visit_monitoring_stats_reflect_real_stop_statuses(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $stores = collect([1, 2, 3, 4])->map(fn () => $this->store());

        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Statistik',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $completedStop = RouteStop::create(['route_id' => $route->id, 'store_id' => $stores[0]->id, 'sequence' => 1, 'status' => 'visited']);
        Visit::create([
            'user_id' => $sales->id, 'store_id' => $stores[0]->id, 'route_stop_id' => $completedStop->id, 'route_id' => $route->id,
            'status' => 'completed', 'check_in_at' => now()->subHours(2), 'check_out_at' => now()->subHour(),
        ]);

        $inProgressStop = RouteStop::create(['route_id' => $route->id, 'store_id' => $stores[1]->id, 'sequence' => 2, 'status' => 'visited']);
        Visit::create([
            'user_id' => $sales->id, 'store_id' => $stores[1]->id, 'route_stop_id' => $inProgressStop->id, 'route_id' => $route->id,
            'status' => 'in_progress', 'check_in_at' => now()->subMinutes(30),
        ]);

        RouteStop::create(['route_id' => $route->id, 'store_id' => $stores[2]->id, 'sequence' => 3, 'status' => 'pending']);
        RouteStop::create(['route_id' => $route->id, 'store_id' => $stores[3]->id, 'sequence' => 4, 'status' => 'skipped']);

        $response = $this->actingAs($admin)->get(route('admin.visits.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Total Kunjungan', '4']);
        $response->assertSeeInOrder(['Selesai', '1']);
        $response->assertSeeInOrder(['Sedang Berjalan', '1']);
        $response->assertSeeInOrder(['Belum Dikunjungi', '1']);
        $response->assertSeeInOrder(['Dilewati', '1']);
    }

    public function test_admin_visit_monitoring_filters_by_status(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Filter',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $pendingStore = Store::create([
            'code' => 'ST-PND', 'name' => 'Toko Pending', 'address' => 'Jl. A', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'status' => 'active',
        ]);
        $completedStore = Store::create([
            'code' => 'ST-SLS', 'name' => 'Toko Selesai', 'address' => 'Jl. B', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'status' => 'active',
        ]);

        RouteStop::create(['route_id' => $route->id, 'store_id' => $pendingStore->id, 'sequence' => 1, 'status' => 'pending']);
        $completedStop = RouteStop::create(['route_id' => $route->id, 'store_id' => $completedStore->id, 'sequence' => 2, 'status' => 'visited']);
        Visit::create([
            'user_id' => $sales->id, 'store_id' => $completedStore->id, 'route_stop_id' => $completedStop->id, 'route_id' => $route->id,
            'status' => 'completed', 'check_in_at' => now()->subHours(2), 'check_out_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.visits.index', ['status' => 'completed']));

        $response->assertOk();
        $response->assertSee('Toko Selesai');
        $response->assertDontSee('Toko Pending');
    }

    public function test_admin_visit_monitoring_filters_by_sales(): void
    {
        $admin = $this->adminUser();
        $salesA = $this->salesUser();
        $salesB = $this->salesUser();
        $storeA = Store::create([
            'code' => 'ST-A', 'name' => 'Toko Sales A', 'address' => 'Jl. A', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'status' => 'active',
        ]);
        $storeB = Store::create([
            'code' => 'ST-B', 'name' => 'Toko Sales B', 'address' => 'Jl. B', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'status' => 'active',
        ]);
        $routeA = Route::create(['user_id' => $salesA->id, 'name' => 'Rute Sales A', 'date' => now()->toDateString(), 'status' => 'active']);
        $routeB = Route::create(['user_id' => $salesB->id, 'name' => 'Rute Sales B', 'date' => now()->toDateString(), 'status' => 'active']);
        RouteStop::create(['route_id' => $routeA->id, 'store_id' => $storeA->id, 'sequence' => 1, 'status' => 'pending']);
        RouteStop::create(['route_id' => $routeB->id, 'store_id' => $storeB->id, 'sequence' => 1, 'status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.visits.index', ['user_id' => $salesA->id]));

        $response->assertOk();
        $response->assertSee('Toko Sales A');
        $response->assertDontSee('Toko Sales B');
    }

    public function test_admin_visit_monitoring_uses_selected_date(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store();
        $routeToday = Route::create(['user_id' => $sales->id, 'name' => 'Rute Hari Ini', 'date' => now()->toDateString(), 'status' => 'active']);
        $routeYesterday = Route::create(['user_id' => $sales->id, 'name' => 'Rute Kemarin', 'date' => now()->subDay()->toDateString(), 'status' => 'active']);
        RouteStop::create(['route_id' => $routeToday->id, 'store_id' => $store->id, 'sequence' => 1, 'status' => 'pending']);
        RouteStop::create(['route_id' => $routeYesterday->id, 'store_id' => $store->id, 'sequence' => 1, 'status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.visits.index'));

        $response->assertOk();
        $response->assertSee('Rute Hari Ini');
        $response->assertDontSee('Rute Kemarin');
    }

    public function test_admin_is_redirected_from_sales_visit_pages_to_admin_monitoring(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('visit.index'))->assertRedirect(route('admin.visits.index'));
        $this->actingAs($admin)->get(route('visit.history'))->assertRedirect(route('admin.visits.index'));
    }

    public function test_admin_can_view_transactions_report(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store();
        Visit::create([
            'user_id' => $sales->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'transaction_amount' => 2500000,
            'cash_received' => 1500000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.transactions'));

        $response->assertOk();
        $response->assertSee('Laporan Transaksi');
    }

    public function test_admin_can_export_transactions_excel(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();
        $store = $this->store();
        Visit::create([
            'user_id' => $sales->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'transaction_amount' => 2500000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export-excel', [
            'type' => 'transactions',
            'period' => 'this_week',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_admin_is_redirected_from_sales_route_pages_to_admin_pages(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('route.index'))->assertRedirect(route('admin.routes.index'));
        $this->actingAs($admin)->get(route('route.create'))->assertRedirect(route('admin.routes.create'));
        $this->actingAs($admin)->get(route('route.history'))->assertRedirect(route('admin.routes.report'));
    }

    public function test_admin_cannot_submit_sales_route_store(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->postJson(route('route.store'), [])
            ->assertStatus(403);
    }

    public function test_sales_cannot_view_another_sales_route_detail(): void
    {
        $salesA = $this->salesUser();
        $salesB = $this->salesUser();

        $route = Route::create([
            'user_id' => $salesA->id,
            'name' => 'Rute Rahasia A',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($salesB)->get(route('route.show', $route->id))->assertStatus(403);
        $this->actingAs($salesB)->get(route('route.map', $route->id))->assertStatus(403);

        $this->actingAs($salesA)->get(route('route.show', $route->id))->assertOk();
    }

    public function test_admin_can_view_any_sales_route_detail_for_monitoring(): void
    {
        $admin = $this->adminUser();
        $sales = $this->salesUser();

        $route = Route::create([
            'user_id' => $sales->id,
            'name' => 'Rute Monitoring Detail',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->get(route('route.show', $route->id))->assertOk();
        $this->actingAs($admin)->get(route('route.map', $route->id))->assertOk();
    }
}
