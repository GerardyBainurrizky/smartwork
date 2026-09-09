<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DataArchive;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DataArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales = User::factory()->create(['status' => 'active']);
        $this->sales->assignRole('sales');
    }

    protected function createStore(string $code = 'T001', string $name = 'Toko Test'): Store
    {
        return Store::create([
            'code' => $code,
            'name' => $name,
            'address' => 'Jl. Merdeka No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_view_archives_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.archives.index'));
        $response->assertStatus(200);
        $response->assertSee('Arsip & Kelola Data');
    }

    public function test_non_super_admin_cannot_access_archives_page(): void
    {
        $response = $this->actingAs($this->sales)->get(route('admin.archives.index'));
        $this->assertContains($response->status(), [302, 403]);
    }

    // =============================================
    // TEST 1 — ARCHIVE SESSION MEMBERSHIP (OVERLAP)
    // =============================================

    public function test_archive_session_membership_isolates_data_even_when_periods_overlap(): void
    {
        // Buat 3 data presensi:
        // - A1: 5 Jan (masuk Sesi A: 1-15 Jan)
        // - A2: 10 Jan (overlap Sesi A dan potensial Sesi B: 10-25 Jan)
        // - B1: 20 Jan (masuk Sesi B: 10-25 Jan saja)
        $a1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-05',
            'clock_in' => '2026-01-05 08:00:00', 'status' => 'present',
        ]);
        $a2 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-10',
            'clock_in' => '2026-01-10 08:00:00', 'status' => 'present',
        ]);

        // Arsipkan Sesi A: periode 1-15 Jan → mengarsipkan A1 dan A2
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
            'notes' => 'SESI-A',
        ]);

        $sessionA = DataArchive::where('notes', 'SESI-A')->first();
        $this->assertNotNull($sessionA);
        $this->assertEquals('archived', $sessionA->status);
        $this->assertEquals(2, $sessionA->records_count);

        // Verifikasi A1 dan A2 memiliki data_archive_id yang benar
        $a1->refresh();
        $a2->refresh();
        $this->assertEquals($sessionA->id, $a1->data_archive_id);
        $this->assertEquals($sessionA->id, $a2->data_archive_id);

        // Buat data baru setelah Sesi A (aktif kembali) — B1: 20 Jan
        $b1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-20',
            'clock_in' => '2026-01-20 08:00:00', 'status' => 'present',
        ]);

        // Arsipkan Sesi B: periode 10-25 Jan → mengarsipkan hanya B1 (karena A2 sudah terarsip)
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-25',
            'notes' => 'SESI-B',
        ]);

        $sessionB = DataArchive::where('notes', 'SESI-B')->first();
        $this->assertNotNull($sessionB);
        $this->assertEquals('archived', $sessionB->status);
        $this->assertEquals(1, $sessionB->records_count);

        $b1->refresh();
        $this->assertEquals($sessionB->id, $b1->data_archive_id);

        // Verifikasi: export Sesi A hanya mengambil A1 dan A2
        $sessionARecords = Attendance::withoutGlobalScope('notArchived')
            ->where('data_archive_id', $sessionA->id)
            ->get();
        $this->assertCount(2, $sessionARecords);
        $this->assertTrue($sessionARecords->pluck('id')->contains($a1->id));
        $this->assertTrue($sessionARecords->pluck('id')->contains($a2->id));
        $this->assertFalse($sessionARecords->pluck('id')->contains($b1->id));

        // Verifikasi: export Sesi B hanya mengambil B1
        $sessionBRecords = Attendance::withoutGlobalScope('notArchived')
            ->where('data_archive_id', $sessionB->id)
            ->get();
        $this->assertCount(1, $sessionBRecords);
        $this->assertFalse($sessionBRecords->pluck('id')->contains($a1->id));
        $this->assertFalse($sessionBRecords->pluck('id')->contains($a2->id));
        $this->assertTrue($sessionBRecords->pluck('id')->contains($b1->id));
    }

    // =============================
    // TEST 2 — RESTORE SESSION ISOLATION
    // =============================

    public function test_restore_session_only_restores_data_belonging_to_that_session(): void
    {
        $a1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-05',
            'clock_in' => '2026-01-05 08:00:00', 'status' => 'present',
        ]);
        $a2 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-10',
            'clock_in' => '2026-01-10 08:00:00', 'status' => 'present',
        ]);

        // Sesi A: 1-10 Jan → arsipkan A1 dan A2
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-10',
            'notes' => 'SESI-A',
        ]);
        $sessionA = DataArchive::where('notes', 'SESI-A')->first();

        // Buat B1 setelah sesi A terbentuk
        $b1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-20',
            'clock_in' => '2026-01-20 08:00:00', 'status' => 'present',
        ]);

        // Sesi B: 15-25 Jan → arsipkan B1
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-15',
            'end_date' => '2026-01-25',
            'notes' => 'SESI-B',
        ]);
        $sessionB = DataArchive::where('notes', 'SESI-B')->first();

        // Restore Sesi A saja
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $sessionA->id));

        // A1 dan A2 harus kembali aktif
        $a1->refresh();
        $a2->refresh();
        $b1->refresh();

        $this->assertNull($a1->archived_at);
        $this->assertNull($a1->data_archive_id);
        $this->assertNull($a2->archived_at);
        $this->assertNull($a2->data_archive_id);

        // B1 tetap terarsip dan masih dalam Sesi B
        $this->assertNotNull($b1->archived_at);
        $this->assertEquals($sessionB->id, $b1->data_archive_id);

        // ID record tidak berubah
        $this->assertEquals($a1->id, Attendance::find($a1->id)->id);

        // Sesi A berstatus restored, Sesi B tetap archived
        $sessionA->refresh();
        $sessionB->refresh();
        $this->assertEquals('restored', $sessionA->status);
        $this->assertEquals('archived', $sessionB->status);

        // Data A1 dan A2 kembali muncul di query aktif
        $this->assertCount(2, Attendance::all());
    }

    // =============================
    // TEST 3 — PURGE SESSION ISOLATION
    // =============================

    public function test_purge_session_only_deletes_data_belonging_to_that_session(): void
    {
        $a1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-05',
            'clock_in' => '2026-01-05 08:00:00', 'status' => 'present',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-10',
            'notes' => 'SESI-A',
        ]);
        $sessionA = DataArchive::where('notes', 'SESI-A')->first();

        $b1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-20',
            'clock_in' => '2026-01-20 08:00:00', 'status' => 'present',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-15',
            'end_date' => '2026-01-25',
            'notes' => 'SESI-B',
        ]);
        $sessionB = DataArchive::where('notes', 'SESI-B')->first();

        // Konfirmasi salah harus ditolak
        $failResponse = $this->actingAs($this->superAdmin)->delete(route('admin.archives.purge', $sessionA->id), [
            'confirmation' => 'HAPUS',
        ]);
        $failResponse->assertSessionHasErrors('confirmation');

        // Purge Sesi A saja
        $this->actingAs($this->superAdmin)->delete(route('admin.archives.purge', $sessionA->id), [
            'confirmation' => 'HAPUS PERMANEN',
        ]);

        // A1 terhapus
        $this->assertDatabaseMissing('attendances', ['id' => $a1->id]);

        // B1 tetap ada
        $this->assertDatabaseHas('attendances', ['id' => $b1->id]);
        $b1->refresh();
        $this->assertEquals($sessionB->id, $b1->data_archive_id);

        $sessionA->refresh();
        $this->assertEquals('purged', $sessionA->status);
        $sessionB->refresh();
        $this->assertEquals('archived', $sessionB->status);
    }

    // =============================
    // TEST 4 — ROUTESTOP STATUS SAAT VISIT DIARSIPKAN
    // =============================

    public function test_routestop_computed_status_remains_visited_when_visit_is_archived(): void
    {
        $store = $this->createStore('T001', 'Toko Alpha');

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Test',
            'date' => '2026-01-15',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => '2026-01-15 09:00:00',
            'check_out_at' => '2026-01-15 10:00:00',
        ]);

        // Sebelum arsip: RouteStop status = visited
        $stop->refresh();
        $this->assertEquals('visited', $stop->computed_status);

        // Arsipkan Visit saja (bukan Route)
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'visit',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]);

        // Visit tidak tampil di query aktif
        $this->assertCount(0, Visit::all());

        // RouteStop masih berstatus visited secara historis (menggunakan anyVisit())
        $stop->refresh();
        $stop->unsetRelation('visit');
        $stop->unsetRelation('anyVisit');
        $this->assertEquals('visited', $stop->computed_status);
    }

    // =============================
    // TEST 5 — RESTORE VISIT
    // =============================

    public function test_restore_visit_makes_routestop_status_consistent_again(): void
    {
        $store = $this->createStore('T002', 'Toko Beta');

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Test',
            'date' => '2026-01-15',
            'status' => 'active',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => '2026-01-15 09:00:00',
            'check_out_at' => '2026-01-15 10:00:00',
        ]);

        // Arsipkan Visit
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'visit',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]);

        $session = DataArchive::first();
        $this->assertCount(0, Visit::all());

        // Restore Visit
        $this->actingAs($this->superAdmin)->post(route('admin.archives.restore', $session->id));

        // Visit kembali muncul di query aktif
        $this->assertCount(1, Visit::all());

        // Tidak ada duplikasi
        $this->assertCount(1, Visit::withoutGlobalScope('notArchived')->get());

        // RouteStop computed status tetap visited
        $stop->refresh();
        $stop->unsetRelation('visit');
        $stop->unsetRelation('anyVisit');
        $this->assertEquals('visited', $stop->computed_status);
    }

    // =============================
    // TEST 6 — EXPORT PDF DAN EXCEL
    // =============================

    public function test_export_pdf_and_excel_use_archive_session_id_as_filter(): void
    {
        $a1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-05',
            'clock_in' => '2026-01-05 08:00:00', 'status' => 'present',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'notes' => 'SESI-A',
        ]);

        $sessionA = DataArchive::where('notes', 'SESI-A')->first();

        // Buat data baru lalu arsipkan di Sesi B dengan periode yang sama
        $b1 = Attendance::create([
            'user_id' => $this->sales->id, 'date' => '2026-01-15',
            'clock_in' => '2026-01-15 08:00:00', 'status' => 'present',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'notes' => 'SESI-B',
        ]);

        $sessionB = DataArchive::where('notes', 'SESI-B')->first();

        // Export Sesi A (hanya A1)
        $sessionAQuery = Attendance::withoutGlobalScope('notArchived')
            ->where('data_archive_id', $sessionA->id)
            ->get();
        $this->assertCount(1, $sessionAQuery);
        $this->assertTrue($sessionAQuery->pluck('id')->contains($a1->id));
        $this->assertFalse($sessionAQuery->pluck('id')->contains($b1->id));

        // Test HTTP download PDF Sesi A dengan custom filename
        $pdfResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.archives.export-pdf', $sessionA->id) . '?filename=Laporan_Presensi_Custom_2026');
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
        $this->assertStringContainsString('Laporan_Presensi_Custom_2026.pdf', $pdfResponse->headers->get('content-disposition') ?? '');

        // Test view template PDF Presensi tidak lagi mengandung teks 'Catatan' dan mengandung 'maps-link'
        $viewContent = view('reports.pdf.attendance', [
            'items' => collect([$a1]),
            'periodLabel' => 'Semua Periode',
            'fromDate' => '2026-01-01',
            'toDate' => '2026-01-31',
            'generatedAt' => now()->format('d M Y, H:i'),
            'printedBy' => 'Administrator',
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'title' => 'LAPORAN PRESENSI',
        ])->render();

        $this->assertStringNotContainsString('Catatan', $viewContent);
        $this->assertStringContainsString('Koordinat Check In', $viewContent);

        // Test HTTP download Excel Sesi A dengan custom filename
        $excelResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.archives.export-excel', $sessionA->id) . '?filename=Rekap_Presensi_Custom_2026');
        $excelResponse->assertStatus(200);
        $this->assertStringContainsString('Rekap_Presensi_Custom_2026.xlsx', $excelResponse->headers->get('content-disposition') ?? '');
    }

    // =============================
    // TEST ARCHIVE SESSION BASIC FLOW
    // =============================

    public function test_archive_records_count_and_data_archive_id_are_set_correctly(): void
    {
        $date = '2026-01-15';

        $att = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $date,
            'clock_in' => "{$date} 08:00:00",
            'clock_out' => "{$date} 17:00:00",
            'status' => 'present',
        ]);

        $this->assertCount(1, Attendance::all());

        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'notes' => 'Arsip Januari 2026',
        ]);

        // Data hilang dari query aktif
        $this->assertCount(0, Attendance::all());

        // Data masih ada di DB
        $this->assertDatabaseHas('attendances', ['id' => $att->id]);

        $session = DataArchive::first();
        $this->assertEquals(1, $session->records_count);
        $this->assertEquals('archived', $session->status);

        // data_archive_id tersimpan di record
        $att->refresh();
        $this->assertEquals($session->id, $att->data_archive_id);
        $this->assertNotNull($att->archived_at);
    }

    // =========================================================================
    // TEST 5 — HAPUS RIWAYAT SESI TIDAK MENGHAPUS DATA ARSIP MAUPUN AKTIF
    // =========================================================================

    public function test_destroy_history_deletes_session_log_only_and_preserves_archived_and_active_data(): void
    {
        $store = $this->createStore();

        // 1. Data aktif di luar periode arsip
        $activeAtt = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => '2026-02-10',
            'clock_in' => '2026-02-10 08:00:00',
            'status' => 'present',
        ]);

        // 2. Data yang akan diarsipkan
        $archiveAtt = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => '2026-01-10',
            'clock_in' => '2026-01-10 08:00:00',
            'status' => 'present',
        ]);

        // Kunjungan yang tidak diarsipkan (jenis data lain)
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Test',
            'date' => '2026-01-10',
            'status' => 'active',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'store_id' => $store->id,
            'check_in_at' => '2026-01-10 09:00:00',
            'status' => 'completed',
        ]);

        // Arsipkan hanya data presensi Januari 2026
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'attendance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'notes' => 'Sesi Presensi Januari',
        ]);

        $session = DataArchive::where('notes', 'Sesi Presensi Januari')->first();
        $this->assertNotNull($session);

        // Verifikasi kondisi sebelum hapus riwayat:
        // - Data presensi Januari hilang dari query aktif
        $this->assertCount(1, Attendance::all());
        $this->assertEquals($activeAtt->id, Attendance::first()->id);
        // - Visit tetap aktif (tidak terpengaruh oleh arsip presensi)
        $this->assertCount(1, Visit::all());

        // Lakukan "Hapus Riwayat" sesi arsip
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.archives.destroy-history', $session->id));
        $response->assertRedirect(route('admin.archives.index'));
        $response->assertSessionHas('success', 'Riwayat sesi arsip berhasil dihapus.');

        // 1. Session log benar-benar terhapus dari database
        $this->assertDatabaseMissing('data_archives', ['id' => $session->id]);

        // 2. Data yang diarsipkan TETAP ADA di database (tidak terhapus)
        $this->assertDatabaseHas('attendances', ['id' => $archiveAtt->id]);
        $this->assertDatabaseHas('attendances', ['id' => $activeAtt->id]);
        $this->assertDatabaseHas('visits', ['id' => $visit->id]);

        // 3. Data presensi yang diarsipkan TETAP berstatus arsip (archived_at not null)
        $reloadedArchiveAtt = Attendance::withoutGlobalScope('notArchived')->find($archiveAtt->id);
        $this->assertNotNull($reloadedArchiveAtt->archived_at);

        // 4. Data aktif tetap aktif dan tidak terganggu
        $this->assertCount(1, Attendance::all());
        $this->assertEquals($activeAtt->id, Attendance::first()->id);
        $this->assertCount(1, Visit::all());
    }

    // =========================================================================
    // TEST 6 — ARSIP BERDASARKAN JENIS DATA MENGISOLASI DATA JENIS LAIN
    // =========================================================================

    public function test_archiving_specific_data_type_only_affects_that_type(): void
    {
        $store = $this->createStore();

        // Buat data pada tanggal yang sama: Presensi & Kunjungan
        $date = '2026-01-12';

        $att = Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $date,
            'clock_in' => "{$date} 08:00:00",
            'status' => 'present',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute 12 Jan',
            'date' => $date,
            'status' => 'active',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'store_id' => $store->id,
            'check_in_at' => "{$date} 09:00:00",
            'status' => 'completed',
        ]);

        // Arsipkan HANYA Jenis Data: visit
        $this->actingAs($this->superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'visit',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'notes' => 'Arsip Kunjungan Saja',
        ]);

        // Kunjungan masuk arsip -> hilang dari query aktif
        $this->assertCount(0, Visit::all());

        // PRESENSI DAN RUTE TIDAK TERPENGARUH -> tetap muncul di query aktif!
        $this->assertCount(1, Attendance::all());
        $this->assertCount(1, Route::all());
    }
}
