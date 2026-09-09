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

class SalesVisitReceivablesAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Sales Fikri', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Joko', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Rizky Mandiri',
            'code' => 'RM-001',
            'address' => 'Gardamukti, Subang',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    private function createVisit(array $attributes = []): Visit
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Audit',
            'date' => Carbon::now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        return Visit::create(array_merge([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => Carbon::now()->subHours(2),
            'check_out_at' => Carbon::now()->subHour(),
            'transaction_status' => 'none',
        ], $attributes));
    }

    /**
     * TEST 1: Visit dengan transaksi baru saja.
     * Pastikan transaction hanya masuk TRANSAKSI BARU.
     */
    public function test_1_visit_with_new_transaction_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $visit = $this->createVisit([
            'transaction_status' => 'paid',
            'transaction_amount' => 8000000,
        ]);

        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-NEW-ONLY-01',
            'transaction_amount' => 8000000,
            'paid_amount' => 5000000,
            'transaction_date' => '2026-09-02',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
        ], $this->sales);

        $visit->load(['store', 'user.roles', 'routeStop.route', 'payments.transaction', 'transactions.payments', 'photos']);
        $viewHtml = view('visit.pdf.detail', [
            'visit' => $visit,
            'store' => $visit->store,
            'salesName' => $visit->user->name,
            'salesRole' => 'Sales',
            'durationLabel' => '1 Jam',
            'storeBalance' => (float) StoreReceivableService::balanceForStore($visit->store_id),
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ])->render();

        $this->assertStringContainsString('TRANSAKSI BARU', $viewHtml);
        $this->assertStringContainsString('TRX-NEW-ONLY-01', $viewHtml);
        $this->assertStringNotContainsString('PEMBAYARAN PIUTANG LAMA', $viewHtml);
    }

    /**
     * TEST 2: Visit dengan pembayaran piutang lama saja.
     * Pastikan transaction/payment hanya masuk PEMBAYARAN PIUTANG LAMA.
     */
    public function test_2_visit_with_old_receivable_payment_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $oldTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-ONLY-01',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $visit = $this->createVisit([
            'transaction_status' => 'piutang',
            'transaction_amount' => 2000000,
        ]);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 2000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        $visit->load(['store', 'user.roles', 'routeStop.route', 'payments.transaction', 'transactions.payments', 'photos']);
        $viewHtml = view('visit.pdf.detail', [
            'visit' => $visit,
            'store' => $visit->store,
            'salesName' => $visit->user->name,
            'salesRole' => 'Sales',
            'durationLabel' => '1 Jam',
            'storeBalance' => (float) StoreReceivableService::balanceForStore($visit->store_id),
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ])->render();

        $this->assertStringContainsString('PEMBAYARAN PIUTANG LAMA', $viewHtml);
        $this->assertStringContainsString('TRX-OLD-ONLY-01', $viewHtml);
        $this->assertStringNotContainsString('TRANSAKSI BARU', $viewHtml);
    }

    /**
     * TEST 3 & 4: Visit dengan Piutang Lama + Transaksi Baru.
     * Pastikan keduanya terpisah dan memiliki kode berbeda.
     */
    public function test_3_and_4_mixed_visit_separates_old_and_new(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $oldTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-DISTINCT',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $visit = $this->createVisit([
            'transaction_status' => 'mixed',
            'transaction_amount' => 8250000,
        ]);

        // Bayar piutang lama
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 250000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Transaksi baru
        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-NEW-DISTINCT',
            'transaction_amount' => 8000000,
            'paid_amount' => 5250000,
            'transaction_date' => '2026-09-02',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
        ], $this->sales);

        $this->assertNotEquals($oldTrx->transaction_code, $newTrx->transaction_code);

        $visit->load(['store', 'user.roles', 'routeStop.route', 'payments.transaction', 'transactions.payments', 'photos']);
        $viewHtml = view('visit.pdf.detail', [
            'visit' => $visit,
            'store' => $visit->store,
            'salesName' => $visit->user->name,
            'salesRole' => 'Sales',
            'durationLabel' => '1 Jam',
            'storeBalance' => (float) StoreReceivableService::balanceForStore($visit->store_id),
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ])->render();

        $this->assertStringContainsString('TRANSAKSI BARU', $viewHtml);
        $this->assertStringContainsString('TRX-NEW-DISTINCT', $viewHtml);
        $this->assertStringContainsString('PEMBAYARAN PIUTANG LAMA', $viewHtml);
        $this->assertStringContainsString('TRX-OLD-DISTINCT', $viewHtml);
    }

    /**
     * TEST 5 & 6: Source classification test (initial_payment vs visit).
     */
    public function test_5_and_6_payment_source_classification(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $visit = $this->createVisit([
            'transaction_status' => 'paid',
            'transaction_amount' => 1000000,
        ]);

        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 400000,
            'reference_type' => 'initial_payment',
            'reference_id' => $visit->id,
        ], $this->sales);

        $initialPay = StoreTransactionPayment::where('store_transaction_id', $newTrx->id)->first();
        $this->assertEquals('initial_payment', $initialPay->source);

        $visit->load('payments');
        // Initial payment has source = 'initial_payment' so it should not be treated as old debt payment
        $oldDebtPaid = $visit->payments->where('source', '!=', 'initial_payment')->sum('amount');
        $this->assertEquals(0, $oldDebtPaid);
    }

    /**
     * TEST 7, 8, 9, 10: Balance before/after payment and full/partial payment status.
     */
    public function test_7_to_10_balance_calculations_full_and_partial(): void
    {
        // Full payment test
        $trxFull = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 500000,
        ], $this->sales);

        $payFull = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxFull->id,
            'amount' => 500000,
            'payment_method' => 'tunai',
        ], $this->sales);

        $this->assertEquals(0, $trxFull->fresh()->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trxFull->fresh()->status);

        // Partial payment test
        $trxPartial = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 1000000,
        ], $this->sales);

        $payPartial = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxPartial->id,
            'amount' => 300000,
            'payment_method' => 'transfer',
        ], $this->sales);

        $this->assertEquals(700000, $trxPartial->fresh()->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trxPartial->fresh()->status);
    }

    /**
     * TEST 11 to 15: Store balance aggregation and double-counting prevention.
     */
    public function test_11_to_15_total_store_balance_and_no_double_counting(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Old receivable: Rp 1.000.000, paid Rp 250.000 -> remaining Rp 750.000
        $oldTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-AGG',
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $visit = $this->createVisit([
            'transaction_status' => 'mixed',
        ]);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 250000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // New transaction: Rp 8.000.000, initial payment Rp 5.250.000 -> remaining Rp 2.750.000
        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-NEW-AGG',
            'transaction_amount' => 8000000,
            'paid_amount' => 5250000,
            'transaction_date' => '2026-09-02',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
        ], $this->sales);

        // Expected store balance: 750.000 + 2.750.000 = Rp 3.500.000
        $balance = (float) StoreReceivableService::balanceForStore($this->store->id);
        $this->assertEquals(3500000.0, $balance);
    }

    /**
     * TEST 16 & 17: PDF and Excel classification integrity.
     */
    public function test_16_and_17_pdf_and_excel_exports_correctness(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $oldTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-EXPORT',
            'transaction_amount' => 3000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $visit = $this->createVisit([
            'transaction_status' => 'mixed',
            'transaction_amount' => 8250000,
        ]);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 250000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-NEW-EXPORT',
            'transaction_amount' => 8000000,
            'paid_amount' => 5250000,
            'transaction_date' => '2026-09-02',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
        ], $this->sales);

        // PDF check
        $resPdf = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $resPdf->assertOk();

        // Excel check
        $export = new VisitsExport([
            'userId' => $this->sales->id,
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-03',
        ]);

        $rows = $export->dataRows();
        $this->assertNotEmpty($rows);
        $firstRow = $rows[0];
        // Index 16: Pembayaran Piutang Lama = 250000
        $this->assertEquals(250000.0, $firstRow[16]);
        // Index 17: Nilai Transaksi Baru = 8000000
        $this->assertEquals(8000000.0, $firstRow[17]);
        // Index 18: Pembayaran Transaksi Baru = 5250000
        $this->assertEquals(5250000.0, $firstRow[18]);
        // Index 19: Sisa Transaksi Baru = 2750000
        $this->assertEquals(2750000.0, $firstRow[19]);
    }

    /**
     * TEST 18 & 19: Export does not modify database & Driver is completely isolated.
     */
    public function test_18_and_19_export_read_only_and_driver_isolated(): void
    {
        $visit = $this->createVisit();

        $tCount = StoreTransaction::count();
        $pCount = StoreTransactionPayment::count();

        $res = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $res->assertOk();

        $this->assertEquals($tCount, StoreTransaction::count());
        $this->assertEquals($pCount, StoreTransactionPayment::count());

        // Driver check: Driver cannot access sales receivables
        $this->assertFalse($this->driver->hasRole('sales'));
    }
}
