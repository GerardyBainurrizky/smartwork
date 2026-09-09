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
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SalesVisitReportsTwoCoreIssuesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private Store $storeA;
    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->admin = User::where('username', 'admin')->first() ?? User::factory()->create(['name' => 'Admin Test', 'status' => 'active']);
        if (! $this->admin->hasRole('admin')) {
            $this->admin->assignRole('admin');
        }

        $this->superAdmin = User::where('username', 'superadmin')->first() ?? User::factory()->create(['name' => 'Super Admin Test', 'status' => 'active']);
        if (! $this->superAdmin->hasRole('super-admin')) {
            $this->superAdmin->assignRole('super-admin');
        }

        $this->sales = User::factory()->create(['name' => 'Sales Budi', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->storeA = Store::create([
            'name' => 'Toko Makmur Sejahtera',
            'code' => 'TMS-001',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko Berkah Abadi',
            'code' => 'TBA-002',
            'address' => 'Jl. Asia Afrika No. 20',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    /**
     * MASALAH 1: Validasi Perhitungan & Ledger Ringkasan Piutang Toko
     * CASE 1: Multi transaksi piutang lama, Saldo Sebelum = total outstanding sebelum pembayaran.
     * CASE 2: Toko bayar sebagian piutang, Saldo Sebelum - Pembayaran = Saldo Setelah.
     * CASE 3: Toko melunasi salah satu transaksi, sisa transaksi menjadi Rp 0.
     * CASE 4: Transaksi baru dengan pembayaran awal, Nilai Transaksi Baru - Pembayaran Awal = Sisa Piutang Baru.
     * CASE 5: Kombinasi Piutang Lama + Transaksi Baru tanpa double counting.
     * CASE 6: Angka pada PDF sama persis dengan perhitungan ledger.
     */
    public function test_masalah_1_ringkasan_piutang_toko_cases_1_to_6(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00'));

        // Transaksi lama 1: TRX-20260901-0006 = 8.650.000
        $trx1 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260901-0006',
            'transaction_amount' => 8650000,
            'transaction_date' => '2026-09-01',
        ], $this->sales);

        // Transaksi lama 2: TRX-20260902-0005 = 700.000
        $trx2 = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260902-0005',
            'transaction_amount' => 700000,
            'transaction_date' => '2026-09-02',
        ], $this->sales);

        // Sebelum kunjungan: Saldo Piutang Toko A = 8.650.000 + 700.000 = 9.350.000
        $this->assertEquals(9350000.0, (float) StoreReceivableService::balanceForStore($this->storeA->id));

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Piutang',
            'date' => '2026-09-03',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => Carbon::parse('2026-09-03 10:00:00'),
            'check_out_at' => Carbon::parse('2026-09-03 11:00:00'),
            'transaction_status' => 'mixed',
            'visit_result' => 'Pembayaran piutang lama dan transaksi baru',
        ]);

        // Bayar TRX 1 sebagian: 550.000 (sisa 8.100.000)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx1->id,
            'amount' => 550000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Bayar TRX 2 lunas: 700.000 (sisa 0)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $trx2->id,
            'amount' => 700000,
            'payment_method' => 'tunai',
            'source' => 'visit',
            'source_id' => $visit->id,
        ], $this->sales);

        // Transaksi Baru: 1.000.000 dengan DP 500.000 (sisa 500.000)
        $newTrx = StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-20260903-0001',
            'transaction_amount' => 1000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'transaction_date' => '2026-09-03',
        ], $this->sales);

        $visit->refresh();
        $visit->load(['payments.transaction.payments', 'transactions.payments', 'store', 'user']);

        // Service ledger calculation check
        $summary = StoreReceivableService::getVisitReceivableSummary($visit);

        // CASE 1 & CASE 5: Saldo Sebelum Kunjungan = Rp 9.350.000 (8.650.000 + 700.000)
        $this->assertEquals(9350000.0, $summary['balance_before']);

        // CASE 2: Pembayaran Piutang Lama = Rp 1.250.000 (550.000 + 700.000)
        $this->assertEquals(1250000.0, $summary['old_debt_paid']);

        // CASE 3: TRX 2 Lunas (remaining = 0)
        $trx2->refresh();
        $this->assertEquals(0.0, $trx2->remaining_amount);
        $this->assertEquals(StoreTransaction::STATUS_LUNAS, $trx2->status);

        // CASE 4: Transaksi baru 1.000.000 - DP 500.000 = Sisa 500.000
        $this->assertEquals(1000000.0, $summary['new_tx_total']);
        $this->assertEquals(500000.0, $summary['new_tx_initial_paid']);
        $this->assertEquals(500000.0, $summary['new_tx_remaining']);

        // CASE 5: Saldo Setelah = 9.350.000 - 1.250.000 + 500.000 = 8.600.000
        $this->assertEquals(8600000.0, $summary['balance_after']);
        $this->assertEquals(8600000.0, (float) StoreReceivableService::balanceForStore($this->storeA->id));

        // CASE 6: PDF Rendering check
        $pdfHtml = view('reports.pdf.visits', [
            'title' => 'LAPORAN KUNJUNGAN SALES',
            'items' => collect([$visit]),
            'skipped' => collect(),
            'periodLabel' => '03 Sep 2026',
            'fromDate' => '2026-09-03',
            'toDate' => '2026-09-03',
            'generatedAt' => '03 Sep 2026, 11:00',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'selectedUserLabel' => $this->sales->name,
            'statusLabel' => 'Semua Status',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('RINGKASAN PIUTANG TOKO', $pdfHtml);
        $this->assertStringContainsString('Saldo Piutang Lama Sebelum Kunjungan', $pdfHtml);
        $this->assertStringContainsString('Rp 9.350.000', $pdfHtml);
        $this->assertStringContainsString('Pembayaran Piutang Lama', $pdfHtml);
        $this->assertStringContainsString('- Rp 1.250.000', $pdfHtml);
        $this->assertStringContainsString('Piutang Baru dari Transaksi Kunjungan', $pdfHtml);
        $this->assertStringContainsString('+ Rp 500.000', $pdfHtml);
        $this->assertStringContainsString('Saldo Piutang Toko Setelah Kunjungan', $pdfHtml);
        $this->assertStringContainsString('Rp 8.600.000', $pdfHtml);
    }

    /**
     * MASALAH 2: Validasi Foto Bukti Toko Dilewati pada PDF dan Excel
     * CASE 1: Visit status DILEWATI + foto skip -> PDF menampilkan foto skip yang benar.
     * CASE 2: Visit status DILEWATI + foto skip -> Excel memasukkan foto skip yang benar.
     * CASE 3: Dua toko dilewati dengan dua foto berbeda -> foto tidak tertukar.
     * CASE 4: Visit Selesai memiliki Foto Check In/Out -> Foto tersebut tidak digunakan sebagai Foto Bukti Dilewati.
     * CASE 5: Visit Dilewati tidak memiliki foto -> Menampilkan "Tidak ada foto".
     */
    public function test_masalah_2_foto_toko_dilewati_cases_1_to_5(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 14:00:00'));

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Test Skip & Selesai',
            'date' => '2026-09-03',
            'status' => 'active',
        ]);

        // Stop 1: Kunjungan Selesai (dengan Selfie In, Out, Storefront)
        $stop1 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $photoIn = UploadedFile::fake()->image('selfie_in.jpg')->store('visits/checkin', 'public');
        $photoStore = UploadedFile::fake()->image('storefront.jpg')->store('visits/storefront', 'public');
        $photoOut = UploadedFile::fake()->image('selfie_out.jpg')->store('visits/checkout', 'public');

        $visitCompleted = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-03 08:00:00',
            'check_out_at' => '2026-09-03 09:00:00',
            'check_in_selfie' => $photoIn,
            'storefront_photo' => $photoStore,
            'check_out_selfie' => $photoOut,
            'visit_result' => 'Kunjungan sukses',
        ]);

        // Stop 2: Kunjungan DILEWATI dengan Foto Skip A (Toko B)
        $photoSkipA = UploadedFile::fake()->image('skip_store_b.jpg')->store('visits/skip', 'public');
        $stop2 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'skipped',
            'notes' => 'Toko tutup istirahat siang',
        ]);
        $visitSkipA = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop2->id,
            'route_id' => $route->id,
            'store_id' => $this->storeB->id,
            'status' => 'completed', // Saved visit record for skip proof
            'initial_notes' => 'Toko tutup istirahat siang',
            'visit_result' => 'Dilewati: Toko tutup istirahat siang',
            'final_store_photo' => $photoSkipA,
            'check_in_at' => '2026-09-03 10:00:00',
            'check_out_at' => '2026-09-03 10:00:00',
        ]);

        // Stop 3: Kunjungan DILEWATI dengan Foto Skip B (Toko C via JSON notes meta)
        $storeC = Store::create([
            'name' => 'Toko Cahaya Rezeki',
            'code' => 'TCR-003',
            'address' => 'Jl. Riau No. 30',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
        $photoSkipB = UploadedFile::fake()->image('skip_store_c.jpg')->store('visits/skip', 'public');
        $stop3 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeC->id,
            'sequence' => 3,
            'status' => 'skipped',
            'notes' => json_encode([
                'reason' => 'Pemilik sedang ke luar kota',
                'photo_path' => $photoSkipB,
            ]),
        ]);

        // Stop 4: Kunjungan DILEWATI tanpa foto (Data lama / tidak ada foto)
        $storeD = Store::create([
            'name' => 'Toko Danau Indah',
            'code' => 'TDI-004',
            'address' => 'Jl. Dago No. 40',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
        $stop4 = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $storeD->id,
            'sequence' => 4,
            'status' => 'skipped',
            'notes' => 'Jalanan menuju toko sedang diperbaiki',
        ]);

        // 1. Validasi PDF
        $stop2->load(['store', 'route.user', 'visit']);
        $stop3->load(['store', 'route.user', 'visit']);
        $stop4->load(['store', 'route.user', 'visit']);
        $skippedStops = collect([$stop2, $stop3, $stop4]);

        $pdfHtml = view('reports.pdf.visits', [
            'title' => 'LAPORAN KUNJUNGAN SALES',
            'items' => collect([$visitCompleted]),
            'skipped' => $skippedStops,
            'periodLabel' => '03 Sep 2026',
            'fromDate' => '2026-09-03',
            'toDate' => '2026-09-03',
            'generatedAt' => '03 Sep 2026, 14:00',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'selectedUserLabel' => 'Semua Sales',
            'statusLabel' => 'Semua Status',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        // CASE 1 & CASE 3: PDF menampilkan foto skip dan tidak tertukar
        $this->assertStringContainsString('KUNJUNGAN DILEWATI (SKIP)', $pdfHtml);
        $this->assertStringContainsString('Foto Bukti Dilewati', $pdfHtml);
        $this->assertStringContainsString($photoSkipA, $pdfHtml);
        $this->assertStringContainsString($photoSkipB, $pdfHtml);

        // CASE 4: Foto Check In/Out kunjungan selesai tidak masuk tabel dilewati
        $this->assertStringNotContainsString("<td>{$photoIn}</td>", $pdfHtml);

        // CASE 5: Toko tanpa foto menampilkan 'Tidak ada foto'
        $this->assertStringContainsString('Tidak ada foto', $pdfHtml);

        // 2. Validasi Excel
        $export = new VisitsExport([
            'fromDate' => '2026-09-03',
            'toDate' => '2026-09-03',
            'userId' => $this->sales->id,
        ]);

        $sheets = $export->sheets();
        $this->assertCount(3, $sheets);

        // Sheet 3: Foto Kunjungan
        $photoRows = $sheets[2]->dataRows();
        $this->assertCount(4, $photoRows); // 1 visit + 3 skipped

        // Row 1 (Selesai): CheckIn, Etalase, CheckOut ada foto, Foto Dilewati 'Tidak ada foto'
        $this->assertEquals('Selesai', $photoRows[0][4]);
        $this->assertEquals('', $photoRows[0][6]);  // Check In
        $this->assertEquals('', $photoRows[0][8]);  // Etalase
        $this->assertEquals('', $photoRows[0][10]); // Check Out
        $this->assertEquals('Tidak ada foto', $photoRows[0][13]); // Dilewati

        // Row 2 (Skip A - Toko B): Dilewati ada foto
        $this->assertEquals('Dilewati', $photoRows[1][4]);
        $this->assertEquals('Toko Berkah Abadi', $photoRows[1][1]);
        $this->assertEquals('Tidak ada foto', $photoRows[1][6]);
        $this->assertEquals('Tidak ada foto', $photoRows[1][8]);
        $this->assertEquals('Tidak ada foto', $photoRows[1][10]);
        $this->assertEquals('', $photoRows[1][13]); // Foto dilewati ter-embed

        // Row 3 (Skip B - Toko C): Dilewati ada foto dari JSON
        $this->assertEquals('Dilewati', $photoRows[2][4]);
        $this->assertEquals('Toko Cahaya Rezeki', $photoRows[2][1]);
        $this->assertEquals('', $photoRows[2][13]); // Foto dilewati ter-embed

        // Row 4 (Skip C - Toko D): Dilewati tanpa foto
        $this->assertEquals('Dilewati', $photoRows[3][4]);
        $this->assertEquals('Toko Danau Indah', $photoRows[3][1]);
        $this->assertEquals('Tidak ada foto', $photoRows[3][13]);
    }

    /**
     * Endpoint Access & Role Verification for Admin and Super Admin
     */
    public function test_admin_and_super_admin_can_export_pdf_and_excel_visits(): void
    {
        // Admin
        $this->actingAs($this->admin);
        $resPdfAdmin = $this->get(route('admin.reports.export-pdf', ['type' => 'visits', 'from_date' => '2026-09-01', 'to_date' => '2026-09-03']));
        $resPdfAdmin->assertOk();
        $this->assertEquals('application/pdf', $resPdfAdmin->headers->get('Content-Type'));

        $resXlsxAdmin = $this->get(route('admin.reports.export-excel', ['type' => 'visits', 'from_date' => '2026-09-01', 'to_date' => '2026-09-03']));
        $resXlsxAdmin->assertOk();

        // Super Admin
        $this->actingAs($this->superAdmin);
        $resPdfSuper = $this->get(route('admin.reports.export-pdf', ['type' => 'visits', 'from_date' => '2026-09-01', 'to_date' => '2026-09-03']));
        $resPdfSuper->assertOk();
        $this->assertEquals('application/pdf', $resPdfSuper->headers->get('Content-Type'));

        $resXlsxSuper = $this->get(route('admin.reports.export-excel', ['type' => 'visits', 'from_date' => '2026-09-01', 'to_date' => '2026-09-03']));
        $resXlsxSuper->assertOk();
    }
}
