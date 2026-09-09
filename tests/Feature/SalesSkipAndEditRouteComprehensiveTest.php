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

class SalesSkipAndEditRouteComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales1;
    protected User $sales2;
    protected Store $storeA;
    protected Store $storeB;
    protected Store $storeC;
    protected Store $storeD;
    protected Route $route;
    protected RouteStop $stopA;
    protected RouteStop $stopB;
    protected RouteStop $stopC;
    protected string $dummyPhoto;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);

        $this->sales1 = User::factory()->create(['status' => 'active']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['status' => 'active']);
        $this->sales2->assignRole('sales');

        $this->storeA = Store::create([
            'code' => 'ST-001', 'name' => 'Toko A', 'city' => 'Bandung', 'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id, 'is_delivery_destination' => true,
        ]);
        $this->storeB = Store::create([
            'code' => 'ST-002', 'name' => 'Toko B', 'city' => 'Bandung', 'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id, 'is_delivery_destination' => true,
        ]);
        $this->storeC = Store::create([
            'code' => 'ST-003', 'name' => 'Toko C', 'city' => 'Bandung', 'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id, 'is_delivery_destination' => true,
        ]);
        $this->storeD = Store::create([
            'code' => 'ST-004', 'name' => 'Toko D', 'city' => 'Bandung', 'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id, 'is_delivery_destination' => true,
        ]);

        $this->route = Route::create([
            'user_id' => $this->sales1->id,
            'created_by' => $this->sales1->id,
            'name' => 'Rute Kunjungan Harian',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->stopA = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 60,
            'status' => 'pending',
        ]);

        $this->stopB = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'estimated_duration_minutes' => 45,
            'status' => 'pending',
        ]);

        $this->stopC = RouteStop::create([
            'route_id' => $this->route->id,
            'store_id' => $this->storeC->id,
            'sequence' => 3,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('fake_image_content');
    }

    /**
     * TEST 10, 11, 12, 14, 15, 18, 19, 20: Skip menyimpan latitude, longitude, timestamp, alasan, foto,
     * dan Detail Skip menampilkan event khusus "Kunjungan Dilewati" tanpa Check In / Check Out palsu.
     */
    public function test_skip_stores_location_and_detail_renders_kunjungan_dilewati(): void
    {
        $this->route->update(['status' => 'active', 'started_at' => now()]);

        $response = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->route->id, $this->stopA->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup banjir',
            'photo' => $this->dummyPhoto,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Sudirman No. 123',
            'maps_url' => 'https://www.google.com/maps?q=-6.2088,106.8456',
        ]);

        $response->assertOk();

        $visit = Visit::where('route_stop_id', $this->stopA->id)->first();
        $this->assertNotNull($visit);
        $this->assertEquals(-6.2088, (float) $visit->check_in_lat);
        $this->assertEquals(106.8456, (float) $visit->check_in_lng);
        $this->assertEquals('Toko tutup banjir', $visit->initial_notes);

        // Open Visit detail page
        $detailRes = $this->actingAs($this->sales1)->get(route('visit.show', $visit->id));
        $detailRes->assertOk();
        $detailRes->assertSee('Detail Kunjungan Dilewati');
        $detailRes->assertSee('Kunjungan Dilewati');
        $detailRes->assertSee('Toko tutup banjir');
        $detailRes->assertSee('-6.2088');
        $detailRes->assertSee('106.8456');
        $detailRes->assertSee('Jl. Sudirman No. 123');

        // Pastikan tidak ada card Check In dan Check Out pada kunjungan yang dilewati
        $detailRes->assertDontSee('Check In Kunjungan');
        $detailRes->assertDontSee('Check Out Kunjungan');
    }
    public function test_skip_requires_photo_and_rejected_without_photo(): void
    {
        $this->route->update(['status' => 'active', 'started_at' => now()]);

        $response = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->route->id, $this->stopA->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup sementara',
            'photo' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photo']);

        $this->stopA->refresh();
        $this->assertEquals('pending', $this->stopA->status);
    }

    /**
     * TEST 9, 10, 11, 12: Edit membuka data route existing, form tidak kosong, nama & estimasi muncul.
     */
    public function test_route_detail_page_loads_existing_route_stops_for_edit(): void
    {
        $response = $this->actingAs($this->sales1)->get(route('route.show', $this->route->id));
        $response->assertOk();
        $response->assertSee('Edit Kunjungan');
        $response->assertSee('Toko A');
        $response->assertSee('Toko B');
        $response->assertSee('Toko C');
        $response->assertSee('60 mnt');
        $response->assertSee('45 mnt');
        $response->assertSee('30 mnt');
    }

    /**
     * TEST 13, 14, 16, 17, 18, 19, 20: Sales mengganti toko (A -> D), mengubah urutan (C->1, D->2, B->3),
     * update route existing secara atomik tanpa duplikasi.
     */
    public function test_edit_route_updates_existing_route_stores_and_order(): void
    {
        $response = $this->actingAs($this->sales1)->putJson(route('route.sequence', $this->route->id), [
            'stops' => [
                ['store_id' => $this->storeC->id, 'sequence' => 1, 'estimated_duration_minutes' => 30, 'notes' => 'Kunjungan pertama'],
                ['store_id' => $this->storeD->id, 'sequence' => 2, 'estimated_duration_minutes' => 60, 'notes' => 'Pengganti toko A'],
                ['store_id' => $this->storeB->id, 'sequence' => 3, 'estimated_duration_minutes' => 45, 'notes' => 'Kunjungan ketiga'],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Rute kunjungan berhasil diperbarui.',
        ]);

        // Route count remains 1 (no duplicate route created)
        $this->assertEquals(1, Route::where('user_id', $this->sales1->id)->count());

        $stops = $this->route->fresh()->stops()->orderBy('sequence')->get();
        $this->assertCount(3, $stops);

        $this->assertEquals($this->storeC->id, $stops[0]->store_id);
        $this->assertEquals(1, $stops[0]->sequence);

        $this->assertEquals($this->storeD->id, $stops[1]->store_id);
        $this->assertEquals(2, $stops[1]->sequence);

        $this->assertEquals($this->storeB->id, $stops[2]->store_id);
        $this->assertEquals(3, $stops[2]->sequence);

        // Verify route detail page reflects the updated stores
        $viewRes = $this->actingAs($this->sales1)->get(route('route.show', $this->route->id));
        $viewRes->assertOk();
        $viewRes->assertSee('Toko C');
        $viewRes->assertSee('Toko D');
        $viewRes->assertSee('Toko B');
    }

    /**
     * TEST 15: Urutan tidak boleh duplicate.
     */
    public function test_edit_route_rejects_duplicate_sequence(): void
    {
        $response = $this->actingAs($this->sales1)->putJson(route('route.sequence', $this->route->id), [
            'stops' => [
                ['store_id' => $this->storeA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
                ['store_id' => $this->storeB->id, 'sequence' => 1, 'estimated_duration_minutes' => 45],
                ['store_id' => $this->storeC->id, 'sequence' => 3, 'estimated_duration_minutes' => 30],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['stops.*.sequence']);
    }

    /**
     * TEST 21 & 22: Edit setelah route dimulai ditolak (direct request ditolak).
     */
    public function test_edit_route_after_start_is_rejected(): void
    {
        $this->route->update(['status' => 'active', 'started_at' => now()]);

        $response = $this->actingAs($this->sales1)->putJson(route('route.sequence', $this->route->id), [
            'stops' => [
                ['store_id' => $this->storeA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Rute sudah dimulai dan tidak dapat diedit.',
        ]);
    }

    /**
     * TEST 23: Sales tidak dapat mengedit route Sales lain.
     */
    public function test_sales_cannot_edit_other_sales_route(): void
    {
        $response = $this->actingAs($this->sales2)->putJson(route('route.sequence', $this->route->id), [
            'stops' => [
                ['store_id' => $this->storeA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);

        $response->assertStatus(404);
    }
}
