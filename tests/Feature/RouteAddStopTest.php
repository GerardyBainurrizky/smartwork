<?php

namespace Tests\Feature;

use App\Exports\RoutesExport;
use App\Models\DataArchive;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RouteAddStopTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;
    protected User $otherSales;
    protected Store $storeA;
    protected Store $storeB;
    protected Store $storeC;
    protected Store $storeD;
    protected Store $inactiveStore;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['status' => 'active']);
        $this->sales->assignRole('sales');

        $this->otherSales = User::factory()->create(['status' => 'active']);
        $this->otherSales->assignRole('sales');

        $this->storeA = Store::create(['code' => 'ST-001', 'name' => 'Toko A', 'city' => 'Subang', 'province' => 'Jawa Barat', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $this->storeB = Store::create(['code' => 'ST-002', 'name' => 'Toko B', 'city' => 'Subang', 'province' => 'Jawa Barat', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $this->storeC = Store::create(['code' => 'ST-003', 'name' => 'Toko C', 'city' => 'Subang', 'province' => 'Jawa Barat', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $this->storeD = Store::create(['code' => 'ST-004', 'name' => 'Toko D', 'city' => 'Subang', 'province' => 'Jawa Barat', 'status' => 'active', 'sales_penanggung_jawab_id' => $this->sales->id]);
        $this->inactiveStore = Store::create(['code' => 'ST-005', 'name' => 'Toko Nonaktif', 'city' => 'Subang', 'province' => 'Jawa Barat', 'status' => 'inactive', 'sales_penanggung_jawab_id' => $this->sales->id]);
    }

    public function test_scenario_1_sales_can_add_stop_when_route_is_draft(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Pagi',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 20,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeB->id,
            'estimated_duration_minutes' => 30,
            'notes' => 'Catatan toko B',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Toko kunjungan berhasil ditambahkan ke rute.',
        ]);

        $this->assertCount(2, $route->stops()->get());
        $newStop = $route->stops()->where('store_id', $this->storeB->id)->first();
        $this->assertEquals(2, $newStop->sequence);
        $this->assertEquals(30, $newStop->estimated_duration_minutes);
        $this->assertEquals('Catatan toko B', $newStop->notes);
        $this->assertEquals('pending', $newStop->status);
    }

    public function test_scenario_2_sales_can_add_stop_when_route_is_active_with_visited_stops(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Siang',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now()->subHours(2),
        ]);

        $stopA = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 20,
            'status' => 'visited',
        ]);

        Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stopA->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
        ]);

        $stopB = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'estimated_duration_minutes' => 20,
            'status' => 'pending',
        ]);

        // Tambah Toko D di tengah rute yang sedang berjalan
        $response = $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeD->id,
            'estimated_duration_minutes' => 25,
            'notes' => 'Tambahan toko D',
        ]);

        $response->assertOk();

        $this->assertCount(3, $route->stops()->get());

        // Stop A tetap visited dan tidak terganggu
        $stopA->refresh();
        $this->assertEquals('visited', $stopA->status);
        $this->assertEquals('visited', $stopA->computed_status);

        // Toko D menjadi urutan ke-3
        $stopD = $route->stops()->where('store_id', $this->storeD->id)->first();
        $this->assertNotNull($stopD);
        $this->assertEquals(3, $stopD->sequence);
        $this->assertEquals('pending', $stopD->status);
    }

    public function test_scenario_3_added_stop_can_be_checked_in_normally(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Berjalan',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeA->id,
            'estimated_duration_minutes' => 30,
        ]);

        $stop = $route->stops()->first();

        // Check in pada toko tambahan tersebut
        $checkInResponse = $this->actingAs($this->sales)->postJson(route('visit.store'), [
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'latitude' => -6.554321,
            'longitude' => 107.654321,
            'address' => 'Jl. Test Subang',
            'maps_url' => 'https://maps.google.com/?q=-6.554321,107.654321',
            'storefront_photo' => 'data:image/jpeg;base64,'.base64_encode('fake_storefront_image'),
            'selfie' => 'data:image/jpeg;base64,'.base64_encode('fake_image_bytes'),
        ]);

        $checkInResponse->assertOk();
        $this->assertDatabaseHas('visits', [
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
        ]);

        $stop->refresh();
        $this->assertEquals('visited', $stop->status);
    }

    public function test_scenario_4_added_stop_can_be_skipped(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Berjalan',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeA->id,
            'estimated_duration_minutes' => 30,
        ]);

        $stop = $route->stops()->first();

        // Lewati toko tambahan
        $skipResponse = $this->actingAs($this->sales)->patchJson(route('route.stop.update', [$route->id, $stop->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup',
            'photo' => 'data:image/jpeg;base64,' . base64_encode('fake_skip_evidence'),
        ]);

        $skipResponse->assertOk();

        $stop->refresh();
        $this->assertEquals('skipped', $stop->status);
        $this->assertEquals('skipped', $stop->computed_status);
    }

    public function test_scenario_5_adding_stop_to_completed_route_is_rejected(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Selesai',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeA->id,
            'estimated_duration_minutes' => 30,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Rute yang sudah selesai tidak dapat ditambahkan kunjungan baru.',
        ]);
    }

    public function test_scenario_6_adding_inactive_store_is_rejected(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->inactiveStore->id,
            'estimated_duration_minutes' => 30,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Toko yang dipilih tidak aktif atau tidak ditemukan.',
        ]);
    }

    public function test_scenario_7_duplicate_store_in_same_route_is_prevented(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Aktif',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 20,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeA->id,
            'estimated_duration_minutes' => 30,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Toko ini sudah ada dalam rute.',
        ]);
    }

    public function test_scenario_8_and_9_admin_and_super_admin_view_synchronized_stops(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Sales',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);

        RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 20,
            'status' => 'pending',
        ]);

        // Sales tambah toko B
        $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeB->id,
            'estimated_duration_minutes' => 30,
        ]);

        // Admin lihat detail rute
        $adminResponse = $this->actingAs($this->admin)->get(route('route.show', $route->id));
        $adminResponse->assertOk();
        $adminResponse->assertSee('Toko A');
        $adminResponse->assertSee('Toko B');

        // Jumlah route tetap 1
        $this->assertCount(1, Route::all());
        // Jumlah perhentian menjadi 2
        $this->assertCount(2, $route->stops()->get());
    }

    public function test_scenario_11_added_stop_appears_in_route_reports(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Laporan',
            'date' => '2026-08-25',
            'status' => 'active',
        ]);

        $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeA->id,
            'estimated_duration_minutes' => 20,
            'notes' => 'Catatan toko pertama',
        ]);

        $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeB->id,
            'estimated_duration_minutes' => 30,
            'notes' => 'Catatan toko tambahan',
        ]);

        // PDF Report
        $viewContent = view('reports.pdf.routes', [
            'items' => collect([$route->load(['stops.store', 'user', 'creator'])]),
            'periodLabel' => 'Semua Periode',
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-31',
            'generatedAt' => now()->format('d M Y, H:i'),
            'printedBy' => 'Administrator',
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'title' => 'LAPORAN RENCANA KUNJUNGAN',
        ])->render();

        $this->assertStringContainsString('Toko A', $viewContent);
        $this->assertStringContainsString('Toko B', $viewContent);
        $this->assertStringContainsString('Catatan toko tambahan', $viewContent);

        // Excel Export
        $export = new RoutesExport(['fromDate' => '2026-08-01', 'toDate' => '2026-08-31']);
        $rows = $export->dataRows();
        $this->assertCount(2, $rows);
        $this->assertEquals(2, $rows[0][4]); // Jumlah Toko = 2
        $this->assertEquals('Toko A', $rows[0][11]); // Baris 1: Toko A
        $this->assertEquals('Toko B', $rows[1][11]); // Baris 2: Toko B
        $this->assertEquals('Catatan toko tambahan', $rows[1][13]); // Catatan Toko B (terpisah)
    }

    public function test_scenario_12_route_archiving_and_restoring_handles_added_stops(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('super-admin');

        $route = Route::create([
            'user_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'name' => 'Rute Arsip',
            'date' => '2026-08-20',
            'status' => 'active',
        ]);

        $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeA->id,
            'estimated_duration_minutes' => 20,
        ]);

        $this->actingAs($this->sales)->postJson(route('route.stop.add', $route->id), [
            'store_id' => $this->storeB->id,
            'estimated_duration_minutes' => 30,
        ]);

        // Super admin mengarsipkan rute
        $this->actingAs($superAdmin)->post(route('admin.archives.store'), [
            'data_type' => 'route',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        $session = DataArchive::first();
        $this->assertEquals('archived', $session->status);

        // Rute hilang dari query aktif
        $this->assertCount(0, Route::all());

        // Restore rute
        $this->actingAs($superAdmin)->post(route('admin.archives.restore', $session->id));

        // Rute kembali aktif dan seluruh stop (termasuk toko tambahan) tetap utuh
        $this->assertCount(1, Route::all());
        $restoredRoute = Route::first();
        $this->assertCount(2, $restoredRoute->stops()->get());
    }
}
