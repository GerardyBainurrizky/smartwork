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

class VisitWorkflowTest extends TestCase
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

    private function makeRoute(User $user, Store $store): RouteStop
    {
        $route = Route::create([
            'user_id' => $user->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
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

    private function checkOutPayload(): array
    {
        return [
            'latitude' => -6.2090,
            'longitude' => 106.8460,
            'address' => 'Jl. Raya Bogor No. 45, Jakarta Timur',
            'maps_url' => 'https://www.google.com/maps?q=-6.2090,106.8460',
            'visit_result' => 'Order lanjutan',
            'delivered_goods_summary' => 'Pupuk 3 sak',
            'returned_goods' => 'Kardus kosong',
            'transaction_amount' => 500000,
            'payment_method' => 'tunai',
            'transaction_status' => 'paid',
            'final_notes' => 'Kunjungan selesai',
            'final_store_photo' => 'data:image/jpeg;base64,'.self::SELFIE,
            'selfie' => 'data:image/jpeg;base64,'.self::SELFIE,
        ];
    }

    public function test_check_in_from_route_plan_creates_visit_and_marks_stop(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($user, $store);

        $response = $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('visits', [
            'user_id' => $user->id,
            'route_stop_id' => $stop->id,
            'store_id' => $store->id,
            'status' => 'in_progress',
            'delivered_goods' => 'Pupuk 5 sak',
            'cash_received' => 250000,
        ]);

        $this->assertDatabaseHas('route_stops', ['id' => $stop->id, 'status' => 'visited']);
    }

    public function test_store_must_come_from_route_plan(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($user, $store);

        $otherStore = Store::create([
            'code' => 'ST-002',
            'name' => 'Toko Lain',
            'address' => 'Jl. Lain',
            'city' => 'Bogor',
            'province' => 'Jawa Barat',
            'status' => 'active',
        ]);

        $payload = $this->checkInPayload($stop, $store);
        $payload['store_id'] = $otherStore->id;

        $response = $this->actingAs($user)->postJson(route('visit.store'), $payload);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('visits', ['user_id' => $user->id]);
    }

    public function test_cannot_check_in_other_users_route(): void
    {
        $owner = $this->salesUser();
        $attacker = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($owner, $store);

        $response = $this->actingAs($attacker)->postJson(route('visit.store'), $this->checkInPayload($stop, $store));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('visits', ['user_id' => $attacker->id]);
    }

    public function test_duplicate_check_in_for_same_stop_is_rejected(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($user, $store);

        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store))->assertOk();

        $response = $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store));

        $response->assertStatus(409);
    }

    public function test_full_workflow_check_in_then_check_out_completes(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($user, $store);

        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store))->assertOk();

        $visit = Visit::where('user_id', $user->id)->first();

        $response = $this->actingAs($user)->postJson(route('visit.check-out', $visit->id), $this->checkOutPayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.route_id', $stop->route_id)
            ->assertJsonPath('data.redirect_url', route('route.show', $stop->route_id));

        $visit->refresh();
        $this->assertTrue($visit->isCompleted());
        $this->assertNotNull($visit->check_out_at);
        $this->assertEquals(500000, (float) $visit->transaction_amount);
        $this->assertEquals('Order lanjutan', $visit->visit_result);
        $this->assertEquals('tunai', $visit->payment_method);
    }

    public function test_check_out_requires_prior_check_in(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($user, $store);

        $visit = Visit::create([
            'user_id' => $user->id,
            'route_stop_id' => $stop->id,
            'route_id' => $stop->route_id,
            'store_id' => $store->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('visit.check-out', $visit->id), $this->checkOutPayload());
        $response->assertOk()->assertJsonPath('data.status', 'completed');
    }

    public function test_check_out_without_transaction_stores_null_amount_and_method(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($user, $store);

        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store))->assertOk();

        $visit = Visit::where('user_id', $user->id)->first();

        $payload = $this->checkOutPayload();
        $payload['transaction_status'] = 'none';
        $payload['transaction_amount'] = null;
        $payload['payment_method'] = null;

        $response = $this->actingAs($user)->postJson(route('visit.check-out', $visit->id), $payload);

        $response->assertOk()->assertJsonPath('data.status', 'completed');

        $visit->refresh();
        $this->assertEquals('none', $visit->transaction_status);
        $this->assertNull($visit->transaction_amount);
        $this->assertNull($visit->payment_method);
    }

    public function test_check_out_with_transaction_requires_method(): void
    {
        $user = $this->salesUser();
        $store = $this->makeStore();
        $stop = $this->makeRoute($user, $store);

        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store))->assertOk();

        $visit = Visit::where('user_id', $user->id)->first();

        $payload = $this->checkOutPayload();
        $payload['payment_method'] = null;
        $payload['new_payment_method'] = null;
        $payload['new_tx_total'] = 100000;
        $payload['new_tx_paid'] = 50000;

        $this->actingAs($user)->postJson(route('visit.check-out', $visit->id), $payload)->assertStatus(422);
    }

    public function test_check_out_normalizes_thousands_separator_dots(): void
    {
        $cases = [
            '10.000' => 10000,
            '100.000' => 100000,
            '1.000.000' => 1000000,
            '10000' => 10000,
            '1000000' => 1000000,
        ];

        $i = 0;

        foreach ($cases as $input => $expected) {
            $i++;
            $user = $this->salesUser();
            $store = Store::create([
                'code' => 'ST-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Toko Sumber Tani',
                'address' => 'Jl. Raya Bogor No. 45, Jakarta Timur',
                'city' => 'Jakarta Timur',
                'province' => 'DKI Jakarta',
                'status' => 'active',
            ]);
            $stop = $this->makeRoute($user, $store);

            $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stop, $store))->assertOk();

            $visit = Visit::where('user_id', $user->id)->first();

            $payload = $this->checkOutPayload();
            $payload['transaction_amount'] = $input;

            $response = $this->actingAs($user)->postJson(route('visit.check-out', $visit->id), $payload);

            $response->assertOk()->assertJsonPath('data.status', 'completed');

            $visit->refresh();
            $this->assertSame($expected, (int) $visit->transaction_amount, "Input {$input} harus tersimpan sebagai {$expected}");
        }
    }

    public function test_check_out_dynamically_redirects_sales_to_specific_route(): void
    {
        $user = $this->salesUser();
        $storeA = $this->makeStore();
        $storeB = Store::create([
            'code' => 'ST-002',
            'name' => 'Toko Dua',
            'address' => 'Jl. Kebon Jeruk',
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);

        $routeA = Route::create([
            'user_id' => $user->id,
            'name' => 'Rute A',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stopA = RouteStop::create([
            'route_id' => $routeA->id,
            'store_id' => $storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $routeB = Route::create([
            'user_id' => $user->id,
            'name' => 'Rute B',
            'date' => now()->addDay()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stopB = RouteStop::create([
            'route_id' => $routeB->id,
            'store_id' => $storeB->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Check in stop A -> Visit A
        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stopA, $storeA))->assertOk();
        $visitA = Visit::where('route_stop_id', $stopA->id)->first();

        // Check-out Visit A -> Redirect ke Route A
        $resA = $this->actingAs($user)->postJson(route('visit.check-out', $visitA->id), $this->checkOutPayload());
        $resA->assertOk()
            ->assertJsonPath('data.route_id', $routeA->id)
            ->assertJsonPath('data.redirect_url', route('route.show', $routeA->id));

        // Setelah Visit A selesai, Check in stop B -> Visit B
        $this->actingAs($user)->postJson(route('visit.store'), $this->checkInPayload($stopB, $storeB))->assertOk();
        $visitB = Visit::where('route_stop_id', $stopB->id)->first();

        // Check-out Visit B -> Redirect ke Route B
        $resB = $this->actingAs($user)->postJson(route('visit.check-out', $visitB->id), $this->checkOutPayload());
        $resB->assertOk()
            ->assertJsonPath('data.route_id', $routeB->id)
            ->assertJsonPath('data.redirect_url', route('route.show', $routeB->id));
    }

    public function test_driver_sequential_delivery_blocking_and_multiple_photos_checkout(): void
    {
        $roleDriver = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $driver = User::factory()->create();
        $driver->assignRole($roleDriver);

        $storeA = Store::create([
            'code' => 'ST-DRV-1',
            'name' => 'Toko Kirim A',
            'address' => 'Jl. Pengiriman No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);

        $storeB = Store::create([
            'code' => 'ST-DRV-2',
            'name' => 'Toko Kirim B',
            'address' => 'Jl. Pengiriman No. 2',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);

        $route = Route::create([
            'user_id' => $driver->id,
            'name' => 'Rute Pengiriman Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stopA = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $stopB = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        // 1. Driver Check In Toko A (1 Foto + Catatan Awal) -> BERHASIL
        $resCheckInA = $this->actingAs($driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stopA->id,
            'route_id' => $route->id,
            'store_id' => $storeA->id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Pengiriman No. 1',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'initial_notes' => 'Tiba di Toko A, muatan siap diturunkan',
        ]);
        $resCheckInA->assertOk();

        $visitA = Visit::where('route_stop_id', $stopA->id)->first();
        $this->assertNotNull($visitA);
        $this->assertEquals('in_progress', $visitA->status);
        $this->assertEquals('Tiba di Toko A, muatan siap diturunkan', $visitA->initial_notes);

        // 2. Driver mencoba Check In Toko B saat Toko A belum Check Out -> DITOLAK (422)
        $resCheckInB_Blocked = $this->actingAs($driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stopB->id,
            'route_id' => $route->id,
            'store_id' => $storeB->id,
            'latitude' => -6.2100,
            'longitude' => 106.8500,
            'address' => 'Jl. Pengiriman No. 2',
            'maps_url' => 'https://maps.google.com/?q=-6.2100,106.8500',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'initial_notes' => 'Coba check in toko B duluan',
        ]);
        $resCheckInB_Blocked->assertStatus(422);
        $this->assertStringContainsString('Toko Kirim A', $resCheckInB_Blocked->json('message'));

        // 3. Driver Check Out Toko A dengan Multiple Foto Barang -> BERHASIL
        $dummyPhoto1 = 'data:image/jpeg;base64,' . self::SELFIE;
        $dummyPhoto2 = 'data:image/jpeg;base64,' . self::SELFIE;
        $dummyPhoto3 = 'data:image/jpeg;base64,' . self::SELFIE;

        $resCheckOutA = $this->actingAs($driver)->postJson(route('visit.check-out', $visitA->id), [
            'latitude' => -6.2089,
            'longitude' => 106.8457,
            'address' => 'Jl. Pengiriman No. 1 Selesai',
            'maps_url' => 'https://maps.google.com/?q=-6.2089,106.8457',
            'visit_result' => 'Barang 10 karton diterima lengkap tanpa cacat oleh pemilik toko.',
            'transaction_status' => 'none',
            'photos' => [$dummyPhoto1, $dummyPhoto2, $dummyPhoto3],
        ]);
        $resCheckOutA->assertOk();

        $visitA->refresh();
        $this->assertEquals('completed', $visitA->status);
        $this->assertEquals('Barang 10 karton diterima lengkap tanpa cacat oleh pemilik toko.', $visitA->visit_result);
        $this->assertCount(3, $visitA->checkoutPhotos);

        // 4. Setelah Toko A selesai Check Out, Driver sekarang BISA Check In ke Toko B -> BERHASIL
        $resCheckInB_Success = $this->actingAs($driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stopB->id,
            'route_id' => $route->id,
            'store_id' => $storeB->id,
            'latitude' => -6.2100,
            'longitude' => 106.8500,
            'address' => 'Jl. Pengiriman No. 2',
            'maps_url' => 'https://maps.google.com/?q=-6.2100,106.8500',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'initial_notes' => 'Tiba di Toko B',
        ]);
        $resCheckInB_Success->assertOk();

        $visitB = Visit::where('route_stop_id', $stopB->id)->first();
        $this->assertNotNull($visitB);
        $this->assertEquals('in_progress', $visitB->status);
    }

    public function test_driver_cannot_access_other_driver_delivery(): void
    {
        $roleDriver = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $driver1 = User::factory()->create();
        $driver1->assignRole($roleDriver);

        $driver2 = User::factory()->create();
        $driver2->assignRole($roleDriver);

        $store = Store::create([
            'code' => 'ST-ISO-1',
            'name' => 'Toko Isolasi',
            'address' => 'Jl. Isolasi',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);

        $route1 = Route::create([
            'user_id' => $driver1->id,
            'name' => 'Rute Driver 1',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $route1->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Driver 2 mencoba Check In ke stop Driver 1 -> DITOLAK (403)
        $response = $this->actingAs($driver2)->postJson(route('visit.store'), [
            'route_stop_id' => $stop1->id,
            'route_id' => $route1->id,
            'store_id' => $store->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Alamat',
            'maps_url' => 'https://maps.google.com',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
        ]);

        $response->assertStatus(403);

        // Driver 2 mencoba akses form check in stop Driver 1 -> DITOLAK (403)
        $formRes = $this->actingAs($driver2)->get(route('visit.check-in-form', $stop1->id));
        $formRes->assertStatus(403);
    }

    public function test_driver_check_in_form_redirects_with_error_when_prior_delivery_active(): void
    {
        $roleDriver = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $driver = User::factory()->create();
        $driver->assignRole($roleDriver);

        $storeA = Store::create([
            'code' => 'ST-SEQ-1',
            'name' => 'Toko Kirim 1',
            'address' => 'Jl. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);

        $storeB = Store::create([
            'code' => 'ST-SEQ-2',
            'name' => 'Toko Kirim 2',
            'address' => 'Jl. 2',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);

        $route = Route::create([
            'user_id' => $driver->id,
            'name' => 'Rute Berurutan',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stopA = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $stopB = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        // 1. Check in Toko A
        $this->actingAs($driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stopA->id,
            'route_id' => $route->id,
            'store_id' => $storeA->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jl. 1',
            'maps_url' => 'https://maps.google.com',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
        ])->assertOk();

        // 2. Akses form Check In Toko B saat Toko A masih in_progress -> Redirect ke route.show dengan session error
        $formResB = $this->actingAs($driver)->get(route('visit.check-in-form', $stopB->id));
        $formResB->assertRedirect(route('route.show', $route->id));
        $formResB->assertSessionHas('error');

        // 3. Check out Toko A
        $visitA = Visit::where('route_stop_id', $stopA->id)->first();
        $this->actingAs($driver)->postJson(route('visit.check-out', $visitA->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jl. 1 Selesai',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Terkirim',
            'transaction_status' => 'none',
            'photos' => ['data:image/jpeg;base64,' . self::SELFIE],
        ])->assertOk();

        // 4. Sekarang akses form Check In Toko B -> BERHASIL (200 OK)
        $formResBSuccess = $this->actingAs($driver)->get(route('visit.check-in-form', $stopB->id));
        $formResBSuccess->assertOk();
    }
}
