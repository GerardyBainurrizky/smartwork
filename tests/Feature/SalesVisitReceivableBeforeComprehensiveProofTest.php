<?php

namespace Tests\Feature;

use App\Exports\VisitsExport;
use App\Models\Route as RouteModel;
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
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesVisitReceivableBeforeComprehensiveProofTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $sales;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::where('username', 'admin')->first() ?? User::factory()->create(['name' => 'Admin Test']);
        if (! $this->admin->hasRole('admin')) {
            $this->admin->assignRole('admin');
        }

        $this->sales = User::create([
            'username' => 'sales_fikri',
            'name' => 'Fikri Haekal',
            'email' => 'fikri@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->sales->syncRoles(['sales']);

        $this->store = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-017',
            'name' => 'Lengkong berkah',
            'owner' => 'Haji Lengkong',
            'address' => 'Jl. Lengkong Raya No. 17',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'status' => 'active',
        ]);
    }

    private function makeVisit(Store $store, string $checkIn, string $checkOut, string $txStatus = 'none', float $txAmt = 0.0): Visit
    {
        $route = RouteModel::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute ' . Carbon::parse($checkIn)->format('Y-m-d'),
            'date' => Carbon::parse($checkIn)->toDateString(),
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        return Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => Carbon::parse($checkIn),
            'check_out_at' => Carbon::parse($checkOut),
            'transaction_status' => $txStatus,
            'transaction_amount' => $txAmt,
        ]);
    }

    /**
     * PROOF 1: KASUS NYATA LENGKONG BERKAH (02 SEP 2026 22:43)
     * Total Outstanding 4 Transaksi Sebelum Kunjungan:
     * - TRX-0002: Nilai 6jt, Bayar Sblm Visit 1.7jt -> Outstanding 4.3jt
     * - TRX-0003: Nilai 9jt, Bayar Sblm Visit 3.9jt -> Outstanding 5.1jt
     * - TRX-0006: Nilai 8.65jt, Bayar Sblm Visit 100k -> Outstanding 8.55jt
     * - TRX-0003b: Nilai 6.666.666, Bayar Sblm Visit 6.666.666 -> Outstanding 0
     * TOTAL SALDO SEBELUM = 4.3jt + 5.1jt + 8.55jt + 0 = 17.950.000
     */
    public function test_lengkong_berkah_comprehensive_proof(): void
    {
        // 1. TRX-1: 6.000.000 created 2026-09-01 15:42
        $tx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0002',
            'transaction_amount' => 6000000,
            'paid_amount' => 1000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // 2. TRX-2: 9.000.000 created 2026-09-01 15:58
        $tx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0003',
            'transaction_amount' => 9000000,
            'paid_amount' => 3500000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // 3. TRX-3 (opening balance): 8.650.000 created 2026-09-01 16:07
        $tx3 = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0006',
            'transaction_date' => '2026-09-01',
            'transaction_amount' => 8650000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'reference_type' => 'opening_balance',
            'created_by' => $this->admin->id,
            'created_at' => Carbon::parse('2026-09-01 16:07:23'),
        ]);

        // Prior payments during visit on 01 Sep 17:01
        $priorVisit = $this->makeVisit($this->store, '2026-09-01 17:00:49', '2026-09-01 17:01:51', 'piutang', 1200000);
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $tx1->id, 'amount' => 700000, 'payment_date' => '2026-09-01', 'source' => 'visit', 'source_id' => $priorVisit->id], $this->sales);
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $tx2->id, 'amount' => 400000, 'payment_date' => '2026-09-01', 'source' => 'visit', 'source_id' => $priorVisit->id], $this->sales);
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $tx3->id, 'amount' => 100000, 'payment_date' => '2026-09-01', 'source' => 'visit', 'source_id' => $priorVisit->id], $this->sales);

        // 4. TRX-4: 6.666.666 (lunas) created 2026-09-02 13:42
        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260902-0003',
            'transaction_amount' => 6666666,
            'paid_amount' => 6666666,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        // Target Visit: 02 Sep 2026 22:43:54
        $targetVisit = $this->makeVisit($this->store, '2026-09-02 22:43:54', '2026-09-02 22:45:58', 'mixed', 18300000);

        // Payments during target visit:
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $tx1->id, 'amount' => 4300000, 'payment_date' => '2026-09-02', 'source' => 'visit', 'source_id' => $targetVisit->id], $this->sales);
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $tx2->id, 'amount' => 5100000, 'payment_date' => '2026-09-02', 'source' => 'visit', 'source_id' => $targetVisit->id], $this->sales);
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $tx3->id, 'amount' => 8000000, 'payment_date' => '2026-09-02', 'source' => 'visit', 'source_id' => $targetVisit->id], $this->sales);

        // New transaction during target visit: 900.000, paid 200.000 -> sisa 700.000
        $newTx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260902-0005',
            'transaction_amount' => 900000,
            'paid_amount' => 200000,
            'reference_type' => 'visit',
            'reference_id' => $targetVisit->id,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        $targetVisit->refresh();

        // 1. Service verification
        $summary = StoreReceivableService::getVisitReceivableSummary($targetVisit);
        $this->assertEquals(17950000.0, $summary['balance_before']);
        $this->assertEquals(17400000.0, $summary['old_debt_paid']);
        $this->assertEquals(700000.0, $summary['new_tx_remaining']);
        $this->assertEquals(1250000.0, $summary['balance_after']);

        // Formula verification: Saldo Awal - Bayar Lama + Piutang Baru = Saldo Akhir
        $this->assertEquals(
            $summary['balance_before'] - $summary['old_debt_paid'] + $summary['new_tx_remaining'],
            $summary['balance_after']
        );

        // 2. Excel export parity
        $export = new VisitsExport([
            'fromDate' => '2026-09-02',
            'toDate' => '2026-09-02',
            'userId' => $this->sales->id,
        ]);
        $rows = $export->sheets()[0]->dataRows();
        $this->assertCount(1, $rows);
        $targetRow = $rows[0];

        $this->assertEquals(17950000.0, $targetRow[15]); // Saldo Piutang Lama Sebelum Kunjungan
        $this->assertEquals(17400000.0, $targetRow[16]); // Pembayaran Piutang Lama pada Kunjungan
        $this->assertEquals(900000.0, $targetRow[17]);   // Nilai Transaksi Baru
        $this->assertEquals(200000.0, $targetRow[18]);   // Pembayaran Awal Transaksi Baru
        $this->assertEquals(700000.0, $targetRow[19]);   // Piutang Baru dari Transaksi Kunjungan
        $this->assertEquals(1250000.0, $targetRow[20]);  // Saldo Piutang Toko Setelah Kunjungan
    }

    /**
     * PROOF 2: VALIDASI 6 SKENARIO LENGKAP
     */
    public function test_six_scenarios_comprehensive_validation(): void
    {
        // ==========================================
        // SKENARIO 1: Ada piutang lama dan tidak ada pembayaran
        // ==========================================
        $s1Store = Store::create(['name' => 'Toko Skenario 1', 'code' => 'TKO-S1', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        StoreReceivableService::createTransaction(['store_id' => $s1Store->id, 'transaction_amount' => 5000000, 'transaction_date' => '2026-09-01'], $this->sales);
        $v1 = $this->makeVisit($s1Store, '2026-09-02 08:00:00', '2026-09-02 08:30:00', 'none');
        $sum1 = StoreReceivableService::getVisitReceivableSummary($v1);

        $this->assertEquals(5000000.0, $sum1['balance_before']);
        $this->assertEquals(0.0, $sum1['old_debt_paid']);
        $this->assertEquals(0.0, $sum1['new_tx_remaining']);
        $this->assertEquals(5000000.0, $sum1['balance_after']);
        $this->assertEquals($sum1['balance_before'] - $sum1['old_debt_paid'] + $sum1['new_tx_remaining'], $sum1['balance_after']);

        // ==========================================
        // SKENARIO 2: Ada piutang lama dan ada pembayaran
        // ==========================================
        $s2Store = Store::create(['name' => 'Toko Skenario 2', 'code' => 'TKO-S2', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $s2Tx = StoreReceivableService::createTransaction(['store_id' => $s2Store->id, 'transaction_amount' => 8000000, 'transaction_date' => '2026-09-01'], $this->sales);
        $v2 = $this->makeVisit($s2Store, '2026-09-02 09:00:00', '2026-09-02 09:30:00', 'piutang', 3000000);
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $s2Tx->id, 'amount' => 3000000, 'payment_date' => '2026-09-02', 'source' => 'visit', 'source_id' => $v2->id], $this->sales);
        $v2->refresh();
        $sum2 = StoreReceivableService::getVisitReceivableSummary($v2);

        $this->assertEquals(8000000.0, $sum2['balance_before']);
        $this->assertEquals(3000000.0, $sum2['old_debt_paid']);
        $this->assertEquals(0.0, $sum2['new_tx_remaining']);
        $this->assertEquals(5000000.0, $sum2['balance_after']);
        $this->assertEquals($sum2['balance_before'] - $sum2['old_debt_paid'] + $sum2['new_tx_remaining'], $sum2['balance_after']);

        // ==========================================
        // SKENARIO 3: Ada transaksi baru yang langsung lunas
        // ==========================================
        $s3Store = Store::create(['name' => 'Toko Skenario 3', 'code' => 'TKO-S3', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        // Old debt: 2.000.000
        StoreReceivableService::createTransaction(['store_id' => $s3Store->id, 'transaction_amount' => 2000000, 'transaction_date' => '2026-09-01'], $this->sales);
        $v3 = $this->makeVisit($s3Store, '2026-09-02 10:00:00', '2026-09-02 10:30:00', 'paid', 4000000);
        // Transaksi baru 4jt bayar awal 4jt (lunas)
        StoreReceivableService::createTransaction(['store_id' => $s3Store->id, 'transaction_amount' => 4000000, 'paid_amount' => 4000000, 'reference_type' => 'visit', 'reference_id' => $v3->id, 'transaction_date' => '2026-09-02'], $this->sales);
        $v3->refresh();
        $sum3 = StoreReceivableService::getVisitReceivableSummary($v3);

        $this->assertEquals(2000000.0, $sum3['balance_before']);
        $this->assertEquals(0.0, $sum3['old_debt_paid']);
        $this->assertEquals(0.0, $sum3['new_tx_remaining']); // Lunas -> piutang baru = 0
        $this->assertEquals(2000000.0, $sum3['balance_after']);
        $this->assertEquals($sum3['balance_before'] - $sum3['old_debt_paid'] + $sum3['new_tx_remaining'], $sum3['balance_after']);

        // ==========================================
        // SKENARIO 4: Ada transaksi baru yang sebagian menjadi piutang
        // ==========================================
        $s4Store = Store::create(['name' => 'Toko Skenario 4', 'code' => 'TKO-S4', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $v4 = $this->makeVisit($s4Store, '2026-09-02 11:00:00', '2026-09-02 11:30:00', 'paid', 6000000);
        // Transaksi baru 6jt bayar awal 2jt -> sisa 4jt menjadi piutang baru
        StoreReceivableService::createTransaction(['store_id' => $s4Store->id, 'transaction_amount' => 6000000, 'paid_amount' => 2000000, 'reference_type' => 'visit', 'reference_id' => $v4->id, 'transaction_date' => '2026-09-02'], $this->sales);
        $v4->refresh();
        $sum4 = StoreReceivableService::getVisitReceivableSummary($v4);

        $this->assertEquals(0.0, $sum4['balance_before']);
        $this->assertEquals(0.0, $sum4['old_debt_paid']);
        $this->assertEquals(4000000.0, $sum4['new_tx_remaining']);
        $this->assertEquals(4000000.0, $sum4['balance_after']);
        $this->assertEquals($sum4['balance_before'] - $sum4['old_debt_paid'] + $sum4['new_tx_remaining'], $sum4['balance_after']);

        // ==========================================
        // SKENARIO 5: Ada pembayaran piutang lama + transaksi baru dalam kunjungan yang sama (Kombinasi)
        // ==========================================
        $s5Store = Store::create(['name' => 'Toko Skenario 5', 'code' => 'TKO-S5', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $s5TxOld = StoreReceivableService::createTransaction(['store_id' => $s5Store->id, 'transaction_amount' => 10000000, 'transaction_date' => '2026-09-01'], $this->sales);
        $v5 = $this->makeVisit($s5Store, '2026-09-02 13:00:00', '2026-09-02 13:30:00', 'mixed', 7000000);
        // Bayar piutang lama: 4.000.000
        StoreReceivableService::recordTransactionPayment(['store_transaction_id' => $s5TxOld->id, 'amount' => 4000000, 'payment_date' => '2026-09-02', 'source' => 'visit', 'source_id' => $v5->id], $this->sales);
        // Transaksi baru 3.000.000, bayar awal 1.000.000 -> sisa 2.000.000
        StoreReceivableService::createTransaction(['store_id' => $s5Store->id, 'transaction_amount' => 3000000, 'paid_amount' => 1000000, 'reference_type' => 'visit', 'reference_id' => $v5->id, 'transaction_date' => '2026-09-02'], $this->sales);
        $v5->refresh();
        $sum5 = StoreReceivableService::getVisitReceivableSummary($v5);

        $this->assertEquals(10000000.0, $sum5['balance_before']);
        $this->assertEquals(4000000.0, $sum5['old_debt_paid']);
        $this->assertEquals(2000000.0, $sum5['new_tx_remaining']);
        $this->assertEquals(8000000.0, $sum5['balance_after']); // 10jt - 4jt + 2jt = 8jt
        $this->assertEquals($sum5['balance_before'] - $sum5['old_debt_paid'] + $sum5['new_tx_remaining'], $sum5['balance_after']);

        // ==========================================
        // SKENARIO 6: Tidak ada transaksi maupun pembayaran (toko tanpa piutang sama sekali)
        // ==========================================
        $s6Store = Store::create(['name' => 'Toko Skenario 6', 'code' => 'TKO-S6', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $v6 = $this->makeVisit($s6Store, '2026-09-02 14:00:00', '2026-09-02 14:30:00', 'none');
        $sum6 = StoreReceivableService::getVisitReceivableSummary($v6);

        $this->assertEquals(0.0, $sum6['balance_before']);
        $this->assertEquals(0.0, $sum6['old_debt_paid']);
        $this->assertEquals(0.0, $sum6['new_tx_remaining']);
        $this->assertEquals(0.0, $sum6['balance_after']);
        $this->assertEquals($sum6['balance_before'] - $sum6['old_debt_paid'] + $sum6['new_tx_remaining'], $sum6['balance_after']);
    }
}
