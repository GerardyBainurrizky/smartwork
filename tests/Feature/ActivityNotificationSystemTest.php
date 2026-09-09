<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route as WorkRoute;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityNotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $sales;
    protected User $staff;
    protected User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->staff = User::where('username', 'staff')->first();

        $this->sales = User::create([
            'username' => 'sales_test',
            'name' => 'Sales User Test',
            'email' => 'sales_test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->sales->syncRoles(['sales']);

        $this->driver = User::create([
            'username' => 'driver_test',
            'name' => 'Driver User Test',
            'email' => 'driver_test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);
    }

    public function test_notification_sent_to_admins(): void
    {
        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'attendance_check_in',
            'title' => 'Presensi Sales Masuk',
            'message' => 'Sales User Test melakukan presensi masuk',
            'actor_name' => 'Sales User Test',
            'actor_role' => 'Sales',
        ]);

        $this->assertEquals(1, $this->admin->notifications()->count());
        $this->assertEquals(1, $this->superAdmin->notifications()->count());
        $this->assertEquals(0, $this->sales->notifications()->count());
    }

    public function test_dropdown_endpoint_returns_json(): void
    {
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Presensi Sales Masuk',
            'message' => 'Sales User Test presensi',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.notifications.dropdown'));
        $response->assertOk();
        $response->assertJsonStructure(['unread_count', 'unread_label', 'items']);
        $this->assertEquals(1, $response->json('unread_count'));
    }

    public function test_mark_as_read(): void
    {
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Test Notification',
            'message' => 'Message',
        ]);

        $notification = $this->admin->notifications()->first();
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->admin)->post(route('admin.notifications.mark-read', $notification->id));
        $response->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read(): void
    {
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Test 1',
            'message' => 'Msg 1',
        ]);
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_out',
            'title' => 'Test 2',
            'message' => 'Msg 2',
        ]);

        $this->assertEquals(2, $this->admin->unreadNotifications()->count());

        $response = $this->actingAs($this->admin)->post(route('admin.notifications.read-all'));
        $response->assertRedirect();

        $this->assertEquals(0, $this->admin->unreadNotifications()->count());
    }

    public function test_destroy_single_notification(): void
    {
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Test',
            'message' => 'Msg',
        ]);

        $notification = $this->admin->notifications()->first();

        $response = $this->actingAs($this->admin)->delete(route('admin.notifications.destroy', $notification->id));
        $response->assertRedirect();

        $this->assertEquals(0, $this->admin->notifications()->count());
    }

    public function test_admin_can_access_destroy_all_endpoint_and_delete_only_own_notifications(): void
    {
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Admin Notif',
            'message' => 'For Admin',
        ]);

        ActivityNotificationService::notifyUser($this->superAdmin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Super Admin Notif',
            'message' => 'For Super Admin',
        ]);

        $this->assertEquals(1, $this->admin->notifications()->count());
        $this->assertEquals(1, $this->superAdmin->notifications()->count());

        $response = $this->actingAs($this->admin)->delete(route('admin.notifications.destroy-all'));
        $response->assertRedirect(route('admin.notifications.index'));
        $response->assertSessionHas('success', 'Semua notifikasi berhasil dihapus.');

        $this->assertEquals(0, $this->admin->fresh()->notifications()->count());
        $this->assertEquals(1, $this->superAdmin->fresh()->notifications()->count());
    }

    public function test_super_admin_can_access_destroy_all_endpoint(): void
    {
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Admin Notif',
            'message' => 'For Admin',
        ]);

        ActivityNotificationService::notifyUser($this->superAdmin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Super Admin Notif',
            'message' => 'For Super Admin',
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.notifications.destroy-all'));
        $response->assertRedirect(route('admin.notifications.index'));

        $this->assertEquals(0, $this->superAdmin->fresh()->notifications()->count());
        $this->assertEquals(1, $this->admin->fresh()->notifications()->count());
    }

    public function test_notifications_index_page_filters(): void
    {
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Presensi Masuk',
            'message' => 'Sales hadir',
        ]);
        ActivityNotificationService::notifyUser($this->admin, [
            'activity_type' => 'route_created',
            'title' => 'Rute Baru',
            'message' => 'Rute dibuat',
        ]);

        $respAll = $this->actingAs($this->admin)->get(route('admin.notifications.index'));
        $respAll->assertOk();
        $respAll->assertSee('Presensi Masuk');
        $respAll->assertSee('Rute Baru');

        $respAtt = $this->get(route('admin.notifications.index', ['type' => 'attendance']));
        $respAtt->assertOk();
        $respAtt->assertSee('Presensi Masuk');

        $respRoute = $this->get(route('admin.notifications.index', ['type' => 'route']));
        $respRoute->assertOk();
        $respRoute->assertSee('Rute Baru');
    }
}
