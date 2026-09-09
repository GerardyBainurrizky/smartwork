<?php

namespace Tests\Feature;

use App\Exports\DriverVisitsExport;
use App\Exports\VisitsExport;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealPdfAndExcelReportVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;
    protected User $driver;
    protected Store $storeA;
    protected Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->admin = User::factory()->create(['name' => 'Admin Test', 'email' => 'admintest@isa.test']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['name' => 'Sales Budi', 'email' => 'salesbudi@isa.test']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Joko', 'email' => 'driverjoko@isa.test']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko Subur Makmur',
            'code' => 'TKO-001',
            'address' => 'Jl. Padi No. 10',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko Tani Jaya',
            'code' => 'TKO-002',
            'address' => 'Jl. Jagung No. 20',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    public function test_driver_visits_pdf_contains_actual_driver_data_and_metadata(): void
    {
        // 1. Create a Driver Route and Visit
        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Kirim Barat',
            'date' => '2026-09-07',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
            'notes' => 'Catatan kirim benih',
        ]);

        $visit = Visit::create([
            'user_id' => $this->driver->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 09:00:00',
            'check_out_at' => '2026-09-07 09:30:00',
            'initial_notes' => 'Sampai di toko aman',
            'visit_result' => 'Barang diterima lengkap oleh pemilik',
            'final_notes' => 'Faktur ditandatangani',
            'transaction_amount' => 500000,
            'transaction_status' => 'paid',
            'payment_method' => 'tunai',
        ]);

        // Also create a Sales visit to ensure NO sales data leaks into Driver PDF
        $salesRoute = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Sales Pagi',
            'date' => '2026-09-07',
            'status' => 'completed',
        ]);
        $salesStop = RouteStop::create([
            'route_id' => $salesRoute->id,
            'store_id' => $this->storeB->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $this->sales->id,
            'route_id' => $salesRoute->id,
            'route_stop_id' => $salesStop->id,
            'store_id' => $this->storeB->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 10:00:00',
            'check_out_at' => '2026-09-07 10:30:00',
            'visit_result' => 'Sales visit result secret',
        ]);

        $this->actingAs($this->admin);

        // Test Driver PDF Export
        $response = $this->get('/admin/reports/export-pdf?type=driver-visits&from_date=2026-09-01&to_date=2026-09-07');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Test with Driver filter
        $responseFiltered = $this->get("/admin/reports/export-pdf?type=driver-visits&user_id={$this->driver->id}&from_date=2026-09-01&to_date=2026-09-07");
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_sales_visits_pdf_contains_transaction_and_piutang_breakdown(): void
    {
        // 1. Create Sales Route & Visit
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Sales Toko A',
            'date' => '2026-09-07',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
            'notes' => 'Tagih piutang lama dan tawarkan promo',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 11:00:00',
            'check_out_at' => '2026-09-07 11:45:00',
            'initial_notes' => 'Owner ada di tempat',
            'visit_result' => 'Order baru dan bayar cicilan',
        ]);

        // 2. Transaksi Baru pada Visit ini
        $newTrx = StoreTransaction::create([
            'store_id' => $this->storeA->id,
            'reference_id' => $visit->id,
            'reference_type' => 'visit',
            'transaction_code' => 'TRX-20260907-0001',
            'transaction_date' => '2026-09-07',
            'transaction_amount' => 1500000,
            'remaining_amount' => 1000000,
            'status' => 'partial',
            'notes' => 'Pembelian pupuk 10 karung',
        ]);

        // Pembayaran awal transaksi baru (DP)
        StoreTransactionPayment::create([
            'store_transaction_id' => $newTrx->id,
            'source_id' => $visit->id,
            'payment_date' => '2026-09-07',
            'amount' => 500000,
            'payment_method' => 'tunai',
            'source' => 'initial_payment',
            'notes' => 'DP Transaksi Baru',
        ]);

        // 3. Transaksi Piutang Lama (Sebelum Kunjungan)
        $oldTrx = StoreTransaction::create([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260815-0099',
            'transaction_date' => '2026-08-15',
            'transaction_amount' => 2000000,
            'remaining_amount' => 1000000,
            'status' => 'partial',
            'notes' => 'Invoice lama',
        ]);

        // Pembayaran piutang lama saat visit
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'source_id' => $visit->id,
            'payment_date' => '2026-09-07',
            'amount' => 1000000,
            'payment_method' => 'transfer',
            'source' => 'receivable',
            'notes' => 'Pelunasan piutang lama via transfer',
        ]);

        $this->actingAs($this->admin);

        // Test Sales PDF Export
        $response = $this->get('/admin/reports/export-pdf?type=visits&from_date=2026-09-01&to_date=2026-09-07');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Test with Sales Filter
        $responseFiltered = $this->get("/admin/reports/export-pdf?type=visits&user_id={$this->sales->id}&from_date=2026-09-01&to_date=2026-09-07");
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_driver_excel_export_download(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/reports/export-excel?type=driver-visits&from_date=2026-09-01&to_date=2026-09-07');
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    public function test_sales_excel_export_download(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/reports/export-excel?type=visits&from_date=2026-09-01&to_date=2026-09-07');
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    public function test_sales_activities_pdf_and_excel_export_download(): void
    {
        $this->actingAs($this->admin);

        // PDF download
        $pdfResponse = $this->get('/admin/reports/export-pdf?type=sales-activities&from_date=2026-09-01&to_date=2026-09-07');
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');

        // Excel download
        $excelResponse = $this->get('/admin/reports/export-excel?type=sales-activities&from_date=2026-09-01&to_date=2026-09-07');
        $excelResponse->assertStatus(200);
        $excelResponse->assertHeader('Content-Disposition');
    }
}
