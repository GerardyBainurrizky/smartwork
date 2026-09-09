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

class SalesStoreTotalReceivableAggregationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $storeA;
    private Store $storeB;
    private Route $route1;
    private RouteStop $stop1;
    private RouteStop $stop2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin Test']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Test']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales1 = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Citra']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Delivery']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko A',
            'code' => 'TKA-01',
            'address' => 'Jl. Pahlawan No. 1',
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko B',
            'code' => 'TKB-02',
            'address' => 'Jl. Sudirman No. 2',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);

        $this->route1 = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Sales Budi',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stop1 = RouteStop::create([
            'route_id' => $this->route1->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $this->stop2 = RouteStop::create([
            'route_id' => $this->route1->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST 1: Toko mempunyai satu transaksi Rp250.000 -> Total piutang: Rp250.000
     */
    public function test_1_toko_mempunyai_satu_transaksi(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        $this->assertSame('250000.00', StoreReceivableService::balanceForStore($this->storeA->id));
        $this->assertEquals(250000.0, $this->storeA->fresh()->total_receivable);
    }

    /**
     * TEST 2: Toko mempunyai dua transaksi: Rp250.000 + Rp5.000.000 = Rp5.250.000
     */
    public function test_2_toko_mempunyai_dua_transaksi(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $this->assertSame('5250000.00', StoreReceivableService::balanceForStore($this->storeA->id));
        $this->assertEquals(5250000.0, $this->storeA->fresh()->total_receivable);
    }

    /**
     * TEST 3: Toko mempunyai tiga transaksi: Rp250.000 + Rp5.000.000 + Rp750.000 = Rp6.000.000
     */
    public function test_3_toko_mempunyai_tiga_transaksi(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 750000,
        ], $this->sales1);

        $this->assertSame('6000000.00', StoreReceivableService::balanceForStore($this->storeA->id));
        $this->assertEquals(6000000.0, $this->storeA->fresh()->total_receivable);
    }

    /**
     * TEST 4: Satu transaksi LUNAS (Rp250.000 lunas) + TRX-2 Rp5.000.000 -> Total piutang Rp5.000.000
     */
    public function test_4_satu_transaksi_lunas_tidak_dihitung(): void
    {
        // TRX 1 Lunas
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
            'paid_amount' => 250000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // TRX 2 Belum Lunas
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $this->assertSame('5000000.00', StoreReceivableService::balanceForStore($this->storeA->id));
    }

    /**
     * TEST 5: Satu transaksi SEBAGIAN (TRX 1: 1.000.000 bayar 400.000 = sisa 600.000)
     */
    public function test_5_satu_transaksi_sebagian_hanya_remaining_yang_dihitung(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 400000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $this->assertSame('600000.00', StoreReceivableService::balanceForStore($this->storeA->id));
    }

    /**
     * TEST 6: Pembayaran mengurangi total toko
     */
    public function test_6_pembayaran_mengurangi_total_toko(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $this->assertSame('5250000.00', StoreReceivableService::balanceForStore($this->storeA->id));

        // Bayar TRX-1 sebesar 100.000 -> sisa toko jadi 5.150.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 100000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $this->assertSame('5150000.00', StoreReceivableService::balanceForStore($this->storeA->id));
    }

    /**
     * TEST 7 & 8: Transaksi baru menambah total dan pembayaran transaksi baru mengurangi
     */
    public function test_7_dan_8_transaksi_baru_menambah_dan_pembayaran_mengurangi(): void
    {
        // Saldo lama 250.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        // Transaksi baru 5.000.000 dengan DP 2.000.000 -> sisa baru 3.000.000 -> total 3.250.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
            'paid_amount' => 2000000,
            'payment_method' => 'transfer',
        ], $this->sales1);

        $this->assertSame('3250000.00', StoreReceivableService::balanceForStore($this->storeA->id));
    }

    /**
     * TEST 9: Multiple transactions + multiple payments total tetap akurat
     */
    public function test_9_multiple_transactions_dan_multiple_payments(): void
    {
        // TRX 1: 250.000 bayar 50.000 = 200.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
            'paid_amount' => 50000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // TRX 2: 5.000.000 bayar 1.000.000 = 4.000.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
            'paid_amount' => 1000000,
            'payment_method' => 'transfer',
        ], $this->sales1);

        // Total = 4.200.000
        $this->assertSame('4200000.00', StoreReceivableService::balanceForStore($this->storeA->id));
    }

    /**
     * TEST 10: /route menampilkan total piutang toko (misal Rp5.250.000)
     */
    public function test_10_route_index_menampilkan_total_piutang_toko(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('route.index'));
        $res->assertOk();
        $res->assertSee('Piutang: Rp 5.250.000');
    }

    /**
     * TEST 11: /visit menampilkan total piutang toko
     */
    public function test_11_visit_index_menampilkan_total_piutang_toko(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('visit.index'));
        $res->assertOk();
        $res->assertSee('Piutang: Rp 5.250.000');
    }

    /**
     * TEST 12: /route/{routeId} menampilkan total piutang toko
     */
    public function test_12_route_show_menampilkan_total_piutang_toko(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('route.show', $this->route1->id));
        $res->assertOk();
        $res->assertSee('Piutang: Rp 5.250.000');
    }

    /**
     * TEST 13: Check Out tetap menampilkan piutang PER TRANSAKSI
     */
    public function test_13_checkout_tetap_menampilkan_piutang_per_transaksi(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
            'description' => 'Faktur 1',
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
            'description' => 'Faktur 2',
        ], $this->sales1);

        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $this->stop1->id,
            'route_id' => $this->route1->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.175392,
            'check_in_lng' => 106.827153,
            'check_in_address' => 'Jakarta Barat',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'checkin.jpg',
        ]);

        $res = $this->actingAs($this->sales1)->get(route('visit.check-out-form', $visit->id));
        $res->assertOk();
        $res->assertSee($trx1->transaction_code);
        $res->assertSee($trx2->transaction_code);
        $res->assertSee('Total Piutang: Rp 5.250.000');
    }

    /**
     * TEST 14: Sales tidak dapat melihat/mengakses toko Sales lain
     */
    public function test_14_sales_tidak_dapat_melihat_toko_sales_lain(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeB->id, // milik sales 2
            'transaction_amount' => 5000000,
        ], $this->sales2);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        StoreReceivableService::assertSalesOwnsStore($this->sales1, $this->storeB);
    }

    /**
     * TEST 15: Driver tidak mendapat akses / data piutang
     */
    public function test_15_driver_tidak_mendapat_akses_piutang(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        $driverRoute = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        RouteStop::create([
            'route_id' => $driverRoute->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->driver)->get(route('route.index'));
        $res->assertOk();
        $res->assertDontSee('Piutang:');
    }

    /**
     * TEST 16 & 17: Admin & Super Admin membaca saldo yang sama
     */
    public function test_16_dan_17_admin_dan_super_admin_membaca_saldo_yang_sama(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $resAdmin = $this->actingAs($this->admin)->getJson(route('admin.receivables.show', $this->storeA->id));
        $resAdmin->assertOk();
        $this->assertEquals(5250000.0, (float) $resAdmin->json('balance'));

        $resSuper = $this->actingAs($this->superAdmin)->getJson(route('admin.receivables.show', $this->storeA->id));
        $resSuper->assertOk();
        $this->assertEquals(5250000.0, (float) $resSuper->json('balance'));
    }

    /**
     * TEST 18: Tidak ada duplicate ledger dan perhitungan konsisten
     */
    public function test_18_tidak_ada_duplicate_ledger(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        $this->assertEquals(2, StoreTransaction::where('store_id', $this->storeA->id)->count());
        $this->assertSame('5250000.00', StoreReceivableService::balanceForStore($this->storeA->id));
    }
}
