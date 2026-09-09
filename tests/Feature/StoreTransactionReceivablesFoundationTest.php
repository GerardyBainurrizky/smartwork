<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreTransactionReceivablesFoundationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin User']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales1 = User::factory()->create(['name' => 'Sales Satu']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Dua']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Joko']);
        $this->driver->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Sumber Barokah',
            'code' => 'SBR-01',
            'address' => 'Jl. Pahlawan No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Maju Bersama',
            'code' => 'MJB-02',
            'address' => 'Jl. Sudirman No. 2',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);
    }

    /**
     * A. Transaksi baru tanpa pembayaran:
     * Rp10.000, Bayar Rp0 => Sisa Rp10.000, Status BELUM_LUNAS
     */
    public function test_transaksi_baru_tanpa_pembayaran_status_belum_lunas(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 10000,
            'description' => 'Faktur #001',
        ], $this->sales1);

        $this->assertNotNull($trx->transaction_code);
        $this->assertStringStartsWith('TRX-', $trx->transaction_code);
        $this->assertEquals(StoreTransaction::STATUS_BELUM_LUNAS, $trx->status);
        $this->assertEquals(0.0, $trx->total_paid);
        $this->assertEquals(10000.0, $trx->remaining_amount);
        $this->assertSame('10000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * B. Transaksi baru lunas:
     * Rp10.000, Bayar Rp10.000 => Sisa Rp0, Status LUNAS
     */
    public function test_transaksi_baru_lunas_status_lunas(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 10000,
            'paid_amount' => 10000,
            'payment_method' => 'tunai',
            'description' => 'Faktur #002 Cash',
        ], $this->sales1);

        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx->status);
        $this->assertEquals(10000.0, $trx->total_paid);
        $this->assertEquals(0.0, $trx->remaining_amount);
        $this->assertSame('0.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * C. Transaksi dibayar sebagian:
     * Rp10.000, Bayar Rp2.000 => Sisa Rp8.000, Status SEBAGIAN
     */
    public function test_transaksi_dibayar_sebagian_status_sebagian(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 10000,
            'paid_amount' => 2000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx->status);
        $this->assertEquals(2000.0, $trx->total_paid);
        $this->assertEquals(8000.0, $trx->remaining_amount);
        $this->assertSame('8000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * D. Pembayaran kedua:
     * Rp10.000, Bayar pertama Rp2.000, Bayar kedua Rp3.000 => Sisa Rp5.000
     */
    public function test_pembayaran_kedua_mengurangi_sisa_transaksi(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 10000,
            'paid_amount' => 2000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // Pembayaran kedua Rp3.000
        $pay2 = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 3000,
            'payment_method' => 'transfer',
            'notes' => 'Cicilan 2',
        ], $this->sales1);

        $trx->refresh();
        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx->status);
        $this->assertEquals(5000.0, $trx->total_paid);
        $this->assertEquals(5000.0, $trx->remaining_amount);
        $this->assertCount(2, $trx->payments);
        $this->assertSame('5000.00', StoreReceivableService::balanceForStore($this->store1->id));

        // Pembayaran ketiga Rp5.000 (Pelunasan)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 5000,
            'payment_method' => 'qris',
            'notes' => 'Pelunasan',
        ], $this->sales1);

        $trx->refresh();
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx->status);
        $this->assertEquals(10000.0, $trx->total_paid);
        $this->assertEquals(0.0, $trx->remaining_amount);
        $this->assertCount(3, $trx->payments);
        $this->assertSame('0.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * E. Pembayaran tidak boleh melebihi sisa
     */
    public function test_pembayaran_melebihi_sisa_transaksi_ditolak(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 10000,
            'paid_amount' => 2000,
        ], $this->sales1);

        $this->expectException(ValidationException::class);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 9000, // Sisa hanya 8000
            'payment_method' => 'tunai',
        ], $this->sales1);
    }

    /**
     * F. Pembayaran negatif ditolak
     */
    public function test_pembayaran_negatif_atau_nol_ditolak(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 10000,
        ], $this->sales1);

        $this->expectException(ValidationException::class);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => -5000,
            'payment_method' => 'tunai',
        ], $this->sales1);
    }

    /**
     * G. Kode transaksi unik & berurutan
     */
    public function test_kode_transaksi_unik_dan_berurutan(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 50000,
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 75000,
        ], $this->sales1);

        $this->assertNotEquals($trx1->transaction_code, $trx2->transaction_code);
        $this->assertStringStartsWith('TRX-', $trx1->transaction_code);
        $this->assertStringStartsWith('TRX-', $trx2->transaction_code);
    }

    /**
     * H. Total piutang toko dihitung dari seluruh remaining transaction
     */
    public function test_total_piutang_toko_dihitung_dari_agregasi_seluruh_remaining_transactions(): void
    {
        // TRX-1: Rp100.000, bayar Rp20.000 -> sisa Rp80.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
            'paid_amount' => 20000,
        ], $this->sales1);

        // TRX-2: Rp50.000, bayar Rp50.000 -> sisa Rp0
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 50000,
            'paid_amount' => 50000,
        ], $this->sales1);

        // TRX-3: Rp200.000, bayar Rp0 -> sisa Rp200.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 200000,
            'paid_amount' => 0,
        ], $this->sales1);

        // Total Piutang Toko 1: 80.000 + 0 + 200.000 = 280.000
        $this->assertSame('280000.00', StoreReceivableService::balanceForStore($this->store1->id));
        $this->assertEquals(280000.0, $this->store1->fresh()->total_receivable);
    }

    /**
     * I. Pembayaran dialokasikan ke transaksi tertentu
     */
    public function test_pembayaran_terisolasi_dan_teralokasi_ke_transaksi_tertentu(): void
    {
        $trxA = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 80000,
        ], $this->sales1);

        $trxB = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 200000,
        ], $this->sales1);

        // Bayar Trx A Rp50.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxA->id,
            'amount' => 50000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        // Bayar Trx B Rp100.000
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxB->id,
            'amount' => 100000,
            'payment_method' => 'transfer',
        ], $this->sales1);

        $trxA->refresh();
        $trxB->refresh();

        $this->assertEquals(30000.0, $trxA->remaining_amount);
        $this->assertEquals(100000.0, $trxB->remaining_amount);
        $this->assertSame('130000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    /**
     * J. Sales hanya dapat mengakses toko/piutang sesuai scope
     */
    public function test_sales_hanya_dapat_mengakses_toko_miliknya(): void
    {
        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store2->id,
            'transaction_amount' => 100000,
        ], $this->sales2);

        // Sales 1 mencoba bayar transaksi Toko 2 (milik Sales 2) -> Forbidden
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx2->id,
            'amount' => 50000,
            'payment_method' => 'tunai',
        ], $this->sales1);
    }

    /**
     * K & L. Admin dan Super Admin dapat mengelola transaksi seluruh toko
     */
    public function test_admin_dan_super_admin_dapat_mengakses_seluruh_toko(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        // Admin bayar transaksi
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 30000,
            'payment_method' => 'tunai',
        ], $this->admin);

        // Super Admin bayar sisa transaksi
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 70000,
            'payment_method' => 'transfer',
        ], $this->superAdmin);

        $trx->refresh();
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx->status);
        $this->assertEquals(0.0, $trx->remaining_amount);
    }

    /**
     * M. Driver tidak memiliki akses terhadap piutang/transaksi
     */
    public function test_driver_tidak_memiliki_akses_piutang(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 100000,
        ], $this->admin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        StoreReceivableService::assertCanAccessStore($this->driver, $this->store1);
    }
}
