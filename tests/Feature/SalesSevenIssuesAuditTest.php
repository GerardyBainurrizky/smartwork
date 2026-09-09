<?php

namespace Tests\Feature;

use App\Exports\VisitsExport;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesSevenIssuesAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected Store $store1;
    protected Store $store2;
    protected Store $store3;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'super-admin']);

        $this->sales = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales->assignRole('sales');

        $this->store1 = Store::create([
            'name' => 'Toko Subur Makmur',
            'code' => 'TKO-SUBUR',
            'address' => 'Jl. Merdeka No. 10',
            'latitude' => -6.2000,
            'longitude' => 106.8166,
            'google_maps_url' => 'https://maps.google.com/?q=-6.2000,106.8166',
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Rejeki Abadi',
            'code' => 'TKO-REJEKI',
            'address' => 'Jl. Sudirman No. 20',
            'latitude' => -6.2100,
            'longitude' => 106.8266,
            'google_maps_url' => 'https://maps.google.com/?q=-6.2100,106.8266',
        ]);

        $this->store3 = Store::create([
            'name' => 'Toko Berkah Jaya',
            'code' => 'TKO-BERKAH',
            'address' => 'Jl. Thamrin No. 30',
            'latitude' => -6.2200,
            'longitude' => 106.8366,
            'google_maps_url' => 'https://maps.google.com/?q=-6.2200,106.8366',
        ]);
    }

    /**
     * Issue 1: Rute Hari Ini mobile card layout contains Piutang & Buka Maps without colliding.
     */
    public function test_issue_1_route_today_card_layout_contains_piutang_and_maps()
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Harian Sales',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'pending',
            'estimated_duration_minutes' => 30,
        ]);

        // Berikan piutang ke toko 1
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-001',
            'transaction_amount' => 500000,
            'paid_amount' => 0,
            'payment_method' => 'tunai',
            'transaction_date' => now()->toDateString(),
        ], $this->sales);

        $response = $this->actingAs($this->sales)->get(route('route.index'));
        $response->assertStatus(200);
        $response->assertSee('Piutang: Rp 500.000');
        $response->assertSee('Buka Maps');
        $response->assertSee('30 mnt');
    }

    /**
     * Issue 2: Sales Check-in Camera uses mirror preview and matching capture in client code.
     */
    public function test_issue_2_check_in_camera_view_contains_non_mirror_capture_and_mirror_preview()
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Checkin',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('visit.check-in-form', $stop->id));
        $response->assertStatus(200);

        $content = $response->getContent();
        // Preview kamera depan mirrored via scaleX(-1)
        $this->assertStringContainsString("video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'none'", $content);
        // Canvas capture maintains preview visual orientation so it doesn't flip
        $this->assertStringContainsString("if (this.selfieFacing === 'user')", $content);
        $this->assertStringContainsString("ctx.scale(-1, 1)", $content);
    }

    /**
     * Issue 3 & Issue 4: Sales Check-out Payment Layout & Camera Capture Matching Preview.
     */
    public function test_issue_3_and_4_checkout_payment_layout_and_camera_non_mirror()
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Checkout',
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
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2000,
            'check_in_lng' => 106.8166,
        ]);

        // Buat transaksi piutang lama
        StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-OLD-500K',
            'transaction_amount' => 500000,
            'paid_amount' => 0,
            'payment_method' => 'tunai',
            'transaction_date' => now()->subDays(5)->toDateString(),
        ], $this->sales);

        $response = $this->actingAs($this->sales)->get(route('visit.check-out-form', $visit->id));
        $response->assertStatus(200);

        $content = $response->getContent();
        // Cek label pembayaran
        $response->assertSee('Bayar Piutang Lama');
        $response->assertSee('Piutang Lama + Transaksi Baru');
        $response->assertSee('Total Piutang: Rp 500.000');

        // Camera: Preview mirror for front camera & capture matches preview
        $this->assertStringContainsString("video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'none'", $content);
        $this->assertStringContainsString("if (this.selfieFacing === 'user')", $content);
        $this->assertStringContainsString("ctx.scale(-1, 1)", $content);
    }

    /**
     * Issue 5: Visit History Excel export orders visits chronologically ascending by Date value.
     */
    public function test_issue_5_visit_history_excel_export_date_chronological_ordering()
    {
        // Buat kunjungan pada tanggal berbeda: 28 Aug, 26 Aug, 02 Sep, 27 Aug
        $dates = [
            '2026-08-28' => 'Toko 28 Aug',
            '2026-08-26' => 'Toko 26 Aug',
            '2026-09-02' => 'Toko 02 Sep',
            '2026-08-27' => 'Toko 27 Aug',
        ];

        foreach ($dates as $dateStr => $storeName) {
            $st = Store::create([
                'name' => $storeName,
                'code' => 'TKO-' . substr($dateStr, -5),
                'address' => 'Alamat ' . $storeName,
            ]);

            $rt = Route::create([
                'user_id' => $this->sales->id,
                'name' => 'Rute ' . $dateStr,
                'date' => $dateStr,
                'status' => 'completed',
            ]);

            $rst = RouteStop::create([
                'route_id' => $rt->id,
                'store_id' => $st->id,
                'sequence' => 1,
                'status' => 'visited',
            ]);

            Visit::create([
                'user_id' => $this->sales->id,
                'route_stop_id' => $rst->id,
                'route_id' => $rt->id,
                'store_id' => $st->id,
                'status' => 'completed',
                'check_in_at' => Carbon::parse($dateStr . ' 09:00:00'),
                'check_out_at' => Carbon::parse($dateStr . ' 10:00:00'),
                'visit_result' => 'Sukses kunjungan ' . $dateStr,
            ]);
        }

        // Juga buat 1 stop yang dilewati pada 2026-08-26 sequence 2
        $route26 = Route::whereDate('date', '2026-08-26')->first();
        RouteStop::create([
            'route_id' => $route26->id,
            'store_id' => $this->store3->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => 'Toko tutup saat sales berkunjung',
        ]);

        $export = new VisitsExport([
            'fromDate' => '2026-08-17',
            'toDate' => '2026-09-08',
            'userId' => $this->sales->id,
        ]);

        $sheets = $export->sheets();
        $rekapRows = $sheets[0]->dataRows();

        $this->assertCount(5, $rekapRows); // 4 completed + 1 skipped

        // Urutan tanggal WAJIB kronologis:
        // Row 1: 26 Aug (Completed)
        $this->assertEquals('26 Aug 2026', $rekapRows[0][1]);
        $this->assertEquals('Toko 26 Aug', $rekapRows[0][3]);
        $this->assertEquals('Selesai', $rekapRows[0][8]);

        // Row 2: 26 Aug (Skipped Sequence 2)
        $this->assertEquals('26 Aug 2026', $rekapRows[1][1]);
        $this->assertEquals($this->store3->name, $rekapRows[1][3]);
        $this->assertEquals('Dilewati', $rekapRows[1][8]);

        // Row 3: 27 Aug
        $this->assertEquals('27 Aug 2026', $rekapRows[2][1]);
        $this->assertEquals('Toko 27 Aug', $rekapRows[2][3]);

        // Row 4: 28 Aug
        $this->assertEquals('28 Aug 2026', $rekapRows[3][1]);
        $this->assertEquals('Toko 28 Aug', $rekapRows[3][3]);

        // Row 5: 02 Sep
        $this->assertEquals('02 Sep 2026', $rekapRows[4][1]);
        $this->assertEquals('Toko 02 Sep', $rekapRows[4][3]);
    }

    /**
     * Issue 6: Store Submissions has only one main CTA and no duplicate "+ Ajukan" in card header.
     */
    public function test_issue_6_store_submissions_no_duplicate_ajukan_cta()
    {
        $response = $this->actingAs($this->sales)->get(route('stores.submissions.index'));
        $response->assertStatus(200);

        // Memiliki CTA utama "Informasikan Toko Baru"
        $response->assertSee('Informasikan Toko Baru');

        // TIDAK memiliki duplicate button "+ Ajukan"
        $response->assertDontSee('+ Ajukan');
    }

    /**
     * Issue 7: Lewati Kunjungan skip camera uses mirror preview and non-mirror capture in client code.
     */
    public function test_issue_7_skip_visit_camera_non_mirror_capture()
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Lewati',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->get(route('route.show', $route->id));
        $response->assertStatus(200);

        $content = $response->getContent();
        // Preview kamera depan mirrored via scaleX(-1)
        $this->assertStringContainsString("video.style.transform = this.skipFacing === 'user' ? 'scaleX(-1)' : 'none'", $content);
        // Canvas capture matches preview
        $this->assertStringContainsString("if (this.skipFacing === 'user')", $content);
        $this->assertStringContainsString("ctx.scale(-1, 1)", $content);
    }
}
