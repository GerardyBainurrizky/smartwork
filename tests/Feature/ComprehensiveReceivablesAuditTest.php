<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreReceivable;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveReceivablesAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('admin');

        $this->sales1 = User::factory()->create(['name' => 'Sales 1']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales 2']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver']);
        $this->driver->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Subur',
            'code' => 'SB-01',
            'address' => 'Jl. Mawar',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Makmur',
            'code' => 'MK-02',
            'address' => 'Jl. Melati',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);
    }

    public function test_admin_and_super_admin_can_add_opening_balance_to_store(): void
    {
        // Admin creates opening balance using formatted input (e.g. "250.000")
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.opening-balance.store'), [
            'store_id' => $this->store1->id,
            'amount' => '250.000',
            'transaction_date' => now()->toDateString(),
            'notes' => 'Saldo piutang existing perusahaan',
        ]);
        $res->assertCreated();

        $this->assertSame('250000.00', StoreReceivableService::balanceForStore($this->store1->id));

        // Sales cannot add opening balance
        $resSales = $this->actingAs($this->sales1)->postJson(route('admin.receivables.opening-balance.store'), [
            'store_id' => $this->store1->id,
            'amount' => 100000,
        ]);
        $resSales->assertRedirect();

        // Driver cannot add opening balance
        $resDriver = $this->actingAs($this->driver)->postJson(route('admin.receivables.opening-balance.store'), [
            'store_id' => $this->store1->id,
            'amount' => 100000,
        ]);
        $resDriver->assertRedirect();
    }

    public function test_sales_check_out_with_piutang_payment_deducts_balance_correctly(): void
    {
        // 1. Set opening balance Rp100.000
        StoreReceivableService::addOpeningBalance([
            'store_id' => $this->store1->id,
            'amount' => 100000,
        ], $this->admin);

        // 2. Sales Check-in
        $route = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Sales',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jl. Mawar',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'path/selfie.jpg',
        ]);

        // 3. Check-out with 'piutang' payment Rp40.000
        $dummySelfie = 'data:image/jpeg;base64,' . base64_encode('fake');
        $resCheckOut = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jl. Mawar',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Bertemu pemilik toko, terima pembayaran sebagian',
            'transaction_status' => 'piutang',
            'transaction_amount' => 0,
            'old_payment_amount' => 40000,
            'old_payment_method' => 'tunai',
            'payment_method' => 'tunai',
            'selfie' => $dummySelfie,
            'final_store_photo' => $dummySelfie,
        ]);
        $resCheckOut->assertOk();

        // 4. Verify balance is now Rp60.000
        $this->assertSame('60000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    public function test_sales_check_out_with_payment_exceeding_balance_is_rejected(): void
    {
        // 1. Set opening balance Rp50.000
        StoreReceivableService::addOpeningBalance([
            'store_id' => $this->store1->id,
            'amount' => 50000,
        ], $this->admin);

        // 2. Sales Check-in
        $route = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Sales',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jl. Mawar',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'path/selfie.jpg',
        ]);

        // 3. Attempt payment Rp60.000 (> Rp50.000) -> Rejected 422
        $dummySelfie = 'data:image/jpeg;base64,' . base64_encode('fake');
        $resCheckOut = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jl. Mawar',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Bayar lebih',
            'transaction_status' => 'piutang',
            'transaction_amount' => 60000,
            'payment_method' => 'tunai',
            'selfie' => $dummySelfie,
        ]);
        $resCheckOut->assertStatus(422);

        // Balance remains Rp50.000
        $this->assertSame('50000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    public function test_driver_workflow_is_completely_isolated_from_receivables(): void
    {
        $this->actingAs($this->driver);

        // Driver cannot access receivables endpoints
        $this->get(route('admin.receivables.index'))->assertRedirect();
        $this->get(route('admin.receivables.show', $this->store1->id))->assertRedirect();

        // Driver check out does not touch receivables
        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->driver->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jl. Mawar',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'path/selfie.jpg',
        ]);

        $dummySelfie = 'data:image/jpeg;base64,' . base64_encode('fake');
        $res = $this->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jl. Mawar',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Barang diserahkan',
            'photos' => [$dummySelfie],
            'transaction_status' => 'none',
        ]);
        $res->assertOk();

        // No receivable rows created by driver
        $this->assertEquals(0, StoreReceivable::where('created_by', $this->driver->id)->count());
    }
}
