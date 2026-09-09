<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staffA;
    protected User $staffB;
    protected User $sales;

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

        $this->staffA = User::factory()->create(['name' => 'Siti Staff', 'status' => 'active']);
        $this->staffA->assignRole('staff');

        $this->staffB = User::factory()->create(['name' => 'Budi Staff', 'status' => 'active']);
        $this->staffB->assignRole('staff');

        $this->sales = User::factory()->create(['name' => 'Fikri Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');
    }

    public function test_staff_receives_notification_when_admin_cancels_attendance(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->staffA->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->setTime(8, 5),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.cancel', $attendance->id), [
            'reason' => 'Foto selfie tidak sesuai dengan ketentuan.',
        ]);

        $response->assertRedirect();

        // Staff A receives 1 notification
        $this->assertEquals(1, $this->staffA->unreadNotifications()->count());
        // Staff B does not receive notification
        $this->assertEquals(0, $this->staffB->unreadNotifications()->count());
        // Sales does not receive notification
        $this->assertEquals(0, $this->sales->unreadNotifications()->count());

        $notification = $this->staffA->notifications()->first();
        $this->assertEquals('attendance_canceled', $notification->data['activity_type']);
        $this->assertStringContainsString('Admin', $notification->data['message']);
        $this->assertStringContainsString('Foto selfie tidak sesuai dengan ketentuan.', $notification->data['message']);
        $this->assertEquals('Foto selfie tidak sesuai dengan ketentuan.', $notification->data['reason']);
    }

    public function test_staff_receives_notification_when_super_admin_cancels_attendance(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->staffA->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->setTime(8, 10),
            'status' => 'late',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.attendance.cancel', $attendance->id), [
            'reason' => 'Lokasi presensi berada di luar ketentuan kantor.',
        ]);

        $response->assertRedirect();

        $this->assertEquals(1, $this->staffA->unreadNotifications()->count());

        $notification = $this->staffA->notifications()->first();
        $this->assertEquals('attendance_canceled', $notification->data['activity_type']);
        $this->assertEquals('Super Admin', $notification->data['actor_role']);
        $this->assertStringContainsString('Super Admin', $notification->data['message']);
        $this->assertStringContainsString('Lokasi presensi berada di luar ketentuan kantor.', $notification->data['message']);
        $this->assertEquals('Lokasi presensi berada di luar ketentuan kantor.', $notification->data['reason']);
    }

    public function test_failed_attendance_cancellation_does_not_create_notification(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->staffA->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->setTime(8, 0),
            'status' => 'present',
        ]);

        // Validation failure (reason too short)
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.cancel', $attendance->id), [
            'reason' => 'ab',
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertEquals(0, $this->staffA->notifications()->count());
    }

    public function test_staff_can_access_notifications_index_and_dropdown(): void
    {
        ActivityNotificationService::notifyUser($this->staffA, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi dibatalkan',
            'message' => 'Super Admin membatalkan presensi Anda. Alasan: Lokasi tidak valid.',
            'actor_name' => 'Super Admin Boss',
            'actor_role' => 'Super Admin',
            'reason' => 'Lokasi tidak valid.',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $this->actingAs($this->staffA)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Presensi dibatalkan')
            ->assertSee('Lokasi tidak valid.');

        $this->actingAs($this->staffA)
            ->getJson(route('notifications.dropdown'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('items.0.title', 'Presensi dibatalkan');
    }

    public function test_staff_can_mark_notification_as_read_and_read_all(): void
    {
        ActivityNotificationService::notifyUser($this->staffA, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi dibatalkan',
            'message' => 'Admin membatalkan presensi Anda.',
            'actor_name' => 'Admin Officer',
            'actor_role' => 'Admin',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notification = $this->staffA->notifications()->first();

        // Mark single read
        $this->actingAs($this->staffA)
            ->post(route('notifications.mark-read', $notification->id))
            ->assertRedirect();

        $this->assertEquals(0, $this->staffA->fresh()->unreadNotifications()->count());

        // Create another notification and mark all as read
        ActivityNotificationService::notifyUser($this->staffA, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi dibatalkan 2',
            'message' => 'Admin membatalkan presensi Anda 2.',
            'actor_name' => 'Admin Officer',
            'actor_role' => 'Admin',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $this->assertEquals(1, $this->staffA->fresh()->unreadNotifications()->count());

        $this->actingAs($this->staffA)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertEquals(0, $this->staffA->fresh()->unreadNotifications()->count());
    }

    public function test_staff_can_delete_own_notification_and_all_notifications(): void
    {
        ActivityNotificationService::notifyUser($this->staffA, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi dibatalkan',
            'message' => 'Catatan 1',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        ActivityNotificationService::notifyUser($this->staffB, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi B',
            'message' => 'Catatan B',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $notificationA = $this->staffA->notifications()->first();
        $notificationB = $this->staffB->notifications()->first();

        // Staff A cannot delete Staff B notification
        $this->actingAs($this->staffA)
            ->delete(route('notifications.destroy', $notificationB->id))
            ->assertNotFound();

        // Staff A deletes own notification
        $this->actingAs($this->staffA)
            ->delete(route('notifications.destroy', $notificationA->id))
            ->assertRedirect();

        $this->assertEquals(0, $this->staffA->fresh()->notifications()->count());
        $this->assertEquals(1, $this->staffB->fresh()->notifications()->count());

        // Create 2 notifications for Staff A and destroy all
        ActivityNotificationService::notifyUser($this->staffA, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi 1',
            'message' => 'Catatan 1',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);
        ActivityNotificationService::notifyUser($this->staffA, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi 2',
            'message' => 'Catatan 2',
            'actor_name' => 'Admin',
            'actor_role' => 'Admin',
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $this->assertEquals(2, $this->staffA->fresh()->notifications()->count());

        $this->actingAs($this->staffA)
            ->delete(route('notifications.destroy-all'))
            ->assertRedirect();

        $this->assertEquals(0, $this->staffA->fresh()->notifications()->count());
        $this->assertEquals(1, $this->staffB->fresh()->notifications()->count());
    }

    public function test_staff_notifications_pagination(): void
    {
        // 5 notifications -> no pagination
        for ($i = 1; $i <= 5; $i++) {
            ActivityNotificationService::notifyUser($this->staffA, [
                'activity_type' => 'attendance_canceled',
                'title' => "Presensi dibatalkan #{$i}",
                'message' => "Alasan pembatalan #{$i}",
                'actor_name' => 'Admin',
                'actor_role' => 'Admin',
                'info' => 'Presensi • Dibatalkan',
                'url' => route('attendance.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        $res5 = $this->actingAs($this->staffA)->get(route('notifications.index'));
        $res5->assertOk();
        $res5->assertDontSee('aria-label="Pagination"', false);

        // Add 6 more -> total 11 notifications -> pagination appears
        for ($i = 6; $i <= 11; $i++) {
            ActivityNotificationService::notifyUser($this->staffA, [
                'activity_type' => 'attendance_canceled',
                'title' => "Presensi dibatalkan #{$i}",
                'message' => "Alasan pembatalan #{$i}",
                'actor_name' => 'Admin',
                'actor_role' => 'Admin',
                'info' => 'Presensi • Dibatalkan',
                'url' => route('attendance.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        $res11 = $this->actingAs($this->staffA)->get(route('notifications.index'));
        $res11->assertOk();
        $res11->assertSee('aria-label="Pagination"', false);
        $res11->assertSee('Selanjutnya');

        $resPage2 = $this->actingAs($this->staffA)->get(route('notifications.index', ['page' => 2]));
        $resPage2->assertOk();
        $resPage2->assertSee('Sebelumnya');
    }
}
