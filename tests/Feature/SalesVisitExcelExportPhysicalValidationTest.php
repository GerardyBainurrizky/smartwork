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
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SalesVisitExcelExportPhysicalValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->sales = User::factory()->create(['name' => 'Fikri Haekal', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store = Store::create([
            'name' => 'Lengkong berkah',
            'code' => 'TKO-LB01',
            'address' => 'Jl. Lengkong No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);
    }

    public function test_physical_xlsx_file_structure_and_drawings(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 12:00:00'));

        // Old debts
        $trxOld1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0006',
            'transaction_amount' => 550000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $trxOld2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260902-0005',
            'transaction_amount' => 700000,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        // Route & Stops
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Bandung',
            'date' => '2026-09-03',
            'status' => 'active',
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $selfieFile = UploadedFile::fake()->image('selfie_in.jpg');
        $storeFile = UploadedFile::fake()->image('storefront.jpg');
        $outSelfieFile = UploadedFile::fake()->image('selfie_out.jpg');

        $inPath = $selfieFile->store('visits/checkin', 'public');
        $frontPath = $storeFile->store('visits/storefront', 'public');
        $outPath = $outSelfieFile->store('visits/checkout', 'public');

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 09:26:00',
            'check_out_at' => '2026-09-03 10:30:00',
            'check_in_address' => 'Jl. Lengkong No. 10 Check In',
            'check_out_address' => 'Jl. Lengkong No. 10 Check Out',
            'check_in_selfie' => $inPath,
            'storefront_photo' => $frontPath,
            'check_out_selfie' => $outPath,
            'visit_result' => 'Pelunasan piutang lama dan transaksi baru',
            'transaction_status' => 'mixed',
        ]);

        // Bayar Piutang Lama
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOld1->id,
            'amount' => 550000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOld2->id,
            'amount' => 700000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Transaksi Baru
        $trxNew = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260903-0001',
            'transaction_amount' => 1000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-03',
        ], $this->sales);

        // Stop 2: Skipped with photo
        $skipFile = UploadedFile::fake()->image('skip.jpg');
        $skipPath = $skipFile->store('visits/skip', 'public');

        $stop2 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => json_encode([
                'reason' => 'Toko tutup hari libur',
                'photo_path' => $skipPath,
                'address' => 'Jl. Lengkong No. 10',
            ]),
        ]);

        // Export to physical file on disk
        $tempDir = storage_path('framework/testing');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $filePath = $tempDir . '/test_export_' . time() . '.xlsx';

        $export = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'userId' => $this->sales->id,
        ]);

        Excel::store($export, 'test_export.xlsx', 'public');
        $physicalPath = Storage::disk('public')->path('test_export.xlsx');

        $this->assertFileExists($physicalPath);

        // Parse with PhpSpreadsheet
        $spreadsheet = IOFactory::load($physicalPath);
        $this->assertCount(3, $spreadsheet->getSheetNames());
        $this->assertEquals(['Rekap Kunjungan', 'Transaksi & Piutang', 'Foto Kunjungan'], $spreadsheet->getSheetNames());

        // Inspect Sheet 1
        $sheet1 = $spreadsheet->getSheetByName('Rekap Kunjungan');
        $this->assertNotNull($sheet1);
        $headerRow = 8; // Based on BaseReportExport
        $this->assertEquals('Saldo Piutang Lama Sebelum Kunjungan (Rp)', $sheet1->getCell("P{$headerRow}")->getValue());
        $this->assertEquals('Pembayaran Piutang Lama pada Kunjungan (Rp)', $sheet1->getCell("Q{$headerRow}")->getValue());
        $this->assertEquals('Nilai Transaksi Baru pada Kunjungan (Rp)', $sheet1->getCell("R{$headerRow}")->getValue());
        $this->assertEquals('Pembayaran atas Transaksi Baru pada Kunjungan (Rp)', $sheet1->getCell("S{$headerRow}")->getValue());
        $this->assertEquals('Piutang Baru dari Transaksi Kunjungan (Rp)', $sheet1->getCell("T{$headerRow}")->getValue());
        $this->assertEquals('Saldo Piutang Toko Setelah Kunjungan (Rp)', $sheet1->getCell("U{$headerRow}")->getValue());

        // Inspect Sheet 2
        $sheet2 = $spreadsheet->getSheetByName('Transaksi & Piutang');
        $this->assertNotNull($sheet2);

        // Inspect Sheet 3
        $sheet3 = $spreadsheet->getSheetByName('Foto Kunjungan');
        $this->assertNotNull($sheet3);
        $drawings = $sheet3->getDrawingCollection();
        $this->assertNotEmpty($drawings); // Validates physical image drawings are embedded in XLSX!
    }
}
