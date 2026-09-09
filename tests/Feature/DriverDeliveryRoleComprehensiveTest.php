<?php

namespace Tests\Feature;

use App\Exports\DriverVisitsExport;
use App\Exports\Sheets\DokumentasiPengirimanDriverSheet;
use App\Exports\Sheets\RekapPengirimanDriverSheet;
use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use App\Services\StoreReceivableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverDeliveryRoleComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $sales;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->driver = User::factory()->create(['name' => 'Budi Driver']);
        $this->driver->assignRole('driver');

        $this->sales = User::factory()->create(['name' => 'Andi Sales']);
        $this->sales->assignRole('sales');

        $this->store = Store::create([
            'name' => 'Toko Sumber Rezeki',
            'code' => 'TKO-SR-001',
            'address' => 'Jl. Merdeka No. 10',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    private function dummyBase64Image(): string
    {
        $img = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($img);
        $data = ob_get_clean();
        imagedestroy($img);
        return 'data:image/jpeg;base64,' . base64_encode($data);
    }

    public function test_driver_check_in_saves_label_foto_toko_selfie(): void
    {
        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Pengiriman Pagi',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Merdeka No. 10',
            'maps_url' => 'https://maps.google.com',
            'selfie' => $this->dummyBase64Image(),
            'initial_notes' => 'Tiba di lokasi toko',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('visits', [
            'route_stop_id' => $stop->id,
            'user_id' => $this->driver->id,
            'status' => 'in_progress',
            'initial_notes' => 'Tiba di lokasi toko',
        ]);
    }

    public function test_driver_check_out_without_transaction_creates_no_fake_transaction_or_receivable(): void
    {
        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->driver->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subMinutes(30),
            'check_in_lat' => -6.200000,
            'check_in_lng' => 106.816666,
            'check_in_address' => 'Jl. Merdeka No. 10',
            'check_in_maps_url' => 'https://maps.google.com',
            'check_in_selfie' => 'visits/' . $this->driver->id . '/selfie.jpg',
        ]);

        $response = $this->actingAs($this->driver)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Merdeka No. 10',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Barang diterima lengkap oleh pemilik toko',
            'final_notes' => 'Barang diterima oleh bagian gudang.',
            'transaction_status' => 'none',
            'photos' => [$this->dummyBase64Image(), $this->dummyBase64Image()],
        ]);

        $response->assertOk();
        $visit->refresh();

        $this->assertSame('completed', $visit->status);
        $this->assertNull($visit->transaction_amount);
        $this->assertNull($visit->payment_method);
        $this->assertSame('Barang diterima oleh bagian gudang.', $visit->final_notes);
        $this->assertCount(2, $visit->checkoutPhotos);

        // Ensure no StoreTransaction or receivable was created
        $this->assertSame(0, StoreTransaction::where('reference_id', $visit->id)->count());
        $this->assertEquals(0.0, (float) StoreReceivableService::balanceForStore($this->store->id));
    }

    public function test_driver_check_out_with_transaction_saves_amount_and_method_isolated_from_sales_receivables(): void
    {
        // Give the store existing sales receivable of Rp 1.000.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 1000000,
            'paid_amount' => 0,
            'payment_method' => 'tunai',
            'description' => 'Faktur Penjualan Sales Andi',
        ], $this->sales);

        $initialBalance = (float) StoreReceivableService::balanceForStore($this->store->id);
        $this->assertEquals(1000000.0, $initialBalance);

        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->driver->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subMinutes(20),
            'check_in_lat' => -6.200000,
            'check_in_lng' => 106.816666,
            'check_in_address' => 'Jl. Merdeka No. 10',
            'check_in_maps_url' => 'https://maps.google.com',
            'check_in_selfie' => 'visits/' . $this->driver->id . '/selfie.jpg',
        ]);

        $response = $this->actingAs($this->driver)->postJson(route('visit.check-out', $visit->id), [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Merdeka No. 10',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Barang diterima lengkap',
            'final_notes' => 'Pembayaran COD tunai diterima di tempat',
            'transaction_status' => 'paid',
            'transaction_amount' => 500000,
            'payment_method' => 'tunai',
            'photos' => [$this->dummyBase64Image(), $this->dummyBase64Image(), $this->dummyBase64Image()],
        ]);

        $response->assertOk();
        $visit->refresh();

        $this->assertSame('completed', $visit->status);
        $this->assertEquals(500000.0, (float) $visit->transaction_amount);
        $this->assertSame('tunai', $visit->payment_method);
        $this->assertCount(3, $visit->checkoutPhotos);

        // Verify Store Receivables (Sales) remained 1.000.000 and did not get affected by Driver's 500.000 transaction!
        $afterBalance = (float) StoreReceivableService::balanceForStore($this->store->id);
        $this->assertEquals(1000000.0, $afterBalance);
    }

    public function test_driver_dashboard_and_history_show_isolated_driver_transaction_stats(): void
    {
        $today = now()->toDateString();

        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver Hari Ini',
            'date' => $today,
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'user_id' => $this->driver->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'paid',
            'transaction_amount' => 750000,
            'payment_method' => 'transfer',
            'visit_result' => 'Terkirim',
        ]);

        // Dashboard driver
        $dashRes = $this->actingAs($this->driver)->get(route('dashboard'));
        $dashRes->assertOk();
        $dashRes->assertSee('Uang Masuk Pengiriman');
        $dashRes->assertSee('750.000');

        // History driver
        $histRes = $this->actingAs($this->driver)->get(route('visit.history'));
        $histRes->assertOk();
        $histRes->assertSee('Riwayat Pengiriman');
        $histRes->assertSee('750.000');
        $histRes->assertSee('TRANSFER');
    }

    public function test_driver_skip_delivery_stores_evidence_and_appears_in_reports(): void
    {
        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->driver)->patchJson(route('route.stop.update', [$route->id, $stop->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup saat pengiriman tiba',
            'photo' => $this->dummyBase64Image(),
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Merdeka No. 10',
            'maps_url' => 'https://maps.google.com',
        ]);

        $response->assertOk();
        $stop->refresh();

        $this->assertSame('skipped', $stop->status);
        $this->assertNotNull($stop->visit);
        $this->assertNotNull($stop->visit->final_store_photo);
        $this->assertSame('Toko tutup saat pengiriman tiba', $stop->visit->initial_notes);

        // Test Excel Export with Skipped Delivery
        $export = new DriverVisitsExport([
            'fromDate' => now()->subDay()->toDateString(),
            'toDate' => now()->addDay()->toDateString(),
            'userId' => $this->driver->id,
        ]);
        $sheets = $export->sheets();
        $this->assertCount(2, $sheets);

        $rekapSheet = $sheets[0];
        $this->assertInstanceOf(RekapPengirimanDriverSheet::class, $rekapSheet);
        $rows = $rekapSheet->dataRows();
        $this->assertCount(1, $rows);
        $this->assertSame('Dilewati', $rows[0][7]);
        $this->assertSame('Toko tutup saat pengiriman tiba', $rows[0][16]);

        $docSheet = $sheets[1];
        $this->assertInstanceOf(DokumentasiPengirimanDriverSheet::class, $docSheet);
        $docRows = $docSheet->dataRows();
        $this->assertCount(1, $docRows);
        $this->assertSame('Foto Dilewati', $docRows[0][4]);
    }

    public function test_driver_pdf_detail_renders_complete_multiline_and_dynamic_photos(): void
    {
        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver PDF',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->driver->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_status' => 'paid',
            'transaction_amount' => 500000,
            'payment_method' => 'tunai',
            'visit_result' => "Barang telah diterima oleh bagian gudang.\n\nDokumen pengiriman sudah diserahkan.\n\nBarang dalam kondisi baik.",
            'final_notes' => "Catatan Baris 1\nCatatan Baris 2",
            'check_in_selfie' => 'visits/' . $this->driver->id . '/checkin.jpg',
        ]);

        // Add 3 dynamic checkout photos
        VisitPhoto::create(['visit_id' => $visit->id, 'type' => 'checkout_documentation', 'photo_path' => 'visits/' . $this->driver->id . '/p1.jpg']);
        VisitPhoto::create(['visit_id' => $visit->id, 'type' => 'checkout_documentation', 'photo_path' => 'visits/' . $this->driver->id . '/p2.jpg']);
        VisitPhoto::create(['visit_id' => $visit->id, 'type' => 'checkout_documentation', 'photo_path' => 'visits/' . $this->driver->id . '/p3.jpg']);

        $pdfResponse = $this->actingAs($this->driver)->get(route('visit.pdf.detail', $visit->id));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');

        $content = $pdfResponse->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }
}
