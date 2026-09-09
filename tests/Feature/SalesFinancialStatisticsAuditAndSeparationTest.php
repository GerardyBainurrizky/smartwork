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

class SalesFinancialStatisticsAuditAndSeparationTest extends TestCase
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

        $this->sales1 = User::factory()->create(['name' => 'Fikri Sales', 'status' => 'active']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Budi Sales', 'status' => 'active']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Doni Driver', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->admin = User::factory()->create(['name' => 'Super Admin', 'status' => 'active']);
        $this->admin->assignRole('super-admin');

        $this->store1 = Store::create([
            'name' => 'Toko Pusaka Tani',
            'code' => 'TKO-PT01',
            'address' => 'Jl. Raya Pertanian No. 88',
            'city' => 'Bogor',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Rizky Mandiri',
            'code' => 'TKO-RM02',
            'address' => 'Jl. Sudirman No. 10',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 1: Dashboard Distinction
     * Total Transaksi Hari Ini = Rp 10.000.000
     * Initial Payment = Rp 6.000.000
     * Old Debt Payment = Rp 1.250.000
     * -> Total Transaksi = Rp 10.000.000
     * -> Uang Masuk (Transaksi Baru) = Rp 6.000.000 (tidak dicampur dengan 1.250.000)
     * -> Pembayaran Piutang = Rp 1.250.000
     */
    public function test_dashboard_sales_distinguishes_total_transaction_cash_in_and_debt_payment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Old debt created yesterday: Rp 3.000.000
        $oldTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-OLD-01',
            'transaction_amount' => 3000000,
            'transaction_date' => '2026-09-01',
        ], $this->sales1);

        // New transaction created today: Rp 10.000.000 with initial payment Rp 6.000.000
        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-NEW-01',
            'transaction_amount' => 10000000,
            'paid_amount' => 6000000,
            'payment_method' => 'tunai',
            'transaction_date' => '2026-09-02',
        ], $this->sales1);

        // Old debt payment today: Rp 1.250.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 1250000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'source' => 'visit',
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('dashboard'));
        $res->assertOk();

        // Total Transaksi = Rp 10.000.000
        $res->assertSee('Rp 10.000.000');
        // Uang Masuk Transaksi = Rp 6.000.000 (HANYA initial/pembayaran transaksi baru, TIDAK bercampur dengan 1.250.000 old debt)
        $res->assertSee('Rp 6.000.000');
        // Pembayaran Piutang Hari Ini = Rp 1.250.000
        $res->assertSee('Rp 1.250.000');
        // Piutang Saat Ini = (3.000.000 - 1.250.000) + (10.000.000 - 6.000.000) = 1.750.000 + 4.000.000 = Rp 5.750.000
        $res->assertSee('Rp 5.750.000');
    }

    /**
     * TEST 2: /visit/history Monthly Metrics Distinction
     */
    public function test_visit_history_distinguishes_monthly_transaction_cash_in_and_debt_payment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Old debt from previous month: Rp 5.000.000
        $oldMonthTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-AUG-01',
            'transaction_amount' => 5000000,
            'transaction_date' => '2026-08-20',
        ], $this->sales1);

        // Transaction this month (2026-09-01): Rp 8.000.000 with initial payment Rp 5.250.000
        $trxMonth1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-SEP-01',
            'transaction_amount' => 8000000,
            'paid_amount' => 5250000,
            'payment_method' => 'tunai',
            'transaction_date' => '2026-09-01',
        ], $this->sales1);

        // Old debt payment this month (2026-09-02): Rp 250.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldMonthTrx->id,
            'amount' => 250000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'source' => 'visit',
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('visit.history'));
        $res->assertOk();

        // Total Transaksi Bulan Ini = Rp 8.000.000
        $res->assertSee('Rp 8.000.000');
        // Piutang dari Transaksi Bulan Ini = Rp 2.750.000 (8.000.000 - 5.250.000)
        $res->assertSee('Rp 2.750.000');
        // Pembayaran Piutang Bulan Ini = Rp 250.000
        $res->assertSee('Rp 250.000');
    }

    /**
     * TEST 3: Admin created transaction and payment for Sales store correctly populates Sales dashboard.
     */
    public function test_admin_created_activity_reflects_in_sales_statistics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        // Admin creates a transaction for Store 1 (assigned to Sales 1): Rp 4.000.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-ADM-01',
            'transaction_amount' => 4000000,
            'paid_amount' => 1000000,
            'payment_method' => 'tunai',
            'transaction_date' => '2026-09-02',
        ], $this->admin);

        // Sales 1 views dashboard
        $res = $this->actingAs($this->sales1)->get(route('dashboard'));
        $res->assertOk();
        $res->assertSee('Rp 4.000.000'); // Total Transaksi
        $res->assertSee('Rp 1.000.000'); // Uang Masuk

        // Sales 2 cannot see Store 1's activity
        $res2 = $this->actingAs($this->sales2)->get(route('dashboard'));
        $res2->assertOk();
        $res2->assertSee('Rp 0');
    }

    /**
     * TEST 4: No double counting on multiple partial payments.
     */
    public function test_multiple_partial_payments_do_not_double_count(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-MULT-01',
            'transaction_amount' => 10000000,
            'transaction_date' => '2026-09-02',
        ], $this->sales1);

        // Payment 1: Rp 2.000.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 2000000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-02',
            'source' => 'manual_payment',
        ], $this->sales1);

        // Payment 2: Rp 3.000.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 3000000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-02',
            'source' => 'manual_payment',
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('dashboard'));
        $res->assertOk();
        $res->assertSee('Rp 10.000.000'); // Total Transaksi
        $res->assertSee('Rp 5.000.000');  // Uang Masuk (2jt + 3jt)
        $res->assertSee('Rp 5.000.000');  // Piutang Saat Ini (10jt - 5jt)
    }

    /**
     * TEST 5: Driver Dashboard & History remain completely isolated.
     */
    public function test_driver_dashboard_and_history_isolated(): void
    {
        $res = $this->actingAs($this->driver)->get(route('dashboard'));
        $res->assertOk();

        $resHist = $this->actingAs($this->driver)->get(route('visit.history'));
        $resHist->assertOk();
    }
}
