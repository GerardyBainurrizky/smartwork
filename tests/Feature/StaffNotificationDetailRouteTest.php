<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route as WorkRoute;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffNotificationDetailRouteTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staff;
    protected User $sales;
    protected User $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin Ops', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->staff = User::factory()->create(['name' => 'Staff Test', 'status' => 'active']);
        $this->staff->assignRole('staff');

        $this->sales = User::factory()->create(['name' => 'Sales Test', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Test', 'status' => 'active']);
        $this->driver->assignRole('driver');
    }

    /**
     * Test 1: Staff clicks detail on attendance_canceled notification redirects properly to attendance.index.
     */
    public function test_staff_click_detail_on_attendance_canceled_redirects_to_attendance_index(): void
    {
        ActivityNotificationService::notifyUser($this->staff, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi Hadir dibatalkan',
            'message' => 'Admin membatalkan presensi Anda.',
            'actor_name' => 'Admin Ops',
            'actor_role' => 'Admin',
            'reason' => 'Selfie tidak jelas',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->staff->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->staff)->get(route('notifications.read', $notification->id));

        $response->assertRedirect(route('attendance.index'));
        $this->assertNotNull($notification->fresh()->read_at);

        // Follow redirect to attendance.index and ensure 200 OK
        $attendancePage = $this->actingAs($this->staff)->get(route('attendance.index'));
        $attendancePage->assertOk();
    }

    /**
     * Test 2: Stored absolute URL with different port/host does NOT break and redirects properly on current host.
     */
    public function test_staff_stored_absolute_url_different_host_redirects_safely_on_current_host(): void
    {
        ActivityNotificationService::notifyUser($this->staff, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi Hadir dibatalkan',
            'message' => 'Admin membatalkan presensi Anda.',
            'actor_name' => 'Admin Ops',
            'actor_role' => 'Admin',
            'reason' => 'Foto blur',
            'info' => 'Presensi • Dibatalkan',
            'url' => 'http://localhost:8080/attendance', // legacy port
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->staff->notifications()->first();

        $response = $this->actingAs($this->staff)->get(route('notifications.read', $notification->id));

        // Must redirect to current application host's /attendance, not localhost:8080
        $response->assertRedirect(route('attendance.index'));
        $this->assertStringNotContainsString(':8080', $response->headers->get('Location'));
    }

    /**
     * Test 3: Staff with notification pointing to admin URL does NOT result in 403, but redirects to attendance page.
     */
    public function test_staff_notification_pointing_to_admin_url_redirects_safely_to_staff_attendance(): void
    {
        ActivityNotificationService::notifyUser($this->staff, [
            'activity_type' => 'attendance_check_in',
            'title' => 'Presensi Check-In',
            'message' => 'Staff Test berhasil melakukan Check-In.',
            'actor_name' => 'Staff Test',
            'actor_role' => 'Staff',
            'info' => 'Staff • 08.00',
            'url' => 'http://localhost:8080/admin/attendance?search=Staff%20Test&date=2026-09-08',
            'related_type' => 'Attendance',
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->staff->notifications()->first();

        $response = $this->actingAs($this->staff)->get(route('notifications.read', $notification->id));

        // For Staff, must resolve to attendance.index, NOT admin.attendance.index (which is 403 Forbidden)
        $response->assertRedirect(route('attendance.index'));

        $this->actingAs($this->staff)
            ->get(route('attendance.index'))
            ->assertOk();
    }

    /**
     * Test 4: Staff with report notification resolves to staff.report.index.
     */
    public function test_staff_report_notification_redirects_to_staff_report(): void
    {
        ActivityNotificationService::notifyUser($this->staff, [
            'activity_type' => 'report_generated',
            'title' => 'Laporan Presensi Bulanan',
            'message' => 'Laporan presensi bulanan Anda siap.',
            'actor_name' => 'System',
            'actor_role' => 'System',
            'info' => 'Laporan • Presensi',
            'url' => route('staff.report.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->staff->notifications()->first();

        $response = $this->actingAs($this->staff)->get(route('notifications.read', $notification->id));
        $response->assertRedirect(route('staff.report.index'));

        $this->actingAs($this->staff)
            ->get(route('staff.report.index'))
            ->assertOk();
    }

    /**
     * Test 5: Staff with non-accessible notification (e.g. route/visit) gracefully falls back without 403/500 error.
     */
    public function test_staff_inaccessible_notification_type_falls_back_to_notifications_index(): void
    {
        ActivityNotificationService::notifyUser($this->staff, [
            'activity_type' => 'route_created',
            'title' => 'Rute Kunjungan Baru',
            'message' => 'Rute baru dibuat.',
            'actor_name' => 'Admin Ops',
            'actor_role' => 'Admin',
            'info' => 'Rute • 3 toko',
            'url' => 'http://localhost:8000/route/non-existent-route-id',
            'related_type' => 'Route',
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->staff->notifications()->first();

        $response = $this->actingAs($this->staff)->get(route('notifications.read', $notification->id));
        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('info', 'Detail notifikasi tidak ditemukan atau data sudah tidak tersedia.');
    }

    /**
     * Test 6: Detail button on notifications/index view is rendered properly for Staff with action styling.
     */
    public function test_staff_notifications_view_renders_detail_button_and_flash_alerts(): void
    {
        ActivityNotificationService::notifyUser($this->staff, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi Hadir dibatalkan',
            'message' => 'Admin membatalkan presensi Anda.',
            'actor_name' => 'Admin Ops',
            'actor_role' => 'Admin',
            'reason' => 'Lokasi tidak sesuai GPS kantor.',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->staff->notifications()->first();

        $response = $this->actingAs($this->staff)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Presensi Hadir dibatalkan');
        $response->assertSee('Lokasi tidak sesuai GPS kantor.');
        $response->assertSee(route('notifications.read', $notification->id));
        $response->assertSee('Detail');
        $response->assertSee('min-h-[32px]');
    }

    /**
     * Test 7: Regression - Admin notifications still redirect properly to admin routes.
     */
    public function test_admin_notification_detail_redirects_properly(): void
    {
        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'attendance_check_in',
            'title' => 'Staff Test Check-in',
            'message' => 'Staff Test melakukan check-in.',
            'actor_id' => (string) $this->staff->id,
            'actor_name' => $this->staff->name,
            'actor_role' => 'Staff',
            'info' => 'Staff • 08.00',
            'url' => route('admin.attendance.index', ['search' => $this->staff->name, 'date' => now()->toDateString()]),
            'related_type' => 'Attendance',
            'activity_time' => now()->toIso8601String(),
        ]);

        $adminNotification = $this->admin->notifications()->first();
        $this->assertNotNull($adminNotification);

        $response = $this->actingAs($this->admin)->get(route('admin.notifications.read', $adminNotification->id));
        $response->assertRedirect(route('admin.attendance.index', ['search' => $this->staff->name, 'date' => now()->toDateString()]));

        $this->actingAs($this->admin)
            ->get($response->headers->get('Location'))
            ->assertOk();
    }

    /**
     * Test 8: Regression - Sales notification detail redirects to Route Show if route exists, or Route Index if deleted.
     */
    public function test_sales_notification_detail_redirects_properly(): void
    {
        $route = WorkRoute::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Subang',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        ActivityNotificationService::notifyUser($this->sales, [
            'activity_type' => 'admin_route_created',
            'title' => 'Route Kunjungan Baru',
            'message' => 'Route dibuatkan untuk Anda.',
            'actor_id' => (string) $this->admin->id,
            'actor_name' => $this->admin->name,
            'actor_role' => 'Admin',
            'route_name' => $route->name,
            'info' => 'Rute Subang • 2 toko',
            'url' => route('route.show', $route->id),
            'related_id' => (string) $route->id,
            'related_type' => 'Route',
            'activity_time' => now()->toIso8601String(),
        ]);

        $salesNotification = $this->sales->notifications()->first();
        $this->assertNotNull($salesNotification);

        $response = $this->actingAs($this->sales)->get(route('notifications.read', $salesNotification->id));
        $response->assertRedirect(route('route.show', $route->id));

        $this->actingAs($this->sales)->get(route('route.show', $route->id))->assertOk();

        // Now test if Route was deleted
        $route->delete();

        $responseDeleted = $this->actingAs($this->sales)->get(route('notifications.read', $salesNotification->id));
        $responseDeleted->assertRedirect(route('route.index'));
        $responseDeleted->assertSessionHas('info', 'Rute yang dituju sudah tidak tersedia atau telah dihapus.');
    }

    /**
     * Test 9: Regression - Driver notification detail works seamlessly for delivery route.
     */
    public function test_driver_notification_detail_redirects_properly(): void
    {
        $route = WorkRoute::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Pengiriman Cirebon',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        ActivityNotificationService::notifyUser($this->driver, [
            'activity_type' => 'admin_route_created',
            'title' => 'Route Pengiriman Baru',
            'message' => 'Route pengiriman dibuatkan untuk Anda.',
            'actor_id' => (string) $this->admin->id,
            'actor_name' => $this->admin->name,
            'actor_role' => 'Admin',
            'route_name' => $route->name,
            'info' => 'Rute Cirebon • 3 toko',
            'url' => route('route.show', $route->id),
            'related_id' => (string) $route->id,
            'related_type' => 'Route',
            'activity_time' => now()->toIso8601String(),
        ]);

        $driverNotification = $this->driver->notifications()->first();
        $this->assertNotNull($driverNotification);

        $response = $this->actingAs($this->driver)->get(route('notifications.read', $driverNotification->id));
        $response->assertRedirect(route('route.show', $route->id));

        $this->actingAs($this->driver)->get(route('route.show', $route->id))->assertOk();
    }
}
