<?php

namespace Tests\Feature;

use App\Exports\RoutesExport;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RouteNotesReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store = Store::create([
            'code' => 'ST-001',
            'name' => 'Toko Subang Jaya',
            'city' => 'Subang',
            'kecamatan' => 'Subang',
            'province' => 'Jawa Barat',
            'status' => 'active',
        ]);
    }

    public function test_pdf_report_displays_route_general_notes_and_stop_notes(): void
    {
        $this->store->update([
            'latitude' => -6.565432,
            'longitude' => 107.765432,
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Subang Selatan',
            'date' => '2026-08-25',
            'notes' => 'Fokus penagihan invoice minggu lalu',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'notes' => 'Cek display produk baru di rak depan',
            'status' => 'pending',
        ]);

        $viewContent = view('reports.pdf.routes', [
            'items' => collect([$route->load(['stops.store', 'user', 'creator'])]),
            'periodLabel' => 'Semua Periode',
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-31',
            'generatedAt' => now()->format('d M Y, H:i'),
            'printedBy' => 'Administrator',
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'title' => 'LAPORAN RENCANA KUNJUNGAN',
        ])->render();

        $this->assertStringContainsString('Catatan Rencana', $viewContent);
        $this->assertStringContainsString('Fokus penagihan invoice minggu lalu', $viewContent);
        $this->assertStringContainsString('Catatan Toko', $viewContent);
        $this->assertStringContainsString('Cek display produk baru di rak depan', $viewContent);
        $this->assertStringContainsString('Maps', $viewContent);
        $this->assertStringContainsString('Buka Lokasi', $viewContent);
        $this->assertStringContainsString('https://www.google.com/maps?q=-6.565432,107.765432', $viewContent);
    }

    public function test_excel_export_includes_route_general_notes_and_stop_notes(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Subang Selatan',
            'date' => '2026-08-25',
            'notes' => 'Fokus penagihan invoice',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 20,
            'notes' => 'Toko tutup jam 12 siang',
            'status' => 'pending',
        ]);

        $export = new RoutesExport([
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-31',
        ]);

        $headings = $export->headings();
        $this->assertContains('Catatan Rencana', $headings);
        $this->assertContains('Daftar Toko', $headings);
        $this->assertContains('Status Kunjungan', $headings);
        $this->assertContains('Catatan Toko', $headings);
        $this->assertContains('Maps', $headings);

        $rows = $export->dataRows();
        $this->assertCount(1, $rows);

        $firstRow = $rows[0];
        // Index 10: Catatan Rencana
        $this->assertEquals('Fokus penagihan invoice', $firstRow[10]);
        // Index 11: Daftar Toko
        $this->assertStringContainsString('Toko Subang Jaya', $firstRow[11]);
        // Index 13: Catatan Toko (terpisah dari Daftar Toko)
        $this->assertEquals('Toko tutup jam 12 siang', $firstRow[13]);
    }

    public function test_empty_notes_are_displayed_gracefully_with_dash(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Kosong Catatan',
            'date' => '2026-08-25',
            'notes' => null,
            'status' => 'draft',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 15,
            'notes' => null,
            'status' => 'pending',
        ]);

        $export = new RoutesExport([
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-31',
        ]);

        $rows = $export->dataRows();
        $this->assertEquals('-', $rows[0][10]);

        $viewContent = view('reports.pdf.routes', [
            'items' => collect([$route->load(['stops.store', 'user', 'creator'])]),
            'periodLabel' => 'Semua Periode',
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-31',
            'generatedAt' => now()->format('d M Y, H:i'),
            'printedBy' => 'Administrator',
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'title' => 'LAPORAN RENCANA KUNJUNGAN',
        ])->render();

        $this->assertStringContainsString('Catatan Rencana', $viewContent);
    }

    public function test_routes_excel_separated_store_status_notes_and_maps_hyperlink(): void
    {
        $this->actingAs($this->admin);

        $storeA = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-A01',
            'name' => 'Toko Subang Mandiri',
            'owner' => 'Pak Ahmad',
            'type' => 'Toko Pertanian',
            'address' => 'Jl. Otista No. 10',
            'city' => 'Subang',
            'kecamatan' => 'Subang',
            'province' => 'Jawa Barat',
            'latitude' => -6.569700,
            'longitude' => 107.759000,
            'status' => 'active',
        ]);

        $storeB = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-B02',
            'name' => 'Toko Tani Makmur',
            'owner' => 'Bu Rina',
            'type' => 'Kios Pertanian',
            'address' => 'Jl. Raya Ciereng No. 5',
            'city' => 'Subang',
            'kecamatan' => 'Subang',
            'province' => 'Jawa Barat',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Test Separated Columns',
            'date' => '2026-08-20',
            'status' => 'active',
            'notes' => 'Catatan rute umum',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'notes' => 'Konfirmasi stok pupuk',
            'status' => 'visited',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeB->id,
            'sequence' => 2,
            'estimated_duration_minutes' => 25,
            'notes' => 'Pengantaran brosur',
            'status' => 'pending',
        ]);

        $export = new RoutesExport([
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-31',
            'userId' => $this->sales->id,
        ]);

        $headings = $export->headings();
        $this->assertEquals([
            'No',
            'Nama Sales',
            'Rute Wilayah',
            'Tanggal',
            'Jumlah Toko',
            'Toko Dikunjungi',
            'Status Rute',
            'Estimasi Durasi',
            'Durasi Aktual',
            'Dibuat Oleh',
            'Catatan Rencana',
            'Daftar Toko',
            'Status Kunjungan',
            'Catatan Toko',
            'Maps',
        ], $headings);

        $rows = $export->dataRows();
        $this->assertCount(2, $rows);

        // Row 1: Toko A (has coordinates)
        $this->assertEquals('Toko Subang Mandiri', $rows[0][11]);
        $this->assertEquals('Dikunjungi', $rows[0][12]);
        $this->assertEquals('Konfirmasi stok pupuk', $rows[0][13]);
        $this->assertEquals('Buka Maps', $rows[0][14]);

        // Row 2: Toko B (no coordinates)
        $this->assertEquals('Toko Tani Makmur', $rows[1][11]);
        $this->assertEquals('Belum Dikunjungi', $rows[1][12]);
        $this->assertEquals('Pengantaran brosur', $rows[1][13]);
        $this->assertEquals('-', $rows[1][14]);

        // Physical XLSX workbook inspection
        $excelContent = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $tempPath = tempnam(sys_get_temp_dir(), 'routes_test_') . '.xlsx';
        file_put_contents($tempPath, $excelContent);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Find heading row
        $headingRow = null;
        for ($r = 1; $r <= 15; $r++) {
            if ($sheet->getCell("L{$r}")->getValue() === 'Daftar Toko') {
                $headingRow = $r;
                break;
            }
        }
        $this->assertNotNull($headingRow, 'Heading row with Daftar Toko must exist');
        $this->assertEquals('Daftar Toko', $sheet->getCell("L{$headingRow}")->getValue());
        $this->assertEquals('Status Kunjungan', $sheet->getCell("M{$headingRow}")->getValue());
        $this->assertEquals('Catatan Toko', $sheet->getCell("N{$headingRow}")->getValue());
        $this->assertEquals('Maps', $sheet->getCell("O{$headingRow}")->getValue());

        // Row 1 data cell for Maps (O11 or similar)
        $dataRow1 = $headingRow + 1;
        $mapsCell1 = $sheet->getCell("O{$dataRow1}");
        $this->assertEquals('Buka Maps', $mapsCell1->getValue());
        $this->assertEquals('https://www.google.com/maps?q=-6.5697,107.759', $mapsCell1->getHyperlink()->getUrl());

        // Row 2 data cell for Maps
        $dataRow2 = $headingRow + 2;
        $mapsCell2 = $sheet->getCell("O{$dataRow2}");
        $this->assertEquals('-', $mapsCell2->getValue());

        @unlink($tempPath);
    }
}
