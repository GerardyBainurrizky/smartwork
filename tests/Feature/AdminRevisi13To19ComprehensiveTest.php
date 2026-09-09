<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRevisi13To19ComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'staff']);
    }

    private function createAdmin(string $role = 'admin'): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function createSales(string $name = 'Sales User'): User
    {
        $user = User::factory()->create(['name' => $name, 'status' => 'active']);
        $user->assignRole('sales');
        return $user;
    }

    private function createDriver(string $name = 'Driver User'): User
    {
        $user = User::factory()->create(['name' => $name, 'status' => 'active']);
        $user->assignRole('driver');
        return $user;
    }

    private function createStore(array $attributes = []): Store
    {
        static $seq = 1;
        return Store::create(array_merge([
            'name' => 'Toko ' . $seq,
            'code' => 'TK-' . str_pad((string)$seq++, 3, '0', STR_PAD_LEFT),
            'address' => 'Jl. Merdeka No. ' . $seq,
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'is_delivery_destination' => false,
        ], $attributes));
    }

    /**
     * Point 13 & 14: Edit & Detail Rute Kunjungan Sales
     */
    public function test_sales_route_edit_view_and_update_maintains_sales_and_atomic_stops(): void
    {
        $admin = $this->createAdmin('admin');
        $salesA = $this->createSales('Sales A');

        $storeA = $this->createStore(['name' => 'Toko A', 'sales_penanggung_jawab_id' => $salesA->id]);
        $storeB = $this->createStore(['name' => 'Toko B', 'sales_penanggung_jawab_id' => $salesA->id]);
        $storeC = $this->createStore(['name' => 'Toko C', 'sales_penanggung_jawab_id' => $salesA->id]);

        $route = Route::create([
            'user_id' => $salesA->id,
            'created_by' => $admin->id,
            'name' => 'Rute Kunjungan Sales A',
            'date' => now()->toDateString(),
            'notes' => 'Catatan awal',
            'status' => 'draft',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 60,
            'status' => 'pending',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeB->id,
            'sequence' => 2,
            'estimated_duration_minutes' => 45,
            'status' => 'pending',
        ]);

        // Detail page contains Edit button
        $detailRes = $this->actingAs($admin)->get(route('route.show', $route->id));
        $detailRes->assertOk();
        $detailRes->assertSee('Edit Kunjungan');
        $detailRes->assertDontSee('+ Edit Kunjungan');

        // Edit page renders with sales prefilled and locked
        $editRes = $this->actingAs($admin)->get(route('admin.routes.edit', $route->id));
        $editRes->assertOk();
        $editRes->assertSee('Edit Rencana Kunjungan');
        $editRes->assertSee('Sales Penanggung Jawab');
        $editRes->assertSee($salesA->name);
        $editRes->assertSee($salesA->id);

        // Update route stops: change to Toko A (90m), Toko C (30m)
        // Simulate an attempted manipulation by submitting another user's ID
        $salesManipulated = $this->createSales('Sales Manipulated');
        $updateRes = $this->actingAs($admin)->putJson(route('admin.routes.update', $route->id), [
            'user_id' => $salesManipulated->id, // Intentionally submit different user
            'name' => 'Rute Kunjungan Sales A Updated',
            'date' => now()->toDateString(),
            'notes' => 'Prioritaskan toko C',
            'stops' => [
                ['store_id' => $storeA->id, 'sequence' => 1, 'estimated_duration_minutes' => 90, 'notes' => 'Urutan 1'],
                ['store_id' => $storeC->id, 'sequence' => 2, 'estimated_duration_minutes' => 30, 'notes' => 'Urutan 2'],
            ],
        ]);

        $updateRes->assertOk()->assertJsonPath('status', 'success');

        $route->refresh();
        $this->assertEquals('Rute Kunjungan Sales A Updated', $route->name);
        $this->assertEquals($salesA->id, $route->user_id); // Backend MUST preserve existing owner Sales A
        $this->assertEquals('Prioritaskan toko C', $route->notes);
        $this->assertCount(2, $route->stops);

        $stops = $route->stops()->orderBy('sequence')->get();
        $this->assertEquals($storeA->id, $stops[0]->store_id);
        $this->assertEquals(90, $stops[0]->estimated_duration_minutes);
        $this->assertEquals($storeC->id, $stops[1]->store_id);
        $this->assertEquals(30, $stops[1]->estimated_duration_minutes);
    }

    /**
     * Point 16 & 17: Edit & Detail Rute Pengiriman Driver
     */
    public function test_driver_route_edit_view_and_update_maintains_driver_and_atomic_stops(): void
    {
        $admin = $this->createAdmin('admin');
        $driverA = $this->createDriver('Driver Budi');

        $destA = $this->createStore(['name' => 'Tujuan A', 'is_delivery_destination' => true]);
        $destB = $this->createStore(['name' => 'Tujuan B', 'is_delivery_destination' => true]);

        $route = Route::create([
            'user_id' => $driverA->id,
            'created_by' => $admin->id,
            'name' => 'Rute Pengiriman Driver Budi',
            'date' => now()->toDateString(),
            'notes' => 'Pengiriman pagi',
            'status' => 'draft',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $destA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Detail page contains Edit Pengiriman button
        $detailRes = $this->actingAs($admin)->get(route('route.show', $route->id));
        $detailRes->assertOk();
        $detailRes->assertSee('Edit Pengiriman');
        $detailRes->assertDontSee('+ Edit Pengiriman');

        // Edit page renders with driver prefilled and locked
        $editRes = $this->actingAs($admin)->get(route('admin.driver.routes.edit', $route->id));
        $editRes->assertOk();
        $editRes->assertSee('Edit Rencana Pengiriman');
        $editRes->assertSee($driverA->name);
        $editRes->assertSee($driverA->id);

        // Update driver route
        // Simulate an attempted manipulation by submitting another driver's ID
        $driverManipulated = $this->createDriver('Driver Manipulated');
        $updateRes = $this->actingAs($admin)->putJson(route('admin.driver.routes.update', $route->id), [
            'user_id' => $driverManipulated->id, // Intentionally submit different user
            'name' => 'Rute Pengiriman Driver Budi Update',
            'date' => now()->toDateString(),
            'notes' => 'Kirim siang',
            'stops' => [
                ['store_id' => $destB->id, 'sequence' => 1, 'notes' => 'Langsung bongkar'],
            ],
        ]);

        $updateRes->assertOk()->assertJsonPath('status', 'success');

        $route->refresh();
        $this->assertEquals('Rute Pengiriman Driver Budi Update', $route->name);
        $this->assertEquals($driverA->id, $route->user_id); // Backend MUST preserve existing owner Driver A
        $this->assertEquals('Kirim siang', $route->notes);
        $this->assertCount(1, $route->stops);
        $this->assertEquals($destB->id, $route->stops->first()->store_id);
    }

    /**
     * Point 15: Dashboard Admin Ringkasan Uang Masuk & Piutang Breakdown
     */
    public function test_dashboard_admin_cash_in_and_receivables_breakdown(): void
    {
        $admin = $this->createAdmin('admin');
        $salesA = $this->createSales('Sales Alpha');
        $salesB = $this->createSales('Sales Beta');
        $driver = $this->createDriver('Driver Charlie');

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $storeA = $this->createStore(['name' => 'Toko Alpha', 'sales_penanggung_jawab_id' => $salesA->id]);
        $storeB = $this->createStore(['name' => 'Toko Beta', 'sales_penanggung_jawab_id' => $salesB->id]);
        $storeDriver = $this->createStore(['name' => 'Toko Driver', 'is_delivery_destination' => true]);

        // Sales A: Transaction 1.000.000, initial payment today 500.000 -> remaining 500.000
        $txA = StoreReceivableService::createTransaction([
            'store_id' => $storeA->id,
            'transaction_amount' => 1000000,
            'transaction_date' => $today,
            'paid_amount' => 500000,
            'created_by' => $salesA->id,
        ], $salesA);

        // Sales B: Transaction yesterday 2.000.000, payment today 1.000.000 -> remaining 1.000.000
        $txB = StoreReceivableService::createTransaction([
            'store_id' => $storeB->id,
            'transaction_amount' => 2000000,
            'transaction_date' => $yesterday,
            'created_by' => $salesB->id,
        ], $salesB);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $txB->id,
            'amount' => 1000000,
            'payment_date' => $today,
            'payment_method' => 'tunai',
        ], $salesB);

        // Driver transaction today: 800.000 paid
        $routeDriver = Route::create([
            'user_id' => $driver->id,
            'created_by' => $admin->id,
            'name' => 'Rute Driver',
            'date' => $today,
            'status' => 'active',
        ]);
        $stopDriver = RouteStop::create([
            'route_id' => $routeDriver->id,
            'store_id' => $storeDriver->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'route_stop_id' => $stopDriver->id,
            'route_id' => $routeDriver->id,
            'store_id' => $storeDriver->id,
            'user_id' => $driver->id,
            'status' => 'completed',
            'check_in_at' => now(),
            'check_out_at' => now(),
            'transaction_status' => 'paid',
            'transaction_amount' => 800000,
        ]);

        $res = $this->actingAs($admin)->get(route('dashboard'));
        $res->assertOk();

        // 1. Uang Masuk Sales Hari Ini = 500k (Sales A) + 1000k (Sales B) = 1.500.000
        $res->assertSee('Uang Masuk Sales Hari Ini');
        $res->assertSee(number_format(1500000, 0, ',', '.'));

        // 2. Piutang Sales = 500k (Sales A) + 1000k (Sales B) = 1.500.000
        $res->assertSee('Piutang Sales');
        $res->assertSee('Saldo Berjalan');

        // 3. Uang Masuk Driver Hari Ini = 800.000
        $res->assertSee('Uang Masuk Driver Hari Ini');
        $res->assertSee(number_format(800000, 0, ',', '.'));
    }

    /**
     * Point 18: Admin Visits Dilewati Column
     */
    public function test_admin_visits_has_dilewati_column(): void
    {
        $admin = $this->createAdmin('admin');
        $sales = $this->createSales('Sales Rudi');
        $store = $this->createStore(['sales_penanggung_jawab_id' => $sales->id]);

        $route = Route::create([
            'user_id' => $sales->id,
            'created_by' => $sales->id,
            'name' => 'Rute Kunjungan',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'skipped',
        ]);

        $res = $this->actingAs($admin)->get(route('admin.visits.index'));
        $res->assertOk();
        $res->assertSee('Dilewati');
    }

    /**
     * Point 19: Admin Stores Filter & Pengelolaan Layout
     */
    public function test_admin_stores_filter_and_pengelolaan(): void
    {
        $admin = $this->createAdmin('admin');
        $sales = $this->createSales('Sales Deni');
        $store = $this->createStore([
            'name' => 'Toko Subang Jaya',
            'code' => 'SBG-001',
            'sales_penanggung_jawab_id' => $sales->id,
            'status' => 'active',
        ]);

        $res = $this->actingAs($admin)->get(route('admin.stores.index'));
        $res->assertOk();
        $res->assertSee('Filter &amp; Pengelolaan', false);
        $res->assertSee('Cari Toko');
        $res->assertSee('Sales Penanggung Jawab');
        $res->assertSee('Tujuan Pengiriman');
        $res->assertSee('TOKO PER SALES');
        $res->assertSee('Lihat Detail');
        $res->assertSee('Sales Deni');
        $res->assertSee('1 toko');

        // Test filter search
        $searchRes = $this->actingAs($admin)->get(route('admin.stores.index', ['search' => 'Subang Jaya']));
        $searchRes->assertOk();
        $searchRes->assertSee('Toko Subang Jaya');

        // Test detail page /admin/stores/by-sales
        $bySalesRes = $this->actingAs($admin)->get(route('admin.stores.by-sales'));
        $bySalesRes->assertOk();
        $bySalesRes->assertSee('Detail Toko per Sales');
        $bySalesRes->assertSee('Kembali ke Master Toko');
        $bySalesRes->assertSee('Sales Deni');
        $bySalesRes->assertSee('Toko Subang Jaya');
        $bySalesRes->assertSee('SBG-001');

        // Super Admin access
        $superAdmin = $this->createAdmin('super-admin');
        $this->actingAs($superAdmin)->get(route('admin.stores.by-sales'))->assertOk();

        // Sales & Driver blocked from accessing admin stores by-sales (redirected by CheckRole middleware to dashboard)
        $driver = $this->createDriver('Driver Agus');
        $this->actingAs($sales)->get(route('admin.stores.by-sales'))->assertRedirect(route('dashboard'));
        $this->actingAs($driver)->get(route('admin.stores.by-sales'))->assertRedirect(route('dashboard'));
    }
}
