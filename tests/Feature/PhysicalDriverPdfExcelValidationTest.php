<?php

namespace Tests\Feature;

use App\Exports\DriverVisitsExport;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhysicalDriverPdfExcelValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_physical_generation_of_driver_pdf_and_excel_reports(): void
    {
        Storage::fake('public');
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'sales']);

        $driver = User::factory()->create(['name' => 'Slamet Driver']);
        $driver->assignRole('driver');

        $store = Store::create([
            'name' => 'Toko Barokah Sentosa',
            'code' => 'TKO-BS-099',
            'address' => 'Jl. Pahlawan No. 45, Semarang',
            'latitude' => -6.9932,
            'longitude' => 110.4203,
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);

        $route = Route::create([
            'user_id' => $driver->id,
            'name' => 'Rute Distribusi Wilayah 1',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit1 = Visit::create([
            'user_id' => $driver->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'check_in_lat' => -6.9932,
            'check_in_lng' => 110.4203,
            'check_in_address' => 'Jl. Pahlawan No. 45, Semarang',
            'check_out_lat' => -6.9932,
            'check_out_lng' => 110.4203,
            'check_out_address' => 'Jl. Pahlawan No. 45, Semarang',
            'transaction_status' => 'paid',
            'transaction_amount' => 1250000,
            'payment_method' => 'transfer',
            'visit_result' => "Barang telah diterima oleh bagian gudang Toko Barokah Sentosa.\n\nFaktur fisik telah ditandatangani dan dicap oleh penerima.\n\nKondisi seluruh karton utuh tanpa cacat.",
            'final_notes' => "Catatan serah terima: diterima oleh Bpk. Gunawan (Kepala Gudang).\nPengiriman tepat waktu.",
            'check_in_selfie' => 'visits/' . $driver->id . '/checkin_selfie.jpg',
        ]);

        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => 'visits/' . $driver->id . '/doc1.jpg']);
        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => 'visits/' . $driver->id . '/doc2.jpg']);
        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => 'visits/' . $driver->id . '/doc3.jpg']);
        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => 'visits/' . $driver->id . '/doc4.jpg']);

        // Stop 2: Skipped delivery with photo
        $stop2 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => 'Toko tutup karena hari libur toko / renovasi mendadak',
        ]);

        Visit::create([
            'user_id' => $driver->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => now()->subMinutes(30),
            'check_out_at' => now()->subMinutes(25),
            'visit_result' => 'Dilewati: Toko tutup karena hari libur toko / renovasi mendadak',
            'initial_notes' => 'Toko tutup karena hari libur toko / renovasi mendadak',
            'final_store_photo' => 'visits/' . $driver->id . '/skip_proof.jpg',
        ]);

        // 1. Single PDF Download
        $detailPdfResponse = $this->actingAs($driver)->get(route('visit.pdf.detail', $visit1->id));
        $detailPdfResponse->assertOk();
        $detailPdfResponse->assertHeader('content-type', 'application/pdf');
        $pdfContent = $detailPdfResponse->getContent();
        $this->assertStringStartsWith('%PDF', $pdfContent);

        // 2. Rekap PDF Download
        $rekapPdfResponse = $this->actingAs($driver)->get(route('visit.pdf.rekap', [
            'from_date' => now()->subDays(2)->toDateString(),
            'to_date' => now()->addDays(2)->toDateString(),
        ]));
        $rekapPdfResponse->assertOk();
        $rekapPdfResponse->assertHeader('content-type', 'application/pdf');
        $rekapPdfContent = $rekapPdfResponse->getContent();
        $this->assertStringStartsWith('%PDF', $rekapPdfContent);

        // 3. Rekap Excel Export & Structure Verification
        $export = new DriverVisitsExport([
            'fromDate' => now()->subDays(2)->toDateString(),
            'toDate' => now()->addDays(2)->toDateString(),
            'userId' => $driver->id,
        ]);

        $sheets = $export->sheets();
        $this->assertCount(2, $sheets);

        // Sheet 1: Rekap Pengiriman
        $rekapRows = $sheets[0]->dataRows();
        $this->assertCount(2, $rekapRows); // 1 completed visit + 1 skipped delivery
        $this->assertSame(1, $rekapRows[0][0]);
        $this->assertSame('Slamet Driver', $rekapRows[0][2]);
        $this->assertSame('Toko Barokah Sentosa', $rekapRows[0][3]);
        $this->assertSame('-', $rekapRows[0][6]); // store note is '-'
        $this->assertSame('Selesai', $rekapRows[0][7]); // status is Col 7 (index 7)
        $this->assertEquals(1250000.0, $rekapRows[0][13]); // txAmount is index 13
        $this->assertSame('TRANSFER', $rekapRows[0][14]); // payMethod is index 14
        $this->assertStringContainsString("Faktur fisik telah ditandatangani", $rekapRows[0][15]);
        $this->assertStringContainsString("Bpk. Gunawan", $rekapRows[0][16]);

        // Skipped row in Sheet 1
        $this->assertSame(2, $rekapRows[1][0]);
        $this->assertSame('Dilewati', $rekapRows[1][7]);
        $this->assertSame('-', $rekapRows[1][13]);
        $this->assertSame('-', $rekapRows[1][14]);

        // Sheet 2: Dokumentasi Pengiriman
        $docRows = $sheets[1]->dataRows();
        // Visit 1: 1 check-in photo + 4 checkout photos = 5 doc rows
        // Stop 2: 1 skipped photo = 1 doc row
        // Total = 6 rows
        $this->assertCount(6, $docRows);
        $this->assertSame('Foto Toko / Selfie', $docRows[0][4]);
        $this->assertSame('Foto Barang/Dokumen #1', $docRows[1][4]);
        $this->assertSame('Foto Barang/Dokumen #2', $docRows[2][4]);
        $this->assertSame('Foto Barang/Dokumen #3', $docRows[3][4]);
        $this->assertSame('Foto Barang/Dokumen #4', $docRows[4][4]);
        $this->assertSame('Foto Dilewati', $docRows[5][4]);
    }
}
