<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffNotificationMobileAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'staff']);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin Ops', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->staff = User::factory()->create(['name' => 'Siti Staff', 'status' => 'active']);
        $this->staff->assignRole('staff');
    }

    public function test_scenario_1_staff_has_5_notifications_no_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            ActivityNotificationService::notifyUser($this->staff, [
                'activity_type' => 'attendance_canceled',
                'title' => "Presensi dibatalkan #{$i}",
                'message' => "Admin membatalkan presensi Anda #{$i}.",
                'actor_name' => 'Admin Ops',
                'actor_role' => 'Admin',
                'reason' => "Alasan pembatalan #{$i}",
                'info' => 'Presensi • Dibatalkan',
                'url' => route('attendance.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        $response = $this->actingAs($this->staff)->get(route('notifications.index'));
        $response->assertOk();
        $response->assertSee('Presensi dibatalkan');
        $response->assertSee('Alasan pembatalan #1');
        $response->assertSee('Alasan pembatalan #5');
        // Pagination nav should not be rendered
        $response->assertDontSee('aria-label="Pagination"', false);
    }

    public function test_scenario_2_staff_has_exactly_10_notifications_no_pagination(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            ActivityNotificationService::notifyUser($this->staff, [
                'activity_type' => 'attendance_canceled',
                'title' => "Presensi dibatalkan #{$i}",
                'message' => "Admin membatalkan presensi Anda #{$i}.",
                'actor_name' => 'Admin Ops',
                'actor_role' => 'Admin',
                'reason' => "Alasan pembatalan ke-{$i}",
                'info' => 'Presensi • Dibatalkan',
                'url' => route('attendance.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        $response = $this->actingAs($this->staff)->get(route('notifications.index'));
        $response->assertOk();
        $response->assertSee('Alasan pembatalan ke-1');
        $response->assertSee('Alasan pembatalan ke-10');
        // Exactly 10 items in 1 page => hasPages() is false => no pagination rendered
        $response->assertDontSee('aria-label="Pagination"', false);
    }

    public function test_scenario_3_staff_has_11_notifications_pagination_appears(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            ActivityNotificationService::notifyUser($this->staff, [
                'activity_type' => 'attendance_canceled',
                'title' => "Presensi dibatalkan #{$i}",
                'message' => "Admin membatalkan presensi Anda #{$i}.",
                'actor_name' => 'Admin Ops',
                'actor_role' => 'Admin',
                'reason' => "Alasan-{$i}",
                'info' => 'Presensi • Dibatalkan',
                'url' => route('attendance.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        $staffNotifications = $this->staff->notifications()->get();
        $index = 1;
        foreach ($staffNotifications as $notif) {
            $notif->created_at = now()->subMinutes(20 - $index);
            $notif->save();
            $index++;
        }

        // Page 1
        $response1 = $this->actingAs($this->staff)->get(route('notifications.index'));
        $response1->assertOk();
        $response1->assertSee('aria-label="Pagination"', false);
        $response1->assertSee('Selanjutnya');
        $response1->assertSee('Alasan-11'); // latest

        // Page 2
        $response2 = $this->actingAs($this->staff)->get(route('notifications.index', ['page' => 2]));
        $response2->assertOk();
        $response2->assertSee('Sebelumnya');
        $response2->assertSee('Alasan-1'); // oldest
    }

    public function test_scenario_4_staff_has_over_20_notifications_pagination_splits_correctly(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            ActivityNotificationService::notifyUser($this->staff, [
                'activity_type' => 'attendance_canceled',
                'title' => "Presensi dibatalkan #{$i}",
                'message' => "Admin membatalkan presensi Anda #{$i}.",
                'actor_name' => 'Admin Ops',
                'actor_role' => 'Admin',
                'reason' => "BatchAlasanKe-{$i}",
                'info' => 'Presensi • Dibatalkan',
                'url' => route('attendance.index'),
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        $staffNotifications = $this->staff->notifications()->get();
        $index = 1;
        foreach ($staffNotifications as $notif) {
            $notif->created_at = now()->subMinutes(30 - $index);
            $notif->save();
            $index++;
        }

        $response1 = $this->actingAs($this->staff)->get(route('notifications.index'));
        $response1->assertOk();
        $response1->assertSeeText('BatchAlasanKe-25');
        $response1->assertDontSeeText('BatchAlasanKe-5');

        $response3 = $this->actingAs($this->staff)->get(route('notifications.index', ['page' => 3]));
        $response3->assertOk();
        $response3->assertSeeText('BatchAlasanKe-1');
        $response3->assertSeeText('BatchAlasanKe-5');
        $response3->assertDontSeeText('BatchAlasanKe-25');
    }

    public function test_scenario_5_filter_preserves_query_string_during_pagination(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            ActivityNotificationService::notifyUser($this->staff, [
                'activity_type' => 'attendance_canceled',
                'title' => "Presensi unread #{$i}",
                'message' => "Admin membatalkan presensi unread #{$i}.",
                'actor_name' => 'Admin Ops',
                'actor_role' => 'Admin',
                'reason' => "Alasan unread #{$i}",
                'info' => 'Presensi • Dibatalkan',
                'url' => route('attendance.index'),
                'activity_time' => now()->addSeconds($i)->toIso8601String(),
            ]);
        }

        $response = $this->actingAs($this->staff)->get(route('notifications.index', [
            'type' => 'attendance',
            'status' => 'unread',
        ]));

        $response->assertOk();
        $response->assertSee('type=attendance');
        $response->assertSee('status=unread');
        $response->assertSee('page=2');
    }

    public function test_scenario_6_long_cancellation_reason_renders_without_breaking(): void
    {
        $longReason = 'Lokasi presensi Anda berada di luar radius toleransi sejauh 15.8 kilometer dari titik koordinat kantor yang telah didaftarkan pada sistem ISA SmartWork. Silakan pastikan GPS akurat dan lakukan presensi ulang jika diizinkan.';

        ActivityNotificationService::notifyUser($this->staff, [
            'activity_type' => 'attendance_canceled',
            'title' => 'Presensi dibatalkan',
            'message' => 'Super Admin membatalkan presensi Anda.',
            'actor_name' => 'Super Admin',
            'actor_role' => 'Super Admin',
            'reason' => $longReason,
            'info' => 'Presensi • Dibatalkan',
            'url' => route('attendance.index'),
            'activity_time' => now()->toIso8601String(),
        ]);

        $response = $this->actingAs($this->staff)->get(route('notifications.index'));
        $response->assertOk();
        $response->assertSee('Alasan Pembatalan:');
        $response->assertSee($longReason);
        $response->assertSee('break-words');
    }
}
