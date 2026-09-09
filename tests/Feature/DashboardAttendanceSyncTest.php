<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardAttendanceSyncTest extends TestCase
{
    use RefreshDatabase;

    private function salesUser(): User
    {
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('sales');
        return $user;
    }

    private function payload(): array
    {
        return [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Test No. 1',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
            'selfie' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==',
        ];
    }

    public function test_dashboard_shows_belum_presensi_before_check_in(): void
    {
        Storage::fake('public');
        $user = $this->salesUser();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum Presensi');
    }

    public function test_dashboard_shows_sedang_bekerja_after_check_in(): void
    {
        Storage::fake('public');
        $user = $this->salesUser();

        $this->actingAs($user)->postJson(route('attendance.check-in'), $this->payload())->assertOk();

        $att = Attendance::where('user_id', $user->id)->first();
        $this->assertNotNull($att);
        $this->assertNotNull($att->clock_in);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sedang Bekerja')
            ->assertSee('Presensi Hari Ini');
    }

    public function test_dashboard_shows_presensi_selesai_after_check_out(): void
    {
        Storage::fake('public');
        $user = $this->salesUser();

        $this->actingAs($user)->postJson(route('attendance.check-in'), $this->payload())->assertOk();
        $this->actingAs($user)->postJson(route('attendance.check-out'), $this->payload())->assertOk();

        $att = Attendance::where('user_id', $user->id)->first();
        $this->assertNotNull($att->clock_in);
        $this->assertNotNull($att->clock_out);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Presensi Selesai');
    }

    public function test_dashboard_status_is_for_logged_in_user_only(): void
    {
        Storage::fake('public');
        $user = $this->salesUser();
        $other = $this->salesUser();

        $this->actingAs($other)->postJson(route('attendance.check-in'), $this->payload())->assertOk();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum Presensi');
    }

    public function test_dashboard_shows_izin_when_status_is_izin(): void
    {
        Storage::fake('public');
        $user = $this->salesUser();

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'izin',
            'absence_note' => 'Izin urusan keluarga penting',
            'clock_in_lat' => -6.2,
            'clock_in_lng' => 106.8,
            'clock_in_address' => 'Rumah',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Izin')
            ->assertSee('Izin urusan keluarga penting')
            ->assertDontSee('Check Out Kerja');
    }

    public function test_dashboard_shows_sakit_when_status_is_sakit(): void
    {
        Storage::fake('public');
        $user = $this->salesUser();

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'sakit',
            'absence_note' => 'Sakit demam',
            'clock_in_lat' => -6.2,
            'clock_in_lng' => 106.8,
            'clock_in_address' => 'Rumah',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sakit')
            ->assertSee('Sakit demam')
            ->assertDontSee('Check Out Kerja');
    }

    public function test_dashboard_shows_dibatalkan_when_attendance_canceled(): void
    {
        Storage::fake('public');
        $user = $this->salesUser();

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'canceled',
            'notes' => json_encode(['reason' => 'Salah input lokasi']),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dibatalkan');
    }
}
