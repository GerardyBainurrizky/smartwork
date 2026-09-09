<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_role_migration_renames_karyawan_to_sales(): void
    {
        $karyawan = Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($karyawan);
        $permission = Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']);
        $karyawan->givePermissionTo($permission);

        $migration = require base_path('database/migrations/2026_08_06_000003_rename_karyawan_role_to_sales_add_staff.php');
        $migration->up();

        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole('sales'));
        $this->assertFalse($user->hasRole('karyawan'));
        $this->assertTrue(DB::table('roles')->where('name', 'karyawan')->doesntExist());
        $this->assertTrue(DB::table('roles')->where('name', 'staff')->exists());

        $sales = Role::where('name', 'sales')->first();
        $this->assertTrue($sales->hasPermissionTo($permission->name));
    }

    public function test_role_migration_merges_when_sales_already_exists(): void
    {
        $karyawan = Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
        $sales = Role::create(['name' => 'sales', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'attendance.view', 'guard_name' => 'web']);
        $karyawan->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($karyawan);

        $migration = require base_path('database/migrations/2026_08_06_000003_rename_karyawan_role_to_sales_add_staff.php');
        $migration->up();

        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole('sales'));
        $this->assertTrue(DB::table('roles')->where('name', 'karyawan')->doesntExist());

        $sales->refresh();
        $this->assertTrue($sales->hasPermissionTo($permission->name));
    }

    public function test_staff_can_access_dashboard_attendance_and_profile(): void
    {
        $user = $this->userWithRole('staff');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Presensi Hari Ini')
            ->assertSee('Laporan Presensi Saya');

        $this->actingAs($user)->get(route('attendance.index'))->assertOk();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_staff_is_redirected_to_dashboard_from_sales_pages(): void
    {
        $user = $this->userWithRole('staff');

        $this->actingAs($user)->get(route('route.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('route.create'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('visit.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('visit.history'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('admin.stores.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('admin.visits.index'))->assertRedirect(route('dashboard'));
    }

    public function test_sales_can_access_route_and_visit_pages(): void
    {
        $user = $this->userWithRole('sales');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kunjungan Hari Ini');

        $this->actingAs($user)->get(route('route.index'))->assertOk();
        $this->actingAs($user)->get(route('route.history'))->assertOk();
        $this->actingAs($user)->get(route('visit.index'))->assertOk();
        $this->actingAs($user)->get(route('visit.history'))->assertOk();
    }

    public function test_admin_and_super_admin_can_access_admin_pages(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->get(route('admin.stores.index'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $superAdmin = $this->userWithRole('super-admin');
        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('dashboard'))->assertOk();
    }

    public function test_staff_sidebar_shows_only_dashboard_presensi_profil(): void
    {
        $user = $this->userWithRole('staff');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Dashboard', $content);
        $this->assertStringContainsString('Presensi', $content);
        $this->assertStringContainsString('Profil Saya', $content);

        $this->assertStringNotContainsString(route('route.index'), $content);
        $this->assertStringNotContainsString(route('visit.index'), $content);
        $this->assertStringNotContainsString(route('admin.users.index'), $content);
        $this->assertStringNotContainsString(route('admin.stores.index'), $content);
        $this->assertStringNotContainsString('Riwayat Rute', $content);
        $this->assertStringNotContainsString('Riwayat Kunjungan', $content);
        $this->assertStringNotContainsString('Monitoring Rute', $content);
    }

    public function test_sales_sidebar_shows_route_and_visit(): void
    {
        $user = $this->userWithRole('sales');

        $content = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString(route('route.index'), $content);
        $this->assertStringContainsString(route('visit.index'), $content);
        $this->assertStringContainsString('Presensi', $content);
    }

    public function test_staff_dashboard_does_not_show_sales_data(): void
    {
        $user = $this->userWithRole('staff');

        $content = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringNotContainsString('Kunjungan Hari Ini', $content);
        $this->assertStringNotContainsString('Rencana Kunjungan', $content);
        $this->assertStringNotContainsString('Transaksi Hari Ini', $content);
        $this->assertStringNotContainsString('Jadwal Kunjungan', $content);
    }

    public function test_super_admin_is_redirected_from_sales_pages_to_admin_monitoring(): void
    {
        $user = $this->userWithRole('super-admin');

        $this->actingAs($user)->get(route('route.index'))->assertRedirect(route('admin.routes.index'));
        $this->actingAs($user)->get(route('route.create'))->assertRedirect(route('admin.routes.create'));
        $this->actingAs($user)->get(route('route.history'))->assertRedirect(route('admin.routes.report'));
        $this->actingAs($user)->get(route('visit.index'))->assertRedirect(route('admin.visits.index'));
        $this->actingAs($user)->get(route('visit.history'))->assertRedirect(route('admin.visits.index'));
        $this->actingAs($user)->get(route('attendance.index'))->assertRedirect(route('admin.attendance.index'));
        $this->actingAs($user)->get(route('attendance.history'))->assertRedirect(route('admin.attendance.index'));
    }

    public function test_super_admin_cannot_perform_personal_presence_or_sales_route_store(): void
    {
        $user = $this->userWithRole('super-admin');

        $this->actingAs($user)->post(route('attendance.check-in'), [])->assertStatus(403);
        $this->actingAs($user)->post(route('attendance.check-out'), [])->assertStatus(403);
        $this->actingAs($user)->get(route('attendance.status'))->assertStatus(403);
        $this->actingAs($user)->postJson(route('route.store'), [])->assertStatus(403);
    }

    public function test_super_admin_sidebar_shows_only_monitoring_menus(): void
    {
        $user = $this->userWithRole('super-admin');

        $content = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString(route('admin.users.index'), $content);
        $this->assertStringContainsString(route('admin.stores.index'), $content);
        $this->assertStringContainsString(route('admin.routes.index'), $content);
        $this->assertStringContainsString(route('admin.visits.index'), $content);
        $this->assertStringContainsString(route('admin.attendance.index'), $content);
        $this->assertStringContainsString(route('admin.reports.index'), $content);

        $this->assertStringNotContainsString('href="/route"', $content);
        $this->assertStringNotContainsString('href="/visit"', $content);
        $this->assertStringNotContainsString('href="/attendance"', $content);
    }

    public function test_seeder_creates_staff_demo_account(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::where('username', 'staff')->first();
        $this->assertNotNull($user);
        $this->assertSame('Staff Demo', $user->name);
        $this->assertSame('staff@isa-smartwork.com', $user->email);
        $this->assertSame('active', $user->status);
        $this->assertTrue($user->hasRole('staff'));
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
