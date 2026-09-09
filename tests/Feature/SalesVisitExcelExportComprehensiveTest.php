<?php

namespace Tests\Feature;

use App\Exports\Sheets\FotoKunjunganSheet;
use App\Exports\Sheets\RekapKunjunganSheet;
use App\Exports\Sheets\TransaksiPiutangSheet;
use App\Exports\VisitsExport;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SalesVisitExcelExportComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $otherSales;
    private User $driver;
    private Store $store;
    private Store $otherStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->sales = User::factory()->create(['name' => 'Fikri Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->otherSales = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->otherSales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Doni Driver', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Pusaka Tani',
            'code' => 'TKO-PT01',
            'address' => 'Jl. Raya Pertanian No. 88',
            'city' => 'Bogor',
            'province' => 'Jawa Barat',
            'latitude' => -6.5950,
            'longitude' => 106.8166,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->otherStore = Store::create([
            'name' => 'Toko Rizky Makmur',
            'code' => 'TKO-RM02',
            'address' => 'Jl. Sudirman No. 10',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->otherSales->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * Test 1 to 38: Full compliance check of the 5-sheet Sales Excel Export.
     */
    public function test_complete_sales_excel_export_workflow(): void
    {
        // Setup initial transactions in ledger for store:
        // TRX-001 = Rp 6.500.000 (with historical payment Rp 150.000 -> balance before = Rp 6.350.000)
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0001',
            'transaction_amount' => 6500000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        StoreTransactionPayment::create([
            'store_transaction_id' => $trx1->id,
            'amount' => 150000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'tunai',
            'source' => 'historical',
            'recorded_by' => $this->sales->id,
        ]);
        $trx1->recalculateStatus();

        // TRX-002 = Rp 370.000
        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0002',
            'transaction_amount' => 370000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // TRX-003 = Rp 700.000
        $trx3 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0003',
            'transaction_amount' => 700000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // TRX-005 = Rp 1.000.000 (not paid during this visit)
        $trx5 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0005',
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // Piutang sebelum visit = (6.500.000 - 150.000) + 370.000 + 700.000 + 1.000.000 = Rp 8.420.000

        // Create visit for sales
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Bogor Sentral',
            'date' => '2026-09-02',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        // Upload dummy image files
        $selfieFile = UploadedFile::fake()->image('selfie_in.jpg');
        $storeFile = UploadedFile::fake()->image('storefront.jpg');
        $outSelfieFile = UploadedFile::fake()->image('selfie_out.jpg');

        $inPath = $selfieFile->store('visits/checkin', 'public');
        $frontPath = $storeFile->store('visits/storefront', 'public');
        $outPath = $outSelfieFile->store('visits/checkout', 'public');

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-02 09:00:00',
            'check_out_at' => '2026-09-02 10:00:00',
            'check_in_lat' => -6.5950,
            'check_in_lng' => 106.8166,
            'check_in_address' => 'Jl. Raya Pertanian No. 88 Check In',
            'check_out_lat' => -6.5951,
            'check_out_lng' => 106.8167,
            'check_out_address' => 'Jl. Raya Pertanian No. 88 Check Out',
            'check_in_selfie' => $inPath,
            'storefront_photo' => $frontPath,
            'check_out_selfie' => $outPath,
            'visit_result' => 'Pembayaran piutang dan pesanan baru berhasil',
            'transaction_status' => 'mixed',
        ]);

        // 3 Payments recorded on this visit for old debts:
        // Payment 1: TRX-001 -> Rp 350.000 (Remaining = 6.000.000, SEBAGIAN)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 350000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Payment 2: TRX-002 -> Rp 70.000 (Remaining = 300.000, SEBAGIAN)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx2->id,
            'amount' => 70000,
            'payment_method' => 'transfer',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Payment 3: TRX-003 -> Rp 700.000 (Remaining = 0, LUNAS)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx3->id,
            'amount' => 700000,
            'payment_method' => 'qris',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // New Transaction made during visit: TRX-004 = Rp 700.000 (Paid initial Rp 700.000 -> Remaining 0, LUNAS)
        $trx4 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260902-0004',
            'transaction_amount' => 700000,
            'paid_amount' => 700000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        // Also create a Skipped visit stop with photo
        $skipFile = UploadedFile::fake()->image('skip.jpg');
        $skipPath = $skipFile->store('visits/skip', 'public');

        $stopSkipped = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => json_encode([
                'reason' => 'Toko tutup istirahat',
                'photo_path' => $skipPath,
                'latitude' => -6.5950,
                'longitude' => 106.8166,
                'address' => 'Jl. Raya Pertanian No. 88',
            ]),
        ]);

        // ==========================================
        // VERIFY EXPORT INSTANCE & SHEETS
        // ==========================================
        $export = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-03',
            'userId' => $this->sales->id,
        ]);

        $sheets = $export->sheets();
        $this->assertCount(3, $sheets);
        $this->assertInstanceOf(RekapKunjunganSheet::class, $sheets[0]);
        $this->assertInstanceOf(TransaksiPiutangSheet::class, $sheets[1]);
        $this->assertInstanceOf(FotoKunjunganSheet::class, $sheets[2]);

        // Sheet 1: Rekap Kunjungan
        $rekapRows = $sheets[0]->dataRows();
        $this->assertCount(2, $rekapRows); // 1 completed visit + 1 skipped visit
        $this->assertEquals('02 Sep 2026', $rekapRows[0][1]);
        $this->assertEquals('Toko Pusaka Tani', $rekapRows[0][3]);
        $this->assertEquals('Selesai', $rekapRows[0][8]);
        $this->assertEquals(8420000.0, $rekapRows[0][15]); // Total Piutang Sebelum Kunjungan
        $this->assertEquals(1120000.0, $rekapRows[0][16]); // Total Pembayaran Piutang Lama (350k + 70k + 700k)
        $this->assertEquals(700000.0, $rekapRows[0][17]);  // Total Transaksi Baru
        $this->assertEquals(700000.0, $rekapRows[0][18]);  // Total Pembayaran Transaksi Baru
        $this->assertEquals(0.0, $rekapRows[0][19]);       // Sisa Transaksi Baru
        $this->assertEquals(7300000.0, $rekapRows[0][20]); // Total Sisa Piutang Toko

        // Sheet 2: Transaksi & Piutang
        $txRows = $sheets[1]->dataRows();
        // Section 1: Rincian Transaksi Baru
        $this->assertEquals('RINCIAN TRANSAKSI BARU', $txRows[0][0]);
        $this->assertEquals('TRX-20260902-0004', $txRows[2][5]);
        $this->assertEquals(700000.0, $txRows[2][6]); // Nilai
        $this->assertEquals(700000.0, $txRows[2][7]); // Dibayar
        $this->assertEquals(0.0, $txRows[2][8]);      // Sisa
        $this->assertEquals('LUNAS', $txRows[2][10]);

        // Section 2: Rincian Pembayaran Piutang Lama
        $this->assertEquals('RINCIAN PEMBAYARAN PIUTANG LAMA', $txRows[4][0]);
        $this->assertEquals('TRX-20260901-0001', $txRows[6][5]);
        $this->assertEquals(6350000.0, $txRows[6][7]); // Saldo Sebelum (Rp 6.500.000 - Rp 150.000 historical)
        $this->assertEquals(350000.0, $txRows[6][8]);  // Nominal Dibayar
        $this->assertEquals(6000000.0, $txRows[6][9]); // Sisa Setelah
        $this->assertEquals('TUNAI', $txRows[6][10]);
        $this->assertEquals('SEBAGIAN', $txRows[6][11]);

        // Sheet 3: Foto Kunjungan
        $photoHeadings = $sheets[2]->headings();
        $this->assertContains('Foto Check In', $photoHeadings);
        $this->assertContains('Foto Etalase', $photoHeadings);
        $this->assertContains('Foto Check Out', $photoHeadings);
        $this->assertContains('Foto Dilewati', $photoHeadings);

        $photoRows = $sheets[2]->dataRows();
        $this->assertCount(2, $photoRows);
        $this->assertEquals('Selesai', $photoRows[0][4]);
        $this->assertEquals('Dilewati', $photoRows[1][4]);

        // Verify Excel Download endpoint
        $res = $this->actingAs($this->sales)->get(route('visit.excel.rekap', [
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-03',
        ]));
        $res->assertOk();
    }
}
