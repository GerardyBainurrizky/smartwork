<?php

namespace Tests\Feature;

use App\Exports\SalesActivitiesExport;
use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesActivitiesReportRealDbAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $store1;
    private Store $store2;
    private Store $store3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::where('username', 'admin')->first() ?? User::factory()->create(['name' => 'Admin User']);
        if (! $this->admin->hasRole('admin')) {
            $this->admin->assignRole('admin');
        }

        $this->superAdmin = User::where('username', 'superadmin')->first() ?? User::factory()->create(['name' => 'Super Admin User']);
        if (! $this->superAdmin->hasRole('super-admin')) {
            $this->superAdmin->assignRole('super-admin');
        }

        $this->sales1 = User::create([
            'username' => 'sales_andi',
            'name' => 'Sales Andi',
            'email' => 'sales_andi@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->sales1->syncRoles(['sales']);

        $this->sales2 = User::create([
            'username' => 'sales_bayu',
            'name' => 'Sales Bayu',
            'email' => 'sales_bayu@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->sales2->syncRoles(['sales']);

        $this->driver = User::create([
            'username' => 'driver_edi',
            'name' => 'Driver Edi',
            'email' => 'driver_edi@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);

        // Stores
        $this->store1 = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'TKO-001',
            'name' => 'Toko Rejeki Andi 1',
            'owner' => 'Owner 1',
            'address' => 'Jl. Mawar No. 1',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'status' => 'active',
        ]);

        $this->store2 = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'TKO-002',
            'name' => 'Toko Berkah Andi 2',
            'owner' => 'Owner 2',
            'address' => 'Jl. Melati No. 2',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'status' => 'active',
        ]);

        $this->store3 = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'TKO-003',
            'name' => 'Toko Sentosa Bayu 3',
            'owner' => 'Owner 3',
            'address' => 'Jl. Anggrek No. 3',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'status' => 'active',
        ]);
    }

    /**
     * Audit Test Scenario:
     * 1. Transaksi baru tanpa pembayaran (Store 1: Rp 500.000, paid: 0)
     * 2. Transaksi baru dengan pembayaran sebagian (Store 1: Rp 1.000.000, paid: Rp 500.000)
     * 3. Transaksi baru lunas (Store 3: Rp 2.500.000, paid: Rp 2.500.000)
     * 4. Pembayaran piutang lama (Store 2: Old debt Rp 3.000.000 from August, paid Rp 1.250.000 in September)
     * 5. Driver visit with transaction (Driver Edi: Rp 800.000 -> MUST NOT COUNT in Sales report)
     */
    public function test_complete_audit_matrix_and_reconciliation(): void
    {
        $this->actingAs($this->admin);

        // Delete any extra sales users
        User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereNotIn('id', [$this->sales1->id, $this->sales2->id])
            ->forceDelete();

        $fromDate = '2026-09-01';
        $toDate = '2026-09-07';

        // 1. Presensi: Andi 3 days, Bayu 2 days, Driver 4 days
        Attendance::create(['user_id' => $this->sales1->id, 'date' => '2026-09-01', 'status' => 'present']);
        Attendance::create(['user_id' => $this->sales1->id, 'date' => '2026-09-02', 'status' => 'present']);
        Attendance::create(['user_id' => $this->sales1->id, 'date' => '2026-09-03', 'status' => 'present']);
        Attendance::create(['user_id' => $this->sales2->id, 'date' => '2026-09-01', 'status' => 'present']);
        Attendance::create(['user_id' => $this->sales2->id, 'date' => '2026-09-02', 'status' => 'present']);
        Attendance::create(['user_id' => $this->driver->id, 'date' => '2026-09-01', 'status' => 'present']);

        // 2. Routes & Visits for Sales Andi
        $route1 = RouteModel::create([
            'user_id' => $this->sales1->id,
            'created_by' => $this->admin->id,
            'name' => 'Rute Andi 1',
            'date' => '2026-09-01',
            'status' => 'completed',
        ]);
        $stop1 = RouteStop::create(['route_id' => $route1->id, 'store_id' => $this->store1->id, 'sequence' => 1, 'status' => 'visited']);
        $visit1 = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $route1->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-01 08:30:00',
            'check_out_at' => '2026-09-01 09:30:00',
        ]);

        // 3. Transactions:
        // Case A: Old debt for Store 2 (created 2026-08-20, Rp 3.000.000)
        $oldDebtStore2 = StoreReceivableService::createTransaction([
            'store_id' => $this->store2->id,
            'transaction_code' => 'TRX-OLD-S2',
            'transaction_amount' => 3000000,
            'transaction_date' => '2026-08-20',
            'description' => 'Faktur Lama Agustus Toko 2',
        ], $this->sales1);

        // Case B: Transaksi baru tanpa pembayaran (Store 1: Rp 500.000)
        $txNewUnpaid = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-NEW-UNPAID',
            'transaction_amount' => 500000,
            'paid_amount' => 0,
            'transaction_date' => '2026-09-01',
            'reference_type' => 'visit',
            'reference_id' => $visit1->id,
            'description' => 'Faktur Baru Belum Dibayar Toko 1',
        ], $this->sales1);

        // Case C: Transaksi baru dengan pembayaran sebagian (Store 1: Rp 1.000.000, Bayar Awal: Rp 500.000)
        $txNewPartial = StoreReceivableService::createTransaction([
            'store_id' => $this->store1->id,
            'transaction_code' => 'TRX-NEW-PARTIAL',
            'transaction_amount' => 1000000,
            'paid_amount' => 500000,
            'payment_method' => 'tunai',
            'transaction_date' => '2026-09-02',
            'description' => 'Faktur Baru Bayar Sebagian Toko 1',
        ], $this->sales1);

        // Case D: Pembayaran Piutang Lama Toko 2 (Rp 1.250.000)
        StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $oldDebtStore2->id,
            'amount' => 1250000,
            'payment_method' => 'transfer',
            'payment_date' => '2026-09-03',
            'source' => 'visit',
            'notes' => 'Cicilan Piutang Lama Toko 2',
        ], $this->sales1);

        // Case E: Transaksi baru lunas untuk Toko 3 (Sales Bayu: Rp 2.500.000, Bayar Awal: Rp 2.500.000)
        $txNewLunas = StoreReceivableService::createTransaction([
            'store_id' => $this->store3->id,
            'transaction_code' => 'TRX-NEW-LUNAS',
            'transaction_amount' => 2500000,
            'paid_amount' => 2500000,
            'payment_method' => 'tunai',
            'transaction_date' => '2026-09-04',
            'description' => 'Faktur Baru Lunas Toko 3',
        ], $this->sales2);

        // Case F: Driver transaction (Rp 800.000 COD) - MUST BE EXCLUDED from Sales Activities
        Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-05 10:00:00',
            'check_out_at' => '2026-09-05 10:30:00',
            'transaction_amount' => 800000,
            'transaction_status' => 'paid',
        ]);

        // ==========================================
        // RECONCILIATION & AUDIT VERIFICATION
        // ==========================================
        // Expected figures for Sales Andi:
        // - Attendance: 3
        // - Routes: 1
        // - Completed Routes: 1 (100%)
        // - Visits: 1 (Completed: 1)
        // - Nilai Transaksi Baru = 500.000 (unpaid) + 1.000.000 (partial) = Rp 1.500.000
        // - Uang Masuk Transaksi Baru = 0 + 500.000 = Rp 500.000
        // - Pembayaran Piutang Lama = Rp 1.250.000
        // - Total Uang Masuk = 500.000 + 1.250.000 = Rp 1.750.000

        // Expected figures for Sales Bayu:
        // - Attendance: 2
        // - Routes: 0
        // - Completed Routes: 0 (0%)
        // - Visits: 0
        // - Nilai Transaksi Baru = Rp 2.500.000
        // - Uang Masuk Transaksi Baru = Rp 2.500.000
        // - Pembayaran Piutang Lama = Rp 0
        // - Total Uang Masuk = Rp 2.500.000

        // Expected TOTAL SUMMARY:
        // - Total Sales: 2 (Driver excluded)
        // - Total Attendance: 5 (3 + 2)
        // - Total Routes: 1
        // - Total Visits: 1
        // - Total Nilai Transaksi Baru = 1.500.000 + 2.500.000 = Rp 4.000.000
        // - Uang Masuk Transaksi Baru = 500.000 + 2.500.000 = Rp 3.000.000
        // - Pembayaran Piutang Lama = Rp 1.250.000
        // - Total Uang Masuk = 3.000.000 + 1.250.000 = Rp 4.250.000

        // 1. Web Page Verification
        $response = $this->get(route('admin.reports.sales-activities', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]));
        $response->assertOk();

        // Check KPI cards rendered
        $response->assertSee('Total Nilai Transaksi Baru');
        $response->assertSee('Uang Masuk Transaksi Baru');
        $response->assertSee('Pembayaran Piutang Lama');
        $response->assertSee('Total Uang Masuk');

        // Check values in HTML
        $response->assertSee('Rp 4.000.000'); // Total Nilai Transaksi Baru
        $response->assertSee('Rp 3.000.000'); // Uang Masuk Transaksi Baru
        $response->assertSee('Rp 1.250.000'); // Pembayaran Piutang Lama
        $response->assertSee('Rp 4.250.000'); // Total Uang Masuk

        $summary = $response->viewData('summary');
        $this->assertEquals(2, $summary['total_sales']);
        $this->assertEquals(5, $summary['total_attendance']);
        $this->assertEquals(1, $summary['total_routes']);
        $this->assertEquals(1, $summary['total_visits']);
        $this->assertEquals(4000000.0, $summary['total_new_tx_amount']);
        $this->assertEquals(3000000.0, $summary['total_new_tx_paid']);
        $this->assertEquals(1250000.0, $summary['total_old_debt_paid']);
        $this->assertEquals(4250000.0, $summary['total_cash_in']);

        $performance = $response->viewData('performance');
        $andiData = collect($performance)->firstWhere('id', $this->sales1->id);
        $this->assertEquals(1500000.0, $andiData['new_tx_amount']);
        $this->assertEquals(500000.0, $andiData['new_tx_paid']);
        $this->assertEquals(1250000.0, $andiData['old_debt_paid']);
        $this->assertEquals(1750000.0, $andiData['total_cash_in']);

        $bayuData = collect($performance)->firstWhere('id', $this->sales2->id);
        $this->assertEquals(2500000.0, $bayuData['new_tx_amount']);
        $this->assertEquals(2500000.0, $bayuData['new_tx_paid']);
        $this->assertEquals(0.0, $bayuData['old_debt_paid']);
        $this->assertEquals(2500000.0, $bayuData['total_cash_in']);

        // 2. Excel Export Verification
        $export = new SalesActivitiesExport([
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'userId' => null,
        ]);
        $headings = $export->headings();
        $this->assertContains('Total Nilai Transaksi Baru (Rp)', $headings);
        $this->assertContains('Uang Masuk Transaksi Baru (Rp)', $headings);
        $this->assertContains('Pembayaran Piutang Lama (Rp)', $headings);
        $this->assertContains('Total Uang Masuk (Rp)', $headings);

        $excelRows = $export->dataRows();
        $this->assertCount(2, $excelRows);
        $excelAndi = collect($excelRows)->firstWhere('1', 'Sales Andi');
        $this->assertEquals(1500000.0, $excelAndi[8]);  // Nilai Tx Baru
        $this->assertEquals(500000.0, $excelAndi[9]);   // Uang Masuk Tx Baru
        $this->assertEquals(1250000.0, $excelAndi[10]); // Bayar Piutang Lama
        $this->assertEquals(1750000.0, $excelAndi[11]); // Total Uang Masuk

        // 3. PDF Export Verification
        $pdfResp = $this->get(route('admin.reports.export-pdf', [
            'type' => 'sales-activities',
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]));
        $pdfResp->assertOk();
        $this->assertEquals('application/pdf', $pdfResp->headers->get('content-type'));

        // 4. Filter by Sales Andi only
        $filterAndiResp = $this->get(route('admin.reports.sales-activities', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'user_id' => $this->sales1->id,
        ]));
        $filterAndiResp->assertOk();
        $sumFilter = $filterAndiResp->viewData('summary');
        $this->assertEquals(1, $sumFilter['total_sales']);
        $this->assertEquals(1500000.0, $sumFilter['total_new_tx_amount']);
        $this->assertEquals(500000.0, $sumFilter['total_new_tx_paid']);
        $this->assertEquals(1250000.0, $sumFilter['total_old_debt_paid']);
        $this->assertEquals(1750000.0, $sumFilter['total_cash_in']);
    }
}
