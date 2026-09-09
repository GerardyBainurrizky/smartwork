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
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalesVisitExcelExportStructuredFiftyRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $sales1;
    private User $sales2;
    private User $driver;
    private User $admin;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->sales1 = User::factory()->create(['name' => 'Fikri Haekal', 'status' => 'active']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Doni Driver', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->admin = User::factory()->create(['name' => 'Super Admin', 'status' => 'active']);
        $this->admin->assignRole('super-admin');

        $this->store1 = Store::create([
            'name' => 'Lengkong berkah',
            'code' => 'TKO-LB01',
            'address' => 'Jl. Lengkong No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Pusaka Tani',
            'code' => 'TKO-PT02',
            'address' => 'Jl. Merak No. 5',
            'city' => 'Bogor',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * Test complete 50-rule export structure.
     */
    public function test_complete_fifty_rule_excel_export_structure(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 12:00:00'));

        // 1. Old Debts for Store 1:
        // TRX-OLD-001: Nilai Rp 550.000
        $trxOld1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260901-0006',
            'transaction_amount' => 550000,
            'transaction_date' => '2026-09-01',
        ], $this->sales1);

        // TRX-OLD-002: Nilai Rp 700.000
        $trxOld2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260902-0005',
            'transaction_amount' => 700000,
            'transaction_date' => '2026-09-02',
        ], $this->sales1);

        // TRX-OLD-003: Nilai Rp 500.000 (tidak dibayar pada visit ini)
        $trxOld3 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260902-0009',
            'transaction_amount' => 500000,
            'transaction_date' => '2026-09-02',
        ], $this->sales1);

        // Total Piutang Sebelum Kunjungan = 550k + 700k + 500k = Rp 1.750.000

        // Create Route & Visit for Sales 1
        $route = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Bandung',
            'date' => '2026-09-03',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
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

        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 09:26:00',
            'check_out_at' => '2026-09-03 10:30:00',
            'check_in_lat' => -6.9175,
            'check_in_lng' => 107.6191,
            'check_in_address' => 'Jl. Lengkong No. 10 Check In',
            'check_out_lat' => -6.9176,
            'check_out_lng' => 107.6192,
            'check_out_address' => 'Jl. Lengkong No. 10 Check Out',
            'check_in_selfie' => $inPath,
            'storefront_photo' => $frontPath,
            'check_out_selfie' => $outPath,
            'visit_result' => 'Pelunasan piutang lama dan transaksi baru separuh tunai',
            'transaction_status' => 'mixed',
        ]);

        // Bayar Piutang Lama:
        // Pay TRX-OLD-001 = 550.000 (sisa = 0, LUNAS)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOld1->id,
            'amount' => 550000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales1);

        // Pay TRX-OLD-002 = 700.000 (sisa = 0, LUNAS)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOld2->id,
            'amount' => 700000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales1);

        // Transaksi Baru: TRX-NEW-001 (Nilai 1.000.000, bayar awal 500.000, sisa 500.000, SEBAGIAN)
        $trxNew = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260903-0001',
            'transaction_amount' => 1000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        // Stop 2: Skipped with photo
        $skipFile = UploadedFile::fake()->image('skip.jpg');
        $skipPath = $skipFile->store('visits/skip', 'public');

        $stopSkipped = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => json_encode([
                'reason' => 'Toko tutup istirahat',
                'photo_path' => $skipPath,
                'latitude' => -6.9175,
                'longitude' => 107.6191,
                'address' => 'Jl. Lengkong No. 10',
            ]),
        ]);

        // Export execution
        $export = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'userId' => $this->sales1->id,
        ]);

        $sheets = $export->sheets();
        $this->assertCount(3, $sheets);
        $this->assertInstanceOf(RekapKunjunganSheet::class, $sheets[0]);
        $this->assertInstanceOf(TransaksiPiutangSheet::class, $sheets[1]);
        $this->assertInstanceOf(FotoKunjunganSheet::class, $sheets[2]);

        // 1. SHEET REKAP KUNJUNGAN
        $rekapHeadings = $sheets[0]->headings();
        $this->assertContains('Saldo Piutang Lama Sebelum Kunjungan (Rp)', $rekapHeadings);
        $this->assertContains('Pembayaran Piutang Lama pada Kunjungan (Rp)', $rekapHeadings);
        $this->assertContains('Nilai Transaksi Baru pada Kunjungan (Rp)', $rekapHeadings);
        $this->assertContains('Pembayaran atas Transaksi Baru pada Kunjungan (Rp)', $rekapHeadings);
        $this->assertContains('Piutang Baru dari Transaksi Kunjungan (Rp)', $rekapHeadings);
        $this->assertContains('Saldo Piutang Toko Setelah Kunjungan (Rp)', $rekapHeadings);

        $rekapRows = $sheets[0]->dataRows();
        $this->assertCount(2, $rekapRows); // 1 completed + 1 skipped
        $row1 = $rekapRows[0];

        $this->assertEquals('03 Sep 2026', $row1[1]);
        $this->assertEquals('Fikri Haekal', $row1[2]);
        $this->assertEquals('Lengkong berkah', $row1[3]);
        $this->assertEquals('TKO-LB01', $row1[4]);
        $this->assertEquals(1, $row1[5]);
        $this->assertEquals('-', $row1[6]);
        $this->assertEquals('-', $row1[7]);
        $this->assertEquals('Selesai', $row1[8]);
        $this->assertEquals('09:26', $row1[9]);
        $this->assertEquals('10:30', $row1[11]);
        $this->assertEquals(1750000.0, $row1[15]); // Total Piutang Sebelum Kunjungan (550k + 700k + 500k)
        $this->assertEquals(1250000.0, $row1[16]); // Pembayaran Piutang Lama (550k + 700k)
        $this->assertEquals(1000000.0, $row1[17]); // Total Transaksi Baru
        $this->assertEquals(500000.0, $row1[18]);  // Pembayaran Transaksi Baru
        $this->assertEquals(500000.0, $row1[19]);  // Sisa Transaksi Baru
        $this->assertEquals(1000000.0, $row1[20]); // Total Piutang Setelah Kunjungan (500k old remaining + 500k new remaining)

        // 2. SHEET TRANSAKSI & PIUTANG
        $txRows = $sheets[1]->dataRows();
        // Section 1: Rincian Transaksi Baru
        $this->assertEquals('RINCIAN TRANSAKSI BARU', $txRows[0][0]);
        // Row Transaksi Baru TRX-NEW-001
        $this->assertEquals('03 Sep 2026', $txRows[2][1]);
        $this->assertEquals('Lengkong berkah', $txRows[2][3]);
        $this->assertEquals('TRX-20260903-0001', $txRows[2][5]);
        $this->assertEquals(1000000.0, $txRows[2][6]); // Nilai Transaksi Baru
        $this->assertEquals(500000.0, $txRows[2][7]);  // Pembayaran Transaksi Baru
        $this->assertEquals(500000.0, $txRows[2][8]);  // Sisa Transaksi Baru
        $this->assertEquals('TUNAI', $txRows[2][9]);
        $this->assertEquals('SEBAGIAN', $txRows[2][10]);

        // Section 2: Rincian Pembayaran Piutang Lama
        $this->assertEquals('RINCIAN PEMBAYARAN PIUTANG LAMA', $txRows[4][0]);
        // Row 1: Bayar Piutang TRX-OLD-001
        $this->assertEquals('Lengkong berkah', $txRows[6][3]);
        $this->assertEquals('TRX-20260901-0006', $txRows[6][5]);
        $this->assertEquals(550000.0, $txRows[6][6]); // Nilai Awal
        $this->assertEquals(550000.0, $txRows[6][7]); // Saldo Sebelum
        $this->assertEquals(550000.0, $txRows[6][8]); // Dibayar
        $this->assertEquals(0.0, $txRows[6][9]);      // Sisa Setelah
        $this->assertEquals('TUNAI', $txRows[6][10]);
        $this->assertEquals('LUNAS', $txRows[6][11]);

        // Row 2: Bayar Piutang TRX-OLD-002
        $this->assertEquals('TRX-20260902-0005', $txRows[7][5]);
        $this->assertEquals(700000.0, $txRows[7][6]); // Nilai Awal
        $this->assertEquals(700000.0, $txRows[7][7]); // Saldo Sebelum
        $this->assertEquals(700000.0, $txRows[7][8]); // Dibayar
        $this->assertEquals(0.0, $txRows[7][9]);      // Sisa Setelah
        $this->assertEquals('TUNAI', $txRows[7][10]);
        $this->assertEquals('LUNAS', $txRows[7][11]);

        // 3. SHEET FOTO KUNJUNGAN
        $photoHeadings = $sheets[2]->headings();
        $this->assertContains('Foto Check In', $photoHeadings);
        $this->assertContains('Foto Etalase', $photoHeadings);
        $this->assertContains('Foto Check Out', $photoHeadings);
        $this->assertContains('Foto Dilewati', $photoHeadings);

        $photoRows = $sheets[2]->dataRows();
        $this->assertCount(2, $photoRows); // 1 row per visit (1 completed + 1 skipped)

        // Row 1: Completed visit photos
        $this->assertEquals('03 Sep 2026', $photoRows[0][0]);
        $this->assertEquals('Lengkong berkah', $photoRows[0][1]);
        $this->assertEquals('Selesai', $photoRows[0][4]);
        $this->assertEquals('09:26', $photoRows[0][5]); // Waktu Check In
        $this->assertEquals('10:30', $photoRows[0][9]); // Waktu Check Out

        // Row 2: Skipped stop photo
        $this->assertEquals('03 Sep 2026', $photoRows[1][0]);
        $this->assertEquals('Dilewati', $photoRows[1][4]);
        $this->assertEquals('Toko tutup istirahat', $photoRows[1][12]); // Alasan Dilewati

        // Verification endpoint
        $res = $this->actingAs($this->sales1)->get(route('visit.excel.rekap', [
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-04',
        ]));
        $res->assertOk();
    }
}
