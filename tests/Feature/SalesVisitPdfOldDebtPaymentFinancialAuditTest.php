<?php

namespace Tests\Feature;

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

class SalesVisitPdfOldDebtPaymentFinancialAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private Store $storeA;
    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Sales Budi', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Joko', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko Subur Makmur',
            'code' => 'SM-001',
            'address' => 'Jl. Pahlawan No. 10',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko Barokah Jaya',
            'code' => 'BJ-002',
            'address' => 'Jl. Melati No. 20',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    /**
     * TEST 1-9: Scenario pengujian multi transaksi piutang lama pada PDF Detail Kunjungan.
     * - TRX-001: Nilai Rp 6.350.000, bayar Rp 350.000 -> sisa Rp 6.000.000
     * - TRX-002: Nilai Rp 370.000, bayar Rp 70.000 -> sisa Rp 300.000
     * - TRX-003: Nilai Rp 700.000, bayar Rp 700.000 -> sisa Rp 0 (LUNAS)
     * - TRX-004: Nilai Rp 1.000.000 (tidak dibayar pada visit ini)
     *
     * Total Piutang Toko Sebelum Kunjungan: 6.350.000 + 370.000 + 700.000 + 1.000.000 = Rp 8.420.000
     * Pembayaran Piutang Lama Saat Kunjungan: 350.000 + 70.000 + 700.000 = Rp 1.120.000
     * Sisa Piutang Toko Setelah Kunjungan: 6.000.000 + 300.000 + 0 + 1.000.000 = Rp 7.300.000
     */
    public function test_pdf_old_debt_table_and_summary_section_accuracy(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260901-0004',
            'transaction_amount' => 6350000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260901-0007',
            'transaction_amount' => 370000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $trx3 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260902-0004',
            'transaction_amount' => 700000,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        $trx4 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260901-0009',
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan PDF',
            'date' => '2026-09-02',
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
            'check_in_at' => Carbon::parse('2026-09-02 10:00:00'),
            'check_out_at' => Carbon::parse('2026-09-02 11:00:00'),
            'transaction_status' => 'piutang',
            'visit_result' => 'Penagihan piutang berhasil',
        ]);

        // Payment 1
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 350000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Payment 2
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx2->id,
            'amount' => 70000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Payment 3 (Lunas)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx3->id,
            'amount' => 700000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Render PDF view secara langsung untuk memeriksa konten HTML sebelum konversi PDF
        $visit->load(['store', 'user.roles', 'routeStop.route', 'payments.transaction', 'transactions.payments', 'photos']);
        $storeBalance = (float) StoreReceivableService::balanceForStore($visit->store_id);

        $viewHtml = view('visit.pdf.detail', [
            'visit' => $visit,
            'store' => $visit->store,
            'salesName' => $visit->user->name,
            'salesRole' => 'Sales',
            'durationLabel' => '1 Jam',
            'storeBalance' => $storeBalance,
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ])->render();

        // 1. Table Headers & Columns
        $this->assertStringContainsString('Saldo Sebelum Pembayaran', $viewHtml);
        $this->assertStringContainsString('Pembayaran pada Kunjungan Ini', $viewHtml);
        $this->assertStringContainsString('Sisa Piutang Faktur', $viewHtml);

        // 2. Transaksi 1: Saldo sebelum 6.350.000, Dibayar 350.000, Sisa 6.000.000
        $this->assertStringContainsString('TRX-20260901-0004', $viewHtml);
        $this->assertStringContainsString('Rp 6.350.000', $viewHtml);
        $this->assertStringContainsString('Rp 350.000', $viewHtml);
        $this->assertStringContainsString('Rp 6.000.000', $viewHtml);

        // 3. Transaksi 2: Saldo sebelum 370.000, Dibayar 70.000, Sisa 300.000
        $this->assertStringContainsString('TRX-20260901-0007', $viewHtml);
        $this->assertStringContainsString('Rp 370.000', $viewHtml);
        $this->assertStringContainsString('Rp 70.000', $viewHtml);
        $this->assertStringContainsString('Rp 300.000', $viewHtml);

        // 4. Transaksi 3: Saldo sebelum 700.000, Dibayar 700.000, Sisa 0
        $this->assertStringContainsString('TRX-20260902-0004', $viewHtml);
        $this->assertStringContainsString('Rp 700.000', $viewHtml);
        $this->assertStringContainsString('Rp 0', $viewHtml);

        // 5. Total Pembayaran Piutang = Rp 1.120.000
        $this->assertStringContainsString('Total Pembayaran Piutang:', $viewHtml);
        $this->assertStringContainsString('Rp 1.120.000', $viewHtml);

        // 6. Section Ringkasan Piutang Toko
        $this->assertStringContainsString('RINGKASAN PIUTANG TOKO', $viewHtml);
        $this->assertStringContainsString('Saldo Piutang Sebelum Kunjungan', $viewHtml);
        $this->assertStringContainsString('Rp 8.420.000', $viewHtml);
        $this->assertStringContainsString('Pembayaran Piutang Lama', $viewHtml);
        $this->assertStringContainsString('Saldo Piutang Toko Setelah Kunjungan', $viewHtml);
        $this->assertStringContainsString('Rp 7.300.000', $viewHtml);

        // 7. Unduh via Controller Route
        $res = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $res->assertOk();
        $this->assertEquals('application/pdf', $res->headers->get('Content-Type'));
    }

    /**
     * TEST 10-14: Historical payment separation & New transaction with remaining balance.
     * Toko A:
     * - TRX Lama: Rp 1.000.000, pembayaran kemarin Rp 200.000 -> saldo sebelum visit Rp 800.000
     * - Pada Visit: bayar Rp 300.000 -> sisa TRX Lama Rp 500.000
     * - Transaksi Baru pada visit: Rp 700.000, DP Rp 300.000 -> sisa TRX Baru Rp 400.000
     *
     * Total Piutang Toko Sebelum Kunjungan: Rp 800.000
     * Pembayaran Piutang Lama Saat Kunjungan: Rp 300.000
     * Sisa Piutang Toko Setelah Kunjungan: 500.000 + 400.000 = Rp 900.000
     */
    public function test_pdf_historical_payment_and_new_transaction_piutang_separation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $oldTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-HIST-01',
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-08-20',
        ], $this->sales);

        // Historical payment kemarin
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 200000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'tunai',
        ], $this->sales);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan 2',
            'date' => '2026-09-02',
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
            'check_in_at' => Carbon::parse('2026-09-02 10:00:00'),
            'check_out_at' => Carbon::parse('2026-09-02 11:00:00'),
            'transaction_status' => 'mixed',
            'transaction_amount' => 700000,
        ]);

        // Bayar piutang lama pada visit: Rp 300.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 300000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'transfer',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Transaksi baru pada visit: Rp 700.000 dengan DP Rp 300.000 (sisa Rp 400.000)
        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-NEW-02',
            'transaction_amount' => 700000,
            'paid_amount' => 300000,
            'transaction_date' => '2026-09-02',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
        ], $this->sales);

        $visit->load(['store', 'user.roles', 'routeStop.route', 'payments.transaction', 'transactions.payments', 'photos']);
        $storeBalance = (float) StoreReceivableService::balanceForStore($visit->store_id);

        $viewHtml = view('visit.pdf.detail', [
            'visit' => $visit,
            'store' => $visit->store,
            'salesName' => $visit->user->name,
            'salesRole' => 'Sales',
            'durationLabel' => '1 Jam',
            'storeBalance' => $storeBalance,
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ])->render();

        // Saldo sebelum pembayaran untuk TRX-HIST-01 adalah Rp 800.000 (bukan Rp 1.000.000 karena Rp 200.000 sudah dibayar kemarin)
        $this->assertStringContainsString('TRX-HIST-01', $viewHtml);
        $this->assertStringContainsString('Rp 800.000', $viewHtml);
        $this->assertStringContainsString('Rp 300.000', $viewHtml);
        $this->assertStringContainsString('Rp 500.000', $viewHtml);

        // Initial payment Rp 300.000 transaksi baru berada di tabel TRANSAKSI BARU, bukan di PEMBAYARAN PIUTANG LAMA
        $this->assertStringContainsString('TRANSAKSI BARU', $viewHtml);
        $this->assertStringContainsString('TRX-NEW-02', $viewHtml);

        // Ringkasan Piutang Toko:
        // Total Piutang Sebelum Kunjungan = Rp 800.000
        // Pembayaran Piutang Lama Saat Kunjungan = Rp 300.000
        // Sisa Piutang Toko Setelah Kunjungan = 500.000 (sisa TRX lama) + 400.000 (sisa TRX baru) = Rp 900.000
        $this->assertStringContainsString('Rp 800.000', $viewHtml);
        $this->assertStringContainsString('Rp 300.000', $viewHtml);
        $this->assertStringContainsString('Rp 900.000', $viewHtml);
    }

    /**
     * TEST 15: Export bersifat Read-Only dan tidak merubah database.
     */
    public function test_pdf_export_is_strictly_read_only(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 500000,
            'transaction_date' => now()->toDateString(),
        ], $this->sales);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Read Only',
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
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'none',
        ]);

        $trxCountBefore = StoreTransaction::count();
        $payCountBefore = StoreTransactionPayment::count();

        $res = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $res->assertOk();

        $this->assertEquals($trxCountBefore, StoreTransaction::count());
        $this->assertEquals($payCountBefore, StoreTransactionPayment::count());
    }
}
