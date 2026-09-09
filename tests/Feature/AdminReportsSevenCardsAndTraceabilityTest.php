<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportsSevenCardsAndTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $salesA;
    private User $salesB;
    private User $driver;
    private User $staff;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeDriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->staff = User::where('username', 'staff')->first();

        $this->salesA = User::create([
            'username' => 'sales_a',
            'name' => 'Sales Ahmad',
            'email' => 'sales_a@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->salesA->syncRoles(['sales']);

        $this->salesB = User::create([
            'username' => 'sales_b',
            'name' => 'Sales Budi',
            'email' => 'sales_b@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->salesB->syncRoles(['sales']);

        $this->driver = User::create([
            'username' => 'driver_deni',
            'name' => 'Driver Deni',
            'email' => 'driver_deni@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);

        // Stores
        $this->storeA = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-A01',
            'name' => 'Toko Amanah A',
            'owner' => 'Owner A',
            'address' => 'Jl. A No. 1',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'status' => 'active',
        ]);

        $this->storeB = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-B01',
            'name' => 'Toko Berkah B',
            'owner' => 'Owner B',
            'address' => 'Jl. B No. 2',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'status' => 'active',
        ]);

        $this->storeC = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-C01',
            'name' => 'Toko Cantik C',
            'owner' => 'Owner C',
            'address' => 'Jl. C No. 3',
            'sales_penanggung_jawab_id' => $this->salesB->id,
            'status' => 'active',
        ]);

        $this->storeDriver = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-D01',
            'name' => 'Toko Tujuan Driver D',
            'owner' => 'Owner D',
            'address' => 'Jl. D No. 4',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    public function test_seven_cards_and_sales_driver_traceability(): void
    {
        $this->actingAs($this->admin);

        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();

        // 1. Store A (Sales A):
        // TRX-A1 (Bulan ini): Rp1.000.000, Bayar Awal: Rp400.000, Sisa Rp600.000 (Open)
        $trxA1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 400000,
            'transaction_date' => $today,
            'description' => 'Invoice Baru Toko A',
        ], $this->salesA);

        // TRX-A2 (Bulan ini): Rp500.000, Bayar Awal: Rp500.000, Sisa Rp0 (Paid/Lunas)
        $trxA2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 500000,
            'paid_amount' => 500000,
            'transaction_date' => $today,
            'description' => 'Invoice Lunas Toko A',
        ], $this->salesA);

        // 2. Store B (Sales A):
        // TRX-B1 (Bulan ini): Rp2.000.000, Bayar Awal: Rp0, Sisa Rp2.000.000 (Open)
        $trxB1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id,
            'transaction_amount' => 2000000,
            'paid_amount' => 0,
            'transaction_date' => $today,
            'description' => 'Invoice Tempo Toko B',
        ], $this->salesA);

        // Cicilan / Pelunasan Piutang Lama Toko B (Bulan ini): Rp500.000
        $payB1 = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxB1->id,
            'amount' => 500000,
            'payment_date' => $today,
            'payment_method' => 'tunai',
            'notes' => 'Cicilan Faktur Toko B',
        ], $this->admin);

        // 3. Store C (Sales B):
        // TRX-C1 (Bulan ini): Rp1.500.000, Bayar Awal: Rp500.000, Sisa Rp1.000.000 (Open)
        $trxC1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_amount' => 1500000,
            'paid_amount' => 500000,
            'transaction_date' => $today,
            'description' => 'Invoice Toko C',
        ], $this->salesB);

        // 4. Driver Flow:
        $routeDriver = RouteModel::create([
            'user_id' => $this->driver->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Pengiriman Driver Deni',
            'date' => $today,
            'status' => 'completed',
        ]);

        $stopDriver = RouteStop::create([
            'route_id' => $routeDriver->id,
            'store_id' => $this->storeDriver->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visitDriver = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'route_stop_id' => $stopDriver->id,
            'route_id' => $routeDriver->id,
            'store_id' => $this->storeDriver->id,
            'status' => 'completed',
            'check_in_at' => now()->startOfDay()->addHours(10),
            'check_out_at' => now()->startOfDay()->addHours(11),
            'delivered_goods' => 'Paket Barang D 5 Box',
            'cash_received' => 800000,
            'transaction_amount' => 800000,
            'payment_method' => 'cash',
            'transaction_status' => 'paid',
        ]);

        // ==========================================
        // VALIDASI 7 CARD KONDISI PIUTANG & TRANSAKSI
        // ==========================================
        // 1. Piutang Toko (Current): Toko A (Rp600.000) + Toko B (Rp1.500.000) + Toko C (Rp1.000.000) = Rp3.100.000
        // 2. Toko Berpiutang (Current): Toko A, Toko B, Toko C = 3 Toko
        // 3. Transaksi Terbuka (Current): trxA1, trxB1, trxC1 = 3 Transaksi
        // 4. Transaksi Lunas (Current): trxA2 = 1 Transaksi
        // 5. Total Transaksi Baru (Bulan Ini): Rp1.000.000 + Rp500.000 + Rp2.000.000 + Rp1.500.000 = Rp5.000.000
        // 6. Uang Masuk TRX Baru (Bulan Ini): Rp400.000 (trxA1) + Rp500.000 (trxA2) + Rp500.000 (trxC1) = Rp1.400.000
        // 7. Bayar Piutang Lama (Bulan Ini): Rp500.000 (payB1)

        $responseReports = $this->get(route('admin.reports.index'));
        $responseReports->assertOk();

        $responseReports->assertViewHas('totalReceivable', 3100000.0);
        $responseReports->assertViewHas('storesWithReceivable', 3);
        $responseReports->assertViewHas('totalOpenTransactions', 3);
        $responseReports->assertViewHas('totalPaidTransactions', 1);
        $responseReports->assertViewHas('newTransactionsThisMonth', 5000000.0);
        $responseReports->assertViewHas('newTransactionsCashInThisMonth', 1400000.0);
        $responseReports->assertViewHas('debtPaymentsThisMonth', 500000.0);

        // Synchronized with /admin/receivables
        $responseReceivables = $this->get(route('admin.receivables.index'));
        $responseReceivables->assertOk();

        $responseReceivables->assertViewHas('totalReceivable', 3100000.0);
        $responseReceivables->assertViewHas('storesWithReceivable', 3);
        $responseReceivables->assertViewHas('totalOpenTransactions', 3);
        $responseReceivables->assertViewHas('totalPaidTransactions', 1);
        $responseReceivables->assertViewHas('newTransactionsThisMonth', 5000000.0);
        $responseReceivables->assertViewHas('newTransactionsCashInThisMonth', 1400000.0);
        $responseReceivables->assertViewHas('todayPayments', 500000.0);

        // Sales Attribution & Top Indebted Sales
        // Sales A: Toko A (Rp600.000) + Toko B (Rp1.500.000) = Rp2.100.000
        // Sales B: Toko C (Rp1.000.000) = Rp1.000.000
        // Top Indebted Sales must be Sales A
        $topIndebted = $responseReports->viewData('topIndebtedSales');
        $this->assertNotNull($topIndebted);
        $this->assertEquals($this->salesA->id, $topIndebted['user']->id);
        $this->assertEquals(2100000.0, $topIndebted['total_receivable']);
        $this->assertEquals(2, $topIndebted['indebted_stores_count']);
        $this->assertEquals(2, $topIndebted['open_transactions_count']);

        // Driver Stats
        $driverStats = $responseReports->viewData('driverStats');
        $this->assertEquals(1, $driverStats['total_deliveries_month']);
        $this->assertEquals(1, $driverStats['completed_deliveries_month']);
        $this->assertEquals(1, $driverStats['deliveries_with_trx_month']);
        $this->assertEquals(800000.0, $driverStats['total_amount_month']);
        $this->assertEquals(800000.0, $driverStats['today_cash_in']);

        // UI & Wording validations
        $responseReports->assertDontSee('COD / Cash lunas driver');
        $responseReports->assertSee('Pembayaran transaksi driver');
        $responseReports->assertSee('Lihat Semua Sales');
        $responseReports->assertSee('Detail Kunjungan');
        $responseReports->assertSee('Lihat Semua Driver');
        $responseReports->assertSee('Lihat Rute Driver');

        // Traceability drill-downs to summary routes
        $respNewTrx = $this->get(route('admin.receivables.summary.new-transactions'));
        $respNewTrx->assertOk();
        $respNewTrx->assertViewHas('totalTransactionAmount', 5000000.0);

        $respNewPayments = $this->get(route('admin.receivables.summary.new-transaction-payments'));
        $respNewPayments->assertOk();
        $respNewPayments->assertViewHas('totalAmount', 1400000.0);

        $respDebtPayments = $this->get(route('admin.receivables.summary.payments'));
        $respDebtPayments->assertOk();
        $respDebtPayments->assertViewHas('totalAmount', 500000.0);

        $respOpenTrx = $this->get(route('admin.receivables.summary.open-transactions'));
        $respOpenTrx->assertOk();
        $respOpenTrx->assertViewHas('totalCount', 3);
        $respOpenTrx->assertViewHas('totalRemaining', 3100000.0);

        $respPaidTrx = $this->get(route('admin.receivables.summary.paid-transactions'));
        $respPaidTrx->assertOk();
        $respPaidTrx->assertViewHas('totalCount', 1);
        $respPaidTrx->assertViewHas('totalTrxAmount', 500000.0);

        // Driver Visits table verification
        $respDriverVisits = $this->get(route('admin.reports.driver-visits'));
        $respDriverVisits->assertOk();
        $respDriverVisits->assertSee('Unduh PDF');
        $respDriverVisits->assertSee('Unduh Excel');
        $respDriverVisits->assertSee('Rp 800.000');
        $respDriverVisits->assertSee('Lunas');

        // Visits table verification
        $respVisits = $this->get(route('admin.reports.visits'));
        $respVisits->assertOk();
        $respVisits->assertSee('Unduh PDF');
        $respVisits->assertSee('Unduh Excel');
    }
}
