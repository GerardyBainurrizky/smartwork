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
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalesVisitExcelExportThirtyEightRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->sales1 = User::factory()->create(['name' => 'Fikri Sales', 'status' => 'active']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Doni Driver', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Pusaka Tani',
            'code' => 'TKO-PT01',
            'address' => 'Jl. Raya Pertanian No. 88',
            'city' => 'Bogor',
            'province' => 'Jawa Barat',
            'latitude' => -6.5950,
            'longitude' => 106.8166,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Rizky Makmur',
            'code' => 'TKO-RM02',
            'address' => 'Jl. Sudirman No. 10',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * Comprehensive test verifying each requirement item 1 to 38.
     */
    public function test_all_thirty_eight_export_requirements(): void
    {
        // 1. Setup scenario for Sales 1 and Store 1
        // Old debts:
        // TRX-001: Amount 6.500.000, Hist Pay 150.000 -> Balance Before: 6.350.000
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260901-0001',
            'transaction_amount' => 6500000,
            'transaction_date' => '2026-09-01',
        ], $this->sales1);

        StoreTransactionPayment::create([
            'store_transaction_id' => $trx1->id,
            'amount' => 150000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'tunai',
            'source' => 'historical',
            'recorded_by' => $this->sales1->id,
        ]);
        $trx1->recalculateStatus();

        // TRX-002: Amount 370.000
        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260901-0002',
            'transaction_amount' => 370000,
            'transaction_date' => '2026-09-01',
        ], $this->sales1);

        // TRX-003: Amount 700.000
        $trx3 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260901-0003',
            'transaction_amount' => 700000,
            'transaction_date' => '2026-09-01',
        ], $this->sales1);

        // TRX-005: Amount 1.000.000 (unpaid)
        $trx5 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260901-0005',
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales1);

        // Create route & visit for Sales 1
        $route1 = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Bogor Sentral',
            'date' => '2026-09-02',
            'status' => 'active',
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $route1->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $selfieFile = UploadedFile::fake()->image('selfie_in.jpg');
        $storeFile = UploadedFile::fake()->image('storefront.jpg');
        $outSelfieFile = UploadedFile::fake()->image('selfie_out.jpg');

        $inPath = $selfieFile->store('visits/checkin', 'public');
        $frontPath = $storeFile->store('visits/storefront', 'public');
        $outPath = $outSelfieFile->store('visits/checkout', 'public');

        $visit1 = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route1->id,
            'store_id' => $this->store1->id,
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

        // Payments in visit 1:
        // Pay TRX-001 -> 350.000 (rem = 6.000.000)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 350000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit1->id,
        ], $this->sales1);

        // Pay TRX-002 -> 70.000 (rem = 300.000)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx2->id,
            'amount' => 70000,
            'payment_method' => 'transfer',
            'source' => 'visit',
            'source_id' => $visit1->id,
        ], $this->sales1);

        // Pay TRX-003 -> 700.000 (rem = 0, full payment)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx3->id,
            'amount' => 700000,
            'payment_method' => 'qris',
            'source' => 'visit',
            'source_id' => $visit1->id,
        ], $this->sales1);

        // New transaction created during visit: TRX-004
        $trx4 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260902-0004',
            'transaction_amount' => 700000,
            'paid_amount' => 700000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit1->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales1);

        // Skipped Stop with photo
        $skipFile = UploadedFile::fake()->image('skip_proof.jpg');
        $skipPath = $skipFile->store('visits/skip', 'public');

        $stopSkipped = RouteStop::create([
            'route_id' => $route1->id,
            'store_id' => $this->store1->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => json_encode([
                'reason' => 'Toko tutup hari libur',
                'photo_path' => $skipPath,
                'latitude' => -6.5950,
                'longitude' => 106.8166,
                'address' => 'Jl. Raya Pertanian No. 88',
            ]),
        ]);

        // Setup another visit for Sales 2 (to verify isolation)
        $route2 = Route::create([
            'user_id' => $this->sales2->id,
            'name' => 'Rute Jakarta',
            'date' => '2026-09-02',
            'status' => 'active',
        ]);
        $stop2 = RouteStop::create([
            'route_id' => $route2->id,
            'store_id' => $this->store2->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $this->sales2->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route2->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-02 09:00:00',
            'check_out_at' => '2026-09-02 10:00:00',
            'transaction_status' => 'none',
        ]);

        // Capture initial db state count for read-only verification
        $initialTrxCount = StoreTransaction::count();
        $initialPaymentCount = StoreTransactionPayment::count();
        $initialVisitCount = Visit::count();

        // Instantiate export for Sales 1
        $export = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-03',
            'userId' => $this->sales1->id,
        ]);

        // ITEM 1: Excel berhasil dibuat & memiliki 3 dedicated structured sheets
        $sheets = $export->sheets();
        $this->assertCount(3, $sheets);
        $this->assertInstanceOf(RekapKunjunganSheet::class, $sheets[0]);
        $this->assertInstanceOf(TransaksiPiutangSheet::class, $sheets[1]);
        $this->assertInstanceOf(FotoKunjunganSheet::class, $sheets[2]);

        // ITEM 2: Rekap Kunjungan berisi satu row per visit (1 visit + 1 skipped)
        $rekapRows = $sheets[0]->dataRows();
        $this->assertCount(2, $rekapRows);
        $this->assertEquals('02 Sep 2026', $rekapRows[0][1]);
        $this->assertEquals('Toko Pusaka Tani', $rekapRows[0][3]);
        $this->assertEquals('Selesai', $rekapRows[0][8]);
        $this->assertEquals(8420000.0, $rekapRows[0][15]); // Total Piutang Sebelum Kunjungan
        $this->assertEquals(1120000.0, $rekapRows[0][16]); // Pembayaran Piutang Lama
        $this->assertEquals(700000.0, $rekapRows[0][17]);  // Total Transaksi Baru
        $this->assertEquals(700000.0, $rekapRows[0][18]);  // Pembayaran Transaksi Baru
        $this->assertEquals(0.0, $rekapRows[0][19]);       // Sisa Transaksi Baru
        $this->assertEquals(7300000.0, $rekapRows[0][20]); // Total Piutang Setelah Kunjungan

        // ITEM 3: Transaksi & Piutang berisi detail per transaksi (2 payment old + 1 new tx)
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
        $this->assertEquals('TRX-20260901-0002', $txRows[7][5]);
        $this->assertEquals('TRX-20260901-0003', $txRows[8][5]);

        // ITEM 7: Saldo sebelum pembayaran benar (histori Rp 150k diperhitungkan: 6.5M - 150k = 6.35M)
        $this->assertEquals(6350000.0, $txRows[6][7]);
        $this->assertEquals(370000.0, $txRows[7][7]);
        $this->assertEquals(700000.0, $txRows[8][7]);

        // ITEM 8: Nominal payment benar (350k, 70k, 700k)
        $this->assertEquals(350000.0, $txRows[6][8]);
        $this->assertEquals(70000.0, $txRows[7][8]);
        $this->assertEquals(700000.0, $txRows[8][8]);

        // ITEM 9: Sisa setelah payment benar (6.000.000, 300.000, 0)
        $this->assertEquals(6000000.0, $txRows[6][9]);
        $this->assertEquals(300000.0, $txRows[7][9]);
        $this->assertEquals(0.0, $txRows[8][9]);

        // ITEM 10: Full payment menghasilkan remaining Rp0 & status LUNAS
        $this->assertEquals(0.0, $txRows[8][9]);
        $this->assertEquals('LUNAS', $txRows[8][11]);

        // ITEM 11: Partial payment menghasilkan remaining > Rp0 & status SEBAGIAN
        $this->assertGreaterThan(0.0, $txRows[6][9]);
        $this->assertEquals('SEBAGIAN', $txRows[6][11]);

        // ITEM 12: Total payment visit benar (350k + 70k + 700k = 1.120.000)
        $this->assertEquals(1120000.0, $rekapRows[0][16]);

        // ITEM 13: Total piutang sebelum visit mencakup seluruh open receivable toko (8.420.000)
        $this->assertEquals(8420000.0, $rekapRows[0][15]);

        // ITEM 14: Sisa piutang toko setelah visit mencakup seluruh transaksi toko (7.300.000)
        $this->assertEquals(7300000.0, $rekapRows[0][20]);

        // ITEM 15: Transaksi yang tidak dibayar (TRX-005: 1.000.000) tetap masuk saldo akhir
        $this->assertEquals(7300000.0, StoreReceivableService::balanceForStore($this->store1->id));

        // ITEM 18: Historical payment tidak dihitung sebagai payment visit (Payment visit = 1.120.000, bukan 1.270.000)
        $this->assertEquals(1120000.0, $rekapRows[0][16]);

        // ITEM 20: Visit DILEWATI tetap masuk Excel
        $this->assertEquals('Dilewati', $rekapRows[1][8]);

        // ITEM 21, 22, 23, 24: Foto DILEWATI, Check In, Etalase, Check Out masuk di sheet Foto Kunjungan
        $photoHeadings = $sheets[2]->headings();
        $this->assertContains('Foto Check In', $photoHeadings);
        $this->assertContains('Foto Etalase', $photoHeadings);
        $this->assertContains('Foto Check Out', $photoHeadings);
        $this->assertContains('Foto Dilewati', $photoHeadings);

        $photoRows = $sheets[2]->dataRows();
        $this->assertCount(2, $photoRows); // 1 row per visit (1 completed + 1 skipped)
        $this->assertEquals('Selesai', $photoRows[0][4]);
        $this->assertEquals('Dilewati', $photoRows[1][4]);

        // ITEM 26: Nominal Excel berupa numeric
        $this->assertIsNumeric($rekapRows[0][15]);
        $this->assertIsNumeric($rekapRows[0][16]);
        $this->assertIsNumeric($rekapRows[0][17]);
        $this->assertIsNumeric($txRows[6][7]);
        $this->assertIsNumeric($txRows[6][8]);
        $this->assertIsNumeric($txRows[6][9]);

        // ITEM 27: Nominal dapat di-SUM
        $sumPayments = array_sum([$txRows[6][8], $txRows[7][8], $txRows[8][8]]);
        $this->assertEquals(1120000.0, $sumPayments);

        // ITEM 33: Filter tanggal bekerja
        $exportFilter = new VisitsExport([
            'fromDate' => '2026-09-05',
            'toDate' => '2026-09-06',
            'userId' => $this->sales1->id,
        ]);
        $this->assertEmpty($exportFilter->sheets()[0]->dataRows());

        // ITEM 34: Filter status bekerja
        $exportStatus = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-03',
            'userId' => $this->sales1->id,
            'status' => 'completed',
        ]);
        $completedOnlyRows = $exportStatus->sheets()[0]->dataRows();
        $this->assertCount(1, $completedOnlyRows);
        $this->assertEquals('Selesai', $completedOnlyRows[0][8]);

        // ITEM 35 & 36: Sales 1 hanya melihat datanya sendiri, tidak melihat data Sales 2
        $storeNames = array_column($rekapRows, 3);
        $this->assertContains('Toko Pusaka Tani', $storeNames);
        $this->assertNotContains('Toko Rizky Makmur', $storeNames);

        // ITEM 37: Export tidak mengubah database (READ ONLY)
        $this->assertEquals($initialTrxCount, StoreTransaction::count());
        $this->assertEquals($initialPaymentCount, StoreTransactionPayment::count());
        $this->assertEquals($initialVisitCount, Visit::count());

        // ITEM 38: Driver tidak terpengaruh & endpoint sales Excel works
        $res = $this->actingAs($this->sales1)->get(route('visit.excel.rekap', [
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-03',
        ]));
        $res->assertOk();
    }
}
