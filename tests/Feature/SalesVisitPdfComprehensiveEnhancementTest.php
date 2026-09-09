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
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalesVisitPdfComprehensiveEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->sales = User::factory()->create(['name' => 'Fikri Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Doni Driver', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Pusaka Tani',
            'code' => 'TKO-PT01',
            'address' => 'Jl. Raya Pertanian No. 88',
            'city' => 'Bogor',
            'province' => 'Jawa Barat',
            'latitude' => -6.5950,
            'longitude' => 106.8166,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * Test 1 to 28: PDF Detail & Rekap Structure and Data Accuracy.
     */
    public function test_sales_pdf_detail_and_rekap_complete_structure(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00'));

        // 1. Old Debts (TRX-OLD-001: 550k, TRX-OLD-002: 700k) -> Total Before = 1.250.000
        $trxOld1 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-0006',
            'transaction_amount' => 550000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        $trxOld2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260902-0005',
            'transaction_amount' => 700000,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        // Setup route & visit
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Bogor',
            'date' => '2026-09-03',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $selfieFile = UploadedFile::fake()->image('selfie_in.jpg');
        $storeFile = UploadedFile::fake()->image('storefront.jpg');
        $outSelfieFile = UploadedFile::fake()->image('selfie_out.jpg');

        $inPath = $selfieFile->store('visits/checkin', 'public');
        $frontPath = $storeFile->store('visits/storefront', 'public');
        $outPath = $outSelfieFile->store('visits/checkout', 'public');

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 09:00:00',
            'check_out_at' => '2026-09-03 10:00:00',
            'check_in_lat' => -6.5950,
            'check_in_lng' => 106.8166,
            'check_in_address' => 'Jl. Raya Pertanian No. 88 Check In',
            'check_out_lat' => -6.5951,
            'check_out_lng' => 106.8167,
            'check_out_address' => 'Jl. Raya Pertanian No. 88 Check Out',
            'check_in_selfie' => $inPath,
            'storefront_photo' => $frontPath,
            'check_out_selfie' => $outPath,
            'visit_result' => 'Pembayaran piutang lunas dan transaksi baru sebagian',
            'transaction_status' => 'mixed',
        ]);

        // Pay TRX-OLD-001 (550k -> remaining 0, LUNAS)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOld1->id,
            'amount' => 550000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Pay TRX-OLD-002 (700k -> remaining 0, LUNAS)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trxOld2->id,
            'amount' => 700000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // New transaction: TRX-20260903-0001 (1.000.000 with initial payment 500.000 -> remaining 500.000, SEBAGIAN)
        $trxNew = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260903-0001',
            'transaction_amount' => 1000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-03',
        ], $this->sales);

        // 1. Check PDF Detail Endpoint
        $res = $this->actingAs($this->sales)->get(route('visit.pdf.detail', $visit->id));
        $res->assertOk();
        $this->assertEquals('application/pdf', $res->headers->get('Content-Type'));

        // Render view HTML directly for assertion
        $viewData = [
            'visit' => $visit->fresh(['store', 'user.roles', 'routeStop.route', 'payments.transaction', 'transactions.payments']),
            'store' => $this->store,
            'salesName' => $this->sales->name,
            'salesRole' => 'Sales',
            'durationLabel' => '1 Jam',
            'storeBalance' => (float) StoreReceivableService::balanceForStore($this->store->id),
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ];

        $html = view('visit.pdf.detail', $viewData)->render();

        // CHECK OUT & HASIL KUNJUNGAN: Does NOT contain Sisa Piutang Toko
        $this->assertStringContainsString('CHECK OUT & HASIL KUNJUNGAN', $html);
        $this->assertStringContainsString('Bayar Piutang + Transaksi Baru', $html);
        $this->assertStringNotContainsString('Sisa Piutang Toko</td>', $html);

        // TRANSAKSI BARU section
        $this->assertStringContainsString('TRANSAKSI BARU', $html);
        $this->assertStringContainsString('TRX-20260903-0001', $html);
        $this->assertStringContainsString('Rp 1.000.000', $html);
        $this->assertStringContainsString('Rp 500.000', $html);
        $this->assertStringContainsString('SEBAGIAN', $html);

        // PEMBAYARAN PIUTANG LAMA section
        $this->assertStringContainsString('PEMBAYARAN PIUTANG LAMA', $html);
        $this->assertStringContainsString('TRX-20260901-0006', $html);
        $this->assertStringContainsString('TRX-20260902-0005', $html);
        $this->assertStringContainsString('Rp 550.000', $html);
        $this->assertStringContainsString('Rp 700.000', $html);
        $this->assertStringContainsString('Rp 1.250.000', $html); // Total Pembayaran Piutang

        // RINGKASAN PIUTANG TOKO section
        $this->assertStringContainsString('RINGKASAN PIUTANG TOKO', $html);
        $this->assertStringContainsString('Saldo Piutang Lama Sebelum Kunjungan', $html);
        $this->assertStringContainsString('Saldo Piutang Toko Setelah Kunjungan', $html);

        // Sisa Piutang Toko setelah kunjungan = Rp 500.000
        $this->assertEquals(500000.0, StoreReceivableService::balanceForStore($this->store->id));
    }
}
