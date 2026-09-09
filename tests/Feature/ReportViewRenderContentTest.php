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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportViewRenderContentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;
    protected User $driver;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->admin = User::factory()->create(['name' => 'Super Administrator']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['name' => 'Sales Ahmad']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Bambang']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Makmur Sejahtera',
            'code' => 'TKO-999',
            'address' => 'Jl. Merdeka No. 45',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    public function test_driver_visits_pdf_view_rendered_html_content(): void
    {
        $route = Route::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Kirim Driver',
            'date' => '2026-09-07',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
            'notes' => 'Catatan rute kirim',
        ]);

        $visit = Visit::create([
            'user_id' => $this->driver->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 08:30:00',
            'check_out_at' => '2026-09-07 09:15:00',
            'initial_notes' => 'Tiba tepat waktu',
            'visit_result' => 'Barang diterima 5 karung',
            'final_notes' => 'Sudah diverifikasi',
            'transaction_amount' => 750000,
            'transaction_status' => 'paid',
            'payment_method' => 'tunai',
        ]);

        $items = collect([$visit]);

        $view = view('reports.pdf.driver-visits', [
            'title' => 'LAPORAN HASIL PENGIRIMAN DRIVER',
            'items' => $items,
            'skipped' => collect(),
            'periodLabel' => '01 Sep 2026 - 07 Sep 2026',
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-07',
            'generatedAt' => '07 Sep 2026, 14:00',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'selectedUserLabel' => 'Driver Bambang',
            'statusLabel' => 'Selesai',
            'trxStatusLabel' => 'COD/Dibayar',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('LAPORAN HASIL PENGIRIMAN DRIVER', $view);
        $this->assertStringContainsString('Driver Bambang', $view);
        $this->assertStringContainsString('Super Administrator', $view);
        $this->assertStringContainsString('Admin', $view);
        $this->assertStringContainsString('Toko Makmur Sejahtera', $view);
        $this->assertStringContainsString('TKO-999', $view);
        $this->assertStringContainsString('Barang diterima 5 karung', $view);
        $this->assertStringContainsString('750.000', $view);
        $this->assertStringContainsString('TUNAI', $view);
    }

    public function test_sales_visits_pdf_view_rendered_html_content(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Sales',
            'date' => '2026-09-07',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
            'notes' => 'Follow up order',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 10:00:00',
            'check_out_at' => '2026-09-07 10:45:00',
            'initial_notes' => 'Toko buka',
            'visit_result' => 'Deal transaksi baru + bayar piutang',
        ]);

        $newTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'reference_id' => $visit->id,
            'reference_type' => 'visit',
            'transaction_code' => 'TRX-20260907-7777',
            'transaction_date' => '2026-09-07',
            'transaction_amount' => 2500000,
            'status' => 'partial',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTrx->id,
            'source_id' => $visit->id,
            'payment_date' => '2026-09-07',
            'amount' => 1000000,
            'payment_method' => 'tunai',
            'source' => 'initial_payment',
            'notes' => 'Pembayaran awal DP',
        ]);

        $oldTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260801-0001',
            'transaction_date' => '2026-08-01',
            'transaction_amount' => 3000000,
            'status' => 'partial',
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'source_id' => $visit->id,
            'payment_date' => '2026-09-07',
            'amount' => 2000000,
            'payment_method' => 'transfer',
            'source' => 'receivable',
            'notes' => 'Cicilan piutang',
        ]);

        $visit->refresh();
        $visit->load(['transactions.payments', 'payments.transaction.payments', 'user', 'store', 'route', 'routeStop']);
        $items = collect([$visit]);

        $view = view('reports.pdf.visits', [
            'title' => 'LAPORAN KUNJUNGAN SALES',
            'items' => $items,
            'skipped' => collect(),
            'periodLabel' => '01 Sep 2026 - 07 Sep 2026',
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-07',
            'generatedAt' => '07 Sep 2026, 14:00',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Super Admin',
            'selectedUserLabel' => 'Sales Ahmad',
            'statusLabel' => 'Selesai',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        // 1. Header Metadata
        $this->assertStringContainsString('LAPORAN KUNJUNGAN SALES', $view);
        $this->assertStringContainsString('Sales Ahmad', $view);
        $this->assertStringContainsString('Super Administrator', $view);
        $this->assertStringContainsString('Super Admin', $view);

        // 2. Sections
        $this->assertStringContainsString('TRANSAKSI BARU', $view);
        $this->assertStringContainsString('PEMBAYARAN PIUTANG LAMA', $view);
        $this->assertStringContainsString('RINGKASAN PIUTANG TOKO', $view);

        // 3. Data Transaksi Baru
        $this->assertStringContainsString('TRX-20260907-7777', $view);
        $this->assertStringContainsString('2.500.000', $view);
        $this->assertStringContainsString('1.000.000', $view);
        $this->assertStringContainsString('1.500.000', $view);

        // 4. Data Pembayaran Piutang Lama
        $this->assertStringContainsString('TRX-20260801-0001', $view);
        $this->assertStringContainsString('2.000.000', $view);
        $this->assertStringContainsString('TRANSFER', $view);
    }

    public function test_sales_activities_web_and_pdf_view_rendered_html_content(): void
    {
        $this->actingAs($this->admin);

        $res = StoreReceivableService::getSalesActivitiesPerformanceData(collect([$this->sales]), '2026-09-01', '2026-09-07');

        // Test Web View
        $webView = view('reports.sales-activities', [
            'performance' => $res['performance'],
            'summary' => $res['summary'],
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-07',
            'periodLabel' => '01 Sep 2026 - 07 Sep 2026',
            'userId' => null,
            'salesFilterUsers' => collect([$this->sales]),
        ])->render();

        // 1. Check Filter Toolbar classes and markup
        $this->assertStringContainsString('sw-report-filter-sales-activities', $webView);
        $this->assertStringContainsString('grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end', $webView);
        $this->assertStringContainsString('name="from_date"', $webView);
        $this->assertStringContainsString('name="to_date"', $webView);
        $this->assertStringContainsString('name="user_id"', $webView);
        $this->assertStringContainsString('Filter', $webView);

        // 2. Check Explicit Financial KPI Labels
        $this->assertStringContainsString('Total Nilai Transaksi Baru', $webView);
        $this->assertStringContainsString('Uang Masuk Transaksi Baru', $webView);
        $this->assertStringContainsString('Pembayaran Piutang Lama', $webView);
        $this->assertStringContainsString('Total Uang Masuk', $webView);

        // 3. Check Table Headers
        $this->assertStringContainsString('Nilai Transaksi Baru', $webView);
        $this->assertStringContainsString('Uang Masuk Tx Baru', $webView);
        $this->assertStringContainsString('Bayar Piutang Lama', $webView);
        $this->assertStringContainsString('Total Uang Masuk', $webView);

        // Test PDF View
        $pdfView = view('reports.pdf.sales-activities', [
            'title' => 'LAPORAN PERFORMA AKTIVITAS SALES',
            'items' => collect([$this->sales]),
            'salesData' => [$this->sales->id => $res['performance'][0]],
            'periodLabel' => '01 Sep 2026 - 07 Sep 2026',
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-07',
            'generatedAt' => '07 Sep 2026, 14:00',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('RINGKASAN PERFORMA AKTIVITAS SALES', $pdfView);
        $this->assertStringContainsString('Total Nilai Transaksi Baru', $pdfView);
        $this->assertStringContainsString('Uang Masuk Transaksi Baru', $pdfView);
        $this->assertStringContainsString('Pembayaran Piutang Lama', $pdfView);
        $this->assertStringContainsString('Total Uang Masuk', $pdfView);
    }
}
