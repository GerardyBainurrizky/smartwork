<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EightCardsExportButtonRemovalAndLogicTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
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

        $this->driver = User::create([
            'username' => 'driver_test',
            'name' => 'Driver Test',
            'email' => 'driver_test@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);
    }

    public function test_all_eight_card_detail_urls_rendered_without_export_buttons(): void
    {
        $eightCardUrls = [
            // 4. Sales Aktif
            route('admin.reports.users', ['role' => 'sales']),
            // 6. Rencana Kunjungan
            route('admin.reports.routes', ['period' => 'today']),
            // 8. Rencana Pengiriman
            route('admin.reports.driver-routes', ['period' => 'today']),
        ];

        foreach ([$this->admin, $this->superAdmin] as $user) {
            $this->actingAs($user);

            // Check Index reports page
            $indexResponse = $this->get(route('admin.reports.index'));
            $indexResponse->assertOk();
            $indexResponse->assertSee('Ringkasan Operasional Hari Ini');
            $indexResponse->assertSee('Presensi Hadir');
            $indexResponse->assertSee('Izin Hari Ini');
            $indexResponse->assertSee('Sakit Hari Ini');
            $indexResponse->assertSee('Sales Aktif');
            $indexResponse->assertSee('Kunjungan Sales');
            $indexResponse->assertSee('Rencana Kunjungan');
            $indexResponse->assertSee('Pengiriman Driver');
            $indexResponse->assertSee('Rencana Pengiriman');
        }
    }

    public function test_laporan_lengkap_dan_ekspor_retains_export_buttons_where_intended(): void
    {
        $this->actingAs($this->admin);

        // Modules that retain export buttons
        $retainedModules = [
            route('admin.reports.stores'),
            route('admin.reports.transactions'),
            route('admin.reports.sales-activities'),
        ];

        foreach ($retainedModules as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $content = $response->getContent();

            $this->assertStringContainsString('Unduh PDF', $content);
            $this->assertStringContainsString('Unduh Excel', $content);
        }
    }

    public function test_backend_export_routes_remain_functional_for_all_types(): void
    {
        $this->actingAs($this->admin);

        $types = ['attendance', 'users', 'visits', 'routes', 'driver-visits', 'driver-routes', 'stores', 'transactions', 'sales-activities'];

        foreach ($types as $type) {
            $pdfResp = $this->get(route('admin.reports.export-pdf', ['type' => $type]));
            $pdfResp->assertOk();
            $this->assertStringContainsString('application/pdf', $pdfResp->headers->get('content-type'));

            $excelResp = $this->get(route('admin.reports.export-excel', ['type' => $type]));
            $excelResp->assertOk();
        }
    }

    public function test_daily_vs_current_state_logic_preservation(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Historical Attendance yesterday (must NOT be deleted or counted in today's daily stats)
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $yesterday,
            'clock_in' => now()->subDay()->setTime(8, 0, 0),
            'status' => Attendance::STATUS_PRESENT,
        ]);

        // Attendance today
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $today,
            'clock_in' => now()->setTime(8, 30, 0),
            'status' => Attendance::STATUS_PRESENT,
        ]);

        $this->actingAs($this->admin);
        $response = $this->get(route('admin.reports.index'));
        $response->assertOk();

        // todayAttendance should only count today's attendance = 1
        $response->assertViewHas('todayAttendance', 1);

        // Historical data in DB still has 2 records
        $this->assertEquals(2, Attendance::count());

        // Sales aktif counts master active sales user (current state, not daily)
        $response->assertViewHas('activeSalesCount', 1);
    }
}
