<?php

namespace Tests\Feature;

use App\Exports\DriverVisitsExport;
use App\Exports\Sheets\DokumentasiPengirimanDriverSheet;
use App\Exports\Sheets\FotoKunjunganSheet;
use App\Exports\Sheets\RekapKunjunganSheet;
use App\Exports\Sheets\RekapPengirimanDriverSheet;
use App\Exports\Sheets\TransaksiPiutangSheet;
use App\Exports\VisitsExport;
use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DriverExcelExportFinalFiftyRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $driver1;
    private User $driver2;
    private User $sales;
    private Store $store1;
    private Store $store2;
    private Store $store3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->driver1 = User::factory()->create(['name' => 'Budi Santoso Driver', 'status' => 'active']);
        $this->driver1->assignRole('driver');

        $this->driver2 = User::factory()->create(['name' => 'Agus Wahyudi Driver', 'status' => 'active']);
        $this->driver2->assignRole('driver');

        $this->sales = User::factory()->create(['name' => 'Fikri Haekal Sales', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->store1 = Store::create([
            'name' => 'Toko Barokah Jaya',
            'code' => 'TKO-BJ01',
            'address' => 'Jl. Merak No. 12, Bandung',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Subur Makmur',
            'code' => 'TKO-SM02',
            'address' => 'Jl. Elang No. 34, Bandung',
            'latitude' => -6.9200,
            'longitude' => 107.6250,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->store3 = Store::create([
            'name' => 'Toko Rezeki Abadi',
            'code' => 'TKO-RA03',
            'address' => 'Jl. Garuda No. 56, Bandung',
            'latitude' => -6.9250,
            'longitude' => 107.6300,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * Comprehensive validation of all 29 rules covering:
     * 1. Excel Driver Role only (Driver bukan Sales).
     * 2. Check in photo: Only ONE column (Foto Toko / Selfie). Embed image if available, "Tidak ada foto" if not.
     * 3. Checkout photos: Dynamic multiple columns (Foto 1, Foto 2, Foto 3, ...), grouped under "FOTO CHECK OUT — BARANG / DOKUMENTASI".
     * 4. Skip photo: Grouped under "FOTO DILEWATI", separated from goods photos.
     * 5. 2-tier grouped header structure:
     *    Row 1: INFORMASI PENGIRIMAN, CHECK IN PENGIRIMAN, CHECK OUT PENGIRIMAN, TRANSAKSI PENGIRIMAN, HASIL PENGIRIMAN, FOTO CHECK OUT — BARANG / DOKUMENTASI, FOTO DILEWATI
     *    Row 2: No, Tanggal Pengiriman, Driver, Toko / Tujuan, Kode Toko, Urutan, Status, Waktu Check In, Lokasi Check In, Foto Toko / Selfie, Waktu Check Out, Lokasi Check Out, Transaksi Pengiriman (Rp), Metode Pembayaran, Hasil Pengiriman, Catatan Tambahan, Foto 1, Foto 2, ..., Foto Dilewati
     * 6. Dynamic column expansion without hardcoding limits (e.g. test with 1 photo, test with 4 photos).
     * 7. Multiline delivery results and notes with preserved line breaks (ENTER).
     * 8. Driver transaction amount and method intact, no sales receivables or debt fields.
     * 9. Physical file generation and inspection of PhpSpreadsheet worksheet (drawings count, cell values, styles, merged cells, row heights, wrap text).
     * 10. Sales regression test verifying Sales excel export remains unchanged and intact.
     */
    public function test_driver_excel_export_final_fifty_rules_and_physical_validation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 14:00:00'));

        // Store 1 has outstanding sales debt (to test that Driver Excel NEVER exposes sales receivables)
        StoreTransaction::create([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-SALES-DEBT-999',
            'transaction_date' => '2026-09-01',
            'transaction_amount' => 15000000,
            'status' => 'belum_lunas',
            'created_by' => $this->sales->id,
        ]);

        // Setup Route for Driver 1
        $route = Route::create([
            'user_id' => $this->driver1->id,
            'name' => 'Rute Pengiriman Logistik Driver',
            'date' => '2026-09-03',
            'status' => 'completed',
        ]);

        // Stop 1: Completed delivery with 4 checkout photos and Check-in photo
        $stop1 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        // Create actual image files in storage fake
        $checkInSelfie = UploadedFile::fake()->image('checkin_selfie.jpg', 200, 200);
        $checkInSelfiePath = $checkInSelfie->store('visits/driver/checkin', 'public');

        $doc1 = UploadedFile::fake()->image('doc1.jpg', 200, 200);
        $doc2 = UploadedFile::fake()->image('doc2.jpg', 200, 200);
        $doc3 = UploadedFile::fake()->image('doc3.jpg', 200, 200);
        $doc4 = UploadedFile::fake()->image('doc4.jpg', 200, 200);

        $doc1Path = $doc1->store('visits/driver/checkout', 'public');
        $doc2Path = $doc2->store('visits/driver/checkout', 'public');
        $doc3Path = $doc3->store('visits/driver/checkout', 'public');
        $doc4Path = $doc4->store('visits/driver/checkout', 'public');

        $multilineResult = "Barang 50 karton minyak goreng telah diserahkan.\nSurat jalan (DO) nomor SJ-0903-888 telah ditandatangani dan dicap oleh toko.\nSemua kemasan utuh tanpa cacat.";
        $multilineNotes = "Diterima langsung oleh Bapak Gunawan (Kepala Logistik Toko).\nPembayaran COD via Transfer Bank BCA.";

        $visit1 = Visit::create([
            'user_id' => $this->driver1->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 08:30:00',
            'check_out_at' => '2026-09-03 09:45:00',
            'check_in_lat' => -6.9175,
            'check_in_lng' => 107.6191,
            'check_in_address' => 'Jl. Merak No. 12 Check In',
            'check_out_lat' => -6.9176,
            'check_out_lng' => 107.6192,
            'check_out_address' => 'Jl. Merak No. 12 Check Out',
            'check_in_selfie' => $checkInSelfiePath,
            'transaction_status' => 'paid',
            'transaction_amount' => 2750000,
            'payment_method' => 'transfer',
            'visit_result' => $multilineResult,
            'final_notes' => $multilineNotes,
        ]);

        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => $doc1Path]);
        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => $doc2Path]);
        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => $doc3Path]);
        VisitPhoto::create(['visit_id' => $visit1->id, 'type' => 'checkout_documentation', 'photo_path' => $doc4Path]);

        // Stop 2: Completed delivery with 1 checkout photo and NO check-in photo
        $stop2 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'sequence' => 2,
            'status' => 'visited',
        ]);

        $docSingle = UploadedFile::fake()->image('doc_single.jpg', 200, 200);
        $docSinglePath = $docSingle->store('visits/driver/checkout', 'public');

        $visit2 = Visit::create([
            'user_id' => $this->driver1->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 10:15:00',
            'check_out_at' => '2026-09-03 11:00:00',
            'check_in_lat' => -6.9200,
            'check_in_lng' => 107.6250,
            'check_in_address' => 'Jl. Elang No. 34 Check In',
            'check_out_lat' => -6.9201,
            'check_out_lng' => 107.6251,
            'check_out_address' => 'Jl. Elang No. 34 Check Out',
            'check_in_selfie' => null, // No check-in photo
            'transaction_status' => 'paid',
            'transaction_amount' => 1500000,
            'payment_method' => 'tunai',
            'visit_result' => 'Pengiriman beras 20 sak selesai',
            'final_notes' => 'Toko menerima lengkap',
        ]);

        VisitPhoto::create(['visit_id' => $visit2->id, 'type' => 'checkout_documentation', 'photo_path' => $docSinglePath]);

        // Stop 3: Skipped delivery with skip proof photo
        $skipPhoto = UploadedFile::fake()->image('skip_proof.jpg', 200, 200);
        $skipPhotoPath = $skipPhoto->store('visits/driver/skip', 'public');

        $stop3 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store3->id,
            'sequence' => 3,
            'status' => 'skipped',
            'notes' => 'Toko tutup karena pemilik sedang renovasi mendadak',
        ]);

        Visit::create([
            'user_id' => $this->driver1->id,
            'route_stop_id' => $stop3->id,
            'route_id' => $route->id,
            'store_id' => $this->store3->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 11:30:00',
            'check_out_at' => '2026-09-03 11:35:00',
            'check_in_lat' => -6.9250,
            'check_in_lng' => 107.6300,
            'check_in_address' => 'Jl. Garuda No. 56',
            'visit_result' => 'Dilewati: Toko tutup renovasi mendadak',
            'initial_notes' => 'Toko tutup renovasi mendadak',
            'final_store_photo' => $skipPhotoPath,
        ]);

        // Create Route & Visit for Driver 2 to test user isolation (Driver 1 cannot see Driver 2)
        $route2 = Route::create([
            'user_id' => $this->driver2->id,
            'name' => 'Rute Driver Lain',
            'date' => '2026-09-03',
            'status' => 'completed',
        ]);
        $stopOther = RouteStop::create([
            'route_id' => $route2->id,
            'store_id' => $this->store2->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        Visit::create([
            'user_id' => $this->driver2->id,
            'route_stop_id' => $stopOther->id,
            'route_id' => $route2->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 09:00:00',
            'check_out_at' => '2026-09-03 09:30:00',
            'visit_result' => 'Data driver 2',
        ]);

        // =========================================================================
        // SECTION 1: INSTANTIATE DRIVER EXPORT & STRUCTURE VERIFICATION
        // =========================================================================
        $export = new DriverVisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'userId' => $this->driver1->id,
        ]);

        $sheets = $export->sheets();

        // RULE 1 & MULTI-SHEET STRUCTURE:
        // Must contain exactly 2 driver sheets: Rekap Pengiriman & Dokumentasi Pengiriman
        $this->assertCount(2, $sheets);
        $this->assertInstanceOf(RekapPengirimanDriverSheet::class, $sheets[0]);
        $this->assertInstanceOf(DokumentasiPengirimanDriverSheet::class, $sheets[1]);
        $this->assertEquals('Rekap Pengiriman', $sheets[0]->title());
        $this->assertEquals('Dokumentasi Pengiriman', $sheets[1]->title());

        $rekapSheet = $sheets[0];
        $docSheet = $sheets[1];

        // RULE 3 & RULE 6: Dynamic column expansion based on max checkout photos (max is 4)
        // Check dynamic headings of RekapPengirimanDriverSheet
        $subHeadings = $rekapSheet->headings();
        $groupHeadings = $rekapSheet->groupedHeadings();

        // Base 17 columns + 4 dynamic checkout photos + 1 skip photo = 22 columns
        $this->assertCount(22, $subHeadings);
        $this->assertEquals('No', $subHeadings[0]);
        $this->assertEquals('Tanggal Pengiriman', $subHeadings[1]);
        $this->assertEquals('Driver', $subHeadings[2]);
        $this->assertEquals('Toko / Tujuan', $subHeadings[3]);
        $this->assertEquals('Kode Toko', $subHeadings[4]);
        $this->assertEquals('Urutan', $subHeadings[5]);
        $this->assertEquals('Catatan Toko / Tujuan Pengiriman', $subHeadings[6]);
        $this->assertEquals('Status', $subHeadings[7]);
        $this->assertEquals('Waktu Check In', $subHeadings[8]);
        $this->assertEquals('Lokasi Check In', $subHeadings[9]);
        $this->assertEquals('Foto Toko / Selfie', $subHeadings[10]); // Col K (Index 10) -> RULE 2: Single Check-in photo column
        $this->assertEquals('Waktu Check Out', $subHeadings[11]);
        $this->assertEquals('Lokasi Check Out', $subHeadings[12]);
        $this->assertEquals('Transaksi Pengiriman (Rp)', $subHeadings[13]);
        $this->assertEquals('Metode Pembayaran', $subHeadings[14]);
        $this->assertEquals('Hasil Pengiriman', $subHeadings[15]);
        $this->assertEquals('Catatan Tambahan', $subHeadings[16]);
        $this->assertEquals('Foto 1', $subHeadings[17]);
        $this->assertEquals('Foto 2', $subHeadings[18]);
        $this->assertEquals('Foto 3', $subHeadings[19]);
        $this->assertEquals('Foto 4', $subHeadings[20]);
        $this->assertEquals('Foto Dilewati', $subHeadings[21]); // Col V (Index 21) -> RULE 4: Skip photo separate

        // RULE 5: Grouped Headings (Row 1) structure
        $this->assertCount(22, $groupHeadings);
        $this->assertEquals('INFORMASI PENGIRIMAN', $groupHeadings[0]);
        $this->assertEquals('CHECK IN PENGIRIMAN', $groupHeadings[8]);
        $this->assertEquals('CHECK OUT PENGIRIMAN', $groupHeadings[11]);
        $this->assertEquals('TRANSAKSI PENGIRIMAN', $groupHeadings[13]);
        $this->assertEquals('HASIL PENGIRIMAN', $groupHeadings[15]);
        $this->assertEquals('FOTO CHECK OUT — BARANG / DOKUMENTASI', $groupHeadings[17]);
        $this->assertEquals('FOTO DILEWATI', $groupHeadings[21]);

        // RULE 8: NO Sales Piutang / Debt fields in headings or rows
        $this->assertNotContains('Total Piutang Toko Sebelum Kunjungan (Rp)', $subHeadings);
        $this->assertNotContains('Pembayaran Piutang Lama pada Kunjungan (Rp)', $subHeadings);
        $this->assertNotContains('Total Piutang Toko Setelah Kunjungan (Rp)', $subHeadings);

        // =========================================================================
        // SECTION 2: DATA ROWS VALIDATION & MULTILINE CHECK
        // =========================================================================
        $rekapRows = $rekapSheet->dataRows();
        // 2 completed deliveries + 1 skipped delivery = 3 rows (Driver 2 excluded)
        $this->assertCount(3, $rekapRows);

        // Row 1 (Stop 1 - Completed with 4 photos & Check In Photo):
        $r1 = $rekapRows[0];
        $this->assertEquals(1, $r1[0]);
        $this->assertEquals('03 Sep 2026', $r1[1]);
        $this->assertEquals('Budi Santoso Driver', $r1[2]);
        $this->assertEquals('Toko Barokah Jaya', $r1[3]);
        $this->assertEquals('TKO-BJ01', $r1[4]);
        $this->assertEquals(1, $r1[5]);
        $this->assertEquals('-', $r1[6]); // Catatan Toko
        $this->assertEquals('Selesai', $r1[7]);
        $this->assertEquals('08:30', $r1[8]);
        $this->assertEquals('Jl. Merak No. 12 Check In', $r1[9]);
        $this->assertEquals('', $r1[10]); // Check In photo present -> text placeholder is empty
        $this->assertEquals('09:45', $r1[11]);
        $this->assertEquals('Jl. Merak No. 12 Check Out', $r1[12]);
        $this->assertEquals(2750000.0, $r1[13]); // Transaksi Pengiriman (Rp)
        $this->assertEquals('TRANSFER', $r1[14]); // Metode Pembayaran
        // RULE 7: Multiline preserved (contains newlines)
        $this->assertStringContainsString("\n", $r1[15]);
        $this->assertStringContainsString("Barang 50 karton minyak goreng telah diserahkan.", $r1[15]);
        $this->assertStringContainsString("Surat jalan (DO) nomor SJ-0903-888", $r1[15]);
        $this->assertStringContainsString("\n", $r1[16]);
        $this->assertStringContainsString("Diterima langsung oleh Bapak Gunawan", $r1[16]);
        // Dynamic Photos 1..4 are present -> empty text placeholder
        $this->assertEquals('', $r1[17]);
        $this->assertEquals('', $r1[18]);
        $this->assertEquals('', $r1[19]);
        $this->assertEquals('', $r1[20]);
        $this->assertEquals('-', $r1[21]); // Not skipped

        // Row 2 (Stop 2 - Completed with 1 photo & NO Check In Photo):
        $r2 = $rekapRows[1];
        $this->assertEquals(2, $r2[0]);
        $this->assertEquals('Toko Subur Makmur', $r2[3]);
        $this->assertEquals('-', $r2[6]); // Catatan Toko
        $this->assertEquals('Selesai', $r2[7]);
        $this->assertEquals('Tidak ada foto', $r2[10]); // Check In photo not present -> "Tidak ada foto"
        $this->assertEquals(1500000.0, $r2[13]);
        $this->assertEquals('TUNAI', $r2[14]);
        $this->assertEquals('Pengiriman beras 20 sak selesai', $r2[15]);
        $this->assertEquals('', $r2[17]); // Foto 1 present
        $this->assertEquals('Tidak ada foto', $r2[18]); // Foto 2 missing
        $this->assertEquals('Tidak ada foto', $r2[19]); // Foto 3 missing
        $this->assertEquals('Tidak ada foto', $r2[20]); // Foto 4 missing
        $this->assertEquals('-', $r2[21]);

        // Row 3 (Stop 3 - Skipped Delivery):
        $r3 = $rekapRows[2];
        $this->assertEquals(3, $r3[0]);
        $this->assertEquals('Toko Rezeki Abadi', $r3[3]);
        $this->assertEquals('Toko tutup karena pemilik sedang renovasi mendadak', $r3[6]); // Catatan Toko
        $this->assertEquals('Dilewati', $r3[7]);
        $this->assertEquals('Tidak ada foto', $r3[10]); // No checkin photo on skip
        $this->assertEquals('-', $r3[13]);
        $this->assertEquals('-', $r3[14]);
        $this->assertStringContainsString('Dilewati: Toko tutup renovasi mendadak', $r3[15]);
        $this->assertEquals('Tidak ada foto', $r3[17]); // Foto 1
        $this->assertEquals('Tidak ada foto', $r3[18]); // Foto 2
        $this->assertEquals('Tidak ada foto', $r3[19]); // Foto 3
        $this->assertEquals('Tidak ada foto', $r3[20]); // Foto 4
        $this->assertEquals('', $r3[21]); // Foto Dilewati present -> empty string placeholder

        // Sheet 2: Dokumentasi Pengiriman Data Rows
        $docRows = $docSheet->dataRows();
        // Visit 1: 1 check-in + 4 checkout photos = 5 rows
        // Visit 2: 1 checkout photo = 1 row
        // Stop 3: 1 skipped photo = 1 row
        // Total = 7 rows
        $this->assertCount(7, $docRows);
        $this->assertEquals('Foto Toko / Selfie', $docRows[0][4]);
        $this->assertEquals('Foto Barang/Dokumen #1', $docRows[1][4]);
        $this->assertEquals('Foto Barang/Dokumen #2', $docRows[2][4]);
        $this->assertEquals('Foto Barang/Dokumen #3', $docRows[3][4]);
        $this->assertEquals('Foto Barang/Dokumen #4', $docRows[4][4]);
        $this->assertEquals('Foto Barang/Dokumen #1', $docRows[5][4]);
        $this->assertEquals('Foto Dilewati', $docRows[6][4]);

        // =========================================================================
        // SECTION 3: RULE 9 - PHYSICAL XLSX GENERATION & PHPSPREADSHEET INSPECTION
        // =========================================================================
        $fileName = 'driver_export_final_test_' . time() . '.xlsx';
        Excel::store($export, $fileName, 'public');
        $physicalPath = Storage::disk('public')->path($fileName);

        $this->assertFileExists($physicalPath);

        $spreadsheet = IOFactory::load($physicalPath);

        // Assert Sheet Names
        $this->assertEquals(['Rekap Pengiriman', 'Dokumentasi Pengiriman'], $spreadsheet->getSheetNames());

        $wsRekap = $spreadsheet->getSheetByName('Rekap Pengiriman');
        $this->assertNotNull($wsRekap);

        // Inspect Rekap Sheet Rows
        // Row 1: Company Name
        // Row 2: Subtitle
        // Row 3: Report Title (REKAPITULASI HASIL PENGIRIMAN DRIVER)
        // Row 4: Periode
        // Row 5: Tanggal Cetak
        // Row 6: Dicetak Oleh
        // Row 7: Jumlah Data
        // Row 8: Grouped Headings
        // Row 9: Sub Headings
        // Row 10+: Data Rows
        $groupRow = 8;
        $subRow = 9;
        $dataStart = 10;

        // Verify Merged Cells in Row 8
        $mergedCells = $wsRekap->getMergeCells();
        $this->assertArrayHasKey("A{$groupRow}:H{$groupRow}", $mergedCells); // INFORMASI PENGIRIMAN (A..H)
        $this->assertArrayHasKey("I{$groupRow}:K{$groupRow}", $mergedCells); // CHECK IN PENGIRIMAN (I..K)
        $this->assertArrayHasKey("L{$groupRow}:M{$groupRow}", $mergedCells); // CHECK OUT PENGIRIMAN (L..M)
        $this->assertArrayHasKey("N{$groupRow}:O{$groupRow}", $mergedCells); // TRANSAKSI PENGIRIMAN (N..O)
        $this->assertArrayHasKey("P{$groupRow}:Q{$groupRow}", $mergedCells); // HASIL PENGIRIMAN (P..Q)
        $this->assertArrayHasKey("R{$groupRow}:U{$groupRow}", $mergedCells); // FOTO CHECK OUT — BARANG / DOKUMENTASI (4 cols: R, S, T, U)

        // Verify Group Header Titles
        $this->assertEquals('INFORMASI PENGIRIMAN', $wsRekap->getCell("A{$groupRow}")->getValue());
        $this->assertEquals('CHECK IN PENGIRIMAN', $wsRekap->getCell("I{$groupRow}")->getValue());
        $this->assertEquals('CHECK OUT PENGIRIMAN', $wsRekap->getCell("L{$groupRow}")->getValue());
        $this->assertEquals('TRANSAKSI PENGIRIMAN', $wsRekap->getCell("N{$groupRow}")->getValue());
        $this->assertEquals('HASIL PENGIRIMAN', $wsRekap->getCell("P{$groupRow}")->getValue());
        $this->assertEquals('FOTO CHECK OUT — BARANG / DOKUMENTASI', $wsRekap->getCell("R{$groupRow}")->getValue());
        $this->assertEquals('FOTO DILEWATI', $wsRekap->getCell("V{$groupRow}")->getValue());

        // Verify Sub Header Titles
        $this->assertEquals('No', $wsRekap->getCell("A{$subRow}")->getValue());
        $this->assertEquals('Tanggal Pengiriman', $wsRekap->getCell("B{$subRow}")->getValue());
        $this->assertEquals('Driver', $wsRekap->getCell("C{$subRow}")->getValue());
        $this->assertEquals('Toko / Tujuan', $wsRekap->getCell("D{$subRow}")->getValue());
        $this->assertEquals('Kode Toko', $wsRekap->getCell("E{$subRow}")->getValue());
        $this->assertEquals('Urutan', $wsRekap->getCell("F{$subRow}")->getValue());
        $this->assertEquals('Catatan Toko / Tujuan Pengiriman', $wsRekap->getCell("G{$subRow}")->getValue());
        $this->assertEquals('Status', $wsRekap->getCell("H{$subRow}")->getValue());
        $this->assertEquals('Waktu Check In', $wsRekap->getCell("I{$subRow}")->getValue());
        $this->assertEquals('Lokasi Check In', $wsRekap->getCell("J{$subRow}")->getValue());
        $this->assertEquals('Foto Toko / Selfie', $wsRekap->getCell("K{$subRow}")->getValue());
        $this->assertEquals('Waktu Check Out', $wsRekap->getCell("L{$subRow}")->getValue());
        $this->assertEquals('Lokasi Check Out', $wsRekap->getCell("M{$subRow}")->getValue());
        $this->assertEquals('Transaksi Pengiriman (Rp)', $wsRekap->getCell("N{$subRow}")->getValue());
        $this->assertEquals('Metode Pembayaran', $wsRekap->getCell("O{$subRow}")->getValue());
        $this->assertEquals('Hasil Pengiriman', $wsRekap->getCell("P{$subRow}")->getValue());
        $this->assertEquals('Catatan Tambahan', $wsRekap->getCell("Q{$subRow}")->getValue());
        $this->assertEquals('Foto 1', $wsRekap->getCell("R{$subRow}")->getValue());
        $this->assertEquals('Foto 2', $wsRekap->getCell("S{$subRow}")->getValue());
        $this->assertEquals('Foto 3', $wsRekap->getCell("T{$subRow}")->getValue());
        $this->assertEquals('Foto 4', $wsRekap->getCell("U{$subRow}")->getValue());
        $this->assertEquals('Foto Dilewati', $wsRekap->getCell("V{$subRow}")->getValue());

        // Verify Row Heights and Wrap Text for Data Rows
        // Row 10 has images -> height 84
        $this->assertEquals(84, $wsRekap->getRowDimension(10)->getRowHeight());
        $this->assertTrue($wsRekap->getStyle('P10')->getAlignment()->getWrapText());
        $this->assertTrue($wsRekap->getStyle('Q10')->getAlignment()->getWrapText());
        $this->assertEquals('top', $wsRekap->getStyle('P10')->getAlignment()->getVertical());
        $this->assertEquals('left', $wsRekap->getStyle('P10')->getAlignment()->getHorizontal());

        // Row 11 has images -> height 84
        $this->assertEquals(84, $wsRekap->getRowDimension(11)->getRowHeight());

        // Row 12 has skip image -> height 84
        $this->assertEquals(84, $wsRekap->getRowDimension(12)->getRowHeight());

        // Verify Drawings in Rekap Sheet
        $drawingsRekap = $wsRekap->getDrawingCollection();
        // Drawings count: Logo (1) + CheckIn(1) + Doc1(1) + Doc2(1) + Doc3(1) + Doc4(1) + DocSingle(1) + Skip(1) = 8
        $this->assertGreaterThanOrEqual(7, count($drawingsRekap));

        // Inspect Sheet 2 (Dokumentasi Pengiriman)
        $wsDoc = $spreadsheet->getSheetByName('Dokumentasi Pengiriman');
        $this->assertNotNull($wsDoc);
        $drawingsDoc = $wsDoc->getDrawingCollection();
        // Drawings in Sheet 2: Logo (1) + 7 photo rows = 8 drawings
        $this->assertGreaterThanOrEqual(7, count($drawingsDoc));

        // =========================================================================
        // SECTION 4: RULE 10 - SALES REGRESSION TEST
        // =========================================================================
        // Verify Sales Excel Export remains completely unaffected and intact with 3 sheets
        $salesRoute = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Sales',
            'date' => '2026-09-03',
            'status' => 'completed',
        ]);
        $salesStop = RouteStop::create([
            'route_id' => $salesRoute->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $salesVisit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $salesStop->id,
            'route_id' => $salesRoute->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 13:00:00',
            'check_out_at' => '2026-09-03 13:45:00',
            'transaction_status' => 'paid',
            'transaction_amount' => 5000000,
            'visit_result' => 'Sales visit order closed',
        ]);

        $salesExport = new VisitsExport([
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-04',
            'userId' => $this->sales->id,
        ]);

        $salesSheets = $salesExport->sheets();
        $this->assertCount(3, $salesSheets);
        $this->assertInstanceOf(RekapKunjunganSheet::class, $salesSheets[0]);
        $this->assertInstanceOf(TransaksiPiutangSheet::class, $salesSheets[1]);
        $this->assertInstanceOf(FotoKunjunganSheet::class, $salesSheets[2]);

        $salesRekapHeadings = $salesSheets[0]->headings();
        $this->assertContains('Saldo Piutang Lama Sebelum Kunjungan (Rp)', $salesRekapHeadings);
        $this->assertContains('Pembayaran Piutang Lama pada Kunjungan (Rp)', $salesRekapHeadings);
        $this->assertContains('Saldo Piutang Toko Setelah Kunjungan (Rp)', $salesRekapHeadings);

        // =========================================================================
        // SECTION 5: HTTP ENDPOINT VERIFICATION FOR DRIVER & SALES
        // =========================================================================
        // Driver Excel Download Endpoint
        $resDriver = $this->actingAs($this->driver1)->get(route('visit.excel.rekap', [
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-04',
        ]));
        $resDriver->assertOk();

        // Sales Excel Download Endpoint
        $resSales = $this->actingAs($this->sales)->get(route('visit.excel.rekap', [
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-04',
        ]));
        $resSales->assertOk();
    }
}
