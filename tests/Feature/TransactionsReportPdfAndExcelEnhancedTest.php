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
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class TransactionsReportPdfAndExcelEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $driver;
    private Store $store;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->sales = User::where('username', 'karyawan')->first();

        $this->driver = User::create([
            'name' => 'Driver Anwar',
            'username' => 'driveranwar',
            'email' => 'driveranwar@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'status' => 'active',
        ]);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'code' => 'TKO-LENGKONG-01',
            'name' => 'Toko Subur Gemilang',
            'owner' => 'Bapak Haji Subur',
            'type' => 'Toko Pertanian',
            'address' => 'Jl. Lengkong Besar No. 88, Bandung',
            'city' => 'Bandung',
            'kecamatan' => 'Lengkong',
            'province' => 'Jawa Barat',
            'latitude' => -6.9250,
            'longitude' => 107.6150,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->store2 = Store::create([
            'code' => 'TKO-CIBIRU-02',
            'name' => 'Toko Tani Makmur Cibiru',
            'owner' => 'Ibu Hj. Siti',
            'type' => 'Toko Pertanian',
            'address' => 'Jl. Raya Cibiru No. 120, Bandung',
            'city' => 'Bandung',
            'kecamatan' => 'Cibiru',
            'province' => 'Jawa Barat',
            'latitude' => -6.9350,
            'longitude' => 107.7150,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    public function test_multiple_old_debts_and_new_transaction_pdf_and_excel_full_validation(): void
    {
        $this->actingAs($this->admin);

        // 1. Setup 3 Old Debt Transactions
        $oldTx1 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0002',
            'transaction_date' => '2026-09-01',
            'transaction_amount' => 4300000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-09-01 08:00:00',
        ]);

        $oldTx2 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0003',
            'transaction_date' => '2026-09-01',
            'transaction_amount' => 5100000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-09-01 08:30:00',
        ]);

        $oldTx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0006',
            'transaction_date' => '2026-09-01',
            'transaction_amount' => 8550000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
            'created_at' => '2026-09-01 09:00:00',
        ]);

        // 2. Visit on 2026-09-02
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan 2 September',
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
            'check_in_at' => '2026-09-02 10:00:00',
            'check_out_at' => '2026-09-02 10:50:00',
            'transaction_status' => 'mixed',
            'visit_result' => 'Penagihan piutang lama 3 nota dan pemesanan insektisida baru',
            'final_notes' => 'Toko melunasi nota 1 & 2 dan mencicil nota 3, sisa Rp 550.000',
            'created_at' => '2026-09-02 10:00:00',
        ]);

        // 3. New Transaction: TRX-20260902-0005 (Nilai 900.000, Pembayaran 200.000, Piutang Baru 700.000)
        $newTx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260902-0005',
            'transaction_date' => '2026-09-02',
            'transaction_amount' => 900000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
            'created_at' => '2026-09-02 10:15:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTx->id,
            'amount' => 200000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal saat transaksi dibuat',
            'created_at' => '2026-09-02 10:15:00',
        ]);

        // 4. Old Debt Payments during Visit: Total 17.400.000
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTx1->id,
            'amount' => 4300000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pelunasan TRX-20260901-0002',
            'created_at' => '2026-09-02 10:20:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTx2->id,
            'amount' => 5100000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pelunasan TRX-20260901-0003',
            'created_at' => '2026-09-02 10:25:00',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTx3->id,
            'amount' => 8000000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Cicilan TRX-20260901-0006',
            'created_at' => '2026-09-02 10:30:00',
        ]);

        // 5. Check Service Calculation
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);
        $this->assertEquals(17950000.0, $summary['balance_before'], 'Saldo Piutang Sebelum harus Rp 17.950.000');
        $this->assertEquals(17400000.0, $summary['old_debt_paid'], 'Total Pembayaran Piutang Lama harus Rp 17.400.000');
        $this->assertEquals(900000.0, $summary['new_tx_total'], 'Nilai Transaksi Baru harus Rp 900.000');
        $this->assertEquals(200000.0, $summary['new_tx_initial_paid'], 'Pembayaran Transaksi Baru harus Rp 200.000');
        $this->assertEquals(700000.0, $summary['new_tx_remaining'], 'Piutang Baru harus Rp 700.000');
        $this->assertEquals(1250000.0, $summary['balance_after'], 'Saldo Piutang Setelah harus Rp 1.250.000');

        // 6. Test Web Table
        $webResp = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-02',
            'to_date' => '2026-09-02',
        ]));
        $webResp->assertOk();
        $webResp->assertSee('TRX-20260902-0005');
        $webResp->assertSee('17.950.000');
        $webResp->assertSee('17.400.000');
        $webResp->assertSee('900.000');
        $webResp->assertSee('200.000');
        $webResp->assertSee('700.000');
        $webResp->assertSee('1.250.000');

        // 7. Test PDF Generation and Content
        $pdfResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'transactions',
            'from_date' => '2026-09-02',
            'to_date' => '2026-09-02',
        ]));
        $pdfResp->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResp->headers->get('content-type'));

        // Check rendered PDF view directly to verify text elements
        $viewContent = view('reports.pdf.transactions', [
            'title' => 'LAPORAN TRANSAKSI KUNJUNGAN SALES',
            'items' => Visit::with([
                'user.roles', 'store', 'route', 'routeStop', 'photos',
                'transactions.payments', 'payments.transaction.payments',
            ])->where('id', $visit->id)->get(),
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
            'periodLabel' => '02 Sep 2026',
            'generatedAt' => '02 September 2026, 12:00 WIB',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'selectedUserLabel' => 'Semua Sales',
            'txStatusLabel' => 'Semua Status',
        ])->render();

        $this->assertStringContainsString('TRX-20260902-0005', $viewContent);
        $this->assertStringContainsString('TRX-20260901-0002', $viewContent);
        $this->assertStringContainsString('TRX-20260901-0003', $viewContent);
        $this->assertStringContainsString('TRX-20260901-0006', $viewContent);
        $this->assertStringContainsString('17.950.000', $viewContent);
        $this->assertStringContainsString('17.400.000', $viewContent);
        $this->assertStringContainsString('900.000', $viewContent);
        $this->assertStringContainsString('200.000', $viewContent);
        $this->assertStringContainsString('700.000', $viewContent);
        $this->assertStringContainsString('1.250.000', $viewContent);
        $this->assertStringContainsString('Penagihan piutang lama 3 nota dan pemesanan insektisida baru', $viewContent);
        $this->assertStringContainsString('Toko melunasi nota 1 &amp; 2 dan mencicil nota 3, sisa Rp 550.000', $viewContent);

        // 8. Test Multi-Sheet Excel Generation and Physical Inspection
        $export = new TransactionsExport([
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
        ]);
        $filename = 'test_tx_multisheet_' . uniqid() . '.xlsx';
        Excel::store($export, $filename, 'local');
        $storedPath = Storage::disk('local')->path($filename);

        $this->assertFileExists($storedPath);

        $spreadsheet = IOFactory::load($storedPath);

        // Verify that 4 sheets exist
        $sheetNames = $spreadsheet->getSheetNames();
        $this->assertContains('Ringkasan Transaksi', $sheetNames);
        $this->assertContains('Detail Piutang Lama', $sheetNames);
        $this->assertContains('Detail Transaksi Baru', $sheetNames);
        $this->assertContains('Detail Kunjungan', $sheetNames);

        // --- SHEET 1: Ringkasan Transaksi ---
        $sheet1 = $spreadsheet->getSheetByName('Ringkasan Transaksi');
        $this->assertEquals('LAPORAN TRANSAKSI KUNJUNGAN SALES', $sheet1->getCell('A3')->getValue());

        $h1 = 0;
        for ($r = 4; $r <= 20; $r++) {
            if ($sheet1->getCell("A{$r}")->getValue() === 'No' && $sheet1->getCell("B{$r}")->getValue() === 'Tanggal Kunjungan') {
                $h1 = $r;
                break;
            }
        }
        $this->assertGreaterThan(0, $h1);

        $dataRow1 = $h1 + 1;
        $this->assertEquals('TRX-20260902-0005', $sheet1->getCell("G{$dataRow1}")->getValue());
        $this->assertEquals(900000.0, $sheet1->getCell("H{$dataRow1}")->getValue());
        $this->assertEquals(200000.0, $sheet1->getCell("I{$dataRow1}")->getValue());
        $this->assertEquals(700000.0, $sheet1->getCell("J{$dataRow1}")->getValue());
        $this->assertEquals(17950000.0, $sheet1->getCell("K{$dataRow1}")->getValue());
        $this->assertEquals(17400000.0, $sheet1->getCell("L{$dataRow1}")->getValue());
        $this->assertEquals(1250000.0, $sheet1->getCell("M{$dataRow1}")->getValue());
        $this->assertEquals('Penagihan piutang lama 3 nota dan pemesanan insektisida baru', $sheet1->getCell("O{$dataRow1}")->getValue());

        // Column O (Hasil Kunjungan) width should be >= 50
        $this->assertGreaterThanOrEqual(50, $sheet1->getColumnDimension('O')->getWidth());

        // --- SHEET 2: Detail Piutang Lama ---
        $sheet2 = $spreadsheet->getSheetByName('Detail Piutang Lama');
        $this->assertEquals('RINCIAN PEMBAYARAN PIUTANG LAMA KUNJUNGAN SALES', $sheet2->getCell('A3')->getValue());

        $h2 = 0;
        for ($r = 4; $r <= 20; $r++) {
            if ($sheet2->getCell("A{$r}")->getValue() === 'No' && $sheet2->getCell("B{$r}")->getValue() === 'Tanggal Kunjungan') {
                $h2 = $r;
                break;
            }
        }
        $this->assertGreaterThan(0, $h2);

        $pRow1 = $h2 + 1;
        $pRow2 = $h2 + 2;
        $pRow3 = $h2 + 3;

        $this->assertEquals('TRX-20260901-0002', $sheet2->getCell("G{$pRow1}")->getValue());
        $this->assertEquals(4300000.0, $sheet2->getCell("H{$pRow1}")->getValue());
        $this->assertEquals(4300000.0, $sheet2->getCell("I{$pRow1}")->getValue());
        $this->assertEquals(4300000.0, $sheet2->getCell("J{$pRow1}")->getValue());
        $this->assertEquals(0.0, $sheet2->getCell("K{$pRow1}")->getValue());

        $this->assertEquals('TRX-20260901-0003', $sheet2->getCell("G{$pRow2}")->getValue());
        $this->assertEquals(5100000.0, $sheet2->getCell("H{$pRow2}")->getValue());
        $this->assertEquals(5100000.0, $sheet2->getCell("I{$pRow2}")->getValue());
        $this->assertEquals(5100000.0, $sheet2->getCell("J{$pRow2}")->getValue());
        $this->assertEquals(0.0, $sheet2->getCell("K{$pRow2}")->getValue());

        $this->assertEquals('TRX-20260901-0006', $sheet2->getCell("G{$pRow3}")->getValue());
        $this->assertEquals(8550000.0, $sheet2->getCell("H{$pRow3}")->getValue());
        $this->assertEquals(8550000.0, $sheet2->getCell("I{$pRow3}")->getValue());
        $this->assertEquals(8000000.0, $sheet2->getCell("J{$pRow3}")->getValue());
        $this->assertEquals(550000.0, $sheet2->getCell("K{$pRow3}")->getValue());

        // --- SHEET 3: Detail Transaksi Baru ---
        $sheet3 = $spreadsheet->getSheetByName('Detail Transaksi Baru');
        $this->assertEquals('RINCIAN TRANSAKSI BARU KUNJUNGAN SALES', $sheet3->getCell('A3')->getValue());

        $h3 = 0;
        for ($r = 4; $r <= 20; $r++) {
            if ($sheet3->getCell("A{$r}")->getValue() === 'No' && $sheet3->getCell("B{$r}")->getValue() === 'Tanggal Kunjungan') {
                $h3 = $r;
                break;
            }
        }
        $this->assertGreaterThan(0, $h3);

        $nRow = $h3 + 1;
        $this->assertEquals('TRX-20260902-0005', $sheet3->getCell("G{$nRow}")->getValue());
        $this->assertEquals(900000.0, $sheet3->getCell("I{$nRow}")->getValue());
        $this->assertEquals(200000.0, $sheet3->getCell("J{$nRow}")->getValue());
        $this->assertEquals(700000.0, $sheet3->getCell("K{$nRow}")->getValue());
        $this->assertEquals('SEBAGIAN', $sheet3->getCell("M{$nRow}")->getValue());

        // --- SHEET 4: Detail Kunjungan ---
        $sheet4 = $spreadsheet->getSheetByName('Detail Kunjungan');
        $this->assertEquals('RINCIAN AKTIVITAS KUNJUNGAN SALES', $sheet4->getCell('A3')->getValue());

        $h4 = 0;
        for ($r = 4; $r <= 20; $r++) {
            if ($sheet4->getCell("A{$r}")->getValue() === 'No' && $sheet4->getCell("B{$r}")->getValue() === 'ID Kunjungan') {
                $h4 = $r;
                break;
            }
        }
        $this->assertGreaterThan(0, $h4);

        $kRow = $h4 + 1;
        $this->assertEquals((string) $visit->id, $sheet4->getCell("B{$kRow}")->getValue());
        $this->assertEquals('Toko Subur Gemilang', $sheet4->getCell("H{$kRow}")->getValue());
        $this->assertEquals('Penagihan piutang lama 3 nota dan pemesanan insektisida baru', $sheet4->getCell("M{$kRow}")->getValue());
        $this->assertGreaterThanOrEqual(50, $sheet4->getColumnDimension('M')->getWidth());

        @unlink($storedPath);
    }

    public function test_role_authorization_and_export_isolation(): void
    {
        // Sales cannot access transaction report or exports
        $this->actingAs($this->sales)
            ->get(route('admin.reports.transactions'))
            ->assertRedirect();

        $this->actingAs($this->sales)
            ->get(route('admin.reports.export-pdf', ['type' => 'transactions']))
            ->assertRedirect();

        $this->actingAs($this->sales)
            ->get(route('admin.reports.export-excel', ['type' => 'transactions']))
            ->assertRedirect();

        // Driver cannot access transaction report or exports
        $this->actingAs($this->driver)
            ->get(route('admin.reports.transactions'))
            ->assertRedirect();

        $this->actingAs($this->driver)
            ->get(route('admin.reports.export-pdf', ['type' => 'transactions']))
            ->assertRedirect();

        $this->actingAs($this->driver)
            ->get(route('admin.reports.export-excel', ['type' => 'transactions']))
            ->assertRedirect();

        // Admin can access
        $this->actingAs($this->admin)
            ->get(route('admin.reports.transactions'))
            ->assertOk();

        // Super Admin can access
        $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.transactions'))
            ->assertOk();
    }

    public function test_filters_consistency_across_web_pdf_and_excel(): void
    {
        $this->actingAs($this->admin);

        // Create visit for store 1 (mixed)
        $route1 = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Toko 1',
            'date' => '2026-09-03',
            'status' => 'completed',
        ]);
        $stop1 = RouteStop::create([
            'route_id' => $route1->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit1 = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route1->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 09:00:00',
            'check_out_at' => '2026-09-03 09:30:00',
            'transaction_status' => 'paid',
            'visit_result' => 'Pembelian tunai pupuk organik',
            'final_notes' => 'Lunas',
            'created_at' => '2026-09-03 09:00:00',
        ]);
        $tx1 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260903-0001',
            'transaction_date' => '2026-09-03',
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_LUNAS,
            'reference_type' => 'visit',
            'reference_id' => $visit1->id,
            'created_by' => $this->sales->id,
            'created_at' => '2026-09-03 09:10:00',
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $tx1->id,
            'amount' => 1000000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-03',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit1->id,
            'notes' => 'Pembayaran awal lunas',
            'created_at' => '2026-09-03 09:10:00',
        ]);

        // Create visit for store 2 (none)
        $route2 = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Toko 2',
            'date' => '2026-09-03',
            'status' => 'completed',
        ]);
        $stop2 = RouteStop::create([
            'route_id' => $route2->id,
            'store_id' => $this->store2->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit2 = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route2->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 11:00:00',
            'check_out_at' => '2026-09-03 11:30:00',
            'transaction_status' => 'none',
            'visit_result' => 'Kunjungan silaturahmi tanpa transaksi',
            'final_notes' => 'Pemilik sedang di luar kota',
            'created_at' => '2026-09-03 11:00:00',
        ]);

        // 1. Filter by store_id = $this->store->id
        $webStoreResp = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-03',
            'to_date' => '2026-09-03',
            'store_id' => $this->store->id,
        ]));
        $webStoreResp->assertOk();
        $webStoreVisits = $webStoreResp->original->getData()['transactions'];
        $this->assertCount(1, $webStoreVisits);
        $this->assertEquals($this->store->id, $webStoreVisits->first()->store_id);

        $pdfStoreResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'transactions',
            'from_date' => '2026-09-03',
            'to_date' => '2026-09-03',
            'store_id' => $this->store->id,
        ]));
        $pdfStoreResp->assertOk();

        $excelStore = new TransactionsExport([
            'fromDate' => '2026-09-03',
            'toDate' => '2026-09-03',
            'store_id' => $this->store->id,
        ]);
        $rowsStore = $excelStore->dataRows();
        $this->assertCount(1, $rowsStore);
        $this->assertEquals('Toko Subur Gemilang', $rowsStore[0][4]);

        // 2. Filter by transaction_status = 'paid'
        $webPaidResp = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-03',
            'to_date' => '2026-09-03',
            'transaction_status' => 'paid',
        ]));
        $webPaidResp->assertOk();
        $webPaidVisits = $webPaidResp->original->getData()['transactions'];
        $this->assertCount(1, $webPaidVisits);
        $this->assertEquals($this->store->id, $webPaidVisits->first()->store_id);

        $excelPaid = new TransactionsExport([
            'fromDate' => '2026-09-03',
            'toDate' => '2026-09-03',
            'transaction_status' => 'paid',
        ]);
        $rowsPaid = $excelPaid->dataRows();
        $this->assertCount(1, $rowsPaid);
        $this->assertEquals('TRX-20260903-0001', $rowsPaid[0][6]);
    }
}
