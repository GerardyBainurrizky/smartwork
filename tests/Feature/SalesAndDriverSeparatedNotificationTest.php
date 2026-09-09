<?php

namespace Tests\Feature;

use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesAndDriverSeparatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $salesA;
    protected User $salesB;
    protected User $driverA;
    protected User $driverB;
    protected Store $storeSalesA;
    protected Store $storeDriverA;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'staff']);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Boss', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin Officer', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->salesA = User::factory()->create(['name' => 'Fikri Sales A', 'status' => 'active']);
        $this->salesA->assignRole('sales');

        $this->salesB = User::factory()->create(['name' => 'Budi Sales B', 'status' => 'active']);
        $this->salesB->assignRole('sales');

        $this->driverA = User::factory()->create(['name' => 'Dedi Driver A', 'status' => 'active']);
        $this->driverA->assignRole('driver');

        $this->driverB = User::factory()->create(['name' => 'Agus Driver B', 'status' => 'active']);
        $this->driverB->assignRole('driver');

        $this->storeSalesA = Store::create([
            'code' => 'ST-SALES-01',
            'name' => 'Toko Retail Sales A',
            'city' => 'Bandung',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'is_delivery_destination' => true,
        ]);

        $this->storeDriverA = Store::create([
            'code' => 'ST-DRIVER-01',
            'name' => 'Gudang Distributor Driver A',
            'city' => 'Subang',
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);
    }

    // ==========================================
    // SALES NOTIFICATION TESTS
    // ==========================================

    public function test_1_admin_creates_route_kunjungan_sales(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->salesA->id,
            'name' => 'Rute Kunjungan Bandung',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeSalesA->id, 'sequence' => 1, 'estimated_duration_minutes' => 45],
            ],
        ]);
        $res->assertOk();

        // Sales A receives notification
        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        $notif = $this->salesA->notifications()->first();
        $this->assertEquals('admin_route_created', $notif->data['activity_type']);
        $this->assertEquals('Route Kunjungan Baru', $notif->data['title']);
        $this->assertStringContainsString('Route kunjungan baru telah dibuat untuk Anda', $notif->data['message']);

        // Others do not receive
        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());
        $this->assertEquals(0, $this->driverA->unreadNotifications()->count());
        $this->assertEquals(0, $this->driverB->unreadNotifications()->count());
    }

    public function test_2_admin_edits_route_kunjungan_sales(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Kunjungan Awal',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeSalesA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin)->putJson(route('admin.routes.update', $route->id), [
            'name' => 'Rute Kunjungan Diubah',
            'date' => now()->toDateString(),
            'notes' => 'Catatan revisi',
            'stops' => [
                ['store_id' => $this->storeSalesA->id, 'sequence' => 1, 'estimated_duration_minutes' => 60],
            ],
        ]);
        $res->assertOk();

        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        $notif = $this->salesA->notifications()->first();
        $this->assertEquals('admin_route_updated', $notif->data['activity_type']);
        $this->assertEquals('Route Kunjungan Diubah', $notif->data['title']);
        $this->assertStringContainsString('Route kunjungan Anda telah diperbarui', $notif->data['message']);

        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());
        $this->assertEquals(0, $this->driverA->unreadNotifications()->count());
    }

    public function test_3_admin_adds_kunjungan_to_sales_route(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Kunjungan Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $res = $this->actingAs($this->admin)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeSalesA->id,
            'estimated_duration_minutes' => 30,
            'notes' => 'Tambah toko kunjungan',
        ]);
        $res->assertOk();

        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        $notif = $this->salesA->notifications()->first();
        $this->assertEquals('admin_stop_added', $notif->data['activity_type']);
        $this->assertEquals('Kunjungan Ditambahkan', $notif->data['title']);
        $this->assertStringContainsString('Kunjungan baru telah ditambahkan ke route Anda', $notif->data['message']);

        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());
        $this->assertEquals(0, $this->driverA->unreadNotifications()->count());
    }

    public function test_4_admin_edits_kunjungan_sales(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->salesA->id,
            'name' => 'Rute Kunjungan Berjalan',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeSalesA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin)->patchJson(route('route.stop.update', ['routeId' => $route->id, 'stopId' => $stop->id]), [
            'status' => 'pending',
            'notes' => 'Catatan diedit admin',
        ]);
        $res->assertOk();

        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        $notif = $this->salesA->notifications()->first();
        $this->assertEquals('admin_stop_updated', $notif->data['activity_type']);
        $this->assertEquals('Kunjungan Diubah', $notif->data['title']);
        $this->assertStringContainsString('Data kunjungan pada route Anda telah diperbarui', $notif->data['message']);

        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());
        $this->assertEquals(0, $this->driverA->unreadNotifications()->count());
    }

    public function test_5_admin_deletes_route_kunjungan_sales(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Kunjungan Hapus',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->admin)->deleteJson(route('admin.routes.destroy', $route->id));
        $res->assertOk();

        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        $notif = $this->salesA->notifications()->first();
        $this->assertEquals('admin_route_deleted', $notif->data['activity_type']);
        $this->assertEquals('Route Kunjungan Dihapus', $notif->data['title']);
        $this->assertStringContainsString('Route kunjungan Rute Kunjungan Hapus telah dihapus', $notif->data['message']);

        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());
        $this->assertEquals(0, $this->driverA->unreadNotifications()->count());
    }

    // ==========================================
    // DRIVER NOTIFICATION TESTS
    // ==========================================

    public function test_6_admin_creates_route_pengiriman_driver(): void
    {
        $res = $this->actingAs($this->admin)->postJson(route('admin.driver.routes.store'), [
            'user_id' => $this->driverA->id,
            'name' => 'Rute Pengiriman Subang',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeDriverA->id, 'sequence' => 1],
            ],
        ]);
        $res->assertOk();

        // Driver A receives notification
        $this->assertEquals(1, $this->driverA->unreadNotifications()->count());
        $notif = $this->driverA->notifications()->first();
        $this->assertEquals('admin_route_created', $notif->data['activity_type']);
        $this->assertEquals('Route Pengiriman Baru', $notif->data['title']);
        $this->assertStringContainsString('Route pengiriman baru telah dibuat untuk Anda', $notif->data['message']);

        // Others do not receive
        $this->assertEquals(0, $this->driverB->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesA->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());
    }

    public function test_7_admin_edits_route_pengiriman_driver(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->driverA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Pengiriman Awal',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeDriverA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin)->putJson(route('admin.driver.routes.update', $route->id), [
            'name' => 'Rute Pengiriman Diperbarui',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeDriverA->id, 'sequence' => 1],
            ],
        ]);
        $res->assertOk();

        $this->assertEquals(1, $this->driverA->unreadNotifications()->count());
        $notif = $this->driverA->notifications()->first();
        $this->assertEquals('admin_route_updated', $notif->data['activity_type']);
        $this->assertEquals('Route Pengiriman Diubah', $notif->data['title']);
        $this->assertStringContainsString('Route pengiriman Anda telah diperbarui', $notif->data['message']);

        $this->assertEquals(0, $this->driverB->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesA->unreadNotifications()->count());
    }

    public function test_8_admin_adds_pengiriman_to_driver_route(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->driverA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Pengiriman Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $res = $this->actingAs($this->admin)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeDriverA->id,
            'notes' => 'Tujuan baru',
        ]);
        $res->assertOk();

        $this->assertEquals(1, $this->driverA->unreadNotifications()->count());
        $notif = $this->driverA->notifications()->first();
        $this->assertEquals('admin_stop_added', $notif->data['activity_type']);
        $this->assertEquals('Pengiriman Ditambahkan', $notif->data['title']);
        $this->assertStringContainsString('Pengiriman baru telah ditambahkan ke route Anda', $notif->data['message']);

        $this->assertEquals(0, $this->driverB->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesA->unreadNotifications()->count());
    }

    public function test_9_admin_edits_pengiriman_driver(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->driverA->id,
            'created_by' => $this->driverA->id,
            'name' => 'Rute Pengiriman Berjalan',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeDriverA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin)->patchJson(route('route.stop.update', ['routeId' => $route->id, 'stopId' => $stop->id]), [
            'status' => 'pending',
            'notes' => 'Catatan diedit admin',
        ]);
        $res->assertOk();

        $this->assertEquals(1, $this->driverA->unreadNotifications()->count());
        $notif = $this->driverA->notifications()->first();
        $this->assertEquals('admin_stop_updated', $notif->data['activity_type']);
        $this->assertEquals('Pengiriman Diubah', $notif->data['title']);
        $this->assertStringContainsString('Data pengiriman pada route Anda telah diperbarui', $notif->data['message']);

        $this->assertEquals(0, $this->driverB->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesA->unreadNotifications()->count());
    }

    public function test_10_admin_deletes_route_pengiriman_driver(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->driverA->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Pengiriman Hapus',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->admin)->deleteJson(route('admin.driver.routes.destroy', $route->id));
        $res->assertOk();

        $this->assertEquals(1, $this->driverA->unreadNotifications()->count());
        $notif = $this->driverA->notifications()->first();
        $this->assertEquals('admin_route_deleted', $notif->data['activity_type']);
        $this->assertEquals('Route Pengiriman Dihapus', $notif->data['title']);
        $this->assertStringContainsString('Route pengiriman Rute Pengiriman Hapus telah dihapus', $notif->data['message']);

        $this->assertEquals(0, $this->driverB->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesA->unreadNotifications()->count());
    }

    // ==========================================
    // SUPER ADMIN REPEAT TESTS
    // ==========================================

    public function test_11_super_admin_creates_route_and_triggers_notifications(): void
    {
        // Sales route
        $resSales = $this->actingAs($this->superAdmin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->salesA->id,
            'name' => 'Rute Kunjungan Super Admin',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeSalesA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resSales->assertOk();
        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        $this->assertEquals('Route Kunjungan Baru', $this->salesA->notifications()->first()->data['title']);

        // Driver route
        $resDriver = $this->actingAs($this->superAdmin)->postJson(route('admin.driver.routes.store'), [
            'user_id' => $this->driverA->id,
            'name' => 'Rute Pengiriman Super Admin',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeDriverA->id, 'sequence' => 1],
            ],
        ]);
        $resDriver->assertOk();
        $this->assertEquals(1, $this->driverA->unreadNotifications()->count());
        $this->assertEquals('Route Pengiriman Baru', $this->driverA->notifications()->first()->data['title']);
    }
}
