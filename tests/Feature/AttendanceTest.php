<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // Minimal valid 1x1 white JPEG (base64, no data URI prefix).
    private const SELFIE = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==';

    private function salesUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function postPayload(): array
    {
        return [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Raya Bogor No. 45, Jakarta Timur',
            'maps_url' => 'https://www.google.com/maps?q=-6.2088,106.8456',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
        ];
    }

    public function test_check_in_saves_attendance(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('attendance.check-in'), $this->postPayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'checked_in');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance, 'Attendance record was not created.');
        $this->assertEquals(now()->toDateString(), $attendance->date->toDateString());
        $this->assertNotNull($attendance->clock_in);
        $this->assertNotNull($attendance->clock_in_lat);
        $this->assertNotNull($attendance->clock_in_lng);
        $this->assertNotNull($attendance->clock_in_address);
        $this->assertNotNull($attendance->clock_in_selfie);
        $this->assertNull($attendance->clock_out);
    }

    public function test_duplicate_check_in_is_rejected(): void
    {
        $user = $this->salesUser();

        $this->actingAs($user)->postJson(route('attendance.check-in'), $this->postPayload())->assertOk();

        $response = $this->actingAs($user)->postJson(route('attendance.check-in'), $this->postPayload());

        $response->assertStatus(409);
    }

    public function test_check_out_requires_check_in_first(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('attendance.check-out'), $this->postPayload());

        $response->assertStatus(409);
    }

    public function test_check_out_after_check_in_completes_flow(): void
    {
        $user = $this->salesUser();

        $this->actingAs($user)->postJson(route('attendance.check-in'), $this->postPayload())->assertOk();

        $response = $this->actingAs($user)->postJson(route('attendance.check-out'), $this->postPayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'completed');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();
        $this->assertNotNull($attendance->clock_in);
        $this->assertNotNull($attendance->clock_out);
        $this->assertNotNull($attendance->clock_out_selfie);
        $this->assertTrue($attendance->isComplete());
    }

    public function test_check_out_requires_valid_selfie(): void
    {
        $user = $this->salesUser();

        $this->actingAs($user)->postJson(route('attendance.check-in'), $this->postPayload())->assertOk();

        $payload = $this->postPayload();
        unset($payload['selfie']);

        $response = $this->actingAs($user)->postJson(route('attendance.check-out'), $payload);

        $response->assertStatus(422);
    }

    public function test_izin_submission_works_for_sales(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'izin',
            'absence_note' => 'Keperluan keluarga mendesak',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Rumah Karyawan No. 12, Jakarta',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('izin', $attendance->status);
        $this->assertEquals('Keperluan keluarga mendesak', $attendance->absence_note);
        $this->assertNotNull($attendance->clock_in_selfie);
        $this->assertEquals(-6.2088, (float) $attendance->clock_in_lat);
        $this->assertEquals(106.8456, (float) $attendance->clock_in_lng);
        $this->assertEquals('Jl. Rumah Karyawan No. 12, Jakarta', $attendance->clock_in_address);
        $this->assertNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);
        $this->assertTrue($attendance->isIzin());
        $this->assertTrue($attendance->isAbsence());
    }

    public function test_sakit_submission_works_for_driver(): void
    {
        $role = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'sakit',
            'absence_note' => 'Demam tinggi dan tidak bisa hadir',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'latitude' => -6.5500,
            'longitude' => 107.6000,
            'address' => 'Rumah Driver, Subang',
            'maps_url' => 'https://maps.google.com/?q=-6.5500,107.6000',
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('sakit', $attendance->status);
        $this->assertEquals(-6.5500, (float) $attendance->clock_in_lat);
        $this->assertEquals(107.6000, (float) $attendance->clock_in_lng);
        $this->assertEquals('Rumah Driver, Subang', $attendance->clock_in_address);
        $this->assertTrue($attendance->isSakit());
        $this->assertNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);
    }

    public function test_izin_submission_requires_note(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'izin',
            'absence_note' => '',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Rumah Karyawan No. 12',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
        ]);

        $response->assertStatus(422);
    }

    public function test_izin_submission_requires_photo(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'izin',
            'absence_note' => 'Keperluan keluarga',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Rumah Karyawan No. 12',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
        ]);

        $response->assertStatus(422);
    }

    public function test_izin_submission_requires_location(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'izin',
            'absence_note' => 'Keperluan keluarga',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
        ]);

        $response->assertStatus(422);
    }

    public function test_sakit_submission_requires_location(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'sakit',
            'absence_note' => 'Sakit demam',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_rejected_for_izin_status_backend(): void
    {
        $user = $this->salesUser();

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Keperluan keluarga',
        ]);

        $response = $this->actingAs($user)->postJson(route('attendance.check-out'), $this->postPayload());

        $response->assertStatus(409);
        $this->assertStringContainsString('Izin', $response->json('message'));
    }

    public function test_checkout_rejected_for_sakit_status_backend(): void
    {
        $user = $this->salesUser();

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'sakit',
            'absence_note' => 'Demam',
        ]);

        $response = $this->actingAs($user)->postJson(route('attendance.check-out'), $this->postPayload());

        $response->assertStatus(409);
        $this->assertStringContainsString('Sakit', $response->json('message'));
    }

    public function test_duplicate_absence_rejected(): void
    {
        $user = $this->salesUser();

        $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'izin',
            'absence_note' => 'Keperluan keluarga',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Rumah Karyawan No. 12',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
        ])->assertOk();

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'sakit',
            'absence_note' => 'Demam',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Rumah Karyawan No. 12',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
        ]);

        $response->assertStatus(409);
    }

    public function test_izin_counted_as_present_not_absent(): void
    {
        $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Izin',
        ]);

        $this->assertEquals('izin', $attendance->status_presensi);
        $this->assertEquals('Izin', $attendance->status_presensi_label);
        $this->assertTrue($attendance->isAbsence());
        $this->assertFalse($attendance->is_canceled);
    }

    public function test_staff_izin_workflow(): void
    {
        $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'izin',
            'absence_note' => 'Urusan keluarga penting',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'latitude' => -6.2000,
            'longitude' => 106.8000,
            'address' => 'Rumah Staff, Jakarta',
            'maps_url' => 'https://maps.google.com/?q=-6.2000,106.8000',
        ]);

        $response->assertOk();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('izin', $attendance->status);
        $this->assertEquals('Urusan keluarga penting', $attendance->absence_note);
        $this->assertEquals(-6.2000, (float) $attendance->clock_in_lat);
        $this->assertEquals(106.8000, (float) $attendance->clock_in_lng);
        $this->assertNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);
    }

    public function test_staff_sakit_workflow(): void
    {
        $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->postJson(route('attendance.submit-absence'), [
            'type' => 'sakit',
            'absence_note' => 'Flu dan demam',
            'selfie' => 'data:image/jpeg;base64,' . self::SELFIE,
            'latitude' => -6.3000,
            'longitude' => 106.9000,
            'address' => 'Rumah Staff, Depok',
            'maps_url' => 'https://maps.google.com/?q=-6.3000,106.9000',
        ]);

        $response->assertOk();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('sakit', $attendance->status);
        $this->assertEquals('Flu dan demam', $attendance->absence_note);
        $this->assertEquals(-6.3000, (float) $attendance->clock_in_lat);
        $this->assertEquals(106.9000, (float) $attendance->clock_in_lng);
    }

    public function test_check_in_and_check_out_with_notes_for_sales_driver_staff(): void
    {
        $roles = ['sales', 'driver', 'staff'];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $user = User::factory()->create(['status' => 'active']);
            $user->assignRole($role);

            // 1. Check in dengan keterangan
            $payload = array_merge($this->postPayload(), [
                'notes' => "Izin masuk terlambat karena ban kendaraan bocor untuk {$roleName}",
            ]);

            $checkInRes = $this->actingAs($user)->postJson(route('attendance.check-in'), $payload);
            $checkInRes->assertOk();

            $att = Attendance::where('user_id', $user->id)
                ->whereDate('date', now()->toDateString())
                ->first();

            $this->assertNotNull($att);
            $this->assertEquals("Izin masuk terlambat karena ban kendaraan bocor untuk {$roleName}", $att->notes);

            // 2. Check out
            $checkOutRes = $this->actingAs($user)->postJson(route('attendance.check-out'), $this->postPayload());
            $checkOutRes->assertOk();

            $att->refresh();
            $this->assertNotNull($att->clock_out);
            $this->assertStringContainsString("Izin masuk terlambat karena ban kendaraan bocor untuk {$roleName}", $att->notes);
        }
    }

    public function test_admin_and_super_admin_can_view_keterangan(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole($adminRole);

        $salesRole = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $sales = User::factory()->create(['name' => 'Sales Budi', 'status' => 'active']);
        $sales->assignRole($salesRole);

        $att = Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_in_lat' => -6.2088,
            'clock_in_lng' => 106.8456,
            'clock_in_address' => 'Jakarta',
            'clock_in_selfie' => 'selfies/test.jpg',
            'status' => 'present',
            'notes' => 'Ada kendala kendaraan di perjalanan',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', ['date' => now()->toDateString()]));
        $response->assertOk();
        $response->assertSee('Ada kendala kendaraan di perjalanan');
    }

    public function test_attendance_page_renders_cleanly_for_sales_driver_staff(): void
    {
        $roles = ['sales', 'driver', 'staff'];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $user = User::factory()->create(['status' => 'active']);
            $user->assignRole($role);

            $response = $this->actingAs($user)->get(route('attendance.index'));
            $response->assertOk();
            $response->assertSee('Pilih Status Presensi');
            $response->assertSee('Hadir');
            $response->assertSee('Izin');
            $response->assertSee('Sakit');
        }
    }

    public function test_attendance_page_renders_cleanly_when_already_izin_or_sakit(): void
    {
        $role = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Izin keperluan keluarga',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertOk();
        $response->assertSee('Presensi Izin Terkirim');
        $response->assertSee('Presensi Anda hari ini telah tercatat.');
    }
}
