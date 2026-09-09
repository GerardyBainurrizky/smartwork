<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTransactionCodeUniquenessAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $sales1;
    private User $sales2;
    private User $admin;
    private User $driver;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales1 = User::factory()->create(['name' => 'Sales Alpha', 'status' => 'active']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Beta', 'status' => 'active']);
        $this->sales2->assignRole('sales');

        $this->admin = User::factory()->create(['name' => 'Admin Boss', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->driver = User::factory()->create(['name' => 'Driver Doni', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Subur',
            'code' => 'TKO-SB01',
            'address' => 'Jl. Subur No. 1',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Makmur',
            'code' => 'TKO-MK02',
            'address' => 'Jl. Makmur No. 2',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 1: Membuat transaksi menghasilkan transaction_code valid berformat TRX-YYYYMMDD-XXXX.
     */
    public function test_1_create_transaction_generates_valid_code_format(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        $this->assertNotNull($trx->transaction_code);
        $this->assertEquals('TRX-20260903-0001', $trx->transaction_code);
    }

    /**
     * TEST 2: Membuat dua transaksi pada tanggal yang sama menghasilkan kode berbeda secara sekuensial.
     */
    public function test_2_two_transactions_same_date_produce_sequential_unique_codes(): void
    {
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 2000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        $this->assertEquals('TRX-20260903-0001', $trx1->transaction_code);
        $this->assertEquals('TRX-20260903-0002', $trx2->transaction_code);
        $this->assertNotEquals($trx1->transaction_code, $trx2->transaction_code);
    }

    /**
     * TEST 3 & 4: Transaksi pada toko berbeda atau sales berbeda tetap menghasilkan kode unik secara global.
     */
    public function test_3_and_4_different_stores_and_sales_maintain_global_uniqueness(): void
    {
        $trxSales1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        $trxSales2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store2->id,
            'transaction_amount' => 2000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales2);

        $this->assertEquals('TRX-20260903-0001', $trxSales1->transaction_code);
        $this->assertEquals('TRX-20260903-0002', $trxSales2->transaction_code);
    }

    /**
     * TEST 5: Admin membuat transaksi untuk toko Sales tetap menghasilkan kode unik global.
     */
    public function test_5_admin_created_transaction_generates_next_unique_code(): void
    {
        $trxSales = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        $trxAdmin = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 3000000,
            'transaction_date' => '2026-09-03',
        ], $this->admin);

        $this->assertEquals('TRX-20260903-0001', $trxSales->transaction_code);
        $this->assertEquals('TRX-20260903-0002', $trxAdmin->transaction_code);
    }

    /**
     * TEST 6 & 7: Database UNIQUE constraint menolak duplicate transaction_code.
     */
    public function test_6_and_7_database_unique_constraint_rejects_duplicate(): void
    {
        StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260903-9999',
            'transaction_date' => '2026-09-03',
            'transaction_amount' => 500000,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        StoreTransaction::create([
            'store_id' => $this->store2->id,
            'transaction_code' => 'TRX-20260903-9999', // Duplicate code!
            'transaction_date' => '2026-09-03',
            'transaction_amount' => 750000,
        ]);
    }

    /**
     * TEST 8 & 9 & 10: 10 transaksi berurutan pada hari yang sama semuanya unik tanpa tabrakan.
     */
    public function test_8_9_10_consecutive_ten_transactions_all_unique(): void
    {
        $codes = [];
        for ($i = 1; $i <= 10; $i++) {
            $trx = StoreReceivableService::createTransaction([
                'store_id' => $this->store1->id,
                'transaction_amount' => 100000 * $i,
                'transaction_date' => '2026-09-03',
            ], $this->sales1);
            $codes[] = $trx->transaction_code;
        }

        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        $this->assertEquals('TRX-20260903-0001', $codes[0]);
        $this->assertEquals('TRX-20260903-0010', $codes[9]);
    }

    /**
     * TEST 11: Payment tetap mengacu pada transaction_id (FK) yang benar.
     */
    public function test_11_payment_references_correct_transaction_id_fk(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        $pay = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx->id,
            'amount' => 500000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-03',
        ], $this->sales1);

        $this->assertEquals($trx->id, $pay->store_transaction_id);
        $this->assertEquals('TRX-20260903-0001', $pay->transaction->transaction_code);
        $this->assertEquals(500000.0, $trx->fresh()->remaining_amount);
    }

    /**
     * TEST 21: Sequence reset harian menghasilkan kode unik antar hari yang berbeda.
     */
    public function test_21_sequence_daily_format_distinct_across_dates(): void
    {
        $trxDay1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-03',
        ], $this->sales1);

        $trxDay2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_amount' => 2000000,
            'transaction_date' => '2026-09-04',
        ], $this->sales1);

        $this->assertEquals('TRX-20260903-0001', $trxDay1->transaction_code);
        $this->assertEquals('TRX-20260904-0001', $trxDay2->transaction_code);
        $this->assertNotEquals($trxDay1->transaction_code, $trxDay2->transaction_code);
    }
}
