<?php

namespace Tests\Feature;

use App\Exports\DriverVisitsExport;
use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PhysicalExportValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;
    private User $sales;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->sales = User::where('username', 'karyawan')->first();
        $this->driver = User::create([
            'username' => 'driver_phys',
            'name' => 'Budi Driver Phys',
            'email' => 'driver_phys@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);

        $this->store = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-PHYS-01',
            'name' => 'Toko Fisik Barokah',
            'owner' => 'Pak Barokah',
            'address' => 'Jl. Barokah No. 99',
            'status' => 'active',
            'is_delivery_destination' => true,
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    /**
     * Physical inspection of PDF Driver HTML / Content with 1, 3, and 5 photos
     */
    public function test_driver_pdf_physical_content_and_dynamic_photos(): void
    {
        // Toko has outstanding debt from Sales
        StoreTransaction::create([
            'id' => (string) Str::uuid(),
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-SALES-DEBT-999',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 15000000,
            'status' => 'belum_lunas',
        ]);

        $route = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'name' => 'Rute Pengiriman Fisik',
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        // Create 1 dummy image file in storage
        Storage::disk('public')->put('photos/checkin_selfie.jpg', 'fake-selfie');
        Storage::disk('public')->put('photos/doc1.jpg', 'doc1');
        Storage::disk('public')->put('photos/doc2.jpg', 'doc2');
        Storage::disk('public')->put('photos/doc3.jpg', 'doc3');
        Storage::disk('public')->put('photos/doc4.jpg', 'doc4');
        Storage::disk('public')->put('photos/doc5.jpg', 'doc5');

        $visit = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'check_in_selfie' => 'photos/checkin_selfie.jpg',
            'visit_result' => 'Barang diterima lengkap dan rapi',
            'delivered_goods_summary' => '25 Dus Minuman',
            'returned_goods' => '0',
            'final_notes' => 'Serah terima aman',
        ]);

        // 1. With 1 Photo
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc1.jpg',
        ]);

        $html1 = view('visit.pdf.detail-driver', [
            'visit' => $visit->fresh(['store', 'user.roles', 'route', 'routeStop', 'photos', 'checkoutPhotos']),
            'store' => $this->store,
            'salesName' => $this->driver->name,
            'salesRole' => 'Driver',
            'durationLabel' => '1 Jam',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('LAPORAN HASIL PENGIRIMAN', $html1);
        $this->assertStringContainsString('DOKUMENTASI BARANG / DOKUMEN PENGIRIMAN (1 FOTO)', $html1);
        $this->assertStringContainsString('Foto Barang/Dokumen #1', $html1);
        $this->assertStringNotContainsString('Foto Etalase', $html1);
        $this->assertStringNotContainsString('Piutang', $html1);
        $this->assertStringNotContainsString('TRX-SALES-DEBT-999', $html1);

        // 2. With 3 Photos
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc2.jpg',
        ]);
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc3.jpg',
        ]);

        $html3 = view('visit.pdf.detail-driver', [
            'visit' => $visit->fresh(['store', 'user.roles', 'route', 'routeStop', 'photos', 'checkoutPhotos']),
            'store' => $this->store,
            'salesName' => $this->driver->name,
            'salesRole' => 'Driver',
            'durationLabel' => '1 Jam',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('DOKUMENTASI BARANG / DOKUMEN PENGIRIMAN (3 FOTO)', $html3);
        $this->assertStringContainsString('Foto Barang/Dokumen #1', $html3);
        $this->assertStringContainsString('Foto Barang/Dokumen #2', $html3);
        $this->assertStringContainsString('Foto Barang/Dokumen #3', $html3);

        // 3. With 5 Photos
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc4.jpg',
        ]);
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc5.jpg',
        ]);

        $html5 = view('visit.pdf.detail-driver', [
            'visit' => $visit->fresh(['store', 'user.roles', 'route', 'routeStop', 'photos', 'checkoutPhotos']),
            'store' => $this->store,
            'salesName' => $this->driver->name,
            'salesRole' => 'Driver',
            'durationLabel' => '1 Jam',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('DOKUMENTASI BARANG / DOKUMEN PENGIRIMAN (5 FOTO)', $html5);
        $this->assertStringContainsString('Foto Barang/Dokumen #5', $html5);
    }

    /**
     * Physical inspection of Excel Driver workbook data and sheet structure
     */
    public function test_driver_excel_physical_workbook_inspection(): void
    {
        $visit = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'check_in_selfie' => 'photos/checkin_selfie.jpg',
            'visit_result' => 'Barang diterima lengkap',
            'final_notes' => '10 Karton Minyak',
            'transaction_amount' => 500000,
            'payment_method' => 'tunai',
        ]);

        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc1.jpg',
        ]);
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc2.jpg',
        ]);
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/doc3.jpg',
        ]);

        $export = new DriverVisitsExport([
            'fromDate' => now()->subDays(2)->toDateString(),
            'toDate' => now()->toDateString(),
            'userId' => $this->driver->id,
        ]);

        $sheets = $export->sheets();
        $this->assertCount(2, $sheets);

        $rekapSheet = $sheets[0];
        $rekapData = $rekapSheet->dataRows();
        $this->assertCount(1, $rekapData);
        $this->assertEquals('Toko Fisik Barokah', $rekapData[0][3]);
        $this->assertEquals('-', $rekapData[0][6]);
        $this->assertEquals('Selesai', $rekapData[0][7]);
        $this->assertEquals(500000.0, $rekapData[0][13]);
        $this->assertEquals('TUNAI', $rekapData[0][14]);
        $this->assertEquals('Barang diterima lengkap', $rekapData[0][15]);
        $this->assertEquals('10 Karton Minyak', $rekapData[0][16]);

        $docSheet = $sheets[1];
        $docData = $docSheet->dataRows();
        // 1 Check In photo + 3 documentation photos = 4 rows
        $this->assertCount(4, $docData);
        $this->assertEquals('Foto Toko / Selfie', $docData[0][4]);
        $this->assertEquals('Foto Barang/Dokumen #1', $docData[1][4]);
        $this->assertEquals('Foto Barang/Dokumen #2', $docData[2][4]);
        $this->assertEquals('Foto Barang/Dokumen #3', $docData[3][4]);
    }
}
