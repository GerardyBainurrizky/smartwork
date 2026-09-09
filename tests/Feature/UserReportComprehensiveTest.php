<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReportComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $driver;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->sales = User::where('username', 'karyawan')->first();
        $this->staff = User::where('username', 'staff')->first();

        // Create driver user
        $this->driver = User::create([
            'name' => 'Driver Budi',
            'username' => 'driverbudi',
            'email' => 'driverbudi@example.com',
            'phone' => '08123456789',
            'password' => 'password',
            'status' => 'active',
        ]);
        $this->driver->assignRole('driver');
    }

    public function test_only_admin_and_super_admin_can_access_user_report(): void
    {
        // Super Admin can access
        $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.users'))
            ->assertOk()
            ->assertSee('Laporan Pengguna')
            ->assertSee('Unduh PDF')
            ->assertSee('Unduh Excel');

        // Admin can access
        $this->actingAs($this->admin)
            ->get(route('admin.reports.users'))
            ->assertOk()
            ->assertSee('Laporan Pengguna');

        // Sales cannot access
        $this->actingAs($this->sales)
            ->get(route('admin.reports.users'))
            ->assertRedirect();

        // Staff cannot access
        $this->actingAs($this->staff)
            ->get(route('admin.reports.users'))
            ->assertRedirect();

        // Driver cannot access
        $this->actingAs($this->driver)
            ->get(route('admin.reports.users'))
            ->assertRedirect();
    }

    public function test_user_report_statistics_and_filtering(): void
    {
        $this->actingAs($this->admin);

        // Filter Sales
        $response = $this->get(route('admin.reports.users', ['role' => 'sales']));
        $response->assertOk();
        $response->assertSee($this->sales->name);
        $response->assertSee($this->sales->username);
        $response->assertDontSee($this->driver->name);

        // Filter Driver
        $response = $this->get(route('admin.reports.users', ['role' => 'driver']));
        $response->assertOk();
        $response->assertSee($this->driver->name);
        $response->assertSee($this->driver->username);
        $response->assertDontSee($this->sales->name);

        // Search
        $response = $this->get(route('admin.reports.users', ['search' => 'driverbudi']));
        $response->assertOk();
        $response->assertSee($this->driver->name);
        $response->assertDontSee($this->sales->name);
    }

    public function test_user_report_pdf_and_excel_export(): void
    {
        $this->actingAs($this->admin);

        // PDF Export All
        $pdfResp = $this->get(route('admin.reports.export-pdf', ['type' => 'users']));
        $pdfResp->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResp->headers->get('content-type'));

        // Excel Export Filtered
        $excelResp = $this->get(route('admin.reports.export-excel', [
            'type' => 'users',
            'role' => 'driver',
        ]));
        $excelResp->assertOk();
        $this->assertNotEmpty($excelResp->streamedContent());
    }
}
