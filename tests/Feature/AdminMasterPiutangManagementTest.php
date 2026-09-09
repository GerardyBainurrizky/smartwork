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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMasterPiutangManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $store1;
    private Store $store2;
    private Store $store3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin Utama']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Pusat']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales1 = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Citra']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Agus']);
        $this->driver->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Maju Jaya',
            'code' => 'ST-001',
            'address' => 'Jl. Kebon Jeruk No. 1',
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Sumber Rezeki',
            'code' => 'ST-002',
            'address' => 'Jl. Pajajaran No. 2',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);

        $this->store3 = Store::create([
            'name' => 'Toko Tanpa Sales',
            'code' => 'ST-003',
            'address' => 'Jl. Diponegoro No. 3',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'status' => 'active',
            'sales_penanggung_jawab_id' => null,
        ]);
    }

    /**
     * TEST 1: Admin dapat membuka /admin/receivables
     */
    public function test_1_admin_dapat_membuka_admin_receivables(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index'));
        $response->assertOk();
        $response->assertSee('Master Piutang Toko');
    }

    /**
     * TEST 2: Super Admin dapat membuka /admin/receivables
     */
    public function test_2_super_admin_dapat_membuka_admin_receivables(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.receivables.index'));
        $response->assertOk();
        $response->assertSee('Master Piutang Toko');
    }

    /**
     * TEST 3: Daftar toko menampilkan total piutang yang benar
     */
    public function test_3_daftar_toko_menampilkan_total_piutang_yang_benar(): void
    {
        // Store 1 piutang Rp280.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 280000,
        ], $this->admin);

        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index'));
        $response->assertOk();
        $response->assertSee('Toko Maju Jaya');
        $response->assertSee('Rp 280.000');
        $response->assertSee('Belum Lunas');
    }

    /**
     * TEST 4: Toko tanpa piutang menampilkan "Tidak Ada Piutang"
     */
    public function test_4_toko_tanpa_piutang_menampilkan_tidak_ada_piutang(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index'));
        $response->assertOk();
        $response->assertSee('Toko Sumber Rezeki');
        $response->assertSee('Tidak Ada Piutang');
    }

    /**
     * TEST 5: Toko memiliki beberapa transaksi: Total piutang = SUM remaining
     */
    public function test_5_total_piutang_adalah_sum_remaining_transactions(): void
    {
        // TRX-1: Rp100.000 bayar Rp20.000 -> sisa Rp80.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // TRX-2: Rp50.000 bayar Rp50.000 -> sisa Rp0
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 50000,
            'paid_amount' => 50000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // TRX-3: Rp200.000 bayar Rp0 -> sisa Rp200.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 200000,
        ], $this->sales1);

        // Total = 80.000 + 0 + 200.000 = 280.000
        $this->assertSame('280000.00', StoreReceivableService::balanceForStore($this->store1->id));

        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index'));
        $response->assertOk();
        $response->assertSee('Rp 280.000');
        $response->assertSee('2 transaksi'); // 2 transaksi belum lunas (TRX-1 dan TRX-3)
    }

    /**
     * TEST 6: Detail toko menampilkan seluruh transaksi
     */
    public function test_6_detail_toko_menampilkan_seluruh_transaksi(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 50000,
            'paid_amount' => 50000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $response = $this->actingAs($this->admin)->get(route('admin.receivables.show', $this->store1->id));
        $response->assertOk();
        $response->assertSee($trx1->transaction_code);
        $response->assertSee($trx2->transaction_code);
        $response->assertSee('SEBAGIAN');
        $response->assertSee('LUNAS');
    }

    /**
     * TEST 7 & 8: Detail transaksi menampilkan histori pembayaran aktual (Payment Rp20.000 + Rp50.000 = Rp70.000, sisa Rp30.000)
     */
    public function test_7_dan_8_detail_transaksi_menampilkan_histori_pembayaran(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // Payment kedua Rp50.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 50000,
            'payment_method' => 'transfer',
            'notes' => 'Pembayaran kedua',
        ], $this->sales1);

        $trx->refresh();
        $this->assertEquals(70000.0, $trx->total_paid);
        $this->assertEquals(30000.0, $trx->remaining_amount);

        $response = $this->actingAs($this->admin)->get(route('admin.receivables.transactions.show', $trx->id));
        $response->assertOk();
        $response->assertSee($trx->transaction_code);
        $response->assertSee('Rp 100.000');
        $response->assertSee('Rp 70.000');
        $response->assertSee('Rp 30.000');
        $response->assertSee('Pembayaran #1');
        $response->assertSee('Pembayaran #2');
        $response->assertSee('Rp 20.000');
        $response->assertSee('Rp 50.000');
    }

    /**
     * TEST 9: Kode transaksi tampil dan dapat dicari
     */
    public function test_9_kode_transaksi_dapat_dicari(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store2->id,
            'transaction_amount' => 150000,
        ], $this->sales2);

        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index', ['search' => $trx1->transaction_code]));
        $response->assertOk();
        $response->assertSee('Toko Maju Jaya');
        $response->assertDontSee('Toko Sumber Rezeki');
    }

    /**
     * TEST 10: Filter berdasarkan Sales bekerja
     */
    public function test_10_filter_berdasarkan_sales(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index', ['sales_id' => $this->sales1->id]));
        $response->assertOk();
        $response->assertSee('Toko Maju Jaya');
        $response->assertDontSee('Toko Sumber Rezeki');
    }

    /**
     * TEST 11: Search nama toko bekerja
     */
    public function test_11_search_nama_toko(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index', ['search' => 'Sumber Rezeki']));
        $response->assertOk();
        $response->assertSee('Toko Sumber Rezeki');
        $response->assertDontSee('Toko Maju Jaya');
    }

    /**
     * TEST 12: Search kode toko bekerja
     */
    public function test_12_search_kode_toko(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index', ['search' => 'ST-002']));
        $response->assertOk();
        $response->assertSee('Toko Sumber Rezeki');
        $response->assertDontSee('Toko Maju Jaya');
    }

    /**
     * TEST 13: Search kode transaksi bekerja
     */
    public function test_13_search_kode_transaksi(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store2->id,
            'transaction_amount' => 50000,
        ], $this->sales2);

        $response = $this->actingAs($this->admin)->get(route('admin.receivables.index', ['search' => $trx->transaction_code]));
        $response->assertOk();
        $response->assertSee('Toko Sumber Rezeki');
        $response->assertDontSee('Toko Maju Jaya');
    }

    /**
     * TEST 14: Sales tidak dapat mengakses Admin Receivables
     */
    public function test_14_sales_tidak_dapat_mengakses_admin_receivables(): void
    {
        $this->actingAs($this->sales1)->get(route('admin.receivables.index'))->assertRedirect();
        $this->actingAs($this->sales1)->get(route('admin.receivables.show', $this->store1->id))->assertRedirect();
    }

    /**
     * TEST 15: Driver tidak dapat mengakses Admin Receivables
     */
    public function test_15_driver_tidak_dapat_mengakses_admin_receivables(): void
    {
        $this->actingAs($this->driver)->get(route('admin.receivables.index'))->assertRedirect();
        $this->actingAs($this->driver)->get(route('admin.receivables.show', $this->store1->id))->assertRedirect();
    }

    /**
     * TEST 16: Driver tidak menerima data piutang melalui endpoint
     */
    public function test_16_driver_tidak_menerima_data_piutang(): void
    {
        $this->actingAs($this->driver)->getJson(route('admin.receivables.index'))->assertRedirect();
    }

    /**
     * TEST 17: Admin dan Super Admin melihat data yang konsisten
     */
    public function test_17_admin_dan_super_admin_melihat_data_yang_konsisten(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 120000,
        ], $this->admin);

        $resAdmin = $this->actingAs($this->admin)->get(route('admin.receivables.index'));
        $resAdmin->assertSee('Rp 120.000');

        $resSuperAdmin = $this->actingAs($this->superAdmin)->get(route('admin.receivables.index'));
        $resSuperAdmin->assertSee('Rp 120.000');
    }

    /**
     * TEST 18: Tidak ada duplicate ledger
     */
    public function test_18_tidak_ada_duplicate_ledger(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        $this->assertEquals(1, StoreTransaction::where('store_id', $this->store1->id)->count());
        $this->assertEquals($trx->remaining_amount, (float) StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * TEST 19: Tidak ada perubahan saldo yang hanya terjadi pada UI
     */
    public function test_19_tidak_ada_perubahan_saldo_hanya_pada_ui(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 50000,
        ], $this->admin);

        $dbBal = StoreReceivableService::balanceForStore($this->store1->id);
        $this->assertSame('50000.00', $dbBal);

        $response = $this->actingAs($this->admin)->get(route('admin.receivables.show', $this->store1->id));
        $response->assertSee('Rp 50.000');
    }

    /**
     * TEST 20: Regression test Sales checkout tetap PASS
     */
    public function test_20_regression_sales_checkout(): void
    {
        $route = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Harian',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jakarta',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'checkin.jpg',
        ]);

        $dummy = 'data:image/jpeg;base64,' . base64_encode('fake');
        $res = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Lancar',
            'transaction_status' => 'paid',
            'new_tx_total' => 100000,
            'new_tx_paid' => 30000,
            'new_payment_method' => 'tunai',
            'selfie' => $dummy,
            'final_store_photo' => $dummy,
        ]);
        $res->assertOk();

        $this->assertSame('70000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * TEST 21: Regression test Driver tetap PASS
     */
    public function test_21_regression_driver_workflow(): void
    {
        $driverRoute = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stop = RouteStop::create([
            'route_id' => $driverRoute->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->driver->id,
            'route_stop_id' => $stop->id,
            'route_id' => $driverRoute->id,
            'store_id' => $this->store1->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jakarta',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'checkin.jpg',
        ]);

        $dummy = 'data:image/jpeg;base64,' . base64_encode('fake');
        $res = $this->actingAs($this->driver)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Pengiriman barang beres',
            'photos' => [$dummy],
            'transaction_status' => 'none',
        ]);
        $res->assertOk();
    }

    /**
     * TEST 22: Regression Master Toko
     */
    public function test_22_regression_master_toko(): void
    {
        $this->assertEquals(3, Store::count());
        $this->assertEquals($this->sales1->id, $this->store1->sales_penanggung_jawab_id);
    }

    /**
     * TEST 23: Regression Attendance
     */
    public function test_23_regression_attendance(): void
    {
        $res = $this->actingAs($this->sales1)->get(route('attendance.index'));
        $res->assertOk();
    }
}
