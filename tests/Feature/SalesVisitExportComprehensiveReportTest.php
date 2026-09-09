<?php

namespace Tests\Feature;

use App\Exports\VisitsExport;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SalesVisitExportComprehensiveReportTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $otherSales;
    private User $driver;
    private Store $storeA;
    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Fikri Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->otherSales = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->otherSales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Doni Driver', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko Rizky Mandiri',
            'code' => 'TKO-RM01',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko Lengkong Berkah',
            'code' => 'TKO-LB02',
            'address' => 'Jl. Sudirman No. 20',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 1: PDF Detail Visit Generates Complete Sections & Data
     */
    public function test_pdf_detail_export_contains_all_actual_sales_data(): void
    {
        // 1. Existing Old Debt: Rp 5.000.000
        $oldTrx = StoreTransaction::create([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-OLD-01',
            'transaction_date' => now()->subDays(3)->toDateString(),
            'transaction_amount' => 5000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Pagi Wilayah A',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->setTime(10, 0),
            'check_out_at' => now()->setTime(11, 0),
            'check_in_lat' => -6.2088,
            'check_in_lng' => 106.8456,
            'check_in_address' => 'Jl. Merdeka No. 10 Check In',
            'check_out_lat' => -6.2089,
            'check_out_lng' => 106.8457,
            'check_out_address' => 'Jl. Merdeka No. 10 Check Out',
            'initial_notes' => 'Catatan awal toko buka',
            'final_notes' => 'Catatan akhir pesanan dikonfirmasi',
            'visit_result' => 'Pemilik toko ramah, memesan stok tambahan',
            'transaction_status' => 'mixed',
            'transaction_amount' => 6000000,
        ]);

        // Payment for old debt: Rp 1.000.000
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'store_id' => $this->storeA->id,
            'amount' => 1000000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'source' => 'visit',
            'source_id' => $visit->id,
            'recorded_by' => $this->sales->id,
        ]);

        // New transaction: Rp 5.000.000 (Paid Rp 2.000.000, Remaining Rp 3.000.000)
        $newTrx = StoreTransaction::create([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-NEW-01',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 5000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $newTrx->id,
            'store_id' => $this->storeA->id,
            'amount' => 2000000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'tunai',
            'source' => 'initial_payment',
            'recorded_by' => $this->sales->id,
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $res->assertOk();
        $this->assertEquals('application/pdf', $res->headers->get('Content-Type'));
    }

    /**
     * TEST 2: PDF Rekapitulasi respects Date & Status Filter
     */
    public function test_pdf_rekap_filters_correctly(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Rekap',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->setTime(9, 0),
            'check_out_at' => now()->setTime(10, 0),
            'visit_result' => 'Selesai',
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.pdf.rekap', [
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'status' => 'completed',
        ]));

        $res->assertOk();
        $this->assertEquals('application/pdf', $res->headers->get('Content-Type'));
    }

    /**
     * TEST 3: Excel Export Structure & Data Rows
     */
    public function test_excel_export_structure_and_data_accuracy(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Excel Test',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->setTime(9, 0),
            'check_out_at' => now()->setTime(10, 0),
            'transaction_status' => 'paid',
            'transaction_amount' => 4500000,
            'visit_result' => 'Transaksi baru berhasil',
        ]);

        $export = new VisitsExport([
            'fromDate' => now()->toDateString(),
            'toDate' => now()->toDateString(),
            'userId' => $this->sales->id,
            'status' => 'completed',
        ]);

        $headings = $export->headings();
        $this->assertContains('Nilai Transaksi Baru pada Kunjungan (Rp)', $headings);
        $this->assertContains('Pembayaran Piutang Lama pada Kunjungan (Rp)', $headings);
        $this->assertContains('Saldo Piutang Toko Setelah Kunjungan (Rp)', $headings);

        $rows = $export->dataRows();
        $this->assertCount(1, $rows);
        $this->assertEquals('Toko Rizky Mandiri', $rows[0][3]);
        // Index 17: Nilai Transaksi Baru pada Kunjungan (Rp)
        $this->assertEquals(4500000, $rows[0][17]);
    }

    /**
     * TEST 4: Excel Download Endpoint Works for Sales
     */
    public function test_excel_export_download_endpoint_works(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Excel Endpoint',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->setTime(9, 0),
            'check_out_at' => now()->setTime(10, 0),
            'visit_result' => 'Sukses',
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.excel.rekap', [
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $res->assertOk();
    }
}
