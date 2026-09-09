<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoutePlanTest extends TestCase
{
    use RefreshDatabase;

    private function salesUser(): User
    {
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('sales');
        return $user;
    }

    private function makeStores(int $count = 3): \Illuminate\Support\Collection
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->first();

        return collect([
            ['name' => 'Toko Sumber Tani', 'city' => 'Jakarta Timur', 'province' => 'DKI Jakarta'],
            ['name' => 'UD Makmur Jaya', 'city' => 'Jakarta Selatan', 'province' => 'DKI Jakarta'],
            ['name' => 'Toko Agro Lestari', 'city' => 'Bekasi', 'province' => 'Jawa Barat'],
        ])->take($count)->map(fn ($s, $i) => Store::create([
            'code' => 'ST-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
            'name' => $s['name'],
            'address' => 'Jl. Test No. ' . ($i + 1),
            'city' => $s['city'],
            'province' => $s['province'],
            'status' => 'active',
            'sales_penanggung_jawab_id' => $sales?->id,
        ]));
    }

    public function test_sales_can_view_create_route_page_with_active_stores(): void
    {
        $user = $this->salesUser();
        $this->makeStores();

        $response = $this->actingAs($user)->get(route('route.create'));

        $response->assertOk()->assertSee('Toko Sumber Tani')->assertSee('UD Makmur Jaya');
    }

    public function test_sales_can_create_route_with_selected_stores_and_notes(): void
    {
        $user = $this->salesUser();
        $stores = $this->makeStores();

        $storeIds = $stores->pluck('id')->all();

        $response = $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Kunjungan Jakarta',
            'date' => now()->addDay()->toDateString(),
            'notes' => 'Kunjungi semua toko utama',
            'stops' => [
                ['store_id' => $storeIds[0], 'sequence' => 1, 'estimated_duration_minutes' => 30],
                ['store_id' => $storeIds[1], 'sequence' => 2, 'estimated_duration_minutes' => 45],
                ['store_id' => $storeIds[2], 'sequence' => 3, 'estimated_duration_minutes' => 20],
            ],
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('routes', [
            'user_id' => $user->id,
            'name' => 'Rute Kunjungan Jakarta',
            'notes' => 'Kunjungi semua toko utama',
            'status' => 'draft',
        ]);

        $route = Route::where('user_id', $user->id)->where('name', 'Rute Kunjungan Jakarta')->first();

        $this->assertCount(3, $route->stops);

        $stops = $route->stops()->orderBy('sequence')->get();
        $this->assertEquals($storeIds[0], $stops[0]->store_id);
        $this->assertEquals(1, $stops[0]->sequence);
        $this->assertEquals($storeIds[1], $stops[1]->store_id);
        $this->assertEquals(2, $stops[1]->sequence);
        $this->assertEquals($storeIds[2], $stops[2]->store_id);
        $this->assertEquals(3, $stops[2]->sequence);
        $this->assertEquals(45, $stops[1]->estimated_duration_minutes);
    }

    public function test_route_requires_at_least_one_store(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Tanpa Toko',
            'date' => now()->addDay()->toDateString(),
            'stops' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_inactive_stores_are_not_selected_in_create_page(): void
    {
        $user = $this->salesUser();
        $this->makeStores(2);
        Store::create([
            'code' => 'ST-099',
            'name' => 'Toko Nonaktif',
            'address' => 'Jl. Lama',
            'city' => 'Depok',
            'province' => 'Jawa Barat',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($user)->get(route('route.create'));

        $response->assertOk()->assertSee('Toko Sumber Tani')->assertDontSee('Toko Nonaktif');
    }

    public function test_route_requires_sequence_for_each_store(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStores(1)->first();

        $response = $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Tanpa Urutan',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'estimated_duration_minutes' => 30],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('stops.0.sequence', $response->json('errors'));
    }

    public function test_route_rejects_duplicate_sequence(): void
    {
        $user = $this->salesUser();
        $stores = $this->makeStores(2);

        $response = $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Urutan Ganda',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $stores[0]->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
                ['store_id' => $stores[1]->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame('Urutan kunjungan harus berurutan dari 1 sampai 2.', $response->json('message'));
    }

    public function test_route_requires_estimated_duration_for_each_store(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStores(1)->first();

        $response = $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Tanpa Durasi',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('stops.0.estimated_duration_minutes', $response->json('errors'));
    }

    public function test_route_rejects_non_numeric_or_zero_duration(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStores(1)->first();

        $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Durasi Teks',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 'abc'],
            ],
        ])->assertStatus(422);

        $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Durasi Nol',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 0],
            ],
        ])->assertStatus(422);
    }

    public function test_sales_can_skip_a_pending_stop(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStores(1)->first();

        $this->actingAs($user)->postJson(route('route.store'), [
            'name' => 'Rute Skip',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 15],
            ],
        ])->assertOk();

        $route = Route::where('user_id', $user->id)->where('name', 'Rute Skip')->first();
        $route->update(['status' => 'active', 'started_at' => now()]);
        $stop = $route->stops()->first();

        $response = $this->actingAs($user)
            ->patchJson(route('route.stop.update', [$route->id, $stop->id]), [
                'status' => 'skipped',
                'notes' => 'Toko tutup hari ini',
                'photo' => 'data:image/jpeg;base64,' . base64_encode('evidence'),
            ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('route_stops', ['id' => $stop->id, 'status' => 'skipped']);
    }

    public function test_user_cannot_update_stop_of_another_users_route(): void
    {
        $owner = $this->salesUser();
        $attacker = $this->salesUser();
        $store = $this->makeStores(1)->first();

        $this->actingAs($owner)->postJson(route('route.store'), [
            'name' => 'Rute Milik Owner',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $store->id, 'sequence' => 1, 'estimated_duration_minutes' => 15],
            ],
        ])->assertOk();

        $route = Route::where('user_id', $owner->id)->where('name', 'Rute Milik Owner')->first();
        $stop = $route->stops()->first();

        $response = $this->actingAs($attacker)
            ->patchJson(route('route.stop.update', [$route->id, $stop->id]), ['status' => 'visited']);

        $response->assertStatus(404);
        $this->assertDatabaseHas('route_stops', ['id' => $stop->id, 'status' => 'pending']);
    }

    public function test_recent_routes_history_is_paginated_ten_per_page(): void
    {
        $user = $this->salesUser();

        for ($i = 1; $i <= 25; $i++) {
            Route::create([
                'user_id' => $user->id,
                'name' => 'Rute Historis ' . $i,
                'date' => now()->subDays($i)->toDateString(),
                'status' => 'completed',
            ]);
        }

        $page1 = $this->actingAs($user)->get(route('route.index'));
        $page1->assertOk();

        $recent = $page1->viewData('recentRoutes');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $recent);
        $this->assertSame(10, $recent->count());
        $this->assertSame(25, $recent->total());
        $this->assertSame(now()->subDay()->toDateString(), $recent->first()->date->toDateString());
        $this->assertSame(1, $recent->firstItem());
        $this->assertSame(10, $recent->lastItem());

        $page1->assertSee('Menampilkan 1–10 dari 25 rute');
        $page1->assertSee('Sebelumnya');
        $page1->assertSee('Berikutnya');

        $page1Ids = $recent->pluck('id')->all();

        $page2 = $this->actingAs($user)->get(route('route.index', ['page' => 2]));
        $page2->assertOk();
        $recent2 = $page2->viewData('recentRoutes');
        $this->assertSame(10, $recent2->count());
        $this->assertSame(25, $recent2->total());
        $this->assertSame(11, $recent2->firstItem());
        $this->assertSame(20, $recent2->lastItem());

        $page2Ids = $recent2->pluck('id')->all();
        $this->assertCount(0, array_intersect($page1Ids, $page2Ids), 'Tidak boleh ada data duplikat antar halaman.');

        $page3 = $this->actingAs($user)->get(route('route.index', ['page' => 3]));
        $page3->assertOk();
        $recent3 = $page3->viewData('recentRoutes');
        $this->assertSame(5, $recent3->count());
        $this->assertSame(21, $recent3->firstItem());
        $this->assertSame(25, $recent3->lastItem());
    }

    public function test_recent_routes_pagination_is_hidden_when_ten_or_fewer(): void
    {
        $user = $this->salesUser();

        for ($i = 1; $i <= 8; $i++) {
            Route::create([
                'user_id' => $user->id,
                'name' => 'Rute Kecil ' . $i,
                'date' => now()->subDays($i)->toDateString(),
                'status' => 'completed',
            ]);
        }

        $response = $this->actingAs($user)->get(route('route.index'));
        $response->assertOk();

        $recent = $response->viewData('recentRoutes');
        $this->assertSame(8, $recent->total());
        $this->assertFalse($recent->hasPages(), 'Pagination tidak perlu ditampilkan bila data maksimal 10.');
        $response->assertDontSee('Sebelumnya');
        $response->assertDontSee('Berikutnya');
        $response->assertSee('Menampilkan 1–8 dari 8 rute');
    }
}