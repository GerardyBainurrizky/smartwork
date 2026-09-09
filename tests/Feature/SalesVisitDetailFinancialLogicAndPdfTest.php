<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesVisitDetailFinancialLogicAndPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store = Store::create([
            'name' => 'Toko Pusaka Tani Test',
            'code' => 'STORE-TEST-001',
            'address' => 'Jl. Pertanian No. 1',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    /**
     * Skenario 1: Transaksi baru lunas (Rp 10.000.000, dibayar Rp 10.000.000)
     */
    public function test_scenario_1_transaksi_baru_lunas(): void
    {
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'paid',
            'transaction_amount' => 10000000,
        ]);

        $trx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0001',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 10000000,
            'status' => 'LUNAS',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $trx->id,
            'amount' => 10000000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal saat transaksi dibuat',
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.show', $visit->id));
        $res->assertOk();
        $res->assertSee('TRX-TEST-0001');
        $res->assertSee('Rp 10.000.000');
        $res->assertSee('LUNAS');
    }

    /**
     * Skenario 2: Transaksi baru sebagian dibayar (Rp 10.000.000, dibayar Rp 8.000.000)
     */
    public function test_scenario_2_transaksi_baru_sebagian(): void
    {
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'paid',
            'transaction_amount' => 10000000,
        ]);

        $trx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0002',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 10000000,
            'status' => 'SEBAGIAN',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $trx->id,
            'amount' => 8000000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal saat transaksi dibuat',
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.show', $visit->id));
        $res->assertOk();
        $res->assertSee('TRX-TEST-0002');
        $res->assertSee('Rp 10.000.000');
        $res->assertSee('Rp 8.000.000');
        $res->assertSee('Rp 2.000.000');
        $res->assertSee('SEBAGIAN');
    }

    /**
     * Skenario 3: Transaksi baru tanpa pembayaran (Rp 10.000.000, dibayar Rp 0)
     */
    public function test_scenario_3_transaksi_baru_tanpa_pembayaran(): void
    {
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'paid',
            'transaction_amount' => 10000000,
        ]);

        $trx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-TEST-0003',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 10000000,
            'status' => 'BELUM_LUNAS',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.show', $visit->id));
        $res->assertOk();
        $res->assertSee('TRX-TEST-0003');
        $res->assertSee('Rp 10.000.000');
        $res->assertSee('Rp 0');
        $res->assertSee('BELUM_LUNAS');
    }

    /**
     * Skenario 4: Pembayaran piutang lama (Piutang lama Rp 300.000, dibayar Rp 300.000)
     */
    public function test_scenario_4_pembayaran_piutang_lama(): void
    {
        // Transaksi lama
        $oldTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-0001',
            'transaction_date' => now()->subDays(5)->toDateString(),
            'transaction_amount' => 870000,
            'status' => 'SEBAGIAN',
            'created_by' => $this->sales->id,
        ]);

        // Pembayaran sebelum kunjungan ini
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 570000,
            'payment_method' => 'tunai',
            'payment_date' => now()->subDays(3)->toDateString(),
            'recorded_by' => $this->sales->id,
            'notes' => 'Cicilan pertama',
        ]);

        // Kunjungan saat ini membayar sisa Rp 300.000
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'piutang',
            'transaction_amount' => 300000,
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 300000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran piutang melalui kunjungan Sales',
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.show', $visit->id));
        $res->assertOk();
        $res->assertSee('TRX-OLD-0001');
        $res->assertSee('Rp 870.000'); // Nilai faktur awal
        $res->assertSee('Rp 300.000'); // Saldo sebelum & pembayaran pada kunjungan ini
        $res->assertSee('Rp 0'); // Sisa piutang faktur
    }

    /**
     * Skenario 5: Piutang lama + Transaksi baru (Mixed)
     */
    public function test_scenario_5_piutang_lama_dan_transaksi_baru(): void
    {
        // 1. Transaksi lama (Piutang Rp 300.000)
        $oldTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-OLD-0005',
            'transaction_date' => now()->subDays(5)->toDateString(),
            'transaction_amount' => 300000,
            'status' => 'BELUM_LUNAS',
            'created_by' => $this->sales->id,
        ]);

        // 2. Kunjungan baru: bayar piutang lama Rp 300.000 + buat transaksi baru Rp 10.000.000 (uang masuk Rp 8.000.000)
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'mixed',
            'transaction_amount' => 10300000,
        ]);

        // Bayar piutang lama
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 300000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran piutang melalui kunjungan Sales',
        ]);

        // Buat transaksi baru
        $newTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-NEW-0005',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 10000000,
            'status' => 'SEBAGIAN',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
        ]);

        // Uang masuk transaksi baru
        StoreTransactionPayment::create([
            'store_transaction_id' => $newTrx->id,
            'amount' => 8000000,
            'payment_method' => 'tunai',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal saat transaksi dibuat',
        ]);

        // Test Web Detail
        $res = $this->actingAs($this->sales)->get(route('visit.show', $visit->id));
        $res->assertOk();
        $res->assertSee('TRX-OLD-0005');
        $res->assertSee('TRX-NEW-0005');
        $res->assertSee('Rp 10.000.000');
        $res->assertSee('Rp 8.000.000');
        $res->assertSee('Rp 2.000.000'); // Saldo setelah kunjungan

        // Test PDF Detail
        $pdfRes = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $pdfRes->assertOk();
        $this->assertEquals('application/pdf', $pdfRes->headers->get('Content-Type'));
    }
}
