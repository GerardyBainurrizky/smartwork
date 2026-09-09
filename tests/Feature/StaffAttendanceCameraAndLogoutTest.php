<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffAttendanceCameraAndLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected User $admin;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->staff = User::factory()->create(['name' => 'Staff Tester', 'username' => 'staff_test', 'status' => 'active']);
        $this->staff->assignRole('staff');

        $this->admin = User::factory()->create(['name' => 'Admin Tester', 'username' => 'admin_test', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Tester', 'username' => 'super_test', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');
    }

    /**
     * 1. Test Attendance Page Render for Staff with all status forms (Hadir, Izin, Sakit)
     * and ensure distinct video/canvas element IDs for Izin and Sakit.
     */
    public function test_staff_attendance_page_renders_cleanly_with_distinct_camera_elements(): void
    {
        $response = $this->actingAs($this->staff)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('camera-preview-izin');
        $response->assertSee('camera-canvas-izin');
        $response->assertSee('camera-preview-sakit');
        $response->assertSee('camera-canvas-sakit');
        $response->assertSee('camera-preview');
        $response->assertSee('camera-canvas');
        $response->assertSee('playsinline');
        $response->assertSee('muted');
        $response->assertSee('Pilih Status Presensi');
    }

    /**
     * 2. Test Staff Submit Sakit Attendance with Photo and Note.
     */
    public function test_staff_can_submit_sakit_attendance_with_photo_and_note(): void
    {
        // 1x1 transparent PNG base64
        $dummySelfie = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($this->staff)->postJson(route('attendance.submit-absence'), [
            'type' => 'sakit',
            'absence_note' => 'Demam dan flu berat, istirahat dokter.',
            'selfie' => $dummySelfie,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Jenderal Sudirman No. 1, Jakarta',
            'maps_url' => 'https://www.google.com/maps?q=-6.200000,106.816666',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.status', 'sakit');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->staff->id,
            'status' => 'sakit',
            'absence_note' => 'Demam dan flu berat, istirahat dokter.',
        ]);

        // Verify that after submitting Sakit, attendance page shows Sakit status
        $this->actingAs($this->staff)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('Presensi Sakit Terkirim');
    }

    /**
     * 3. Test Staff Submit Izin Attendance with Photo and Note.
     */
    public function test_staff_can_submit_izin_attendance_with_photo_and_note(): void
    {
        $dummySelfie = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($this->staff)->postJson(route('attendance.submit-absence'), [
            'type' => 'izin',
            'absence_note' => 'Keperluan keluarga mendesak.',
            'selfie' => $dummySelfie,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Jenderal Sudirman No. 1, Jakarta',
            'maps_url' => 'https://www.google.com/maps?q=-6.200000,106.816666',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.status', 'izin');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->staff->id,
            'status' => 'izin',
            'absence_note' => 'Keperluan keluarga mendesak.',
        ]);

        $this->actingAs($this->staff)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('Presensi Izin Terkirim');
    }

    /**
     * 4. Test Staff Normal Logout (Success, Session Invalidated, Redirect to Login).
     */
    public function test_staff_can_logout_cleanly(): void
    {
        $this->actingAs($this->staff);
        $this->assertAuthenticatedAs($this->staff);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * 5. Test Staff Logout when Token Mismatch / Expired Session gracefully redirects to login instead of 419 error.
     */
    public function test_staff_logout_with_expired_session_or_mismatch_token_redirects_to_login_gracefully(): void
    {
        $this->actingAs($this->staff);

        // Send POST to /logout with an invalid/stale CSRF token
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => 'invalid-stale-csrf-token',
        ])->post(route('logout'), [
            '_token' => 'invalid-stale-csrf-token',
        ]);

        // Must redirect gracefully without 419 Page Expired
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * 6. Test Multiple / Sequential Logout attempts (5 times) consistently succeed without error.
     */
    public function test_staff_logout_repeatable_five_times_without_error(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = User::factory()->create(['status' => 'active']);
            $user->assignRole('staff');

            $this->actingAs($user);
            $this->assertAuthenticatedAs($user);

            // Access a staff page before logging out
            if ($i === 2) {
                $this->get(route('dashboard'))->assertOk();
            } elseif ($i === 3) {
                $this->get(route('notifications.index'))->assertOk();
            } elseif ($i === 4) {
                $this->get(route('attendance.index'))->assertOk();
            } elseif ($i === 5) {
                $this->get(route('staff.report.index'))->assertOk();
            }

            $response = $this->post(route('logout'));
            $response->assertRedirect();
            $this->assertGuest();
        }
    }

    /**
     * 7. Test Sidebar critical CSS in head prevents layout flicker on mobile.
     */
    public function test_layout_contains_critical_mobile_sidebar_styles_in_head(): void
    {
        $response = $this->actingAs($this->staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('.sw-sidebar {', false);
        $response->assertSee('transform: translateX(-100%) !important;', false);
        $response->assertSee('visibility: hidden !important;', false);
    }

    /**
     * 8. Test Form Inputs have min 16px font-size on mobile to prevent browser automatic pinch zoom.
     */
    public function test_staff_form_inputs_have_mobile_auto_zoom_prevention_styles(): void
    {
        $response = $this->actingAs($this->staff)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('font-size: 16px !important;', false);

        $attendanceResponse = $this->actingAs($this->staff)->get(route('attendance.index'));
        $attendanceResponse->assertOk();
        $attendanceResponse->assertSee('text-base sm:text-sm');

        $reportResponse = $this->actingAs($this->staff)->get(route('staff.report.index'));
        $reportResponse->assertOk();
        $reportResponse->assertSee('font-size: 16px !important;', false);
    }
}
