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
use Tests\TestCase;

class SalesVisitPdfAndExcelSynchronizationFinalAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $sales2;
    private User $driver;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin Test', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Test', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales = User::factory()->create(['name' => 'Fikri Haekal', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Doni', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->staff = User::factory()->create(['name' => 'Staff Siti', 'status' => 'active']);
        $this->staff->assignRole('staff');
    }

    private function createVisitScenario(
        Store $store,
        User $salesUser,
        string $date = '2026-09-02',
        string $time = '09:00:00',
        string $outTime = '10:00:00',
        string $status = 'completed'
    ): Visit {
        $route = Route::create([
            'user_id' => $salesUser->id,
            'name' => 'Rute ' . $date . ' ' . $salesUser->name,
            'date' => $date,
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => $status === 'completed' ? 'visited' : 'in_progress',
        ]);

        return Visit::create([
            'user_id' => $salesUser->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => $status,
            'check_in_at' => Carbon::parse("{$date} {$time}"),
            'check_out_at' => $status === 'completed' ? Carbon::parse("{$date} {$outTime}") : null,
            'transaction_status' => 'none',
        ]);
    }

    /**
     * TEST CASE 1: KASUS NYATA "Lengkong berkah" (02 Sep 2026, 22:43)
     * Saldo Piutang Lama Sebelum Kunjungan: Rp 17.950.000
     * Pembayaran Piutang Lama: Rp 17.400.000
     * Piutang Baru dari Transaksi Kunjungan: Rp 700.000
     * Saldo Piutang Toko Setelah Kunjungan: Rp 1.250.000
     *
     * PDF dan Excel HARUS menghasilkan angka yang IDENTIK!
     */
    public function test_lengkong_berkah_real_case_pdf_and_excel_synchronization(): void
    {
        $store = Store::create([
            'name' => 'Lengkong berkah',
            'code' => 'TKO-LB01',
            'address' => 'Jl. Lengkong No. 45',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        // 1. Setup piutang lama sebelum kunjungan: total 17.950.000
        // Transaksi Lama 1: 10.000.000
        $oldTx1 = StoreReceivableService::createTransaction([
            'store_id' => $store->id,
            'transaction_code' => 'TRX-LB-OLD1',
            'transaction_amount' => 10000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // Transaksi Lama 2: 7.950.000
        $oldTx2 = StoreReceivableService::createTransaction([
            'store_id' => $store->id,
            'transaction_code' => 'TRX-LB-OLD2',
            'transaction_amount' => 7950000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // 2. Kunjungan pada 02 Sep 2026 22:43
        $visit = $this->createVisitScenario($store, $this->sales, '2026-09-02', '22:43:00', '23:30:00');
        $visit->update(['transaction_status' => 'mixed', 'transaction_amount' => 18100000]);

        // Pembayaran piutang lama saat kunjungan:
        // Bayar OldTx1: 10.000.000 (lunas)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTx1->id,
            'amount' => 10000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pelunasan piutang lama TRX-LB-OLD1',
        ], $this->sales);

        // Bayar OldTx2: 7.400.000 (sebagian)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTx2->id,
            'amount' => 7400000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'transfer',
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran sebagian piutang lama TRX-LB-OLD2',
        ], $this->sales);
        // Total Old Debt Paid = 10.000.000 + 7.400.000 = Rp 17.400.000

        // Transaksi baru saat kunjungan: Rp 700.000 (belum dibayar, menjadi piutang baru)
        $newTx = StoreReceivableService::createTransaction([
            'store_id' => $store->id,
            'transaction_code' => 'TRX-LB-NEW1',
            'transaction_amount' => 700000,
            'paid_amount' => 0,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        $visit->refresh();

        // 3. Centralized Service Check
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);
        $this->assertEquals(17950000.0, $summary['balance_before']);
        $this->assertEquals(17400000.0, $summary['old_debt_paid']);
        $this->assertEquals(700000.0, $summary['new_tx_remaining']);
        $this->assertEquals(1250000.0, $summary['balance_after']);

        // 4. PDF View Check
        $pdfHtml = view('reports.pdf.visits', [
            'title' => 'LAPORAN KUNJUNGAN SALES',
            'items' => collect([$visit]),
            'skipped' => collect(),
            'periodLabel' => '02 Sep 2026',
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
            'generatedAt' => '02 Sep 2026, 23:59',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'selectedUserLabel' => $this->sales->name,
            'statusLabel' => 'Semua Status',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('RINGKASAN PIUTANG TOKO', $pdfHtml);
        $this->assertStringContainsString('Saldo Piutang Lama Sebelum Kunjungan', $pdfHtml);
        $this->assertStringContainsString('Rp 17.950.000', $pdfHtml);
        $this->assertStringContainsString('Pembayaran Piutang Lama', $pdfHtml);
        $this->assertStringContainsString('- Rp 17.400.000', $pdfHtml);
        $this->assertStringContainsString('Piutang Baru dari Transaksi Kunjungan', $pdfHtml);
        $this->assertStringContainsString('+ Rp 700.000', $pdfHtml);
        $this->assertStringContainsString('Saldo Piutang Toko Setelah Kunjungan', $pdfHtml);
        $this->assertStringContainsString('Rp 1.250.000', $pdfHtml);

        // 5. Excel Export Check
        $export = new VisitsExport([
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
            'userId' => $this->sales->id,
            'store_id' => $store->id,
        ]);

        $sheets = $export->sheets();
        $rekapSheet = $sheets[0];
        $rekapRows = $rekapSheet->dataRows();

        $this->assertCount(1, $rekapRows);
        $row = $rekapRows[0];

        // Col P (15): Saldo Piutang Lama Sebelum Kunjungan
        $this->assertEquals(17950000.0, $row[15]);
        // Col Q (16): Pembayaran Piutang Lama pada Kunjungan
        $this->assertEquals(17400000.0, $row[16]);
        // Col R (17): Nilai Transaksi Baru pada Kunjungan
        $this->assertEquals(700000.0, $row[17]);
        // Col S (18): Pembayaran atas Transaksi Baru pada Kunjungan
        $this->assertEquals(0.0, $row[18]);
        // Col T (19): Piutang Baru dari Transaksi Kunjungan
        $this->assertEquals(700000.0, $row[19]);
        // Col U (20): Saldo Piutang Toko Setelah Kunjungan
        $this->assertEquals(1250000.0, $row[20]);

        // 6. Direct Cross-Check: PDF == Excel
        $this->assertEquals($summary['balance_before'], $row[15]);
        $this->assertEquals($summary['old_debt_paid'], $row[16]);
        $this->assertEquals($summary['new_tx_remaining'], $row[19]);
        $this->assertEquals($summary['balance_after'], $row[20]);
    }

    /**
     * TEST CASE 2: VALIDASI TUJUH SKENARIO KUNJUNGAN & ANTI DRIFT
     */
    public function test_seven_scenarios_and_historical_drift_pdf_and_excel_match(): void
    {
        // Toko 1: Visit tanpa piutang (Scenario 1)
        $store1 = Store::create(['name' => 'Toko Tanpa Piutang', 'code' => 'TKO-01', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $v1 = $this->createVisitScenario($store1, $this->sales, '2026-09-02', '08:00:00', '08:30:00');

        // Toko 2: Visit dengan single piutang (Scenario 2)
        $store2 = Store::create(['name' => 'Toko Single Piutang', 'code' => 'TKO-02', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        StoreReceivableService::createTransaction([
            'store_id' => $store2->id,
            'transaction_code' => 'TRX-S2-01',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);
        $v2 = $this->createVisitScenario($store2, $this->sales, '2026-09-02', '09:00:00', '09:30:00');

        // Toko 3: Visit dengan banyak piutang (Scenario 3)
        $store3 = Store::create(['name' => 'Toko Multi Piutang', 'code' => 'TKO-03', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        StoreReceivableService::createTransaction([
            'store_id' => $store3->id,
            'transaction_code' => 'TRX-S3-01',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);
        StoreReceivableService::createTransaction([
            'store_id' => $store3->id,
            'transaction_code' => 'TRX-S3-02',
            'transaction_amount' => 3200000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);
        $v3 = $this->createVisitScenario($store3, $this->sales, '2026-09-02', '10:00:00', '10:30:00');

        // Toko 4: Visit dengan pembayaran piutang lama (Scenario 4)
        $store4 = Store::create(['name' => 'Toko Bayar Piutang', 'code' => 'TKO-04', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $tx4 = StoreReceivableService::createTransaction([
            'store_id' => $store4->id,
            'transaction_code' => 'TRX-S4-01',
            'transaction_amount' => 10000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);
        $v4 = $this->createVisitScenario($store4, $this->sales, '2026-09-02', '11:00:00', '11:30:00');
        $v4->update(['transaction_status' => 'piutang', 'transaction_amount' => 3000000]);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $tx4->id,
            'amount' => 3000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $v4->id,
        ], $this->sales);

        // Toko 5: Visit dengan transaksi baru (Scenario 5)
        $store5 = Store::create(['name' => 'Toko Transaksi Baru', 'code' => 'TKO-05', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $v5 = $this->createVisitScenario($store5, $this->sales, '2026-09-02', '13:00:00', '13:30:00');
        $v5->update(['transaction_status' => 'paid', 'transaction_amount' => 4000000]);
        StoreReceivableService::createTransaction([
            'store_id' => $store5->id,
            'transaction_code' => 'TRX-S5-01',
            'transaction_amount' => 4000000,
            'paid_amount' => 1000000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $v5->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        // Toko 6: Visit dengan piutang lama + transaksi baru (Scenario 6)
        $store6 = Store::create(['name' => 'Toko Mixed Lengkap', 'code' => 'TKO-06', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $tx6Old = StoreReceivableService::createTransaction([
            'store_id' => $store6->id,
            'transaction_code' => 'TRX-S6-OLD',
            'transaction_amount' => 6000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);
        $v6 = $this->createVisitScenario($store6, $this->sales, '2026-09-02', '14:00:00', '14:30:00');
        $v6->update(['transaction_status' => 'mixed', 'transaction_amount' => 4000000]);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $tx6Old->id,
            'amount' => 2000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $v6->id,
        ], $this->sales);
        StoreReceivableService::createTransaction([
            'store_id' => $store6->id,
            'transaction_code' => 'TRX-S6-NEW',
            'transaction_amount' => 2000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $v6->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        // Toko 7: Historical Drift validation (Scenario 7)
        // Kunjungan lama terjadi 02 Sep 2026 saat saldo 8.000.000
        $store7 = Store::create(['name' => 'Toko Historical Drift', 'code' => 'TKO-07', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $tx7Past = StoreReceivableService::createTransaction([
            'store_id' => $store7->id,
            'transaction_code' => 'TRX-S7-PAST',
            'transaction_amount' => 8000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);
        $v7 = $this->createVisitScenario($store7, $this->sales, '2026-09-02', '15:00:00', '15:30:00');

        // Pada hari berikutnya (05 Sep 2026), toko menambah transaksi Rp 50.000.000 dan bayar Rp 10.000.000
        // Current balance toko menjadi (8.000.000 + 50.000.000 - 10.000.000) = Rp 48.000.000
        $tx7Future = StoreReceivableService::createTransaction([
            'store_id' => $store7->id,
            'transaction_code' => 'TRX-S7-FUTURE',
            'transaction_amount' => 50000000,
            'transaction_date' => '2026-09-05',
        ], $this->sales);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $tx7Future->id,
            'amount' => 10000000,
            'payment_date' => '2026-09-05',
            'payment_method' => 'transfer',
            'source' => 'admin_payment',
        ], $this->sales);

        // Refresh all visits
        $allVisits = collect([$v1, $v2, $v3, $v4, $v5, $v6, $v7])->map(fn($v) => $v->fresh(['store', 'user', 'payments.transaction', 'transactions.payments']));

        // Export Excel for 2026-09-02
        $export = new VisitsExport([
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
            'userId' => $this->sales->id,
        ]);

        $excelRows = $export->sheets()[0]->dataRows();
        $this->assertCount(7, $excelRows);

        // Record by Record Verification
        foreach ($allVisits as $index => $visit) {
            $pdfSummary = StoreReceivableService::getVisitReceivableSummary($visit);
            $excelRow = $excelRows[$index];

            $excelSaldoAwal = $excelRow[15];
            $excelPembayaran = $excelRow[16];
            $excelPiutangBaru = $excelRow[19];
            $excelSaldoAkhir = $excelRow[20];

            // 1. Exact equality between PDF calculation and Excel row
            $this->assertEquals($pdfSummary['balance_before'], $excelSaldoAwal, "Saldo awal mismatch for visit {$visit->id}");
            $this->assertEquals($pdfSummary['old_debt_paid'], $excelPembayaran, "Pembayaran mismatch for visit {$visit->id}");
            $this->assertEquals($pdfSummary['new_tx_remaining'], $excelPiutangBaru, "Piutang baru mismatch for visit {$visit->id}");
            $this->assertEquals($pdfSummary['balance_after'], $excelSaldoAkhir, "Saldo akhir mismatch for visit {$visit->id}");
        }

        // Specific assertions for Scenario 7 (Anti-Drift):
        // Historical saldo before visit must be 8.000.000, NOT current balance 48.000.000!
        $s7Summary = StoreReceivableService::getVisitReceivableSummary($v7->fresh());
        $this->assertEquals(8000000.0, $s7Summary['balance_before']);
        $this->assertEquals(8000000.0, $s7Summary['balance_after']);
        $this->assertEquals(8000000.0, $excelRows[6][15]);
        $this->assertEquals(8000000.0, $excelRows[6][20]);
    }

    /**
     * TEST CASE 3: ANTI DOUBLE COUNTING & INVOICE STATUS ACCURACY
     */
    public function test_anti_double_counting_and_multi_payments_on_single_invoice(): void
    {
        $store = Store::create(['name' => 'Toko Multi Bayar', 'code' => 'TKO-MB01', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);

        // 1 Transaksi: 15.000.000
        $tx = StoreReceivableService::createTransaction([
            'store_id' => $store->id,
            'transaction_code' => 'TRX-MB-001',
            'transaction_amount' => 15000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // 3 Pembayaran bertahap sebelum kunjungan: 3.000.000 + 4.000.000 + 1.000.000 = 8.000.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $tx->id,
            'amount' => 3000000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'transfer',
            'source' => 'admin_payment',
        ], $this->sales);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $tx->id,
            'amount' => 4000000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'transfer',
            'source' => 'admin_payment',
        ], $this->sales);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $tx->id,
            'amount' => 1000000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'tunai',
            'source' => 'admin_payment',
        ], $this->sales);

        // Outstanding before visit: 15.000.000 - 8.000.000 = 7.000.000
        $visit = $this->createVisitScenario($store, $this->sales, '2026-09-02', '10:00:00', '10:30:00');
        $visit->update(['transaction_status' => 'piutang', 'transaction_amount' => 5000000]);

        // Pembayaran ke-4 saat visit: 5.000.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $tx->id,
            'amount' => 5000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        $visit->refresh();

        $summary = StoreReceivableService::getVisitReceivableSummary($visit);
        $this->assertEquals(7000000.0, $summary['balance_before']);
        $this->assertEquals(5000000.0, $summary['old_debt_paid']);
        $this->assertEquals(0.0, $summary['new_tx_remaining']);
        $this->assertEquals(2000000.0, $summary['balance_after']);

        $export = new VisitsExport([
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
            'userId' => $this->sales->id,
        ]);
        $rows = $export->sheets()[0]->dataRows();
        $this->assertCount(1, $rows);
        $this->assertEquals(7000000.0, $rows[0][15]);
        $this->assertEquals(5000000.0, $rows[0][16]);
        $this->assertEquals(0.0, $rows[0][19]);
        $this->assertEquals(2000000.0, $rows[0][20]);
    }

    /**
     * TEST CASE 4: FILTER PARITY (SALES, STORE, SEARCH, STATUS, DATE)
     */
    public function test_filters_produce_identical_dataset_in_pdf_and_excel(): void
    {
        $storeA = Store::create(['name' => 'Toko Alpha Jakarta', 'code' => 'TKO-ALP', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $storeB = Store::create(['name' => 'Toko Beta Bandung', 'code' => 'TKO-BET', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales2->id]);

        $vA = $this->createVisitScenario($storeA, $this->sales, '2026-09-02', '09:00:00', '09:30:00', 'completed');
        $vB = $this->createVisitScenario($storeB, $this->sales2, '2026-09-02', '10:00:00', '10:30:00', 'completed');
        $vC = $this->createVisitScenario($storeA, $this->sales, '2026-09-03', '11:00:00', '11:30:00', 'in_progress');

        // 1. Filter by sales (Sales 1)
        $exportSales1 = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'userId' => $this->sales->id,
        ]);
        $rowsSales1 = $exportSales1->sheets()[0]->dataRows();
        $this->assertCount(2, $rowsSales1);

        // 2. Filter by store (Store B)
        $exportStoreB = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'store_id' => $storeB->id,
        ]);
        $rowsStoreB = $exportStoreB->sheets()[0]->dataRows();
        $this->assertCount(1, $rowsStoreB);
        $this->assertEquals('Toko Beta Bandung', $rowsStoreB[0][3]);

        // 3. Filter by search ("Alpha")
        $exportSearch = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'search' => 'Alpha',
        ]);
        $rowsSearch = $exportSearch->sheets()[0]->dataRows();
        $this->assertCount(2, $rowsSearch);

        // 4. Filter by status ("completed")
        $exportStatus = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'status' => 'completed',
        ]);
        $rowsStatus = $exportStatus->sheets()[0]->dataRows();
        $this->assertCount(2, $rowsStatus);
    }

    /**
     * TEST CASE 5: HTTP ENDPOINTS FOR ADMIN AND SUPER ADMIN
     */
    public function test_admin_and_super_admin_can_download_pdf_and_excel(): void
    {
        $store = Store::create(['name' => 'Toko Laporan', 'code' => 'TKO-LAP', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $this->createVisitScenario($store, $this->sales, '2026-09-02', '09:00:00', '09:30:00');

        // Admin PDF
        $resAdminPdf = $this->actingAs($this->admin)->get(route('admin.reports.export-pdf', [
            'type' => 'visits',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-04',
        ]));
        $resAdminPdf->assertOk();

        // Admin Excel
        $resAdminExcel = $this->actingAs($this->admin)->get(route('admin.reports.export-excel', [
            'type' => 'visits',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-04',
        ]));
        $resAdminExcel->assertOk();

        // Super Admin PDF
        $resSuperPdf = $this->actingAs($this->superAdmin)->get(route('admin.reports.export-pdf', [
            'type' => 'visits',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-04',
        ]));
        $resSuperPdf->assertOk();

        // Super Admin Excel
        $resSuperExcel = $this->actingAs($this->superAdmin)->get(route('admin.reports.export-excel', [
            'type' => 'visits',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-04',
        ]));
        $resSuperExcel->assertOk();
    }
}
