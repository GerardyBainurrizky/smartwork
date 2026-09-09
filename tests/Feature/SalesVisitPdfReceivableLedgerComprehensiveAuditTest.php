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

class SalesVisitPdfReceivableLedgerComprehensiveAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin User', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin User', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales = User::factory()->create(['name' => 'Sales Budi', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store = Store::create([
            'name' => 'Toko Barokah Jaya',
            'code' => 'BJ-001',
            'address' => 'Jl. Merdeka No. 123',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    private function createVisitScenario(string $date = '2026-09-02'): Visit
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan ' . $date,
            'date' => $date,
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        return Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => Carbon::parse("{$date} 09:00:00"),
            'check_out_at' => Carbon::parse("{$date} 10:00:00"),
            'transaction_status' => 'none',
        ]);
    }

    /**
     * TEST 1: Toko tanpa piutang. Expected = Rp0.
     */
    public function test_1_store_without_receivable_balance_before_is_zero(): void
    {
        $visit = $this->createVisitScenario('2026-09-02');
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        $this->assertEquals(0.0, $summary['balance_before']);
        $this->assertEquals(0.0, $summary['old_debt_paid']);
        $this->assertEquals(0.0, $summary['new_tx_remaining']);
        $this->assertEquals(0.0, $summary['balance_after']);
    }

    /**
     * TEST 2: Toko dengan satu transaksi outstanding. Expected = outstanding transaksi tersebut.
     */
    public function test_2_store_with_single_outstanding_transaction(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0001',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $visit = $this->createVisitScenario('2026-09-02');
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        $this->assertEquals(5000000.0, $summary['balance_before']);
        $this->assertEquals(0.0, $summary['old_debt_paid']);
        $this->assertEquals(5000000.0, $summary['balance_after']);
    }

    /**
     * TEST 3: Toko dengan beberapa transaksi outstanding. Expected = total outstanding yang sesuai.
     */
    public function test_3_store_with_multiple_outstanding_transactions(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0001',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0002',
            'transaction_amount' => 3200000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $visit = $this->createVisitScenario('2026-09-02');
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        $this->assertEquals(8200000.0, $summary['balance_before']);
    }

    /**
     * TEST 4 & 5: Toko dengan transaksi LUNAS dan beberapa payment.
     * Pastikan transaksi lunas tidak dihitung sebagai saldo outstanding.
     */
    public function test_4_and_5_store_with_paid_and_settled_transactions(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0001',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // Pelunasan penuh sebelum visit
        StoreTransactionPayment::create([
            'store_transaction_id' => $trx1->id,
            'amount' => 5000000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'transfer',
            'source' => 'admin_payment',
        ]);
        $trx1->recalculateStatus();

        $visit = $this->createVisitScenario('2026-09-02');
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        $this->assertEquals(0.0, $summary['balance_before']);
        $this->assertEquals(0.0, $summary['balance_after']);
    }

    /**
     * TEST 6: Toko dengan transaksi SEBAGIAN.
     * Pastikan hanya remaining amount yang dihitung sebagai outstanding.
     */
    public function test_6_store_with_partial_payment_before_visit(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0001',
            'transaction_amount' => 6000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        StoreTransactionPayment::create([
            'store_transaction_id' => $trx1->id,
            'amount' => 2500000,
            'payment_date' => '2026-09-01',
            'payment_method' => 'transfer',
            'source' => 'admin_payment',
        ]);
        $trx1->recalculateStatus();

        $visit = $this->createVisitScenario('2026-09-02');
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        // 6.000.000 - 2.500.000 = 3.500.000
        $this->assertEquals(3500000.0, $summary['balance_before']);
        $this->assertEquals(3500000.0, $summary['balance_after']);
    }

    /**
     * TEST 7: Toko dengan pembayaran pada kunjungan.
     * Pastikan saldo sebelum dan pembayaran tidak tertukar.
     */
    public function test_7_store_with_old_debt_payment_during_visit(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0001',
            'transaction_amount' => 10000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $visit = $this->createVisitScenario('2026-09-02');
        $visit->update(['transaction_status' => 'piutang', 'transaction_amount' => 3000000]);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 3000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        $visit->refresh();
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        $this->assertEquals(10000000.0, $summary['balance_before']);
        $this->assertEquals(3000000.0, $summary['old_debt_paid']);
        $this->assertEquals(0.0, $summary['new_tx_remaining']);
        $this->assertEquals(7000000.0, $summary['balance_after']);
    }

    /**
     * TEST 8: Toko dengan transaksi baru pada kunjungan.
     * Pastikan transaksi baru tidak salah masuk ke saldo sebelum kunjungan.
     */
    public function test_8_store_with_new_transaction_during_visit(): void
    {
        $visit = $this->createVisitScenario('2026-09-02');
        $visit->update(['transaction_status' => 'paid', 'transaction_amount' => 4000000]);

        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0002',
            'transaction_amount' => 4000000,
            'paid_amount' => 1000000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        $visit->refresh();
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        $this->assertEquals(0.0, $summary['balance_before']);
        $this->assertEquals(0.0, $summary['old_debt_paid']);
        $this->assertEquals(4000000.0, $summary['new_tx_total']);
        $this->assertEquals(1000000.0, $summary['new_tx_initial_paid']);
        $this->assertEquals(3000000.0, $summary['new_tx_remaining']);
        $this->assertEquals(3000000.0, $summary['balance_after']);
    }

    /**
     * TEST 9: Multi transactions & payments and no double counting.
     * Verifikasi kasus lengkap: Old Debt + Partial Old Payment + New Trx + Initial Payment.
     */
    public function test_9_multi_transaction_and_multi_payment_full_isolation(): void
    {
        // 1. Transaksi lama 1: 6.000.000, bayar awal 1.000.000 -> sisa 5.000.000
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-1',
            'transaction_amount' => 6000000,
            'paid_amount' => 1000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // 2. Transaksi lama 2: 9.000.000, bayar awal 3.500.000 -> sisa 5.500.000
        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-2',
            'transaction_amount' => 9000000,
            'paid_amount' => 3500000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // Saldo sebelum kunjungan = 5.000.000 + 5.500.000 = Rp 10.500.000
        $visit = $this->createVisitScenario('2026-09-02');
        $visit->update(['transaction_status' => 'mixed', 'transaction_amount' => 8000000]);

        // Bayar piutang lama pada visit: bayar trx1 3.000.000 & bayar trx2 2.000.000 (total bayar piutang = 5.000.000)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 3000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx2->id,
            'amount' => 2000000,
            'payment_date' => '2026-09-02',
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Transaksi baru pada visit: 2.000.000, bayar awal 500.000 -> sisa piutang baru 1.500.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-NEW-1',
            'transaction_amount' => 2000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        $visit->refresh();
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        // Saldo Sebelum = 10.500.000
        $this->assertEquals(10500000.0, $summary['balance_before']);
        // Bayar Piutang Lama = 5.000.000
        $this->assertEquals(5000000.0, $summary['old_debt_paid']);
        // Transaksi Baru Sisa = 1.500.000
        $this->assertEquals(1500000.0, $summary['new_tx_remaining']);
        // Saldo Setelah = 10.500.000 - 5.000.000 + 1.500.000 = 7.000.000
        $this->assertEquals(7000000.0, $summary['balance_after']);

        // PDF View rendering verification
        $pdfHtml = view('reports.pdf.visits', [
            'title' => 'LAPORAN KUNJUNGAN SALES',
            'items' => collect([$visit]),
            'skipped' => collect(),
            'periodLabel' => '02 Sep 2026',
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
            'generatedAt' => '02 Sep 2026, 11:00',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'selectedUserLabel' => $this->sales->name,
            'statusLabel' => 'Semua Status',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('RINGKASAN PIUTANG TOKO', $pdfHtml);
        $this->assertStringContainsString('Saldo Piutang Lama Sebelum Kunjungan', $pdfHtml);
        $this->assertStringContainsString('Rp 10.500.000', $pdfHtml);
        $this->assertStringContainsString('- Rp 5.000.000', $pdfHtml);
        $this->assertStringContainsString('+ Rp 1.500.000', $pdfHtml);
        $this->assertStringContainsString('Saldo Piutang Toko Setelah Kunjungan', $pdfHtml);
        $this->assertStringContainsString('Rp 7.000.000', $pdfHtml);
    }
}
