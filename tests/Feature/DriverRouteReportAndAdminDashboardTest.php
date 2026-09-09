<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DriverRouteReportAndAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $driver;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->sales = User::where('username', 'karyawan')->first();

        $this->driver = User::create([
            'username' => 'driver1',
            'name' => 'Budi Driver',
            'email' => 'driver@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-001',
            'name' => 'Toko Subur',
            'owner' => 'Pak Joko',
            'type' => 'Toko Kelontong',
            'address' => 'Jl. Kebon Jeruk No. 10',
            'city' => 'Jakarta Barat',
            'kecamatan' => 'Kebon Jeruk',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);

        // Sales Route
        $salesRoute = Route::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Kunjungan Sales A',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHour(),
        ]);
        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $salesRoute->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        // Driver Route
        $driverRoute = Route::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Pengiriman Driver B',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $driverRoute->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_admin_dashboard_renders_all_four_user_and_store_cards(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Total Sales', $content);
        $this->assertStringContainsString('Total Driver', $content);
        $this->assertStringContainsString('Total Staff', $content);
        $this->assertStringContainsString('Toko Terdaftar', $content);
    }

    public function test_driver_routes_report_page_renders_only_driver_data(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.reports.driver-routes'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Laporan Rencana Pengiriman', $content);
        $this->assertStringContainsString('Rute Pengiriman Driver B', $content);
        $this->assertStringContainsString('Budi Driver', $content);
        $this->assertStringNotContainsString('Rute Kunjungan Sales A', $content);
        $this->assertStringNotContainsString('Sales Demo', $content);
    }

    public function test_sales_routes_report_page_renders_only_sales_data(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.reports.routes'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Laporan Rencana Kunjungan', $content);
        $this->assertStringContainsString('Rute Kunjungan Sales A', $content);
        $this->assertStringContainsString('Sales Demo', $content);
        $this->assertStringNotContainsString('Rute Pengiriman Driver B', $content);
        $this->assertStringNotContainsString('Budi Driver', $content);
    }

    public function test_driver_routes_report_export_pdf_and_excel(): void
    {
        $this->actingAs($this->admin);

        $pdf = $this->get(route('admin.reports.export-pdf', ['type' => 'driver-routes']));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));

        $excel = $this->get(route('admin.reports.export-excel', ['type' => 'driver-routes']));
        $excel->assertOk();
        $this->assertNotEmpty($excel->streamedContent());
    }

    public function test_driver_routes_excel_formatting_and_stops_readability(): void
    {
        $this->actingAs($this->admin);

        $export = new \App\Exports\DriverRoutesExport([
            'fromDate' => now()->startOfMonth()->toDateString(),
            'toDate' => now()->endOfMonth()->toDateString(),
            'userId' => $this->driver->id,
            'status' => 'draft',
        ]);

        $headings = $export->headings();
        $this->assertEquals([
            'No',
            'Nama Driver',
            'Rute Pengiriman',
            'Tanggal Pengiriman',
            'Jumlah Tujuan',
            'Tujuan Dikunjungi',
            'Status Pengiriman',
            'Durasi Aktual',
            'Dibuat Oleh',
            'Catatan Rencana',
            'Daftar Tujuan',
            'Status Tujuan',
            'Catatan',
            'Maps',
        ], $headings);

        $rows = $export->dataRows();
        $this->assertNotEmpty($rows);

        // Baris pertama: Daftar Tujuan (index 10), Status Tujuan (index 11), Catatan (index 12), Maps (index 13)
        $this->assertEquals('Toko Subur', $rows[0][10]);
        $this->assertEquals('Belum Dikunjungi', $rows[0][11]);
        $this->assertEquals('-', $rows[0][12]);
        $this->assertEquals('-', $rows[0][13]);

        // Generate binary and parse with PhpSpreadsheet
        $excelContent = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $tempPath = tempnam(sys_get_temp_dir(), 'dr_test_') . '.xlsx';
        file_put_contents($tempPath, $excelContent);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Verifikasi metadata
        $metaValues = [];
        for ($r = 1; $r <= 10; $r++) {
            $metaValues[] = (string)$sheet->getCell("A{$r}")->getValue();
        }
        $metaText = implode(' ', $metaValues);
        $this->assertStringContainsString('Driver: Budi Driver', $metaText);
        $this->assertStringContainsString('Status: Draft', $metaText);

        // Verifikasi Column widths
        $this->assertEquals(28, $sheet->getColumnDimension('K')->getWidth());
        $this->assertEquals(18, $sheet->getColumnDimension('L')->getWidth());
        $this->assertEquals(30, $sheet->getColumnDimension('M')->getWidth());
        $this->assertEquals(15, $sheet->getColumnDimension('N')->getWidth());

        // Verifikasi row formatting
        $destCell = $sheet->getCell("K{$highestRow}");
        $this->assertTrue($destCell->getStyle()->getAlignment()->getWrapText());
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP, $destCell->getStyle()->getAlignment()->getVertical());

        @unlink($tempPath);
    }

    public function test_driver_routes_pdf_metadata_consistency(): void
    {
        $this->actingAs($this->admin);

        // Case 1: Filter Driver spesifik & Status Draft
        $response1 = $this->get(route('admin.reports.export-pdf', [
            'type' => 'driver-routes',
            'user_id' => $this->driver->id,
            'status' => 'draft',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));
        $response1->assertOk();

        // Render view langsung untuk memeriksa metadata ter-render
        $view1 = view('reports.pdf.driver-routes', [
            'title' => 'LAPORAN RENCANA PENGIRIMAN DRIVER',
            'items' => Route::where('user_id', $this->driver->id)->get(),
            'selectedUserLabel' => $this->driver->name,
            'statusFilterLabel' => 'Draft',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'periodLabel' => 'Hari Ini (' . now()->format('d M Y') . ')',
            'fromDate' => now()->toDateString(),
            'toDate' => now()->toDateString(),
            'generatedAt' => now()->format('d M Y, H:i'),
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('Driver', $view1);
        $this->assertStringContainsString('Budi Driver', $view1);
        $this->assertStringContainsString('Status Rute', $view1);
        $this->assertStringContainsString('Draft', $view1);
        $this->assertStringContainsString('Dicetak Oleh', $view1);
        $this->assertStringContainsString($this->admin->name, $view1);

        // Case 2: Semua Driver & Semua Status
        $view2 = view('reports.pdf.driver-routes', [
            'title' => 'LAPORAN RENCANA PENGIRIMAN DRIVER',
            'items' => Route::all(),
            'selectedUserLabel' => 'Semua Driver',
            'statusFilterLabel' => 'Semua Status',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'periodLabel' => 'Semua Periode',
            'fromDate' => null,
            'toDate' => null,
            'generatedAt' => now()->format('d M Y, H:i'),
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('Semua Driver', $view2);
        $this->assertStringContainsString('Semua Status', $view2);
    }

    public function test_super_admin_can_access_driver_routes_report(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.reports.driver-routes'));
        $response->assertOk();
        $this->assertStringContainsString('Laporan Rencana Pengiriman', $response->getContent());
    }

    public function test_driver_cannot_access_admin_reports(): void
    {
        $this->actingAs($this->driver);

        $this->get(route('admin.reports.index'))->assertRedirect();
        $this->get(route('admin.reports.driver-routes'))->assertRedirect();
    }

    public function test_driver_routes_pdf_and_excel_have_zero_estimasi_and_clear_separated_stops_and_maps(): void
    {
        $this->actingAs($this->admin);

        // Store with coordinates
        $storeWithCoords = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-002',
            'name' => 'Toko Padi Berkah',
            'owner' => 'Bu Siti',
            'type' => 'Toko Pertanian',
            'address' => 'Jl. Merdeka No. 45',
            'city' => 'Subang',
            'kecamatan' => 'Pamanukan',
            'province' => 'Jawa Barat',
            'latitude' => -6.300464,
            'longitude' => 107.820510,
            'status' => 'active',
        ]);

        $multiRoute = Route::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Multi Driver C',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);

        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $multiRoute->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
            'notes' => 'Barang diterima lengkap',
        ]);

        RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $multiRoute->id,
            'store_id' => $storeWithCoords->id,
            'sequence' => 2,
            'status' => 'pending',
            'notes' => null,
        ]);

        // 1. EXCEL VERIFICATION
        $export = new \App\Exports\DriverRoutesExport([
            'fromDate' => now()->startOfMonth()->toDateString(),
            'toDate' => now()->endOfMonth()->toDateString(),
            'userId' => $this->driver->id,
        ]);

        $headings = $export->headings();
        $this->assertCount(14, $headings);
        $this->assertNotContains('Estimasi Durasi', $headings);
        $this->assertNotContains('Estimasi', $headings);
        $this->assertEquals('Daftar Tujuan', $headings[10]);
        $this->assertEquals('Status Tujuan', $headings[11]);
        $this->assertEquals('Catatan', $headings[12]);
        $this->assertEquals('Maps', $headings[13]);

        foreach ($headings as $h) {
            $this->assertStringNotContainsStringIgnoringCase('estimasi', $h);
            $this->assertStringNotContainsStringIgnoringCase('estimated', $h);
        }

        $rows = $export->dataRows();
        $this->assertNotEmpty($rows);

        // Cari baris-baris rute multi
        $multiRows = collect($rows)->where(2, 'Rute Multi Driver C')->values();
        $this->assertCount(2, $multiRows);

        // Stop 1: Toko Subur (no coordinates)
        $this->assertEquals('Toko Subur', $multiRows[0][10]);
        $this->assertEquals('Dikunjungi', $multiRows[0][11]);
        $this->assertEquals('Barang diterima lengkap', $multiRows[0][12]);
        $this->assertEquals('-', $multiRows[0][13]);

        // Stop 2: Toko Padi Berkah (has coordinates)
        $this->assertEquals('Toko Padi Berkah', $multiRows[1][10]);
        $this->assertEquals('Belum Dikunjungi', $multiRows[1][11]);
        $this->assertEquals('-', $multiRows[1][12]);
        $this->assertEquals('Buka Maps', $multiRows[1][13]);

        // Physical XLSX workbook inspection for Hyperlink
        $excelContent = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $tempPath = tempnam(sys_get_temp_dir(), 'dr_multi_test_') . '.xlsx';
        file_put_contents($tempPath, $excelContent);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Cari heading row
        $headingRow = null;
        for ($r = 1; $r <= 15; $r++) {
            if ($sheet->getCell("K{$r}")->getValue() === 'Daftar Tujuan') {
                $headingRow = $r;
                break;
            }
        }
        $this->assertNotNull($headingRow);
        $this->assertEquals('Daftar Tujuan', $sheet->getCell("K{$headingRow}")->getValue());
        $this->assertEquals('Status Tujuan', $sheet->getCell("L{$headingRow}")->getValue());
        $this->assertEquals('Catatan', $sheet->getCell("M{$headingRow}")->getValue());
        $this->assertEquals('Maps', $sheet->getCell("N{$headingRow}")->getValue());

        // Cari baris data untuk Toko Padi Berkah (stop 2)
        $padiRow = null;
        for ($r = $headingRow + 1; $r <= $highestRow; $r++) {
            if ($sheet->getCell("K{$r}")->getValue() === 'Toko Padi Berkah') {
                $padiRow = $r;
                break;
            }
        }
        $this->assertNotNull($padiRow);
        $padiMapsCell = $sheet->getCell("N{$padiRow}");
        $this->assertEquals('Buka Maps', $padiMapsCell->getValue());
        $this->assertEquals('https://www.google.com/maps?q=-6.300464,107.82051', $padiMapsCell->getHyperlink()->getUrl());

        @unlink($tempPath);

        // 2. PDF VIEW VERIFICATION
        $view = view('reports.pdf.driver-routes', [
            'title' => 'LAPORAN RENCANA PENGIRIMAN DRIVER',
            'items' => Route::where('id', $multiRoute->id)->get(),
            'selectedUserLabel' => $this->driver->name,
            'statusFilterLabel' => 'Semua Status',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'periodLabel' => 'Hari Ini (' . now()->format('d M Y') . ')',
            'fromDate' => now()->toDateString(),
            'toDate' => now()->toDateString(),
            'generatedAt' => now()->format('d M Y, H:i'),
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringNotContainsStringIgnoringCase('Estimasi Durasi', $view);
        $this->assertStringNotContainsString('<th>Estimasi</th>', $view);
        $this->assertStringNotContainsString('<th style="width:8%">Estimasi</th>', $view);
        $this->assertStringContainsString('Dikunjungi', $view);
        $this->assertStringContainsString('Belum Dikunjungi', $view);
        $this->assertStringContainsString('Buka Maps', $view);
        $this->assertStringContainsString('https://www.google.com/maps?q=-6.300464,107.82051', $view);
    }
}
