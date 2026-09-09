<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDateIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private Store $storeA;
    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Ahmad']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko A',
            'code' => 'TKA-01',
            'address' => 'Jl. A No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko B',
            'code' => 'TKB-02',
            'address' => 'Jl. B No. 2',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    public function test_past_uncompleted_visit_does_not_appear_as_today_active_visit_on_visit_index(): void
    {
        // 1. Visit past date (28 August 2026) in_progress
        $pastDate = '2026-08-28';
        $pastRoute = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute 28 Agustus',
            'date' => $pastDate,
            'status' => 'active',
            'started_at' => Carbon::parse("{$pastDate} 08:00:00"),
        ]);
        $pastStop = RouteStop::create([
            'route_id' => $pastRoute->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $pastVisit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $pastStop->id,
            'route_id' => $pastRoute->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => Carbon::parse("{$pastDate} 09:00:00"),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jl. A No. 1',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'path/selfie.jpg',
        ]);

        // Current simulated date: 1 September 2026
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00'));

        $todayRoute = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute 1 September',
            'date' => '2026-09-01',
            'status' => 'active',
            'started_at' => Carbon::parse('2026-09-01 08:00:00'),
        ]);
        $todayStop = RouteStop::create([
            'route_id' => $todayRoute->id,
            'store_id' => $this->storeB->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Access /visit page as Sales
        $response = $this->actingAs($this->sales)->get(route('visit.index'));
        $response->assertOk();

        // Must NOT show banner "Kunjungan Sedang Berlangsung" for Toko A from past date
        $response->assertDontSee('Kunjungan Sedang Berlangsung');

        // Past visit remains accessible directly at /visit/{id} and can be completed
        $showRes = $this->actingAs($this->sales)->get(route('visit.show', $pastVisit->id));
        $showRes->assertOk();
        $showRes->assertSee('Check Out Kunjungan');

        // Check Out the past visit on 1 September
        $dummySelfie = 'data:image/jpeg;base64,' . base64_encode('fake');
        $checkoutRes = $this->actingAs($this->sales)->postJson(route('visit.check-out', $pastVisit->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jl. A No. 1 Selesai',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Selesai tertunda',
            'transaction_status' => 'none',
            'selfie' => $dummySelfie,
            'final_store_photo' => $dummySelfie,
        ]);
        $checkoutRes->assertOk();
        $checkoutRes->assertJsonPath('data.redirect_url', route('route.show', $pastRoute->id));

        // Verify past visit keeps original check_in_at date
        $pastVisit->refresh();
        $this->assertEquals('completed', $pastVisit->status);
        $this->assertEquals('2026-08-28 09:00:00', $pastVisit->check_in_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-09-01 10:00:00', $pastVisit->check_out_at->format('Y-m-d H:i:s'));

        // Today's stop can be checked in without being blocked by past visit
        $todayCheckIn = $this->actingAs($this->sales)->postJson(route('visit.store'), [
            'route_stop_id' => $todayStop->id,
            'route_id' => $todayRoute->id,
            'store_id' => $this->storeB->id,
            'latitude' => -6.21,
            'longitude' => 106.81,
            'address' => 'Jl. B No. 2',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $dummySelfie,
            'selfie' => $dummySelfie,
        ]);
        $todayCheckIn->assertOk();

        $todayVisit = Visit::where('route_stop_id', $todayStop->id)->first();
        $this->assertNotNull($todayVisit);
        $this->assertEquals('in_progress', $todayVisit->status);

        Carbon::setTestNow(); // reset test time
    }

    public function test_past_uncompleted_sales_visit_does_not_block_sales_today_check_in(): void
    {
        $pastDate = '2026-08-28';
        $pastRoute = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute 28 Agustus',
            'date' => $pastDate,
            'status' => 'active',
            'started_at' => Carbon::parse("{$pastDate} 08:00:00"),
        ]);
        $pastStop = RouteStop::create([
            'route_id' => $pastRoute->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $pastStop->id,
            'route_id' => $pastRoute->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => Carbon::parse("{$pastDate} 09:00:00"),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jl. A No. 1',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'path/selfie.jpg',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-01 09:00:00'));

        $todayRoute = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute 1 September',
            'date' => '2026-09-01',
            'status' => 'active',
            'started_at' => now(),
        ]);
        $todayStop = RouteStop::create([
            'route_id' => $todayRoute->id,
            'store_id' => $this->storeB->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Sales check-in form for today is accessible
        $formRes = $this->actingAs($this->sales)->get(route('visit.check-in-form', $todayStop->id));
        $formRes->assertOk();

        // Sales check-in succeeds
        $dummySelfie = 'data:image/jpeg;base64,' . base64_encode('fake');
        $checkInRes = $this->actingAs($this->sales)->postJson(route('visit.store'), [
            'route_stop_id' => $todayStop->id,
            'route_id' => $todayRoute->id,
            'store_id' => $this->storeB->id,
            'latitude' => -6.21,
            'longitude' => 106.81,
            'address' => 'Jl. B No. 2',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $dummySelfie,
            'selfie' => $dummySelfie,
        ]);
        $checkInRes->assertOk();

        Carbon::setTestNow();
    }
}
