<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DataArchive;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreReceivable;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComprehensiveDataArchiveAllCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $sales;
    protected User $driver;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['status' => 'active', 'name' => 'Sales Budi']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['status' => 'active', 'name' => 'Driver Doni']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'code' => 'T-001',
            'name' => 'Toko Sumber Rezeki',
            'owner' => 'Pak Haji',
            'address' => 'Jl. Raya No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    /**
     * 1. Authorization: Only Super Admin has access to /admin/archives.
     */
    public function test_authorization_only_super_admin_can_access_archives(): void
    {
        // Super Admin -> OK (200)
        $respSuperAdmin = $this->actingAs($this->superAdmin)->get(route('admin.archives.index'));
        $respSuperAdmin->assertStatus(200);
        $respSuperAdmin->assertSee('Arsip &amp; Kelola Data', false);

        // Regular Admin -> Forbidden / Redirect (403 or 302)
        $respAdmin = $this->actingAs($this->admin)->get(route('admin.archives.index'));
        $this->assertContains($respAdmin->status(), [302, 403]);

        // Sales -> Forbidden / Redirect
        $respSales = $this->actingAs($this->sales)->get(route('admin.archives.index'));
        $this->assertContains($respSales->status(), [302, 403]);

        // Driver -> Forbidden / Redirect
        $respDriver = $this->actingAs($this->driver)->get(route('admin.archives.index'));
        $this->assertContains($respDriver->status(), [302, 403]);
    }

    /**
     * 2. Archive, Restore, and Purge for PRESENSI.
     */
    public function test_archive_restore_and_purge_for_attendance(): void
    {
        $att = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => '2026-03-05',
            'clock_in' => '2026-03-05 08:00:00',
            'status' => 'present',
        ]);

        $this->assertCount(1, Attendance::all());

        // Archive
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'notes' => 'Arsip Presensi Maret 2026',
        ]);

        $this->assertCount(0, Attendance::all());
        $archive = DataArchive::where('data_type', 'attendance')->first();
        $this->assertNotNull($archive);
        $this->assertEquals(1, $archive->records_count);

        // Restore
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $archive->id));
        $this->assertCount(1, Attendance::all());
        $archive->refresh();
        $this->assertEquals('restored', $archive->status);

        // Re-archive for purge test
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'notes' => 'Re-archive for purge',
        ]);
        $newArchive = DataArchive::where('notes', 'Re-archive for purge')->first();
        $this->assertNotNull($newArchive);
        $this->assertEquals('archived', $newArchive->status);

        // Purge
        $this->actingAs($this->superAdmin)->delete(route('admin.archives.purge', $newArchive->id), [
            'confirmation' => 'HAPUS PERMANEN',
        ]);
        $this->assertDatabaseMissing('attendances', ['id' => $att->id]);
    }

    /**
     * 3. Archive, Restore, and Purge for TRANSAKSI (store_transactions + payments).
     */
    public function test_archive_restore_and_purge_for_transactions_and_payments(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 500000,
            'transaction_date' => '2026-04-10',
            'description' => 'Penjualan Pupuk NPK',
            'paid_amount' => 200000,
            'payment_method' => 'tunai',
        ], $this->superAdmin);

        $this->assertEquals(StoreTransaction::STATUS_SEBAGIAN, $trx->status);
        $this->assertEquals(300000, (float) StoreReceivableService::balanceForStore($this->store->id));
        $this->assertCount(1, StoreTransaction::all());
        $this->assertCount(1, StoreTransactionPayment::all());

        // Archive
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'transaction',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'notes' => 'Arsip Transaksi April 2026',
        ]);

        // Transaction & Payment are hidden from active queries
        $this->assertCount(0, StoreTransaction::all());
        $this->assertCount(0, StoreTransactionPayment::all());
        $this->assertEquals(0, (float) StoreReceivableService::balanceForStore($this->store->id));

        $archive = DataArchive::where('data_type', 'transaction')->first();
        $this->assertNotNull($archive);
        $this->assertEquals(1, $archive->records_count);

        // Restore
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $archive->id));
        $this->assertCount(1, StoreTransaction::all());
        $this->assertCount(1, StoreTransactionPayment::all());
        $this->assertEquals(300000, (float) StoreReceivableService::balanceForStore($this->store->id));

        // Purge
        // Re-archive first
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'transaction',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'notes' => 'Re-archive for purge trx',
        ]);
        $newArchiveTrx = DataArchive::where('notes', 'Re-archive for purge trx')->first();
        $this->assertNotNull($newArchiveTrx);
        $this->assertEquals('archived', $newArchiveTrx->status);

        $this->actingAs($this->superAdmin)->delete(route('admin.archives.purge', $newArchiveTrx->id), [
            'confirmation' => 'HAPUS PERMANEN',
        ]);

        $this->assertDatabaseMissing('store_transactions', ['id' => $trx->id]);
    }

    /**
     * 4. Archive, Restore, and Purge for HASIL TRANSAKSI KUNJUNGAN (visit transaction result).
     */
    public function test_archive_restore_and_purge_for_transaction_result(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Kunjungan Transaksi',
            'date' => '2026-05-12',
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
            'check_in_at' => '2026-05-12 09:00:00',
            'check_out_at' => '2026-05-12 10:00:00',
            'transaction_amount' => 750000,
            'transaction_status' => 'paid',
        ]);

        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 750000,
            'transaction_date' => '2026-05-12',
            'description' => 'Transaksi Visit',
            'reference_type' => 'visit',
            'reference_id' => $visit->id,
            'paid_amount' => 750000,
            'payment_method' => 'tunai',
        ], $this->sales);

        $this->assertCount(1, Visit::all());

        // Archive transaction_result
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'transaction_result',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'notes' => 'Arsip Hasil Transaksi Mei 2026',
        ]);

        $this->assertCount(0, Visit::all());
        $archive = DataArchive::where('data_type', 'transaction_result')->first();
        $this->assertNotNull($archive);

        // Restore
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $archive->id));
        $this->assertCount(1, Visit::all());
    }

    /**
     * 5. Archive, Restore, and Purge for PIUTANG (outstanding store transactions).
     */
    public function test_archive_restore_and_purge_for_receivable(): void
    {
        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-06-01',
            'description' => 'Penjualan Bibit Jagung',
        ], $this->superAdmin);

        $this->assertEquals(1000000, (float) StoreReceivableService::balanceForStore($this->store->id));

        // Archive receivable
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'receivable',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'notes' => 'Arsip Piutang Juni 2026',
        ]);

        $this->assertEquals(0, (float) StoreReceivableService::balanceForStore($this->store->id));
        $archive = DataArchive::where('data_type', 'receivable')->first();
        $this->assertNotNull($archive);

        // Restore
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $archive->id));
        $this->assertEquals(1000000, (float) StoreReceivableService::balanceForStore($this->store->id));
    }

    /**
     * 6. Session Isolation (AU): Two sessions with the same date range must NOT mix data.
     */
    public function test_session_isolation_same_period_different_sessions(): void
    {
        // 1. Create data for Session A
        $attA = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => '2026-07-05',
            'clock_in' => '2026-07-05 08:00:00',
            'status' => 'present',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'notes' => 'SESI-A-JULI',
        ]);
        $sessionA = DataArchive::where('notes', 'SESI-A-JULI')->first();

        // 2. Create data for Session B (created after Session A was archived)
        $attB = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => '2026-07-20',
            'clock_in' => '2026-07-20 08:00:00',
            'status' => 'present',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'notes' => 'SESI-B-JULI',
        ]);
        $sessionB = DataArchive::where('notes', 'SESI-B-JULI')->first();

        // Verify Session A dataset contains ONLY attA
        $recordsA = Attendance::withoutGlobalScope('notArchived')
            ->where('data_archive_id', $sessionA->id)
            ->get();
        $this->assertCount(1, $recordsA);
        $this->assertTrue($recordsA->pluck('id')->contains($attA->id));
        $this->assertFalse($recordsA->pluck('id')->contains($attB->id));

        // Verify Session B dataset contains ONLY attB
        $recordsB = Attendance::withoutGlobalScope('notArchived')
            ->where('data_archive_id', $sessionB->id)
            ->get();
        $this->assertCount(1, $recordsB);
        $this->assertFalse($recordsB->pluck('id')->contains($attA->id));
        $this->assertTrue($recordsB->pluck('id')->contains($attB->id));

        // Export PDF Sesi A
        $pdfA = $this->actingAs($this->superAdmin)->get(route('admin.archives.export-pdf', $sessionA->id));
        $pdfA->assertStatus(200);

        // Export Excel Sesi A
        $excelA = $this->actingAs($this->superAdmin)->get(route('admin.archives.export-excel', $sessionA->id));
        $excelA->assertStatus(200);
    }

    /**
     * 7. Driver archive remains completely isolated from Sales.
     */
    public function test_driver_archive_isolation_from_sales(): void
    {
        $routeDriver = Route::create([
            'user_id' => $this->driver->id,
            'created_by' => $this->driver->id,
            'name' => 'Rute Pengiriman Driver',
            'date' => '2026-08-01',
            'status' => 'active',
        ]);

        $routeSales = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Kunjungan Sales',
            'date' => '2026-08-01',
            'status' => 'active',
        ]);

        // Archive driver routes only
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'route_driver',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'notes' => 'Arsip Rute Driver',
        ]);

        // Driver route is archived, Sales route is STILL active
        $this->assertNotNull(Route::withTrashed()->withoutGlobalScope('notArchived')->find($routeDriver->id)->archived_at);
        $this->assertNull(Route::find($routeSales->id)->archived_at);
    }

    /**
     * 8. Archive, Restore, and Purge for KEUANGAN / SALES ACTIVITIES.
     */
    public function test_archive_restore_and_export_for_finance(): void
    {
        // Setup attendance, route, visit, and transactions
        $att = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => '2026-09-02',
            'clock_in' => '2026-09-02 08:00:00',
            'status' => 'present',
        ]);

        $trxNew = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 1000000,
            'transaction_date' => '2026-09-02',
            'description' => 'Penjualan Pestisida',
            'paid_amount' => 400000,
            'payment_method' => 'tunai',
        ], $this->sales);

        // Archive finance
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'finance',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'notes' => 'Arsip Keuangan September 2026',
        ]);

        $archive = DataArchive::where('data_type', 'finance')->first();
        $this->assertNotNull($archive);

        // Export PDF
        $pdfResp = $this->actingAs($this->superAdmin)->get(route('admin.archives.export-pdf', $archive->id));
        $pdfResp->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResp->headers->get('content-type'));

        // Export Excel
        $excelResp = $this->actingAs($this->superAdmin)->get(route('admin.archives.export-excel', $archive->id));
        $excelResp->assertStatus(200);

        // Restore
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $archive->id));
        $archive->refresh();
        $this->assertEquals('restored', $archive->status);
    }

    /**
     * 9. Archive ALL operational data at once and verify comprehensive restore.
     */
    public function test_archive_all_operational_data_and_restore(): void
    {
        $att = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => '2026-10-05',
            'clock_in' => '2026-10-05 08:00:00',
            'status' => 'present',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute All Test',
            'date' => '2026-10-05',
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
            'check_in_at' => '2026-10-05 09:00:00',
            'check_out_at' => '2026-10-05 10:00:00',
        ]);

        $trx = StoreReceivableService::createTransaction([
            'store_id' => $this->store->id,
            'transaction_amount' => 250000,
            'transaction_date' => '2026-10-05',
            'description' => 'Trx All',
        ], $this->superAdmin);

        // Archive all
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'all',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'notes' => 'Arsip Semua Data Oktober 2026',
        ]);

        $this->assertCount(0, Attendance::all());
        $this->assertCount(0, Route::all());
        $this->assertCount(0, Visit::all());
        $this->assertCount(0, StoreTransaction::all());

        $archive = DataArchive::where('data_type', 'all')->first();
        $this->assertNotNull($archive);
        $this->assertGreaterThan(0, $archive->records_count);

        // Restore all
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $archive->id));

        $this->assertCount(1, Attendance::all());
        $this->assertCount(1, Route::all());
        $this->assertCount(1, Visit::all());
        $this->assertCount(1, StoreTransaction::all());
    }
}
