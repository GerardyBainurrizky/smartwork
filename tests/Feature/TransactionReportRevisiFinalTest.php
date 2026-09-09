<?php

namespace Tests\Feature;

use App\Exports\TransactionsExport;
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
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TransactionReportRevisiFinalTest extends TestCase
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
            'name' => 'Driver Budi',
            'username' => 'driverbudi',
            'email' => 'driverbudi@example.com',
            'phone' => '081233334444',
            'password' => 'password',
            'status' => 'active',
        ]);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'code' => 'TKO-LENGKONG-01',
            'name' => 'Toko Tani Lengkong',
            'owner' => 'Bapak Lengkong',
            'type' => 'Toko Pertanian',
            'address' => 'Jl. Lengkong Besar No. 45',
            'city' => 'Bandung',
            'kecamatan' => 'Lengkong',
            'province' => 'Jawa Barat',
            'latitude' => -6.9250,
            'longitude' => 107.6150,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    public function test_admin_and_super_admin_access_transactions_report(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.transactions'))
            ->assertOk()
            ->assertSee('Laporan Transaksi Kunjungan Sales');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.transactions'))
            ->assertOk()
            ->assertSee('Laporan Transaksi Kunjungan Sales');

        $this->actingAs($this->sales)
            ->get(route('admin.reports.transactions'))
            ->assertRedirect();

        $this->actingAs($this->driver)
            ->get(route('admin.reports.transactions'))
            ->assertRedirect();
    }

    public function test_lengkong_case_historical_ledger_calculation_and_consistency(): void
    {
        $this->actingAs($this->admin);

        // 1. Prior old transactions totaling Rp 17.950.000
        $oldTx1 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260801-0001',
            'transaction_date' => '2026-08-01',
            'transaction_amount' => 10000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-08-01 08:00:00',
        ]);

        $oldTx2 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260815-0002',
            'transaction_date' => '2026-08-15',
            'transaction_amount' => 7950000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-08-15 08:00:00',
        ]);

        // Prior total receivable before visit = 10.000.000 + 7.950.000 = 17.950.000

        // 2. Visit on 2026-09-01
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Lengkong',
            'date' => '2026-09-01',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-01 10:00:00',
            'check_out_at' => '2026-09-01 10:45:00',
            'transaction_status' => 'mixed',
            'visit_result' => 'Pemesanan baru dan pembayaran piutang lama',
            'final_notes' => 'Pembayaran lunas piutang lama Rp 17.400.000 dan DP pesanan baru Rp 200.000',
            'created_at' => '2026-09-01 10:00:00',
        ]);

        // New transaction created during visit: Rp 900.000, Initial payment Rp 200.000, Remaining Rp 700.000
        $newTx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0001',
            'transaction_date' => '2026-09-01',
            'transaction_amount' => 900000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
            'created_at' => '2026-09-01 10:15:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTx->id,
            'amount' => 200000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-01',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal saat transaksi dibuat',
            'created_at' => '2026-09-01 10:15:00',
        ]);

        // Old debt payments during visit: Total Rp 17.400.000 (Rp 10.000.000 for oldTx1 + Rp 7.400.000 for oldTx2)
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTx1->id,
            'amount' => 10000000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-01',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran piutang transaksi TRX-20260801-0001',
            'created_at' => '2026-09-01 10:20:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTx2->id,
            'amount' => 7400000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-01',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran piutang transaksi TRX-20260815-0002',
            'created_at' => '2026-09-01 10:25:00',
        ]);

        // 3. Test Service
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);
        $this->assertEquals(17950000.0, $summary['balance_before'], 'Saldo Piutang Sebelum Kunjungan harus 17.950.000');
        $this->assertEquals(17400000.0, $summary['old_debt_paid'], 'Pembayaran Piutang Lama harus 17.400.000');
        $this->assertEquals(900000.0, $summary['new_tx_total'], 'Nilai Transaksi Baru harus 900.000');
        $this->assertEquals(200000.0, $summary['new_tx_initial_paid'], 'Pembayaran Transaksi Baru harus 200.000');
        $this->assertEquals(700000.0, $summary['new_tx_remaining'], 'Piutang Baru dari Transaksi harus 700.000');
        $this->assertEquals(1250000.0, $summary['balance_after'], 'Saldo Piutang Setelah Kunjungan harus 1.250.000');

        // Validation formula: 17.950.000 - 17.400.000 + 700.000 = 1.250.000
        $this->assertEquals($summary['balance_before'] - $summary['old_debt_paid'] + $summary['new_tx_remaining'], $summary['balance_after']);

        // 4. Test Web Page
        $webResp = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-01',
        ]));
        $webResp->assertOk();
        $webResp->assertSee('TRX-20260901-0001');
        $webResp->assertSee('17.950.000'); // Saldo Sebelum
        $webResp->assertSee('17.400.000'); // Bayar Lama
        $webResp->assertSee('900.000');    // Transaksi Baru
        $webResp->assertSee('200.000');    // Bayar Baru
        $webResp->assertSee('700.000');    // Piutang Baru
        $webResp->assertSee('1.250.000');  // Saldo Setelah

        // 5. Test Excel Export
        $excelExport = new TransactionsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-01',
        ]);
        $rows = $excelExport->dataRows();
        $this->assertCount(1, $rows);
        $r = $rows[0];

        $this->assertEquals('TRX-20260901-0001', $r[6]);
        $this->assertEquals(900000.0, $r[7]);    // Nilai Transaksi Baru
        $this->assertEquals(200000.0, $r[8]);    // Pembayaran Transaksi Baru
        $this->assertEquals(700000.0, $r[9]);    // Piutang Baru dari Transaksi
        $this->assertEquals(17950000.0, $r[10]); // Saldo Piutang Sebelum Kunjungan
        $this->assertEquals(17400000.0, $r[11]); // Pembayaran Piutang Lama
        $this->assertEquals(1250000.0, $r[12]);  // Saldo Piutang Setelah Kunjungan
        $this->assertEquals('Transaksi + Piutang', $r[13]);

        // 6. Test PDF Export
        $pdfResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'transactions',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-01',
        ]));
        $pdfResp->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResp->headers->get('content-type'));
    }

    public function test_kasus_transaksi_lunas_zero_piutang_baru(): void
    {
        $this->actingAs($this->admin);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Lunas',
            'date' => '2026-09-02',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-02 11:00:00',
            'check_out_at' => '2026-09-02 11:30:00',
            'transaction_status' => 'paid',
            'visit_result' => 'Pemesanan lunas',
            'created_at' => '2026-09-02 11:00:00',
        ]);

        $newTx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260902-0001',
            'transaction_date' => '2026-09-02',
            'transaction_amount' => 700000,
            'status' => StoreTransaction::STATUS_LUNAS,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
            'created_at' => '2026-09-02 11:15:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTx->id,
            'amount' => 700000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-02',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal lunas',
            'created_at' => '2026-09-02 11:15:00',
        ]);

        $summary = StoreReceivableService::getVisitReceivableSummary($visit);
        $this->assertEquals(700000.0, $summary['new_tx_total']);
        $this->assertEquals(700000.0, $summary['new_tx_initial_paid']);
        $this->assertEquals(0.0, $summary['new_tx_remaining'], 'Piutang baru harus Rp0 jika transaksi lunas');
    }

    public function test_kasus_tidak_ada_pembayaran_piutang_lama(): void
    {
        $this->actingAs($this->admin);

        // Old debt Rp 5.000.000
        StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260820-0001',
            'transaction_date' => '2026-08-20',
            'transaction_amount' => 5000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-08-20 09:00:00',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Test 3',
            'date' => '2026-09-03',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 14:00:00',
            'check_out_at' => '2026-09-03 14:30:00',
            'transaction_status' => 'paid',
            'visit_result' => 'Pemesanan baru DP 50%',
            'created_at' => '2026-09-03 14:00:00',
        ]);

        $newTx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260903-0001',
            'transaction_date' => '2026-09-03',
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
            'created_at' => '2026-09-03 14:10:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTx->id,
            'amount' => 500000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-03',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal 500k',
            'created_at' => '2026-09-03 14:10:00',
        ]);

        $summary = StoreReceivableService::getVisitReceivableSummary($visit);
        $this->assertEquals(5000000.0, $summary['balance_before']);
        $this->assertEquals(0.0, $summary['old_debt_paid']);
        $this->assertEquals(1000000.0, $summary['new_tx_total']);
        $this->assertEquals(500000.0, $summary['new_tx_initial_paid']);
        $this->assertEquals(500000.0, $summary['new_tx_remaining']);
        $this->assertEquals(5500000.0, $summary['balance_after'], 'Saldo setelah harus 5.000.000 - 0 + 500.000 = 5.500.000');
    }

    public function test_kasus_tidak_ada_transaksi_baru(): void
    {
        $this->actingAs($this->admin);

        // Old debt Rp 7.750.000
        $oldTx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260825-0001',
            'transaction_date' => '2026-08-25',
            'transaction_amount' => 7750000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-08-25 09:00:00',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Test 4',
            'date' => '2026-09-04',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-04 15:00:00',
            'check_out_at' => '2026-09-04 15:30:00',
            'transaction_status' => 'piutang',
            'visit_result' => 'Bayar cicilan piutang',
            'created_at' => '2026-09-04 15:00:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTx->id,
            'amount' => 5000000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-04',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran piutang',
            'created_at' => '2026-09-04 15:15:00',
        ]);

        $summary = StoreReceivableService::getVisitReceivableSummary($visit);
        $this->assertEquals(7750000.0, $summary['balance_before']);
        $this->assertEquals(5000000.0, $summary['old_debt_paid']);
        $this->assertEquals(0.0, $summary['new_tx_total']);
        $this->assertEquals(0.0, $summary['new_tx_remaining']);
        $this->assertEquals(2750000.0, $summary['balance_after'], 'Saldo setelah harus 7.750.000 - 5.000.000 + 0 = 2.750.000');
    }

    public function test_physical_file_generation_and_inspection(): void
    {
        $this->actingAs($this->admin);

        // Setup 1 visit with transaction and old debt payment
        $oldTx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260830-0001',
            'transaction_date' => '2026-08-30',
            'transaction_amount' => 3000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-08-30 08:00:00',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Fisik',
            'date' => '2026-09-06',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-06 09:00:00',
            'check_out_at' => '2026-09-06 09:40:00',
            'transaction_status' => 'mixed',
            'visit_result' => 'Pemesanan baru dan cicil piutang',
            'final_notes' => 'Catatan visit fisik test',
            'check_in_address' => 'Jl. Lengkong Besar No. 45 Bandung',
            'created_at' => '2026-09-06 09:00:00',
        ]);

        $newTx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260906-0001',
            'transaction_date' => '2026-09-06',
            'transaction_amount' => 1500000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
            'created_at' => '2026-09-06 09:15:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTx->id,
            'amount' => 500000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-06',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit->id,
            'notes' => 'DP 500k',
            'created_at' => '2026-09-06 09:15:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTx->id,
            'amount' => 1000000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-06',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Bayar cicilan piutang lama',
            'created_at' => '2026-09-06 09:20:00',
        ]);

        // Physical Excel Generation
        $export = new TransactionsExport([
            'fromDate' => '2026-09-06',
            'toDate' => '2026-09-06',
        ]);
        $filename = 'test_tx_' . uniqid() . '.xlsx';
        Excel::store($export, $filename, 'local');
        $storedPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filename);

        $this->assertFileExists($storedPath);
        $this->assertGreaterThan(5000, filesize($storedPath));

        // Read physical excel with PhpSpreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($storedPath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('LAPORAN TRANSAKSI KUNJUNGAN SALES', $sheet->getCell('A3')->getValue());

        // Find heading row
        $headingRow = 0;
        for ($r = 4; $r <= 20; $r++) {
            if ($sheet->getCell("A{$r}")->getValue() === 'No' && $sheet->getCell("B{$r}")->getValue() === 'Tanggal Kunjungan') {
                $headingRow = $r;
                break;
            }
        }
        $this->assertGreaterThan(0, $headingRow);

        $this->assertEquals('Nilai Transaksi Baru (Rp)', $sheet->getCell("H{$headingRow}")->getValue());
        $this->assertEquals('Pembayaran Transaksi Baru (Rp)', $sheet->getCell("I{$headingRow}")->getValue());
        $this->assertEquals('Piutang Baru dari Transaksi (Rp)', $sheet->getCell("J{$headingRow}")->getValue());
        $this->assertEquals('Saldo Piutang Sebelum Kunjungan (Rp)', $sheet->getCell("K{$headingRow}")->getValue());
        $this->assertEquals('Pembayaran Piutang Lama (Rp)', $sheet->getCell("L{$headingRow}")->getValue());
        $this->assertEquals('Saldo Piutang Setelah Kunjungan (Rp)', $sheet->getCell("M{$headingRow}")->getValue());

        $dataRow = $headingRow + 1;
        $this->assertEquals(1500000, $sheet->getCell("H{$dataRow}")->getValue()); // Nilai Baru
        $this->assertEquals(500000, $sheet->getCell("I{$dataRow}")->getValue());  // Bayar Baru
        $this->assertEquals(1000000, $sheet->getCell("J{$dataRow}")->getValue()); // Piutang Baru
        $this->assertEquals(3000000, $sheet->getCell("K{$dataRow}")->getValue()); // Saldo Sebelum
        $this->assertEquals(1000000, $sheet->getCell("L{$dataRow}")->getValue()); // Bayar Lama
        $this->assertEquals(3000000, $sheet->getCell("M{$dataRow}")->getValue()); // Saldo Setelah (3.000.000 - 1.000.000 + 1.000.000 = 3.000.000)

        @unlink($storedPath);

        // Physical PDF Generation
        $pdfResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'transactions',
            'from_date' => '2026-09-06',
            'to_date' => '2026-09-06',
        ]));
        $pdfResp->assertOk();
        $pdfContent = $pdfResp->getContent();
        $this->assertNotEmpty($pdfContent);
        $this->assertStringStartsWith('%PDF-', $pdfContent);
    }
}
