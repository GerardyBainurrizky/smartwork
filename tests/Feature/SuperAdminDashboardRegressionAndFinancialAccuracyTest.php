<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminDashboardRegressionAndFinancialAccuracyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'staff']);
    }

    private function makeUser(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'status' => 'active',
        ], $attributes));
        $user->assignRole($role);
        return $user;
    }

    public function test_super_admin_dashboard_financial_definitions_and_no_double_count(): void
    {
        $superAdmin = $this->makeUser('super-admin', ['name' => 'Super Admin Test']);
        $sales1 = $this->makeUser('sales', ['name' => 'Sales Ahmad']);
        $sales2 = $this->makeUser('sales', ['name' => 'Sales Budi']);
        $driver1 = $this->makeUser('driver', ['name' => 'Driver Joko']);
        $staff1 = $this->makeUser('staff', ['name' => 'Staff Santi']);

        $store1 = Store::create([
            'name' => 'Toko Makmur 1',
            'code' => 'TM-001',
            'sales_penanggung_jawab_id' => $sales1->id,
            'status' => 'active',
        ]);
        $store2 = Store::create([
            'name' => 'Toko Makmur 2',
            'code' => 'TM-002',
            'sales_penanggung_jawab_id' => $sales2->id,
            'status' => 'active',
        ]);

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $lastWeek = now()->subDays(3)->toDateString();

        // CASE 1: Transaksi Baru Hari Ini Toko 1 -> Rp 500.000, Bayar Awal Rp 200.000 hari ini
        $txNew = StoreTransaction::create([
            'store_id' => $store1->id,
            'transaction_code' => 'TRX-NEW-01',
            'transaction_date' => $today,
            'transaction_amount' => 500000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'created_by' => $superAdmin->id,
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $txNew->id,
            'amount' => 200000,
            'payment_method' => 'tunai',
            'payment_date' => $today,
            'source' => 'initial_payment',
            'notes' => 'Pembayaran awal',
            'recorded_by' => $superAdmin->id,
        ]);

        // CASE 2: Transaksi Lama Toko 1 (Kemarin) -> Rp 1.000.000, Bayar cicilan parsial Rp 300.000 hari ini (BELUM LUNAS)
        $txOldPartial = StoreTransaction::create([
            'store_id' => $store1->id,
            'transaction_code' => 'TRX-OLD-01',
            'transaction_date' => $yesterday,
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'created_by' => $superAdmin->id,
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $txOldPartial->id,
            'amount' => 300000,
            'payment_method' => 'transfer',
            'payment_date' => $today,
            'source' => 'manual_payment',
            'notes' => 'Cicilan belum lunas',
            'recorded_by' => $superAdmin->id,
        ]);

        // CASE 3: Transaksi Lama Toko 2 (3 Hari Lalu) -> Rp 700.000, Bayar Lunas Rp 700.000 hari ini (MENJADI LUNAS / Rp0)
        $txOldSettled = StoreTransaction::create([
            'store_id' => $store2->id,
            'transaction_code' => 'TRX-OLD-02',
            'transaction_date' => $lastWeek,
            'transaction_amount' => 700000,
            'status' => StoreTransaction::STATUS_LUNAS,
            'created_by' => $superAdmin->id,
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $txOldSettled->id,
            'amount' => 700000,
            'payment_method' => 'transfer',
            'payment_date' => $today,
            'source' => 'manual_payment',
            'notes' => 'Pelunasan total',
            'recorded_by' => $superAdmin->id,
        ]);

        // Verifikasi Nilai Finansial yang Diharapkan Hari Ini:
        // Pemasukan Transaksi Baru = Rp 200.000
        // Pembayaran Piutang Lama = Rp 300.000 + Rp 700.000 = Rp 1.000.000
        // Uang Pelunasan Piutang (HANYA txOldSettled yang Lunas) = Rp 700.000
        // Total Uang Masuk Sales = Rp 200.000 + Rp 1.000.000 = Rp 1.200.000 (BUKAN Rp 1.900.000 -> NO DOUBLE COUNT!)
        // Saldo Piutang Berjalan:
        // Toko 1: (500k - 200k) + (1000k - 300k) = 300k + 700k = 1.000.000
        // Toko 2: 700k - 700k = 0
        // Total Saldo Piutang = Rp 1.000.000

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewIs('dashboard.super-admin');

        // Check view data
        $viewData = $response->viewData('financialTrend');
        $this->assertNotNull($viewData);
        $this->assertArrayHasKey('points', $viewData);
        $this->assertArrayHasKey('summary', $viewData);

        // Check Today's values in Controller compacted data
        $this->assertEquals(1200000.0, $response->viewData('totalSalesCashInToday'));
        $this->assertEquals(200000.0, $response->viewData('totalNewTxCashInToday'));
        $this->assertEquals(1000000.0, $response->viewData('totalOldDebtPaymentToday'));
        $this->assertEquals(700000.0, $response->viewData('totalDebtSettlementToday'));
        $this->assertEquals(1000000.0, $response->viewData('totalSalesReceivable'));
        $this->assertEquals(1000000.0, $response->viewData('totalCurrentReceivable'));

        // Assert that HTML renders the exact financial terms
        $response->assertSee('Performa Keuangan Sales');
        $response->assertSee('Pemasukan Transaksi Baru');
        $response->assertSee('Pembayaran Piutang Lama');
        $response->assertSee('Saldo Piutang Berjalan');
        $response->assertSee('Pelunasan Lunas (Rp0)');
        $response->assertSee(number_format(1200000, 0, ',', '.'));
        $response->assertSee(number_format(200000, 0, ',', '.'));
        $response->assertSee(number_format(1000000, 0, ',', '.'));
        $response->assertSee(number_format(700000, 0, ',', '.'));
    }

    public function test_all_8_charts_containers_and_data_exist(): void
    {
        $superAdmin = $this->makeUser('super-admin', ['name' => 'Super Boss']);
        $sales1 = $this->makeUser('sales', ['name' => 'Sales Joni']);
        $driver1 = $this->makeUser('driver', ['name' => 'Driver Bambang']);
        $staff1 = $this->makeUser('staff', ['name' => 'Staff Ratna']);

        $store = Store::create([
            'name' => 'Toko Subur',
            'code' => 'TS-001',
            'sales_penanggung_jawab_id' => $sales1->id,
            'status' => 'active',
        ]);

        $today = now()->toDateString();

        // 1. Presensi Hadir, Izin, Sakit
        Attendance::create([
            'user_id' => $sales1->id,
            'date' => $today,
            'clock_in' => now()->setTime(8, 0),
            'status' => Attendance::STATUS_PRESENT,
        ]);
        Attendance::create([
            'user_id' => $driver1->id,
            'date' => $today,
            'status' => Attendance::STATUS_IZIN,
            'absence_note' => 'Izin keluarga',
        ]);
        Attendance::create([
            'user_id' => $staff1->id,
            'date' => $today,
            'status' => Attendance::STATUS_SAKIT,
            'absence_note' => 'Sakit flu',
        ]);

        // 2. Sales Route & Visit
        $salesRoute = Route::create([
            'user_id' => $sales1->id,
            'name' => 'Rute Sales Hari Ini',
            'date' => $today,
            'status' => 'active',
        ]);
        $salesStop = RouteStop::create([
            'route_id' => $salesRoute->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $sales1->id,
            'store_id' => $store->id,
            'route_stop_id' => $salesStop->id,
            'check_in_at' => now(),
            'check_out_at' => now()->addHour(),
            'status' => 'completed',
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
        ]);

        // 3. Driver Route & Visit
        $driverRoute = Route::create([
            'user_id' => $driver1->id,
            'name' => 'Rute Driver Hari Ini',
            'date' => $today,
            'status' => 'active',
        ]);
        $driverStop = RouteStop::create([
            'route_id' => $driverRoute->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $driver1->id,
            'store_id' => $store->id,
            'route_stop_id' => $driverStop->id,
            'check_in_at' => now(),
            'check_out_at' => now()->addHour(),
            'status' => 'completed',
            'transaction_status' => 'paid',
            'transaction_amount' => 350000,
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));
        $response->assertOk();

        // 1. Chart Performa Keuangan Sales
        $response->assertSee('id="chart-financial"', false);
        // 2. Chart Presensi 7 Hari Terakhir
        $response->assertSee('id="chart-attendance"', false);
        // 3. Chart Kunjungan 7 Hari Terakhir (Sales)
        $response->assertSee('id="chart-visits"', false);
        // 4. Chart Pengiriman 7 Hari Terakhir (Driver)
        $response->assertSee('id="chart-deliveries"', false);
        // 5. Chart Top Sales Minggu Ini
        $response->assertSee('id="chart-top-sales"', false);
        // 6. Chart Top Driver Minggu Ini
        $response->assertSee('id="chart-top-drivers"', false);
        // 7. Chart Rencana Kunjungan 7 Hari Terakhir (Sales)
        $response->assertSee('id="chart-routes"', false);
        // 8. Chart Rencana Pengiriman 7 Hari Terakhir (Driver)
        $response->assertSee('id="chart-delivery-routes"', false);

        // Verify CTA links
        $response->assertSee('Kelola Pengguna', false);
        $response->assertSee('Lihat Detail', false);
        $response->assertSee('Lihat Piutang', false);
        $response->assertSee('Kelola Toko', false);
        $response->assertSee('Lihat Rencana', false);

        // Verify Attendance Trend Data
        $attendanceTrend = $response->viewData('attendanceTrend');
        $this->assertCount(7, $attendanceTrend);
        $todayAtt = end($attendanceTrend);
        $this->assertEquals(1, $todayAtt['hadir']);
        $this->assertEquals(1, $todayAtt['izin']);
        $this->assertEquals(1, $todayAtt['sakit']);
        $this->assertEquals(3, $todayAtt['total']);

        // Verify Top Sales and Top Driver
        $topSales = $response->viewData('topSalesWeek');
        $this->assertNotEmpty($topSales);
        $this->assertEquals('Sales Joni', $topSales[0]['name']);
        $this->assertEquals(1, $topSales[0]['total']);

        $topDrivers = $response->viewData('topDriversWeek');
        $this->assertNotEmpty($topDrivers);
        $this->assertEquals('Driver Bambang', $topDrivers[0]['name']);
        $this->assertEquals(1, $topDrivers[0]['total']);
    }
}
