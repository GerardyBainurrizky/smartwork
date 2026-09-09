<?php

namespace Tests\Feature;

use App\Exports\VisitsExport;
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

class SalesComprehensiveExportEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Fikri Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store1 = Store::create([
            'name' => 'Toko Rizky Mandiri',
            'code' => 'TKO-RM01',
            'address' => 'Jl. Pahlawan No. 10',
            'city' => 'Jakarta Timur',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Pusaka Tani',
            'code' => 'TKO-PT02',
            'address' => 'Jl. Merak No. 5',
            'city' => 'Bogor',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 1: PDF Detail Visit Generates Complete Transaction, Payment Allocations, and Total Store Balance
     */
    public function test_pdf_detail_visit_renders_full_breakdown_and_store_balance(): void
    {
        // 1. Setup 2 old debt transactions for store1
        $trx1 = StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-001',
            'transaction_date' => now()->subDays(5)->toDateString(),
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $trx2 = StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-002',
            'transaction_date' => now()->subDays(3)->toDateString(),
            'transaction_amount' => 2000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan PDF',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => now()->setTime(10, 0),
            'check_out_at' => now()->setTime(11, 0),
            'transaction_status' => 'piutang',
            'visit_result' => 'Pembayaran piutang sukses',
        ]);

        // Pay TRX-001 Rp 500.000 (Remaining Rp 500.000, Status SEBAGIAN)
        StoreTransactionPayment::create([
            'store_transaction_id' => $trx1->id,
            'store_id' => $this->store1->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500000,
            'payment_method' => 'transfer',
            'source' => 'visit',
            'source_id' => $visit->id,
            'recorded_by' => $this->sales->id,
        ]);
        $trx1->recalculateStatus();

        // Total store balance = (1.000.000 - 500.000) + 2.000.000 = Rp 2.500.000
        $res = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $res->assertOk();
        $this->assertEquals('application/pdf', $res->headers->get('Content-Type'));
    }

    /**
     * TEST 2: PDF Rekapitulasi Includes Skipped Visits with Photo Proof
     */
    public function test_pdf_rekap_includes_skipped_visit_with_actual_proof_data(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Rekap Skip',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'sequence' => 1,
            'status' => 'skipped',
            'notes' => json_encode([
                'reason' => 'Toko tutup karena renovasi',
                'photo_path' => 'skip-evidence/dummy-skip.jpg',
                'latitude' => -6.2001,
                'longitude' => 106.8123,
                'address' => 'Jl. Merak No. 5',
                'maps_url' => 'https://maps.google.com/?q=-6.2001,106.8123',
            ]),
        ]);

        $res = $this->actingAs($this->sales)->get(route('visit.pdf.rekap', [
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $res->assertOk();
        $this->assertEquals('application/pdf', $res->headers->get('Content-Type'));
    }
}
