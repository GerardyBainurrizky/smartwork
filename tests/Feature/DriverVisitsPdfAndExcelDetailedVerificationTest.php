<?php

namespace Tests\Feature;

use App\Exports\DriverVisitsExport;
use Spatie\Permission\Models\Role;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverVisitsPdfAndExcelDetailedVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $superAdmin;
    protected User $driverA;
    protected User $driverB;
    protected Store $store1;
    protected Store $store2;
    protected Store $store3;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['name' => 'Admin Utama']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Utama']);
        $this->superAdmin->assignRole('super-admin');

        $this->driverA = User::factory()->create(['name' => 'Budi Driver A']);
        $this->driverA->assignRole('driver');

        $this->driverB = User::factory()->create(['name' => 'Joko Driver B']);
        $this->driverB->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Subur Makmur',
            'code' => 'TKO-SUBUR-01',
            'address' => 'Jl. Merdeka No. 12',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Sumber Rejeki',
            'code' => 'TKO-SUMBER-02',
            'address' => 'Jl. Sudirman No. 45',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        $this->store3 = Store::create([
            'name' => 'Toko Maju Bersama',
            'code' => 'TKO-MAJU-03',
            'address' => 'Jl. Diponegoro No. 88',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    public function test_driver_visits_pdf_and_excel_cases(): void
    {
        // Setup Route for Driver A
        $routeA = Route::create([
            'user_id' => $this->driverA->id,
            'name' => 'Rute Pengiriman Driver A',
            'date' => '2026-09-07',
            'status' => 'completed',
        ]);

        // Stop 1 (Driver A): Completed with transaction
        $stopA1 = RouteStop::create([
            'route_id' => $routeA->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
            'notes' => 'Catatan kirim toko 1',
        ]);

        $visitA1 = Visit::create([
            'user_id' => $this->driverA->id,
            'route_id' => $routeA->id,
            'route_stop_id' => $stopA1->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 08:00:00',
            'check_out_at' => '2026-09-07 08:30:00',
            'transaction_amount' => 750000,
            'transaction_status' => 'paid',
            'payment_method' => 'tunai',
            'visit_result' => 'Terkirim 50 karton',
            'final_notes' => 'Pembayaran tunai diterima',
            'check_in_selfie' => 'visits/' . $this->driverA->id . '/checkin_a1.jpg',
        ]);

        // Stop 2 (Driver A): Skipped with photo
        $stopA2 = RouteStop::create([
            'route_id' => $routeA->id,
            'store_id' => $this->store2->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => 'Toko tutup karena libur keluarga',
        ]);

        $visitA2 = Visit::create([
            'user_id' => $this->driverA->id,
            'route_stop_id' => $stopA2->id,
            'route_id' => $routeA->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 09:00:00',
            'check_out_at' => '2026-09-07 09:05:00',
            'visit_result' => 'Dilewati: Toko tutup karena libur keluarga',
            'initial_notes' => 'Toko tutup karena libur keluarga',
            'final_store_photo' => 'visits/' . $this->driverA->id . '/skip_photo_a2.jpg',
        ]);

        // Setup Route for Driver B
        $routeB = Route::create([
            'user_id' => $this->driverB->id,
            'name' => 'Rute Pengiriman Driver B',
            'date' => '2026-09-07',
            'status' => 'completed',
        ]);

        // Stop 1 (Driver B): Completed WITHOUT transaction (transaction_amount = 0)
        $stopB1 = RouteStop::create([
            'route_id' => $routeB->id,
            'store_id' => $this->store3->id,
            'sequence' => 1,
            'status' => 'visited',
            'notes' => 'Catatan kirim toko 3',
        ]);

        $visitB1 = Visit::create([
            'user_id' => $this->driverB->id,
            'route_id' => $routeB->id,
            'route_stop_id' => $stopB1->id,
            'store_id' => $this->store3->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 10:00:00',
            'check_out_at' => '2026-09-07 10:45:00',
            'transaction_amount' => 0,
            'transaction_status' => 'none',
            'visit_result' => 'Barang titipan telah diserahterimakan',
            'check_in_selfie' => 'visits/' . $this->driverB->id . '/checkin_b1.jpg',
        ]);

        // Stop 2 (Driver B): Skipped WITHOUT photo
        $stopB2 = RouteStop::create([
            'route_id' => $routeB->id,
            'store_id' => $this->store1->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => 'Jalanan banjir tidak bisa lewat',
        ]);

        $visitB2 = Visit::create([
            'user_id' => $this->driverB->id,
            'route_stop_id' => $stopB2->id,
            'route_id' => $routeB->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 11:00:00',
            'check_out_at' => '2026-09-07 11:05:00',
            'visit_result' => 'Dilewati: Jalanan banjir tidak bisa lewat',
            'initial_notes' => 'Jalanan banjir tidak bisa lewat',
            'final_store_photo' => null, // No photo
        ]);

        // 1. Verify Blade PDF View Rendering
        $view = view('reports.pdf.driver-visits', [
            'items' => collect([$visitA1, $visitB1]),
            'skipped' => collect([$stopA2, $stopB2]),
            'title' => 'LAPORAN HASIL PENGIRIMAN DRIVER',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'selectedUserLabel' => 'Semua Driver',
            'statusLabel' => 'Semua Status',
            'trxStatusLabel' => 'Semua',
            'printedByRole' => 'Super Admin',
        ])->render();

        // Check Section TRANSAKSI exists clearly
        $this->assertStringContainsString('TRANSAKSI', $view);
        $this->assertStringContainsString('Nominal Transaksi', $view);
        $this->assertStringContainsString('Rp 750.000', $view);
        $this->assertStringContainsString('TUNAI', $view);
        $this->assertStringContainsString('Tidak ada transaksi', $view);

        // Check Skipped Table and Photo Column Header & Content
        $this->assertStringContainsString('PENGIRIMAN DILEWATI (SKIP)', $view);
        $this->assertStringContainsString('Foto Bukti Dilewati', $view);
        $this->assertStringContainsString('Toko tutup karena libur keluarga', $view);
        $this->assertStringContainsString('Jalanan banjir tidak bisa lewat', $view);
        $this->assertStringContainsString('Tidak ada foto', $view);

        // 2. Verify PDF Download endpoint as Admin & Super Admin
        $this->actingAs($this->admin);
        $pdfRespAdmin = $this->get('/admin/reports/export-pdf?type=driver-visits&from_date=2026-09-01&to_date=2026-09-07');
        $pdfRespAdmin->assertOk();
        $pdfRespAdmin->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->superAdmin);
        $pdfRespSuper = $this->get('/admin/reports/export-pdf?type=driver-visits&from_date=2026-09-01&to_date=2026-09-07');
        $pdfRespSuper->assertOk();
        $pdfRespSuper->assertHeader('content-type', 'application/pdf');

        // 3. Verify Filter Driver
        $pdfRespFilterDriver = $this->get("/admin/reports/export-pdf?type=driver-visits&user_id={$this->driverA->id}&from_date=2026-09-01&to_date=2026-09-07");
        $pdfRespFilterDriver->assertOk();

        // 4. Verify Excel Export
        $excelResp = $this->get('/admin/reports/export-excel?type=driver-visits&from_date=2026-09-01&to_date=2026-09-07');
        $excelResp->assertOk();
        $excelResp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $export = new DriverVisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-07',
        ]);
        $sheets = $export->sheets();
        $this->assertCount(2, $sheets);
        $this->assertSame('Rekap Pengiriman', $sheets[0]->title());
        $this->assertSame('Dokumentasi Pengiriman', $sheets[1]->title());
    }
}
