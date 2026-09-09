<?php

namespace Tests\Feature;

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

class SalesVisitTransactionsReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $driver;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->sales = User::where('username', 'karyawan')->first();

        $this->driver = User::create([
            'name' => 'Driver Joko',
            'username' => 'driverjoko',
            'email' => 'driverjoko@example.com',
            'phone' => '081299998888',
            'password' => 'password',
            'status' => 'active',
        ]);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'code' => 'TKO-TEST-01',
            'name' => 'Toko Subur Makmur',
            'owner' => 'Haji Subur',
            'type' => 'Toko Pertanian',
            'address' => 'Jl. Raya Pertanian No. 12',
            'city' => 'Bandung',
            'kecamatan' => 'Coblong',
            'province' => 'Jawa Barat',
            'latitude' => -6.9025,
            'longitude' => 107.6125,
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);
    }

    public function test_authorization_only_admin_and_super_admin_can_access(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.transactions'))
            ->assertOk()
            ->assertSee('Laporan Transaksi Kunjungan Sales');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.transactions'))
            ->assertOk()
            ->assertSee('Laporan Transaksi Kunjungan Sales');

        $this->actingAs($this->sales)
            ->get(route('admin.reports.transactions'))
            ->assertRedirect();

        $this->actingAs($this->driver)
            ->get(route('admin.reports.transactions'))
            ->assertRedirect();
    }

    public function test_financial_breakdown_and_zero_double_counting(): void
    {
        $this->actingAs($this->admin);

        // 1. Existing Old Debt Transaction
        $oldTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260901-9999',
            'transaction_date' => '2026-09-01',
            'transaction_amount' => 5000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->admin->id,
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Kunjungan Test',
            'date' => '2026-09-04',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-04 10:00:00',
            'check_out_at' => '2026-09-04 10:30:00',
            'transaction_status' => 'mixed',
            'visit_result' => 'Pemesanan barang & cicil piutang lama',
            'final_notes' => 'Toko membayar tunai.',
        ]);

        // New transaction created during visit: Total 2.000.000, Initial payment 500.000, Remaining 1.500.000
        $newTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260904-0001',
            'transaction_date' => '2026-09-04',
            'transaction_amount' => 2000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTrx->id,
            'amount' => 500000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-04',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal saat transaksi dibuat',
        ]);

        // Old debt payment during visit: 1.250.000 for $oldTrx
        StoreTransactionPayment::create([
            'store_transaction_id' => $oldTrx->id,
            'amount' => 1250000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-04',
            'recorded_by' => $this->sales->id,
            'source' => 'visit',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran piutang melalui kunjungan Sales ' . $this->sales->name,
        ]);

        // Request Web Report
        $response = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-04',
            'to_date' => '2026-09-04',
        ]));

        $response->assertOk();
        $response->assertSee('TRX-20260904-0001');
        $response->assertSee('2.000.000'); // Nilai Transaksi Baru
        $response->assertSee('500.000');   // Pembayaran Transaksi Baru
        $response->assertSee('1.250.000'); // Pembayaran Piutang Lama
        $response->assertSee('1.500.000'); // Piutang Baru dari Transaksi
        $response->assertSee('5.000.000'); // Saldo Piutang Sebelum Kunjungan
        $response->assertSee('5.250.000'); // Saldo Piutang Setelah Kunjungan
        $response->assertSee('Nilai Transaksi Baru (Rp)');
        $response->assertSee('Pembayaran Transaksi Baru (Rp)');
        $response->assertSee('Piutang Baru dari Transaksi (Rp)');
        $response->assertSee('Saldo Piutang Sebelum Kunjungan (Rp)');
        $response->assertSee('Pembayaran Piutang Lama (Rp)');
        $response->assertSee('Saldo Piutang Setelah Kunjungan (Rp)');
        $response->assertSee('Sales Penanggung Jawab');
        $response->assertSee('Total Nilai Transaksi Baru');

        // Test PDF Export
        $pdfResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'transactions',
            'from_date' => '2026-09-04',
            'to_date' => '2026-09-04',
        ]));
        $pdfResp->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResp->headers->get('content-type'));

        // Test Excel Export
        $excelExport = new \App\Exports\TransactionsExport([
            'fromDate' => '2026-09-04',
            'toDate' => '2026-09-04',
        ]);
        $headings = $excelExport->headings();
        $this->assertContains('Nilai Transaksi Baru (Rp)', $headings);
        $this->assertContains('Pembayaran Transaksi Baru (Rp)', $headings);
        $this->assertContains('Piutang Baru dari Transaksi (Rp)', $headings);
        $this->assertContains('Saldo Piutang Sebelum Kunjungan (Rp)', $headings);
        $this->assertContains('Pembayaran Piutang Lama (Rp)', $headings);
        $this->assertContains('Saldo Piutang Setelah Kunjungan (Rp)', $headings);
        $this->assertContains('Catatan Kunjungan', $headings);

        $rows = $excelExport->dataRows();
        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertEquals('TRX-20260904-0001', $row[6]); // Kode Transaksi
        $this->assertEquals(2000000.0, $row[7]);           // Nilai Transaksi Baru
        $this->assertEquals(500000.0, $row[8]);            // Pembayaran Transaksi Baru
        $this->assertEquals(1500000.0, $row[9]);           // Piutang Baru dari Transaksi
        $this->assertEquals(5000000.0, $row[10]);          // Saldo Piutang Sebelum Kunjungan
        $this->assertEquals(1250000.0, $row[11]);          // Pembayaran Piutang Lama
        $this->assertEquals(5250000.0, $row[12]);          // Saldo Piutang Setelah Kunjungan (5.000.000 - 1.250.000 + 1.500.000 = 5.250.000)
        $this->assertEquals('Transaksi + Piutang', $row[13]); // Status Transaksi
        $this->assertEquals('Pemesanan barang & cicil piutang lama', $row[14]); // Hasil Kunjungan
        $this->assertEquals('Toko membayar tunai.', $row[15]); // Catatan Kunjungan
    }

    public function test_filtering_by_sales_store_and_status(): void
    {
        $this->actingAs($this->admin);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Test Filter',
            'date' => '2026-09-05',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-05 09:00:00',
            'check_out_at' => '2026-09-05 09:30:00',
            'transaction_status' => 'paid',
            'transaction_amount' => 1000000,
            'cash_received' => 1000000,
            'visit_result' => 'Lunas pesanan pupuk',
            'final_notes' => 'Pembayaran lunas tunai.',
        ]);

        $newTrx = StoreTransaction::create([
            'store_id' => $this->store->id,
            'transaction_code' => 'TRX-20260905-0001',
            'transaction_date' => '2026-09-05',
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_LUNAS,
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'created_by' => $this->sales->id,
        ]);

        StoreTransactionPayment::create([
            'store_transaction_id' => $newTrx->id,
            'amount' => 1000000,
            'payment_method' => 'tunai',
            'payment_date' => '2026-09-05',
            'recorded_by' => $this->sales->id,
            'source' => 'initial_payment',
            'source_id' => $visit->id,
            'notes' => 'Pembayaran awal lunas',
        ]);

        // Filter with sales_id
        $resp = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-05',
            'to_date' => '2026-09-05',
            'user_id' => $this->sales->id,
        ]));
        $resp->assertOk();
        $resp->assertSee('TRX-20260905-0001');

        // Filter with another sales_id (should not see)
        $respOther = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-05',
            'to_date' => '2026-09-05',
            'user_id' => $this->admin->id,
        ]));
        $respOther->assertOk();
        $respOther->assertDontSee('TRX-20260905-0001');

        // Filter with transaction_status = 'has_transaction'
        $respStatus = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-05',
            'to_date' => '2026-09-05',
            'transaction_status' => 'has_transaction',
        ]));
        $respStatus->assertOk();
        $respStatus->assertSee('TRX-20260905-0001');

        // Filter with transaction_status = 'has_old_debt_payment' (should not see because this visit only has new tx payment)
        $respNoOld = $this->get(route('admin.reports.transactions', [
            'from_date' => '2026-09-05',
            'to_date' => '2026-09-05',
            'transaction_status' => 'has_old_debt_payment',
        ]));
        $respNoOld->assertOk();
        $respNoOld->assertDontSee('TRX-20260905-0001');
    }
}
