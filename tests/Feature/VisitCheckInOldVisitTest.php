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

class VisitCheckInOldVisitTest extends TestCase
{
    use RefreshDatabase;

    private const SELFIE = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==';

    private const OLD_DATE = '2026-08-14';

    private const NEW_DATE = '2026-08-16';

    private function salesUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makeStore(string $name): Store
    {
        return Store::create([
            'code' => 'ST-'.str_pad((string) mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'name' => $name,
            'address' => 'Jl. Test No. 1, Jakarta',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);
    }

    private function makeStop(User $user, Store $store, string $date): RouteStop
    {
        $route = Route::create([
            'user_id' => $user->id,
            'name' => 'Rute '.$date,
            'date' => $date,
            'status' => 'active',
            'started_at' => now(),
        ]);

        return RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);
    }

    private function makeOldVisit(User $user, RouteStop $stop, Store $store): Visit
    {
        $stop->update(['status' => 'visited']);

        return Visit::create([
            'user_id' => $user->id,
            'route_stop_id' => $stop->id,
            'route_id' => $stop->route_id,
            'store_id' => $store->id,
            'status' => 'in_progress',
            'check_in_at' => self::OLD_DATE.' 08:00:00',
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
            'address' => 'Jl. Test No. 1, Jakarta',
            'maps_url' => 'https://www.google.com/maps?q=-6.2088,106.8456',
            'storefront_photo' => 'data:image/jpeg;base64,'.self::SELFIE,
            'selfie' => 'data:image/jpeg;base64,'.self::SELFIE,
            'delivered_goods' => 'Barang baru',
            'cash_received' => null,
            'initial_notes' => 'Check in hari ini',
        ];
    }

    private function checkOutPayload(): array
    {
        return [
            'latitude' => -6.2090,
            'longitude' => 106.8460,
            'address' => 'Jl. Test No. 1, Jakarta',
            'maps_url' => 'https://www.google.com/maps?q=-6.2090,106.8460',
            'visit_result' => 'Kunjungan selesai',
            'delivered_goods_summary' => 'Barang terkirim',
            'returned_goods' => null,
            'transaction_amount' => null,
            'payment_method' => null,
            'transaction_status' => 'none',
            'final_notes' => 'Selesai',
            'final_store_photo' => 'data:image/jpeg;base64,'.self::SELFIE,
            'selfie' => 'data:image/jpeg;base64,'.self::SELFIE,
        ];
    }

    public function test_scenario_1_old_in_progress_visit_does_not_redirect_check_in_form(): void
    {
        $user = $this->salesUser();
        $oldStore = $this->makeStore('Toko Lama');
        $newStore = $this->makeStore('Toko Baru');

        $oldStop = $this->makeStop($user, $oldStore, self::OLD_DATE);
        $this->makeOldVisit($user, $oldStop, $oldStore);

        $newStop = $this->makeStop($user, $newStore, self::NEW_DATE);

        $response = $this->actingAs($user)->get(route('visit.check-in-form', $newStop->id));

        $response->assertOk();
        $response->assertViewIs('visit.check-in');
        $response->assertViewHas('routeStop', fn (RouteStop $stop) => $stop->id === $newStop->id);
    }

    public function test_scenario_2_check_in_today_succeeds_even_with_old_in_progress_visit(): void
    {
        $user = $this->salesUser();
        $oldStore = $this->makeStore('Toko Lama');
        $newStore = $this->makeStore('Toko Baru');

        $oldStop = $this->makeStop($user, $oldStore, self::OLD_DATE);
        $this->makeOldVisit($user, $oldStop, $oldStore);

        $newStop = $this->makeStop($user, $newStore, self::NEW_DATE);

        $response = $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($newStop, $newStore));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('visits', [
            'user_id' => $user->id,
            'route_stop_id' => $newStop->id,
            'route_id' => $newStop->route_id,
            'store_id' => $newStore->id,
            'status' => 'in_progress',
        ]);

        $newVisit = Visit::where('route_stop_id', $newStop->id)->first();
        $this->assertNotNull($newVisit->check_in_at);
    }

    public function test_scenario_3_duplicate_check_in_same_stop_still_rejected(): void
    {
        $user = $this->salesUser();
        $newStore = $this->makeStore('Toko Baru');

        $newStop = $this->makeStop($user, $newStore, self::NEW_DATE);

        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($newStop, $newStore))->assertOk();

        $response = $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($newStop, $newStore));

        $response->assertStatus(409);
        $this->assertSame(1, Visit::where('route_stop_id', $newStop->id)->count());
    }

    public function test_scenario_4_old_visit_stays_intact_after_new_check_in(): void
    {
        $user = $this->salesUser();
        $oldStore = $this->makeStore('Toko Lama');
        $newStore = $this->makeStore('Toko Baru');

        $oldStop = $this->makeStop($user, $oldStore, self::OLD_DATE);
        $oldVisit = $this->makeOldVisit($user, $oldStop, $oldStore);

        $newStop = $this->makeStop($user, $newStore, self::NEW_DATE);

        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($newStop, $newStore))->assertOk();

        $oldVisit->refresh();

        $this->assertDatabaseHas('visits', ['id' => $oldVisit->id]);
        $this->assertSame('in_progress', $oldVisit->status);
        $this->assertSame(self::OLD_DATE.' 08:00:00', $oldVisit->check_in_at->format('Y-m-d H:i:s'));
        $this->assertNull($oldVisit->check_out_at);
        $this->assertSame($oldStop->id, $oldVisit->route_stop_id);
        $this->assertSame($oldStop->route_id, $oldVisit->route_id);
        $this->assertSame($oldStore->id, $oldVisit->store_id);
    }

    public function test_scenario_5_old_visit_can_still_be_checked_out(): void
    {
        $user = $this->salesUser();
        $oldStore = $this->makeStore('Toko Lama');
        $newStore = $this->makeStore('Toko Baru');

        $oldStop = $this->makeStop($user, $oldStore, self::OLD_DATE);
        $oldVisit = $this->makeOldVisit($user, $oldStop, $oldStore);

        $newStop = $this->makeStop($user, $newStore, self::NEW_DATE);
        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($newStop, $newStore))->assertOk();

        $form = $this->actingAs($user)->get(route('visit.check-out-form', $oldVisit->id));
        $form->assertOk();
        $form->assertViewIs('visit.check-out');

        $response = $this->actingAs($user)->postJson(route('visit.check-out', $oldVisit->id), $this->checkOutPayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'completed');

        $oldVisit->refresh();
        $this->assertTrue($oldVisit->isCompleted());
        $this->assertNotNull($oldVisit->check_out_at);

        $newVisit = Visit::where('route_stop_id', $newStop->id)->first();
        $this->assertSame('in_progress', $newVisit->status);
    }

    public function test_scenario_6_check_out_today_visit_processes_today_visit_only(): void
    {
        $user = $this->salesUser();
        $oldStore = $this->makeStore('Toko Lama');
        $newStore = $this->makeStore('Toko Baru');

        $oldStop = $this->makeStop($user, $oldStore, self::OLD_DATE);
        $oldVisit = $this->makeOldVisit($user, $oldStop, $oldStore);

        $newStop = $this->makeStop($user, $newStore, self::NEW_DATE);
        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($newStop, $newStore))->assertOk();

        $newVisit = Visit::where('route_stop_id', $newStop->id)->first();

        $response = $this->actingAs($user)->postJson(route('visit.check-out', $newVisit->id), $this->checkOutPayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'completed');

        $newVisit->refresh();
        $this->assertTrue($newVisit->isCompleted());
        $this->assertNotNull($newVisit->check_out_at);

        $oldVisit->refresh();
        $this->assertSame('in_progress', $oldVisit->status);
        $this->assertNull($oldVisit->check_out_at);
    }
}
