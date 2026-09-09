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

class SalesRouteDetailCheckInSkipBeforeStartTest extends TestCase
{
    use RefreshDatabase;

    private const SELFIE = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==';

    private function salesUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makeStore(): Store
    {
        return Store::create([
            'code' => 'ST-001',
            'name' => 'Toko Sumber Tani',
            'address' => 'Jl. Raya Bogor No. 45, Jakarta Timur',
            'city' => 'Jakarta Timur',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);
    }

    private function makeDraftRoute(User $user, Store $store): RouteStop
    {
        $route = Route::create([
            'user_id' => $user->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        return RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);
    }

    private function checkInPayload(RouteStop $stop, Store $store): array
    {
        return [
            'route_stop_id' => $stop->id,
            'route_id' => $stop->route_id,
            'store_id' => $store->id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Raya Bogor No. 45, Jakarta Timur',
            'maps_url' => 'https://www.google.com/maps?q=-6.2088,106.8456',
            'storefront_photo' => 'data:image/jpeg;base64,'.self::SELFIE,
            'selfie' => 'data:image/jpeg;base64,'.self::SELFIE,
            'delivered_goods' => 'Pupuk 5 sak',
            'cash_received' => 250000,
            'initial_notes' => 'Kunjungan pagi',
        ];
    }

    public function test_cannot_check_in_if_route_is_draft(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeDraftRoute($user, $store);

        $response = $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Rute belum dimulai. Silakan klik Mulai Rute terlebih dahulu sebelum melakukan check-in kunjungan.');

        $this->assertDatabaseMissing('visits', ['route_stop_id' => $stop->id]);
    }

    public function test_cannot_skip_store_if_route_is_draft(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeDraftRoute($user, $store);

        $response = $this->actingAs($user)->patchJson(route('route.stop.update', [$stop->route_id, $stop->id]), [
            'status' => 'skipped',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Rute belum dimulai. Silakan klik Mulai Rute terlebih dahulu sebelum melewati kunjungan.');

        $this->assertDatabaseHas('route_stops', [
            'id' => $stop->id,
            'status' => 'pending',
        ]);
    }
}
