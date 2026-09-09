<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Toko Cahaya Tani',
            'owner' => 'H. Suryadi',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'city' => 'Jakarta Timur',
            'kecamatan' => 'Kramat Jati',
            'province' => 'DKI Jakarta',
            'latitude' => '-6.2114',
            'longitude' => '106.8456',
            'maps_url' => 'https://maps.app.goo.gl/xyz123',
            'phone' => '081234567890',
            'status' => 'active',
        ], $overrides);
    }

    public function test_admin_can_view_store_master_list(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.stores.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_store_with_auto_code(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.stores.store'), $this->payload());

        $response->assertRedirect(route('admin.stores.index'));
        $this->assertDatabaseHas('stores', [
            'name' => 'Toko Cahaya Tani',
            'code' => 'ST-001',
            'owner' => 'H. Suryadi',
            'city' => 'Jakarta Timur',
            'kecamatan' => 'Kramat Jati',
            'province' => 'DKI Jakarta',
            'latitude' => -6.2114,
            'longitude' => 106.8456,
            'maps_url' => 'https://maps.app.goo.gl/xyz123',
            'phone' => '081234567890',
            'status' => 'active',
        ]);
    }

    public function test_create_store_rejects_invalid_maps_url_and_coordinates(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->from(route('admin.stores.create'))
            ->post(route('admin.stores.store'), $this->payload([
                'maps_url' => 'bukan-url',
                'latitude' => '999',
                'longitude' => '999',
            ]));

        $response->assertSessionHasErrors(['maps_url', 'latitude', 'longitude']);
    }

    public function test_create_store_requires_city_and_province(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->from(route('admin.stores.create'))
            ->post(route('admin.stores.store'), $this->payload(['city' => '', 'province' => '']));

        $response->assertSessionHasErrors(['city', 'province']);
    }

    public function test_admin_can_update_store(): void
    {
        $user = $this->adminUser();
        $store = Store::create($this->payload() + ['code' => 'ST-001']);

        $response = $this->actingAs($user)->put(route('admin.stores.update', $store->id), [
            'name' => 'Toko Cahaya Tani Baru',
            'owner' => 'Ibu Ratna',
            'address' => 'Jl. Baru No. 1',
            'city' => 'Bogor',
            'kecamatan' => 'Bogor Selatan',
            'province' => 'Jawa Barat',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.stores.index'));
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => 'Toko Cahaya Tani Baru',
            'owner' => 'Ibu Ratna',
            'city' => 'Bogor',
            'kecamatan' => 'Bogor Selatan',
            'province' => 'Jawa Barat',
        ]);
    }

    public function test_admin_can_deactivate_and_restore_store(): void
    {
        $user = $this->adminUser();
        $store = Store::create($this->payload() + ['code' => 'ST-001']);

        // 1. Nonaktifkan toko
        $response = $this->actingAs($user)->delete(route('admin.stores.destroy', $store->id));
        $response->assertRedirect(route('admin.stores.index'));
        $response->assertSessionHas('success', 'Toko berhasil dinonaktifkan.');

        $this->assertDatabaseHas('stores', ['id' => $store->id, 'status' => 'inactive']);
        $this->assertSoftDeleted('stores', ['id' => $store->id]);

        // Toko nonaktif tetap tampil di halaman index dengan withTrashed
        $indexResponse = $this->actingAs($user)->get(route('admin.stores.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($store->name);
        $indexResponse->assertSee('Nonaktif');

        // Filter status inactive menampilkan toko nonaktif
        $inactiveResponse = $this->actingAs($user)->get(route('admin.stores.index', ['status' => 'inactive']));
        $inactiveResponse->assertOk();
        $inactiveResponse->assertSee($store->name);

        // Filter status active tidak menampilkan toko nonaktif
        $activeResponse = $this->actingAs($user)->get(route('admin.stores.index', ['status' => 'active']));
        $activeResponse->assertOk();
        $activeResponse->assertDontSee($store->name);

        // 2. Aktifkan kembali toko
        $restoreResponse = $this->actingAs($user)->post(route('admin.stores.restore', $store->id));
        $restoreResponse->assertRedirect(route('admin.stores.index'));
        $restoreResponse->assertSessionHas('success', 'Toko berhasil diaktifkan kembali.');

        $store->refresh();
        $this->assertEquals('active', $store->status);
        $this->assertNull($store->deleted_at);

        // Toko kembali tampil di filter active
        $activeAgainResponse = $this->actingAs($user)->get(route('admin.stores.index', ['status' => 'active']));
        $activeAgainResponse->assertOk();
        $activeAgainResponse->assertSee($store->name);
    }

    public function test_store_stats_accurately_reflect_active_and_inactive_counts(): void
    {
        $user = $this->adminUser();

        // Buat 3 toko aktif
        $s1 = Store::create($this->payload(['code' => 'ST-001', 'name' => 'Toko 1']));
        $s2 = Store::create($this->payload(['code' => 'ST-002', 'name' => 'Toko 2']));
        $s3 = Store::create($this->payload(['code' => 'ST-003', 'name' => 'Toko 3']));

        // Awalnya: Total 3, Aktif 3, Nonaktif 0
        $response1 = $this->actingAs($user)->get(route('admin.stores.index'));
        $response1->assertOk();
        $response1->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 3
                && $stats['active'] === 3
                && $stats['inactive'] === 0;
        });

        // Nonaktifkan 1 toko (s1)
        $this->actingAs($user)->delete(route('admin.stores.destroy', $s1->id));

        // Setelah nonaktif: Total 3, Aktif 2, Nonaktif 1
        $response2 = $this->actingAs($user)->get(route('admin.stores.index'));
        $response2->assertOk();
        $response2->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 3
                && $stats['active'] === 2
                && $stats['inactive'] === 1;
        });

        // Aktifkan kembali s1
        $this->actingAs($user)->post(route('admin.stores.restore', $s1->id));

        // Kembali: Total 3, Aktif 3, Nonaktif 0
        $response3 = $this->actingAs($user)->get(route('admin.stores.index'));
        $response3->assertOk();
        $response3->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 3
                && $stats['active'] === 3
                && $stats['inactive'] === 0;
        });
    }

    public function test_store_reports_pdf_and_excel_include_accurate_status(): void
    {
        $user = $this->adminUser();

        $activeStore = Store::create($this->payload(['code' => 'ST-001', 'name' => 'Toko Aktif']));
        $inactiveStore = Store::create($this->payload(['code' => 'ST-002', 'name' => 'Toko Nonaktif']));
        $this->actingAs($user)->delete(route('admin.stores.destroy', $inactiveStore->id));

        // Web Report
        $reportResponse = $this->actingAs($user)->get(route('admin.reports.stores'));
        $reportResponse->assertOk();
        $reportResponse->assertSee('Toko Aktif');
        $reportResponse->assertSee('Toko Nonaktif');

        // PDF Export
        $pdfResponse = $this->actingAs($user)->get(route('admin.reports.export-pdf', ['type' => 'stores']));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));

        // Test view template PDF Master Toko me-render kolom Maps & hyperlink Google Maps
        $viewContent = view('reports.pdf.stores', [
            'items' => collect([$activeStore, $inactiveStore]),
            'periodLabel' => 'Semua Periode',
            'fromDate' => null,
            'toDate' => null,
            'generatedAt' => now()->format('d M Y, H:i'),
            'printedBy' => 'Administrator',
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'title' => 'LAPORAN MASTER TOKO',
        ])->render();

        $this->assertStringContainsString('Maps', $viewContent);
        $this->assertStringContainsString('Buka Lokasi', $viewContent);
        $this->assertStringContainsString('https://www.google.com/maps?q=-6.2114,106.8456', $viewContent);

        // Excel Export
        $excelExport = new \App\Exports\StoresExport();
        $this->assertContains('Google Maps', $excelExport->headings());
        $excelRows = $excelExport->dataRows();
        $this->assertCount(2, $excelRows);

        $excelResponse = $this->actingAs($user)->get(route('admin.reports.export-excel', ['type' => 'stores']));
        $excelResponse->assertOk();
    }

    public function test_historical_visits_and_routes_stay_intact_when_store_is_deactivated(): void
    {
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $sales = User::factory()->create();
        $sales->assignRole('sales');

        $store = Store::create($this->payload(['code' => 'ST-001', 'name' => 'Toko Riwayat']));

        $route = Route::create([
            'user_id' => $sales->id,
            'created_by' => $sales->id,
            'name' => 'Rute Kemarin',
            'date' => '2026-08-20',
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $visit = Visit::create([
            'user_id' => $sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => '2026-08-20 09:00:00',
            'check_out_at' => '2026-08-20 10:00:00',
            'transaction_amount' => 500000,
        ]);

        // Nonaktifkan toko
        $admin = $this->adminUser();
        $this->actingAs($admin)->delete(route('admin.stores.destroy', $store->id));

        // Verifikasi relasi visit -> store dan stop -> store tetap utuh
        $visit->refresh();
        $this->assertNotNull($visit->store);
        $this->assertEquals('Toko Riwayat', $visit->store->name);

        $stop->refresh();
        $this->assertNotNull($stop->store);
        $this->assertEquals('Toko Riwayat', $stop->store->name);
    }
}
