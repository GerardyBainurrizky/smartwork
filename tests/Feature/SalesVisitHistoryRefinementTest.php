<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesVisitHistoryRefinementTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Sales Refine', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Refine', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Rizky Mandiri',
            'code' => 'TKO-RM01',
            'address' => 'Jl. Merdeka No. 10',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Lengkong Berkah',
            'code' => 'TKO-LB02',
            'address' => 'Jl. Sudirman No. 20',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 1: Card Nominal Full Display Verification (No Elision, No Bad Wrap)
     */
    public function test_visit_history_cards_display_full_rupiah(): void
    {
        // 1. Transaksi baru bulan ini: Rp 43.566.666
        $trx = StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-20260901-0001',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 43566666,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        // 2. Transaksi lama (saldo awal/bulan lalu) yang dibayar piutang lamanya bulan ini sebesar Rp 5.000.000
        $oldTrx = StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-OLD-BALANCE',
            'transaction_date' => now()->subMonth()->toDateString(),
            'transaction_amount' => 10000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'created_by' => $this->sales->id,
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'payment_date' => now()->toDateString(),
            'amount' => 5000000,
            'payment_method' => 'transfer',
            'source' => 'visit',
            'recorded_by' => $this->sales->id,
            'notes' => 'Pembayaran piutang lama',
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.history'));
        $res->assertStatus(200);

        // Header statistics
        $res->assertSee('Total Transaksi Baru Bulan Ini');
        $res->assertSee('Total Saldo Piutang');
        $res->assertSee('Pembayaran Piutang Lama Bulan Ini');

        // Check full nominals
        $res->assertSee('Rp 43.566.666'); // Total transaksi baru
        $res->assertSee('Rp 48.566.666'); // Total saldo piutang (43.566.666 + 5.000.000)
        $res->assertSee('Rp 5.000.000');  // Pembayaran piutang lama bulan ini
        $res->assertDontSee('Rp 43.566...');
        $res->assertDontSee('Rp 43.5...');
    }

    /**
     * TEST 2: Table Column Structure: Transaksi Baru, Bayar Piutang, Sisa Piutang
     */
    public function test_table_columns_display_clear_financial_structure(): void
    {
        // 1. Toko 1 has old debt transaction: Rp 10.000.000
        $oldTrx1 = StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-OLD-01',
            'transaction_date' => now()->subDays(5)->toDateString(),
            'transaction_amount' => 10000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Refine',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Stop 1: Bayar Piutang Lama Rp 2.500.000 & Transaksi Baru Rp 5.000.000 (Bayar Awal Rp 1.000.000)
        $stop1 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit1 = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => now()->setTime(10, 0),
            'check_out_at' => now()->setTime(11, 0),
            'transaction_amount' => 5000000,
            'transaction_status' => 'mixed',
        ]);

        // Old debt payment
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx1->id,
            'store_id' => $this->store1->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2500000,
            'payment_method' => 'transfer',
            'source' => 'visit',
            'source_id' => $visit1->id,
            'recorded_by' => $this->sales->id,
        ]);

        // New transaction created during visit
        $newTrx = StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-NEW-01',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 5000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'reference_type' => 'visit',
            'reference_id' => $visit1->id,
            'created_by' => $this->sales->id,
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $newTrx->id,
            'store_id' => $this->store1->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000000,
            'payment_method' => 'transfer',
            'source' => 'initial_payment',
            'recorded_by' => $this->sales->id,
        ]);

        // Stop 2: Visit without any transaction or payment
        $stop2 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'sequence' => 2,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => now()->setTime(11, 30),
            'check_out_at' => now()->setTime(12, 0),
            'transaction_status' => 'none',
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.history'));
        $res->assertStatus(200);

        // Verify obsolete labels are removed
        $res->assertDontSee('Aktivitas Keuangan');
        $res->assertDontSee('Piutang Setelah Kunjungan');

        // Verify new column headers
        $res->assertSee('Transaksi Baru');
        $res->assertSee('Pembayaran Piutang Lama');
        $res->assertSee('Saldo Piutang Toko');

        // Verify values:
        // Transaksi Baru: Rp 5.000.000
        $res->assertSee('Rp 5.000.000');
        // Bayar Piutang (hanya pelunasan piutang lama): Rp 2.500.000
        $res->assertSee('Rp 2.500.000');
        // Sisa Piutang Toko 1: (10.000.000 - 2.500.000) + (5.000.000 - 1.000.000) = Rp 11.500.000
        $res->assertSee('Rp 11.500.000');
        // Sisa Piutang Toko 2: Rp 0
        $res->assertSee('Rp 0');
    }

    /**
     * TEST 3: Driver Isolation (Driver Table Does Not Show Piutang Columns)
     */
    public function test_driver_visit_history_is_isolated(): void
    {
        $res = $this->actingAs($this->driver)->get(route('visit.history'));
        $res->assertStatus(200);
        $res->assertDontSee('Piutang dari Transaksi Bulan Ini');
        $res->assertDontSee('Pembayaran Piutang Bulan Ini');
        $res->assertDontSee('Bayar Piutang');
        $res->assertDontSee('Sisa Piutang');
    }
}
