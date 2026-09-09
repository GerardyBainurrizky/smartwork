<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $salesA;
    protected User $salesB;
    protected Store $storeA;
    protected Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'staff']);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Boss', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin Officer', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->salesA = User::factory()->create(['name' => 'Fikri Haekal', 'status' => 'active']);
        $this->salesA->assignRole('sales');

        $this->salesB = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->salesB->assignRole('sales');

        $this->storeA = Store::create(['code' => 'ST-001', 'name' => 'Toko Berkah Abadi', 'city' => 'Subang', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->salesA->id]);
        $this->storeB = Store::create(['code' => 'ST-002', 'name' => 'Toko Barokah Jaya', 'city' => 'Subang', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->salesA->id]);
    }

    public function test_sales_receives_notification_when_admin_creates_route(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->salesA->id,
            'name' => 'Subang Road',
            'date' => now()->toDateString(),
            'notes' => 'Rute prioritas',
            'stops' => [
                [
                    'store_id' => $this->storeA->id,
                    'sequence' => 1,
                    'estimated_duration_minutes' => 30,
                ],
                [
                    'store_id' => $this->storeB->id,
                    'sequence' => 2,
                    'estimated_duration_minutes' => 45,
                ]
            ]
        ]);

        $response->assertOk();

        // Sales A receives notification
        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        // Sales B does not receive
        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());

        $notification = $this->salesA->notifications()->first();
        $this->assertEquals('admin_route_created', $notification->data['activity_type']);
        $this->assertEquals('Route Kunjungan Baru', $notification->data['title']);
        $this->assertStringContainsString('Route kunjungan baru telah dibuat untuk Anda', $notification->data['message']);
        $this->assertEquals('Subang Road', $notification->data['route_name']);
    }

    public function test_sales_receives_notification_when_super_admin_creates_route(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->salesA->id,
            'name' => 'Rute Pantura',
            'date' => now()->toDateString(),
            'stops' => [
                [
                    'store_id' => $this->storeA->id,
                    'sequence' => 1,
                    'estimated_duration_minutes' => 30,
                ]
            ]
        ]);

        $response->assertOk();
        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());

        $notification = $this->salesA->notifications()->first();
        $this->assertEquals('admin_route_created', $notification->data['activity_type']);
        $this->assertEquals('Route Kunjungan Baru', $notification->data['title']);
        $this->assertStringContainsString('Route kunjungan baru telah dibuat untuk Anda', $notification->data['message']);
    }

    public function test_sales_receives_notification_when_admin_or_super_admin_adds_stop(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->salesA->id,
            'name' => 'ererere',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Super Admin adds stop to Sales A route
        $response = $this->actingAs($this->superAdmin)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeA->id,
            'estimated_duration_minutes' => 30,
            'notes' => 'Toko baru ditambahkan',
        ]);

        $response->assertOk();

        // Sales A receives notification
        $this->assertEquals(1, $this->salesA->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesB->unreadNotifications()->count());

        $notification = $this->salesA->notifications()->first();
        $this->assertEquals('admin_stop_added', $notification->data['activity_type']);
        $this->assertEquals('Kunjungan Ditambahkan', $notification->data['title']);
        $this->assertStringContainsString('Kunjungan baru telah ditambahkan ke route Anda', $notification->data['message']);
        $this->assertEquals('ererere', $notification->data['route_name']);
        $this->assertEquals('Toko Berkah Abadi', $notification->data['store_name']);
    }

    public function test_sales_creating_own_route_or_stop_does_not_notify_sales(): void
    {
        $response = $this->actingAs($this->salesA)->postJson(route('route.store'), [
            'name' => 'Rute Mandiri',
            'date' => now()->toDateString(),
            'stops' => [
                [
                    'store_id' => $this->storeA->id,
                    'sequence' => 1,
                    'estimated_duration_minutes' => 30,
                ]
            ]
        ]);

        $response->assertOk();
        // Sales does not receive own creation notification (admins receive it via notifyAdmins)
        $this->assertEquals(0, $this->salesA->notifications()->count());
        $this->assertEquals(1, $this->admin->notifications()->count());
    }

    public function test_sales_can_access_notifications_index_and_dropdown(): void
    {
        ActivityNotificationService::notifyUser($this->salesA, [
            'activity_type' => 'admin_route_created',
            'title' => 'Admin membuatkan rute baru',
            'message' => 'Admin membuat rute "Subang" untuk Anda dengan 2 toko.',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'route_name' => 'Subang',
            'info' => 'Rute Subang • 2 toko',
            'url' => route('route.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $this->actingAs($this->salesA)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Admin membuatkan rute baru')
            ->assertSee('Subang');

        $this->actingAs($this->salesA)
            ->getJson(route('notifications.dropdown'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('items.0.title', 'Admin membuatkan rute baru');
    }

    public function test_sales_can_mark_notification_as_read(): void
    {
        ActivityNotificationService::notifyUser($this->salesA, [
            'activity_type' => 'admin_stop_added',
            'title' => 'Kunjungan baru ditambahkan',
            'message' => 'Super Admin menambahkan kunjungan baru.',
            'actor_name' => 'Super Admin',
            'actor_role' => 'Super Admin',
            'info' => '09.00',
            'url' => route('route.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->salesA->notifications()->first();

        // Mark read via POST
        $this->actingAs($this->salesA)
            ->post(route('notifications.mark-read', $notification->id))
            ->assertRedirect();

        $this->assertEquals(0, $this->salesA->fresh()->unreadNotifications()->count());
    }

    public function test_sales_can_delete_own_notification(): void
    {
        ActivityNotificationService::notifyUser($this->salesA, [
            'activity_type' => 'admin_stop_added',
            'title' => 'Notif to delete',
            'message' => 'Test delete',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => '10.00',
            'url' => route('route.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->salesA->notifications()->first();

        $this->actingAs($this->salesA)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertRedirect();

        $this->assertEquals(0, $this->salesA->fresh()->notifications()->count());
    }

    public function test_sales_cannot_delete_other_sales_notification(): void
    {
        ActivityNotificationService::notifyUser($this->salesB, [
            'activity_type' => 'admin_stop_added',
            'title' => 'Notif B',
            'message' => 'Message B',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => '10.00',
            'url' => route('route.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notificationB = $this->salesB->notifications()->first();

        // Sales A tries to delete Sales B notification
        $this->actingAs($this->salesA)
            ->delete(route('notifications.destroy', $notificationB->id))
            ->assertNotFound();

        $this->assertEquals(1, $this->salesB->fresh()->notifications()->count());
    }

    public function test_sales_can_destroy_all_own_notifications(): void
    {
        ActivityNotificationService::notifyUser($this->salesA, [
            'activity_type' => 'admin_stop_added',
            'title' => 'Notif 1',
            'message' => 'Message 1',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => '10.00',
            'url' => route('route.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        ActivityNotificationService::notifyUser($this->salesB, [
            'activity_type' => 'admin_stop_added',
            'title' => 'Notif Sales B',
            'message' => 'Message 2',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => '10.00',
            'url' => route('route.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $this->assertEquals(1, $this->salesA->notifications()->count());
        $this->assertEquals(1, $this->salesB->notifications()->count());

        $this->actingAs($this->salesA)
            ->delete(route('notifications.destroy-all'))
            ->assertRedirect();

        $this->assertEquals(0, $this->salesA->fresh()->notifications()->count());
        // Sales B notification is not touched
        $this->assertEquals(1, $this->salesB->fresh()->notifications()->count());
    }

    public function test_sales_notifications_pagination_works(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            ActivityNotificationService::notifyUser($this->salesA, [
                'activity_type' => 'admin_route_created',
                'title' => "Rute Baru Ke-{$i}",
                'message' => "Admin membuat rute Rute-{$i}",
                'actor_name' => 'Admin',
                'actor_role' => 'Admin',
                'route_name' => "Rute-{$i}",
                'info' => '08.00',
                'url' => route('route.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        $salesNotifications = $this->salesA->notifications()->get();
        $index = 1;
        foreach ($salesNotifications as $notif) {
            $notif->created_at = now()->subMinutes(25 - $index);
            $notif->save();
            $index++;
        }

        $response1 = $this->actingAs($this->salesA)->get(route('notifications.index'));
        $response1->assertOk()
            ->assertSeeText('Sebelumnya')
            ->assertSeeText('Selanjutnya')
            ->assertSeeText('1')
            ->assertSeeText('2')
            ->assertSeeText('Rute-15')
            ->assertDontSeeText('Rute-5');

        $response2 = $this->actingAs($this->salesA)->get(route('notifications.index', ['page' => 2]));
        $response2->assertOk()
            ->assertSeeText('Sebelumnya')
            ->assertSeeText('Selanjutnya')
            ->assertSeeText('Rute-5')
            ->assertDontSeeText('Rute-15');
    }

    public function test_sales_single_page_does_not_render_pagination(): void
    {
        ActivityNotificationService::notifyUser($this->salesA, [
            'activity_type' => 'admin_route_created',
            'title' => 'Notif Tunggal',
            'message' => 'Satu notifikasi saja',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'route_name' => 'Rute Tunggal',
            'info' => '08.00',
            'url' => route('route.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $response = $this->actingAs($this->salesA)->get(route('notifications.index'));
        $response->assertOk()
            ->assertDontSee('aria-label="Pagination Notifikasi"', false)
            ->assertDontSeeText('Sebelumnya')
            ->assertDontSeeText('Selanjutnya');
    }

    public function test_sales_notification_filters_are_preserved_in_pagination_links(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            ActivityNotificationService::notifyUser($this->salesA, [
                'activity_type' => 'admin_route_deleted',
                'title' => 'Admin menghapus rute',
                'message' => "Admin telah menghapus rute Rute {$i}.",
                'actor_name' => 'Admin',
                'actor_role' => 'Admin',
                'route_name' => "Rute {$i}",
                'info' => 'Rute • Dihapus',
                'url' => route('route.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        foreach ($this->salesA->notifications()->get() as $index => $notification) {
            $notification->created_at = now()->subMinutes($index);
            $notification->save();
        }

        $this->actingAs($this->salesA)
            ->get(route('notifications.index', ['type' => 'route', 'status' => 'unread']))
            ->assertOk()
            ->assertSee('type=route&amp;status=unread&amp;page=2', false);

        $this->actingAs($this->salesA)
            ->get(route('notifications.index', ['type' => 'route', 'status' => 'unread', 'page' => 2]))
            ->assertOk()
            ->assertSeeText('Rute 11')
            ->assertDontSeeText('Rute 1.');
    }

    public function test_sales_receives_notification_when_admin_deletes_own_route(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->salesA->id,
            'name' => 'Pamanukan Shorty',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.routes.destroy', $route->id))
            ->assertOk();

        $notification = $this->salesA->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('admin_route_deleted', $notification->data['activity_type']);
        $this->assertEquals('Route Kunjungan Dihapus', $notification->data['title']);
        $this->assertStringContainsString('Pamanukan Shorty', $notification->data['message']);
        $this->assertEquals(1, $this->salesA->fresh()->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesB->fresh()->notifications()->count());
    }

    public function test_sales_receives_notification_when_super_admin_deletes_own_route(): void
    {
        $route = RouteModel::create([
            'user_id' => $this->salesA->id,
            'created_by' => $this->salesA->id,
            'name' => 'Pamanukan Shorty',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('admin.routes.destroy', $route->id))
            ->assertOk();

        $notification = $this->salesA->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('admin_route_deleted', $notification->data['activity_type']);
        $this->assertEquals('Route Kunjungan Dihapus', $notification->data['title']);
        $this->assertStringContainsString('Pamanukan Shorty', $notification->data['message']);
    }

    public function test_sales_receives_notification_when_attendance_canceled_with_reason(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->salesA->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->setTime(8, 5),
            'clock_out' => null,
            'status' => 'present',
            'notes' => null,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.attendance.cancel', $attendance->id), [
                'reason' => 'Data lokasi tidak sesuai dengan lokasi kerja.',
            ])
            ->assertRedirect();

        $notification = $this->salesA->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('attendance_canceled', $notification->data['activity_type']);
        $this->assertStringContainsString('Data lokasi tidak sesuai', $notification->data['message']);
        $this->assertEquals('Data lokasi tidak sesuai dengan lokasi kerja.', $notification->data['reason']);
        $this->assertEquals(1, $this->salesA->fresh()->unreadNotifications()->count());
        $this->assertEquals(0, $this->salesB->fresh()->notifications()->count());
    }
}

