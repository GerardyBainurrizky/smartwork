<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReceivablesRefinementsAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $driver;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin Test']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Test']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales = User::factory()->create(['name' => 'Sales Test']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Test']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Cahaya Abadi',
            'code' => 'TCA-01',
            'address' => 'Jl. Kebon Jeruk No. 5',
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    /**
     * TEST 1: Pagination pada /admin/receivables (15 toko -> 2 halaman)
     */
    public function test_1_pagination_admin_receivables(): void
    {
        for ($i = 2; $i <= 15; $i++) {
            Store::create([
                'name' => "Toko Cabang {$i}",
                'code' => "TC-{$i}",
                'address' => "Jl. Raya {$i}",
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'status' => 'active',
                'sales_penanggung_jawab_id' => $this->sales->id,
            ]);
        }

        // Total 15 toko -> Halaman 1 (10 toko), Halaman 2 (5 toko)
        $resPage1 = $this->actingAs($this->admin)->get(route('admin.receivables.index', ['page' => 1]));
        $resPage1->assertOk();
        $resPage1->assertSee('Toko Cahaya Abadi');

        $resPage2 = $this->actingAs($this->admin)->get(route('admin.receivables.index', ['page' => 2]));
        $resPage2->assertOk();
    }

    /**
     * TEST 2: Filter & Search dipertahankan saat pagination
     */
    public function test_2_filter_dan_search_dipertahankan_pada_pagination(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Store::create([
                'name' => "Toko Cahaya {$i}",
                'code' => "TCH-{$i}",
                'address' => "Jl. Cahaya {$i}",
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'status' => 'active',
                'sales_penanggung_jawab_id' => $this->sales->id,
            ]);
        }

        $res = $this->actingAs($this->admin)->get(route('admin.receivables.index', [
            'search' => 'Cahaya',
            'sales_id' => $this->sales->id,
            'page' => 2,
        ]));

        $res->assertOk();
        $res->assertSee('search=Cahaya');
    }

    /**
     * TEST 3: Penyesuaian Tambah Piutang (Add Adjustment)
     */
    public function test_3_penyesuaian_tambah_piutang(): void
    {
        // Saldo awal 250.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 250000,
        ], $this->admin);

        // Tambah piutang 500.000 via adjust
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.adjust', $this->store->id), [
            'adjustment_type' => 'add',
            'amount' => 500000,
            'notes' => 'Penambahan invoice manual hasil audit',
            'transaction_date' => now()->toDateString(),
        ]);

        $res->assertCreated();
        $this->assertSame('750000.00', StoreReceivableService::balanceForStore($this->store->id));
        $this->assertEquals(750000.0, $this->store->fresh()->total_receivable);
    }

    /**
     * TEST 4: Penyesuaian Kurangi Piutang (Subtract Adjustment)
     */
    public function test_4_penyesuaian_kurangi_piutang(): void
    {
        // Saldo awal 750.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 750000,
        ], $this->admin);

        // Kurangi piutang 250.000 via adjust
        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.adjust', $this->store->id), [
            'adjustment_type' => 'subtract',
            'amount' => 250000,
            'notes' => 'Koreksi retur barang khusus',
            'transaction_date' => now()->toDateString(),
        ]);

        $res->assertCreated();
        $this->assertSame('500000.00', StoreReceivableService::balanceForStore($this->store->id));
    }

    /**
     * TEST 5: Pengurangan melebihi saldo piutang ditolak (Mencegah Saldo Negatif)
     */
    public function test_5_pengurangan_melebihi_saldo_ditolak(): void
    {
        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.adjust', $this->store->id), [
            'adjustment_type' => 'subtract',
            'amount' => 150000, // Saldo hanya 100.000
            'notes' => 'Koreksi kebesaran',
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment([
            'message' => 'Nominal pengurangan melebihi saldo piutang toko.',
        ]);

        $this->assertSame('100000.00', StoreReceivableService::balanceForStore($this->store->id));
    }

    /**
     * TEST 6: Pagination Transaksi pada Detail Toko (/admin/receivables/{storeId})
     */
    public function test_6_pagination_transaksi_detail_toko(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            StoreReceivableService::createTransaction([
                'store_id' => $this->store->id,
                'transaction_amount' => 10000 * $i,
                'description' => "Tagihan Faktur #{$i}",
            ], $this->admin);
        }

        $resShow = $this->actingAs($this->admin)->get(route('admin.receivables.show', $this->store->id));
        $resShow->assertOk();
        $resShow->assertSee('Total 15 Transaksi');

        $resPage2 = $this->actingAs($this->admin)->get(route('admin.receivables.show', ['storeId' => $this->store->id, 'page' => 2]));
        $resPage2->assertOk();
    }

    /**
     * TEST 7: Detail Transaksi (/admin/receivables/transactions/{id})
     */
    public function test_7_detail_transaksi_dan_histori(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 300000,
            'payment_method' => 'tunai',
            'description' => 'Faktur Pembelian Stok',
        ], $this->admin);

        $res = $this->actingAs($this->admin)->get(route('admin.receivables.transactions.show', $trx->id));
        $res->assertOk();
        $res->assertSee($trx->transaction_code);
        $res->assertSee('Rp 1.000.000');
        $res->assertSee('Rp 300.000');
        $res->assertSee('Rp 700.000');
        $res->assertSee('SEBAGIAN');
    }
}
