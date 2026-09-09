<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeViewsSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function salesUser(): User
    {
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('sales');
        return $user;
    }

    public function test_sales_dashboard_renders(): void
    {
        $user = $this->salesUser();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Presensi Hari Ini')->assertSee('Jadwal Kunjungan Hari Ini');
        $response->assertSee('Rencana Kunjungan')->assertSee('Kunjungan Hari Ini')->assertSee('Profil Saya');
    }

    public function test_visit_index_renders_today_plan(): void
    {
        $user = $this->salesUser();
        $store = Store::create([
            'code' => 'ST-001', 'name' => 'Toko Sumber Tani',
            'address' => 'Jl. Raya Bogor No. 45', 'city' => 'Jakarta Timur',
            'province' => 'DKI Jakarta', 'status' => 'active',
        ]);
        $route = Route::create(['user_id' => $user->id, 'name' => 'Rute Hari Ini', 'date' => now()->toDateString(), 'status' => 'draft']);
        RouteStop::create(['route_id' => $route->id, 'store_id' => $store->id, 'sequence' => 1, 'status' => 'pending']);

        $this->assertDatabaseHas('routes', ['user_id' => $user->id, 'name' => 'Rute Hari Ini']);
        $this->assertDatabaseHas('route_stops', ['route_id' => $route->id, 'store_id' => $store->id]);

        $response = $this->actingAs($user)->get(route('visit.index'));
        $response->assertOk();
        $response->assertSee('Kunjungan Hari Ini')->assertSee('Toko Sumber Tani')->assertSee('check-in');
    }

    public function test_attendance_index_renders(): void
    {
        $user = $this->salesUser();
        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertOk()->assertSee('Presensi');
    }

    private function adminUser(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_dashboard_renders(): void
    {
        $user = $this->adminUser('admin');
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Master Toko')->assertSee('Rencana Kunjungan')->assertSee('Laporan');
    }

    public function test_super_admin_dashboard_renders(): void
    {
        $user = $this->adminUser('super-admin');
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('Isa SmartWork')->assertSee('Masuk');
        $response->assertSee('Satu Platform untuk Aktivitas Operasional');
        $response->assertSee('Kelola presensi, kunjungan, rute, dan aktivitas sales secara lebih terstruktur dalam satu sistem.');
        $response->assertDontSee('Satu ruang kerja untuk aktivitas operasional yang lebih terarah.');
        $response->assertSee('Username')->assertSee('Password');
        $response->assertSee('Lupa Password?');
        $response->assertSee('Ingat saya')->assertDontSee('Ingat saya selama 30 hari');
        $response->assertSee('Kirim Tautan');
        $response->assertSee('Masukkan username Anda untuk menerima tautan');
        $response->assertSee('PT ISA TRI SELARAS GEMILANG');
        $response->assertDontSee('Selamat datang kembali');
        $response->assertDontSee('Akses Sistem');
        $response->assertDontSee('Sign In');
        $response->assertDontSee('Enterprise Security');
    }
}