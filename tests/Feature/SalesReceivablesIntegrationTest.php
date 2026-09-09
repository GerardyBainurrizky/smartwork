<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReceivablesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $sales;
    private User $driver;
    private Store $storeWithDebt;
    private Store $storeWithoutDebt;
    private Route $route;
    private RouteStop $stop1;
    private RouteStop $stop2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['name' => 'Sales User']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver User']);
        $this->driver->assignRole('driver');

        $this->storeWithDebt = Store::create([
            'name' => 'Toko Ada Piutang',
            'code' => 'TAP-01',
            'address' => 'Jl. Mawar No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->storeWithoutDebt = Store::create([
            'name' => 'Toko Lunas',
            'code' => 'TLN-02',
            'address' => 'Jl. Melati No. 2',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        StoreReceivableService::addOpeningBalance([
            'store_id' => $this->storeWithDebt->id,
            'amount' => 10000,
        ], $this->admin);

        $today = now()->toDateString();
        $this->route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Sales',
            'date' => $today,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stop1 = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->storeWithDebt->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $this->stop2 = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->storeWithoutDebt->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_sales_sees_piutang_balance_on_route_page(): void
    {
        $res = $this->actingAs($this->sales)->get(route('route.index'));
        $res->assertOk();
        $res->assertSee('Piutang: Rp 10.000');
        $res->assertSee('Piutang: Tidak Ada Piutang');
    }

    public function test_sales_sees_piutang_balance_on_visit_page(): void
    {
        $res = $this->actingAs($this->sales)->get(route('visit.index'));
        $res->assertOk();
        $res->assertSee('Piutang: Rp 10.000');
        $res->assertSee('Piutang: Tidak Ada Piutang');
    }

    public function test_sales_sees_piutang_balance_on_route_show_page(): void
    {
        $res = $this->actingAs($this->sales)->get(route('route.show', $this->route->id));
        $res->assertOk();
        $res->assertSee('Piutang: Rp 10.000');
        $res->assertSee('Piutang: Tidak Ada Piutang');
    }

    public function test_driver_does_not_see_piutang_information_on_route_pages(): void
    {
        $driverRoute = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Pengiriman Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        RouteStop::create([
            'route_id' => $driverRoute->id,
            'store_id' => $this->storeWithDebt->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $resIndex = $this->actingAs($this->driver)->get(route('route.index'));
        $resIndex->assertOk();
        $resIndex->assertDontSee('Piutang: Rp 10.000');
        $resIndex->assertDontSee('Piutang: Tidak Ada Piutang');

        $resShow = $this->actingAs($this->driver)->get(route('route.show', $driverRoute->id));
        $resShow->assertOk();
        $resShow->assertDontSee('Piutang: Rp 10.000');
        $resShow->assertDontSee('Piutang: Tidak Ada Piutang');

        $resVisit = $this->actingAs($this->driver)->get(route('visit.index'));
        $resVisit->assertOk();
        $resVisit->assertDontSee('Piutang: Rp 10.000');
        $resVisit->assertDontSee('Piutang: Tidak Ada Piutang');
    }
}
