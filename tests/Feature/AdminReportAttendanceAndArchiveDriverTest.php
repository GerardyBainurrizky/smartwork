<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DataArchive;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportAttendanceAndArchiveDriverTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $driver;
    private User $staff;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->sales = User::where('username', 'karyawan')->first();
        $this->staff = User::where('username', 'staff')->first();

        $this->driver = User::create([
            'username' => 'driver1',
            'name' => 'Budi Driver',
            'email' => 'driver@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);

        $this->store = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-001',
            'name' => 'Toko Sumber Rejeki',
            'owner' => 'Pak Budi',
            'address' => 'Jl. Kebon Jeruk No. 1',
            'status' => 'active',
        ]);
    }

    public function test_attendance_report_role_and_user_filtering_with_accurate_stats(): void
    {
        $this->actingAs($this->admin);

        $today = now()->toDateString();

        // Staff checked in and checked out
        Attendance::create([
            'user_id' => $this->staff->id,
            'date' => $today,
            'clock_in' => now()->subHours(8),
            'clock_out' => now()->subHours(1),
            'status' => 'present',
        ]);

        // Sales checked in only
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $today,
            'clock_in' => now()->subHours(4),
            'status' => 'present',
        ]);

        // Driver has NOT checked in (0 presensi)

        // 1. Role = Driver
        $response = $this->get(route('admin.reports.attendance', [
            'period' => 'today',
            'role' => 'driver',
        ]));
        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertSame(1, $stats['total_users']);
        $this->assertSame(0, $stats['present']);
        $this->assertSame(0, $stats['checked_in']);
        $this->assertSame(0, $stats['checked_out']);
        $this->assertSame(1, $stats['not_present']);
        $this->assertSame(0, $stats['percentage']);

        // 2. Role = Sales
        $responseSales = $this->get(route('admin.reports.attendance', [
            'period' => 'today',
            'role' => 'sales',
        ]));
        $responseSales->assertOk();
        $statsSales = $responseSales->viewData('stats');
        $this->assertSame(1, $statsSales['total_users']);
        $this->assertSame(1, $statsSales['present']);
        $this->assertSame(1, $statsSales['checked_in']);
        $this->assertSame(0, $statsSales['checked_out']);
        $this->assertSame(0, $statsSales['not_present']);
        $this->assertSame(100, $statsSales['percentage']);

        // 3. Role = Staff
        $responseStaff = $this->get(route('admin.reports.attendance', [
            'period' => 'today',
            'role' => 'staff',
        ]));
        $responseStaff->assertOk();
        $statsStaff = $responseStaff->viewData('stats');
        $this->assertSame(1, $statsStaff['total_users']);
        $this->assertSame(1, $statsStaff['present']);
        $this->assertSame(0, $statsStaff['checked_in']);
        $this->assertSame(1, $statsStaff['checked_out']);
        $this->assertSame(0, $statsStaff['not_present']);
        $this->assertSame(100, $statsStaff['percentage']);

        // 4. Role = All (Semua Pengguna)
        $responseAll = $this->get(route('admin.reports.attendance', [
            'period' => 'today',
        ]));
        $responseAll->assertOk();
        $statsAll = $responseAll->viewData('stats');
        $this->assertSame(3, $statsAll['total_users']); // Sales + Driver + Staff
        $this->assertSame(2, $statsAll['present']); // Staff + Sales
        $this->assertSame(1, $statsAll['checked_in']); // Sales
        $this->assertSame(1, $statsAll['checked_out']); // Staff
        $this->assertSame(1, $statsAll['not_present']); // Driver
        $this->assertSame(67, $statsAll['percentage']);
    }

    public function test_attendance_report_exports_with_izin_and_sakit(): void
    {
        $today = now()->toDateString();

        // 1. Sales Hadir
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $today,
            'status' => 'present',
            'clock_in' => now()->subHours(4),
            'clock_out' => now()->subHour(),
            'clock_in_lat' => -6.2088,
            'clock_in_lng' => 106.8456,
            'clock_in_address' => 'Kantor Pusat',
        ]);

        // 2. Driver Izin
        Attendance::create([
            'user_id' => $this->driver->id,
            'date' => $today,
            'status' => 'izin',
            'absence_note' => 'Izin keperluan dinas luar',
            'clock_in_lat' => -6.2100,
            'clock_in_lng' => 106.8500,
            'clock_in_address' => 'Rumah Driver',
        ]);

        // 3. Staff Sakit
        Attendance::create([
            'user_id' => $this->staff->id,
            'date' => $today,
            'status' => 'sakit',
            'absence_note' => 'Sakit demam berdarah',
            'clock_in_lat' => -6.2200,
            'clock_in_lng' => 106.8600,
            'clock_in_address' => 'Rumah Sakit',
        ]);

        // Test Export PDF
        $resPdf = $this->actingAs($this->admin)->get(route('admin.reports.export-pdf', [
            'type' => 'attendance',
            'period' => 'today',
        ]));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));

        // Test Export Excel
        $resExcel = $this->actingAs($this->admin)->get(route('admin.reports.export-excel', [
            'type' => 'attendance',
            'period' => 'today',
        ]));
        $resExcel->assertOk();
    }

    public function test_driver_archive_categories_preview_archive_restore_and_purge(): void
    {
        $this->actingAs($this->superAdmin);

        $today = now()->toDateString();

        // Create Driver Route & Driver Visit
        $driverRoute = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Pengiriman Driver Barat',
            'date' => $today,
            'status' => 'completed',
        ]);
        $driverStop = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $driverRoute->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $driverVisit = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'route_stop_id' => $driverStop->id,
            'route_id' => $driverRoute->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
        ]);

        // Create Sales Route & Sales Visit to ensure isolation
        $salesRoute = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Kunjungan Sales Utara',
            'date' => $today,
            'status' => 'completed',
        ]);
        $salesStop = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $salesRoute->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $salesVisit = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->sales->id,
            'route_stop_id' => $salesStop->id,
            'route_id' => $salesRoute->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(3),
            'check_out_at' => now()->subHours(2),
        ]);

        // 1. Test Preview for Driver Routes (route_driver)
        $preview = $this->postJson(route('admin.archives.preview'), [
            'data_type' => 'route_driver',
            'start_date' => $today,
            'end_date' => $today,
        ]);
        $preview->assertOk();
        $this->assertSame(1, $preview->json('counts.route_driver'));
        $this->assertSame(0, $preview->json('counts.route'));

        // 2. Archive Driver Delivery (visit_driver)
        $archiveRes = $this->post(route('admin.archives.store'), [
            'data_type' => 'visit_driver',
            'start_date' => $today,
            'end_date' => $today,
            'notes' => 'Arsip Pengiriman Driver',
        ]);
        $archiveRes->assertRedirect(route('admin.archives.index'));

        // Assert Driver Visit is archived
        $this->assertNotNull($driverVisit->fresh()->archived_at);
        // Assert Sales Visit is NOT archived (still active)
        $this->assertNull($salesVisit->fresh()->archived_at);

        $archive = DataArchive::where('data_type', 'visit_driver')->latest()->first();
        $this->assertNotNull($archive);

        // 3. Test Export Driver Visit Archive
        $pdfRes = $this->get(route('admin.archives.export-pdf', ['id' => $archive->id, 'type' => 'visit_driver']));
        $pdfRes->assertOk();

        $excelRes = $this->get(route('admin.archives.export-excel', ['id' => $archive->id, 'type' => 'visit_driver']));
        $excelRes->assertOk();

        // 4. Test Restore
        $restoreRes = $this->post(route('admin.archives.restore', $archive->id));
        $restoreRes->assertRedirect(route('admin.archives.index'));
        $this->assertNull($driverVisit->fresh()->archived_at);
    }
}
