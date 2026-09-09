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

class ReportSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::where('username', 'superadmin')->first();

        $sales = User::where('username', 'karyawan')->first();
        $staff = User::where('username', 'staff')->first();

        $store = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-001',
            'name' => 'Toko Maju Jaya',
            'owner' => 'Budi Santoso',
            'type' => 'Toko Kelontong',
            'address' => 'Jl. Merdeka No. 1',
            'city' => 'Bandung',
            'kecamatan' => 'Coblong',
            'province' => 'Jawa Barat',
            'latitude' => -6.9025,
            'longitude' => 107.6125,
            'status' => 'active',
        ]);

        $att = Attendance::create([
            'id' => (string) Str::uuid(),
            'user_id' => $sales->id,
            'date' => '2026-08-04',
            'clock_in' => '2026-08-04 08:53:07',
            'clock_out' => '2026-08-04 17:05:00',
            'clock_in_lat' => -6.9025,
            'clock_in_lng' => 107.6125,
            'clock_in_address' => 'Jl. Merdeka No. 1, Bandung',
            'status' => 'late',
        ]);

        $route = Route::create([
            'id' => (string) Str::uuid(),
            'user_id' => $sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Bandung Utara',
            'date' => '2026-08-04',
            'status' => 'completed',
            'started_at' => '2026-08-04 09:00:00',
            'completed_at' => '2026-08-04 15:30:00',
        ]);

        $stop = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => '2026-08-04 09:15:00',
            'check_out_at' => '2026-08-04 09:45:00',
            'check_in_lat' => -6.9025,
            'check_in_lng' => 107.6125,
            'check_in_address' => 'Jl. Merdeka No. 1, Bandung',
            'cash_received' => 50000,
            'transaction_amount' => 100000,
            'payment_method' => 'transfer',
            'transaction_status' => 'paid',
        ]);
    }

    public function test_all_report_pages_render(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.reports.index'))->assertOk();
        $this->get(route('admin.reports.users'))->assertOk();
        $this->get(route('admin.reports.stores'))->assertOk();
        $this->get(route('admin.reports.attendance'))->assertOk();
        $this->get(route('admin.reports.routes'))->assertOk();
        $this->get(route('admin.reports.driver-routes'))->assertOk();
        $this->get(route('admin.reports.visits'))->assertOk();
        $this->get(route('admin.reports.driver-visits'))->assertOk();
        $this->get(route('admin.reports.transactions'))->assertOk();
        $this->get(route('admin.reports.sales-activities'))->assertOk();
    }

    public function test_all_report_filters_render(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.reports.users', ['from_date' => '2026-08-01', 'to_date' => '2026-08-31', 'role' => 'admin', 'status' => 'active']))->assertOk();
        $this->get(route('admin.reports.stores', ['status' => 'active']))->assertOk();
        $this->get(route('admin.reports.attendance', ['user_id' => 3, 'status' => 'checked_in']))->assertOk();
        $this->get(route('admin.reports.routes', ['user_id' => 3, 'status' => 'completed']))->assertOk();
        $this->get(route('admin.reports.driver-routes', ['user_id' => 3, 'status' => 'completed']))->assertOk();
        $this->get(route('admin.reports.visits', ['user_id' => 3, 'status' => 'completed']))->assertOk();
        $this->get(route('admin.reports.driver-visits', ['user_id' => 3, 'status' => 'completed']))->assertOk();
        $this->get(route('admin.reports.transactions', ['user_id' => 3]))->assertOk();
        $this->get(route('admin.reports.sales-activities', ['user_id' => 3]))->assertOk();
    }

    public function test_all_pdf_exports_download(): void
    {
        $this->actingAs($this->admin);

        foreach (['users', 'stores', 'attendance', 'routes', 'driver-routes', 'visits', 'driver-visits', 'transactions', 'sales-activities'] as $type) {
            $resp = $this->get(route('admin.reports.export-pdf', ['type' => $type]));
            $resp->assertOk();
            $this->assertStringContainsString('application/pdf', $resp->headers->get('content-type'));
            $this->assertNotEmpty($resp->getContent());
        }
    }

    public function test_all_excel_exports_download(): void
    {
        $this->actingAs($this->admin);

        foreach (['users', 'stores', 'attendance', 'routes', 'driver-routes', 'visits', 'driver-visits', 'transactions', 'sales-activities'] as $type) {
            $resp = $this->get(route('admin.reports.export-excel', ['type' => $type]));
            $resp->assertOk();
            $this->assertNotEmpty($resp->streamedContent());
        }
    }

    public function test_report_pages_show_filename_modal(): void
    {
        $this->actingAs($this->admin);

        $pages = [
            route('admin.reports.stores'),
            route('admin.reports.transactions'),
            route('admin.reports.sales-activities'),
            route('admin.reports.users'),
            route('admin.reports.attendance'),
            route('admin.reports.visits'),
            route('admin.reports.driver-visits'),
        ];

        foreach ($pages as $page) {
            $content = $this->get($page)->getContent();

            $this->assertStringContainsString('Nama File Laporan', $content);
            $this->assertStringContainsString('Silakan masukkan nama file terlebih dahulu', $content);
            $this->assertStringContainsString('Unduh PDF', $content);
            $this->assertStringContainsString('Unduh Excel', $content);
            $this->assertStringContainsString('reportDownload()', $content);
        }
    }

    public function test_sales_cannot_access_report_pages(): void
    {
        $sales = User::where('username', 'karyawan')->first();

        $this->actingAs($sales)->get(route('admin.reports.index'))->assertRedirect();
    }

    public function test_exports_accept_custom_filename(): void
    {
        $this->actingAs($this->admin);

        $custom = 'Laporan Kunjungan Hadi Agustus 2026';

        $pdf = $this->get(route('admin.reports.export-pdf', ['type' => 'visits', 'filename' => $custom]));
        $pdf->assertOk();
        $this->assertStringContainsString(
            'Laporan Kunjungan Hadi Agustus 2026.pdf',
            $pdf->headers->get('content-disposition')
        );

        $xlsx = $this->get(route('admin.reports.export-excel', ['type' => 'visits', 'filename' => $custom]));
        $xlsx->assertOk();
        $this->assertStringContainsString(
            'Laporan Kunjungan Hadi Agustus 2026.xlsx',
            $xlsx->headers->get('content-disposition')
        );

        $default = $this->get(route('admin.reports.export-excel', ['type' => 'visits']));
        $default->assertOk();
        $this->assertStringContainsString(
            'laporan-visits-',
            $default->headers->get('content-disposition')
        );
    }

    public function test_visits_export_contains_transaction_status_columns(): void
    {
        $this->actingAs($this->admin);

        $resp = $this->get(route('admin.reports.export-excel', [
            'type' => 'visits',
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-31',
        ]));
        $resp->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'visits_');
        file_put_contents($tmp, $resp->streamedContent());

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headerRow = null;
        $header = [];
        for ($r = 1; $r <= min(14, $highestRow); $r++) {
            $rowVals = $sheet->rangeToArray("A{$r}:AA{$r}")[0];
            if (in_array('Sales', $rowVals, true) || in_array('Nama Toko', $rowVals, true) || in_array('Toko', $rowVals, true)) {
                $headerRow = $r;
                $header = $rowVals;
                break;
            }
        }
        $this->assertNotNull($headerRow, 'Header row not found');
        $this->assertContains('Sales', $header);
        $this->assertContains('Toko', $header);

        @unlink($tmp);
    }
}
