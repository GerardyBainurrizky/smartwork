<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private const SELFIE = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==';

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['name' => 'Admin Test']);
        $user->assignRole('admin');

        return $user;
    }

    private function roleUser(string $name, string $roleName): User
    {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole($roleName);

        return $user;
    }

    private function postPayload(): array
    {
        return [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Raya Bogor No. 45, Jakarta Timur',
            'maps_url' => 'https://www.google.com/maps?q=-6.2088,106.8456',
            'selfie' => 'data:image/jpeg;base64,'.self::SELFIE,
        ];
    }

    private function canceledNotes(User $canceller, string $reason = 'Salah input'): string
    {
        return json_encode([
            'cancelled_by' => $canceller->id,
            'cancelled_at' => now()->toDateTimeString(),
            'reason' => $reason,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function test_admin_attendance_page_shows_new_status_vocabulary(): void
    {
        $admin = $this->adminUser();
        $checkedIn = $this->roleUser('Budi Santoso', 'sales');
        $checkedOut = $this->roleUser('Siti Aminah', 'staff');
        $canceled = $this->roleUser('Dedi Kurniawan', 'sales');

        Attendance::create([
            'user_id' => $checkedIn->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);
        Attendance::create([
            'user_id' => $checkedOut->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(5),
            'clock_out' => now()->subHour(),
        ]);
        Attendance::create([
            'user_id' => $canceled->id,
            'date' => now()->toDateString(),
            'status' => 'canceled',
            'notes' => $this->canceledNotes($admin),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $response->assertSee('Monitoring Presensi');
        $response->assertSee('Check In');
        $response->assertSee('Check Out');
        $response->assertSee('Dibatalkan');
        $response->assertSee($canceled->name);
        $response->assertSee('Salah input');
        $response->assertDontSee('Absen');
    }

    public function test_admin_attendance_stats_reflect_real_counts(): void
    {
        $admin = $this->adminUser();
        $sales = $this->roleUser('Andi Wijaya', 'sales');
        $staff = $this->roleUser('Ratna Sari', 'staff');
        $canceledUser = $this->roleUser('Dedi Kurniawan', 'sales');

        Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);
        Attendance::create([
            'user_id' => $staff->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(5),
            'clock_out' => now()->subHour(),
        ]);
        Attendance::create([
            'user_id' => $canceledUser->id,
            'date' => now()->toDateString(),
            'status' => 'canceled',
            'notes' => $this->canceledNotes($admin),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['checked_in']);
        $this->assertSame(1, $stats['checked_out']);
        $this->assertSame(1, $stats['canceled']);
    }

    public function test_admin_attendance_status_filter_works(): void
    {
        $admin = $this->adminUser();
        $checkedIn = $this->roleUser('Budi Santoso', 'sales');
        $checkedOut = $this->roleUser('Siti Aminah', 'sales');
        $canceled = $this->roleUser('Dedi Kurniawan', 'sales');

        Attendance::create([
            'user_id' => $checkedIn->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);
        Attendance::create([
            'user_id' => $checkedOut->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(5),
            'clock_out' => now()->subHour(),
        ]);
        Attendance::create([
            'user_id' => $canceled->id,
            'date' => now()->toDateString(),
            'status' => 'canceled',
            'notes' => $this->canceledNotes($admin),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', ['status' => 'checked_out']));

        $response->assertOk();
        $response->assertSee($checkedOut->name);
        $response->assertDontSee($checkedIn->name);
        $response->assertDontSee($canceled->name);
    }

    public function test_admin_attendance_role_filter_works(): void
    {
        $admin = $this->adminUser();
        $sales = $this->roleUser('Andi Wijaya', 'sales');
        $staff = $this->roleUser('Ratna Sari', 'staff');

        Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);
        Attendance::create([
            'user_id' => $staff->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(4),
            'clock_out' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', ['role' => 'staff']));

        $response->assertOk();
        $response->assertSee($staff->name);
        $response->assertDontSee($sales->name);
    }

    public function test_admin_can_cancel_attendance_with_reason(): void
    {
        $admin = $this->adminUser();
        $sales = $this->roleUser('Budi Santoso', 'sales');
        $attendance = Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
            'clock_out' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.attendance.cancel', $attendance->id), [
            'reason' => 'Salah input data presensi',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'canceled',
        ]);

        $attendance->refresh();
        $this->assertNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);

        $meta = json_decode($attendance->notes, true);
        $this->assertSame($admin->id, $meta['cancelled_by']);
        $this->assertArrayHasKey('cancelled_at', $meta);
        $this->assertSame('Salah input data presensi', $meta['reason']);
    }

    public function test_admin_canceling_requires_reason(): void
    {
        $admin = $this->adminUser();
        $sales = $this->roleUser('Budi Santoso', 'sales');
        $attendance = Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.attendance.cancel', $attendance->id), []);

        $response->assertSessionHasErrors('reason');
        $this->assertSame('present', $attendance->fresh()->status);
    }

    public function test_admin_cannot_cancel_already_canceled_attendance(): void
    {
        $admin = $this->adminUser();
        $sales = $this->roleUser('Budi Santoso', 'sales');
        $attendance = Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);

        $this->actingAs($admin)->post(route('admin.attendance.cancel', $attendance->id), [
            'reason' => 'Alasan pertama',
        ])->assertRedirect();

        $response = $this->actingAs($admin)->post(route('admin.attendance.cancel', $attendance->id), [
            'reason' => 'Alasan kedua',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('canceled', $attendance->fresh()->status);
        $this->assertSame('Alasan pertama', json_decode($attendance->fresh()->notes, true)['reason']);
    }

    public function test_admin_can_delete_attendance_and_selfie(): void
    {
        $admin = $this->adminUser();
        $sales = $this->roleUser('Budi Santoso', 'sales');
        $selfie = 'attendances/'.$sales->id.'/selfie.jpg';
        Storage::disk('public')->put($selfie, 'image-bytes');

        $attendance = Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
            'clock_in_selfie' => $selfie,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.attendance.destroy', $attendance->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
        Storage::disk('public')->assertMissing($selfie);
    }

    public function test_employee_can_check_in_again_after_cancel(): void
    {
        $admin = $this->adminUser();
        $sales = $this->roleUser('Budi Santoso', 'sales');
        Attendance::create([
            'user_id' => $sales->id,
            'date' => now()->toDateString(),
            'status' => 'canceled',
            'notes' => $this->canceledNotes($admin),
        ]);

        $response = $this->actingAs($sales)->postJson(route('attendance.check-in'), $this->postPayload());

        $response->assertOk();
        $attendance = Attendance::where('user_id', $sales->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotSame('canceled', $attendance->status);
        $this->assertNull($attendance->notes);
        $this->assertNotNull($attendance->clock_in);
    }

    public function test_canceled_attendance_excluded_from_report(): void
    {
        $admin = $this->adminUser();
        $valid = $this->roleUser('Andi Wijaya', 'sales');
        $canceled = $this->roleUser('Dedi Kurniawan', 'sales');

        Attendance::create([
            'user_id' => $valid->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(3),
        ]);
        Attendance::create([
            'user_id' => $canceled->id,
            'date' => now()->toDateString(),
            'status' => 'canceled',
            'notes' => $this->canceledNotes($admin),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.attendance', [
            'period' => 'custom',
            'from_date' => now()->toDateString(),
            'to_date' => now()->addDays(2)->toDateString(),
        ]));

        $response->assertOk();
        $attendances = $response->viewData('attendances');
        $userIds = collect($attendances->items())->pluck('user_id')->map(fn ($id) => (string) $id);
        $this->assertContains((string) $valid->id, $userIds);
        $this->assertNotContains((string) $canceled->id, $userIds);
    }

    public function test_admin_monitoring_shows_hadir_izin_sakit_and_belum_presensi(): void
    {
        $admin = $this->adminUser();
        $salesHadir = $this->roleUser('Fikri Sales', 'sales');
        $driverIzin = $this->roleUser('Ahmad Driver', 'driver');
        $staffSakit = $this->roleUser('Budi Staff', 'staff');
        $salesBelum = $this->roleUser('Andi Sales Belum', 'sales');

        Attendance::create([
            'user_id' => $salesHadir->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(4),
            'clock_in_lat' => -6.2088,
            'clock_in_lng' => 106.8456,
            'clock_in_address' => 'Kantor Sales',
        ]);

        Attendance::create([
            'user_id' => $driverIzin->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Izin urusan keluarga',
            'clock_in_lat' => -6.2100,
            'clock_in_lng' => 106.8500,
            'clock_in_address' => 'Rumah Driver',
        ]);

        Attendance::create([
            'user_id' => $staffSakit->id,
            'date' => now()->toDateString(),
            'status' => 'sakit',
            'absence_note' => 'Sakit demam',
            'clock_in_lat' => -6.2200,
            'clock_in_lng' => 106.8600,
            'clock_in_address' => 'Klinik Sehat',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $stats = $response->viewData('stats');

        $this->assertEquals(4, $stats['total_users']);
        $this->assertEquals(3, $stats['present']); // Hadir + Izin + Sakit
        $this->assertEquals(1, $stats['checked_in']); // Hanya Hadir yang checked in
        $this->assertEquals(1, $stats['izin']);
        $this->assertEquals(1, $stats['sakit']);
        $this->assertEquals(1, $stats['not_present']); // Belum presensi

        $response->assertSee('Fikri Sales');
        $response->assertSee('Ahmad Driver');
        $response->assertSee('Budi Staff');
        $response->assertSee('Andi Sales Belum');
        $response->assertSee('Izin urusan keluarga');
        $response->assertSee('Sakit demam');
    }

    public function test_admin_monitoring_filter_role_and_status_combination(): void
    {
        $admin = $this->adminUser();
        $salesIzin = $this->roleUser('Sales Izin User', 'sales');
        $driverIzin = $this->roleUser('Driver Izin User', 'driver');
        $salesSakit = $this->roleUser('Sales Sakit User', 'sales');

        Attendance::create([
            'user_id' => $salesIzin->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Izin Sales',
            'clock_in_lat' => -6.2,
            'clock_in_lng' => 106.8,
            'clock_in_address' => 'Lokasi Sales',
        ]);
        Attendance::create([
            'user_id' => $driverIzin->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Izin Driver',
            'clock_in_lat' => -6.2,
            'clock_in_lng' => 106.8,
            'clock_in_address' => 'Lokasi Driver',
        ]);
        Attendance::create([
            'user_id' => $salesSakit->id,
            'date' => now()->toDateString(),
            'status' => 'sakit',
            'absence_note' => 'Sakit Sales',
            'clock_in_lat' => -6.2,
            'clock_in_lng' => 106.8,
            'clock_in_address' => 'Lokasi Sakit',
        ]);

        // Filter: role=sales & status=izin -> Hanya Sales Izin yang muncul
        $response = $this->actingAs($admin)->get(route('admin.attendance.index', [
            'role' => 'sales',
            'status' => 'izin',
        ]));

        $response->assertOk();
        $attendances = $response->viewData('attendances');
        $names = collect($attendances->items())->map(fn ($a) => $a->user->name);

        $this->assertContains('Sales Izin User', $names);
        $this->assertNotContains('Driver Izin User', $names);
        $this->assertNotContains('Sales Sakit User', $names);
    }

    public function test_dashboard_admin_and_super_admin_statistics_with_izin_and_sakit(): void
    {
        $admin = $this->adminUser();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin = User::factory()->create(['name' => 'Super Admin Test']);
        $superAdmin->assignRole('super-admin');

        // Buat 10 Hadir, 2 Izin, 1 Sakit, 8 Belum Presensi (Total 21 User)
        $today = now()->toDateString();

        // 10 Hadir (6 sales, 2 driver, 2 staff)
        for ($i = 1; $i <= 6; $i++) {
            $u = $this->roleUser("Sales Hadir {$i}", 'sales');
            Attendance::create(['user_id' => $u->id, 'date' => $today, 'status' => 'present', 'clock_in' => now()->subHours(3)]);
        }
        for ($i = 1; $i <= 2; $i++) {
            $u = $this->roleUser("Driver Hadir {$i}", 'driver');
            Attendance::create(['user_id' => $u->id, 'date' => $today, 'status' => 'present', 'clock_in' => now()->subHours(3)]);
        }
        for ($i = 1; $i <= 2; $i++) {
            $u = $this->roleUser("Staff Hadir {$i}", 'staff');
            Attendance::create(['user_id' => $u->id, 'date' => $today, 'status' => 'present', 'clock_in' => now()->subHours(3)]);
        }

        // 2 Izin (1 sales, 1 driver)
        $salesIzin = $this->roleUser("Sales Izin 1", 'sales');
        Attendance::create(['user_id' => $salesIzin->id, 'date' => $today, 'status' => 'izin', 'absence_note' => 'Izin pribadi', 'clock_in_lat' => -6.2, 'clock_in_lng' => 106.8, 'clock_in_address' => 'Rumah']);
        $driverIzin = $this->roleUser("Driver Izin 1", 'driver');
        Attendance::create(['user_id' => $driverIzin->id, 'date' => $today, 'status' => 'izin', 'absence_note' => 'Izin keluarga', 'clock_in_lat' => -6.2, 'clock_in_lng' => 106.8, 'clock_in_address' => 'Rumah']);

        // 1 Sakit (1 staff)
        $staffSakit = $this->roleUser("Staff Sakit 1", 'staff');
        Attendance::create(['user_id' => $staffSakit->id, 'date' => $today, 'status' => 'sakit', 'absence_note' => 'Demam', 'clock_in_lat' => -6.2, 'clock_in_lng' => 106.8, 'clock_in_address' => 'Rumah']);

        // 8 Belum Presensi (4 sales, 2 driver, 2 staff)
        for ($i = 1; $i <= 4; $i++) {
            $this->roleUser("Sales Belum {$i}", 'sales');
        }
        for ($i = 1; $i <= 2; $i++) {
            $this->roleUser("Driver Belum {$i}", 'driver');
        }
        for ($i = 1; $i <= 2; $i++) {
            $this->roleUser("Staff Belum {$i}", 'staff');
        }

        // 1. Uji Dashboard Admin
        $resAdmin = $this->actingAs($admin)->get(route('dashboard'));
        $resAdmin->assertOk();
        $this->assertEquals(21, $resAdmin->viewData('attendanceBase')); // 21 user
        $this->assertEquals(13, $resAdmin->viewData('todayAttendance')); // 13 sudah presensi
        $this->assertEquals(10, $resAdmin->viewData('hadirCount')); // 10 hadir
        $this->assertEquals(2, $resAdmin->viewData('izinCount')); // 2 izin
        $this->assertEquals(1, $resAdmin->viewData('sakitCount')); // 1 sakit
        $this->assertEquals(8, $resAdmin->viewData('totalAbsent')); // 8 belum presensi

        // 2. Uji Dashboard Super Admin
        $resSuperAdmin = $this->actingAs($superAdmin)->get(route('dashboard'));
        $resSuperAdmin->assertOk();
        $this->assertEquals(21, $resSuperAdmin->viewData('operationalUserCount'));
        $this->assertEquals(13, $resSuperAdmin->viewData('presentCount'));
        $this->assertEquals(10, $resSuperAdmin->viewData('hadirCount'));
        $this->assertEquals(2, $resSuperAdmin->viewData('izinCount'));
        $this->assertEquals(1, $resSuperAdmin->viewData('sakitCount'));
        $this->assertEquals(8, $resSuperAdmin->viewData('absentCount'));
    }

    public function test_driver_hadir_does_not_leak_izin_notes_and_table_shows_cancellation_notes(): void
    {
        $admin = $this->adminUser();
        $driverHadir = $this->roleUser('Driver Budi Hadir', 'driver');
        $driverIzin = $this->roleUser('Driver Joko Izin', 'driver');
        $staffCanceled = $this->roleUser('Staff Rina Batal', 'staff');

        // 1. Driver Hadir (Check in)
        Attendance::create([
            'user_id' => $driverHadir->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => now()->subHours(4),
            'clock_out' => now()->subHour(),
            'clock_in_lat' => -6.2088,
            'clock_in_lng' => 106.8456,
            'clock_in_address' => 'Kantor Driver',
            'absence_note' => null,
        ]);

        // 2. Driver Izin
        Attendance::create([
            'user_id' => $driverIzin->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Izin mengurus SIM',
            'clock_in_lat' => -6.2100,
            'clock_in_lng' => 106.8500,
            'clock_in_address' => 'Rumah Driver Joko',
        ]);

        // 3. Staff Dibatalkan
        Attendance::create([
            'user_id' => $staffCanceled->id,
            'date' => now()->toDateString(),
            'status' => 'canceled',
            'notes' => json_encode([
                'cancelled_by' => $admin->id,
                'cancelled_at' => now()->toDateTimeString(),
                'reason' => 'Lokasi GPS mencurigakan',
                'previous_status' => 'present',
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));
        $response->assertOk();

        // Kolom Catatan Pembatalan tampil di header tabel
        $response->assertSee('Catatan Pembatalan');
        $response->assertDontSee('Catatan / Foto');

        // Alasan pembatalan tampil pada kolom pembatalan
        $response->assertSee('Lokasi GPS mencurigakan');

        // Di tabel, catatan izin "Izin mengurus SIM" TIDAK ditampilkan secara langsung di baris tabel (hanya ada di modal detail)
        $attendances = $response->viewData('attendances');
        $driverHadirRecord = collect($attendances->items())->firstWhere('user_id', $driverHadir->id);
        $this->assertNotNull($driverHadirRecord);
        $this->assertNull($driverHadirRecord->absence_note);
        $this->assertEquals('checked_out', $driverHadirRecord->status_presensi);

        $driverIzinRecord = collect($attendances->items())->firstWhere('user_id', $driverIzin->id);
        $this->assertNotNull($driverIzinRecord);
        $this->assertEquals('Izin mengurus SIM', $driverIzinRecord->absence_note);
        $this->assertEquals('izin', $driverIzinRecord->status_presensi);
    }
}
