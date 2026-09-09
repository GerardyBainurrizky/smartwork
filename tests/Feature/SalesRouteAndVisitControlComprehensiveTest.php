<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRouteAndVisitControlComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeD;
    private Route $routeSales;
    private RouteStop $stopA;
    private RouteStop $stopB;
    private RouteStop $stopC;
    private string $dummyPhoto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales1 = User::factory()->create(['name' => 'Sales Satu']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Dua']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko A',
            'code' => 'TKA-01',
            'address' => 'Jl. A No. 1',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko B',
            'code' => 'TKB-02',
            'address' => 'Jl. B No. 2',
            'latitude' => -6.175400,
            'longitude' => 106.827200,
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->storeC = Store::create([
            'name' => 'Toko C',
            'code' => 'TKC-03',
            'address' => 'Jl. C No. 3',
            'latitude' => -6.175500,
            'longitude' => 106.827300,
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->storeD = Store::create([
            'name' => 'Toko D',
            'code' => 'TKD-04',
            'address' => 'Jl. D No. 4',
            'latitude' => -6.175600,
            'longitude' => 106.827400,
            'city' => 'Jakarta Barat',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);

        $this->routeSales = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Kunjungan 1',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->stopA = RouteStop::create([
            'route_id' => $this->routeSales->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $this->stopB = RouteStop::create([
            'route_id' => $this->routeSales->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $this->stopC = RouteStop::create([
            'route_id' => $this->routeSales->id,
            'store_id' => $this->storeC->id,
            'sequence' => 3,
            'estimated_duration_minutes' => 30,
            'status' => 'pending',
        ]);

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('sample_image_data');
    }

    private function checkInPayload(RouteStop $stop, Store $store): array
    {
        return [
            'route_stop_id' => $stop->id,
            'route_id' => $stop->route_id,
            'store_id' => $store->id,
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Jakarta Barat',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
            'initial_notes' => 'Catatan awal',
        ];
    }

    private function checkOutPayload(): array
    {
        return [
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'address' => 'Jakarta Barat Check Out',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Kunjungan selesai',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ];
    }

    /**
     * TEST 1: Sales dapat Check In kunjungan pertama
     */
    public function test_1_sales_dapat_check_in_kunjungan_pertama(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        $res = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->checkInPayload($this->stopA, $this->storeA));
        $res->assertOk();
        $this->assertDatabaseHas('visits', [
            'route_stop_id' => $this->stopA->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * TEST 2: Sales tidak dapat Check In kunjungan kedua jika pertama belum selesai
     */
    public function test_2_sales_tidak_dapat_check_in_kunjungan_kedua_jika_pertama_belum_selesai(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        // Check In stop 2 directly -> Rejected
        $res = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->checkInPayload($this->stopB, $this->storeB));
        $res->assertStatus(422);
        $res->assertJsonFragment(['status' => 'error']);
        $this->assertStringContainsString('Toko A', $res->json('message'));
    }

    /**
     * TEST 3: Sales tidak dapat Check In kunjungan ketiga jika pertama atau kedua belum selesai
     */
    public function test_3_sales_tidak_dapat_check_in_kunjungan_ketiga_jika_sebelumnya_belum_selesai(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        // Check In stop 3 directly -> Rejected
        $res = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->checkInPayload($this->stopC, $this->storeC));
        $res->assertStatus(422);
        $this->assertStringContainsString('Toko A', $res->json('message'));
    }

    /**
     * TEST 4: Setelah Check Out pertama, kunjungan kedua dapat Check In
     */
    public function test_4_setelah_check_out_pertama_kunjungan_kedua_dapat_check_in(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        // 1. Check in & checkout stop 1
        $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->checkInPayload($this->stopA, $this->storeA))->assertOk();
        $visitA = Visit::where('route_stop_id', $this->stopA->id)->first();
        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visitA->id), $this->checkOutPayload())->assertOk();

        // 2. Check in stop 2 -> Berhasil
        $resB = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->checkInPayload($this->stopB, $this->storeB));
        $resB->assertOk();
    }

    /**
     * TEST 5: Setelah Skip pertama dengan valid, kunjungan kedua dapat dimulai
     */
    public function test_5_setelah_skip_pertama_dengan_valid_kunjungan_kedua_dapat_dimulai(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        // Skip stop 1 dengan alasan & foto
        $skipRes = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->routeSales->id, $this->stopA->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup hari libur',
            'photo' => $this->dummyPhoto,
        ]);
        $skipRes->assertOk();

        // Check in stop 2 -> Berhasil
        $resB = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->checkInPayload($this->stopB, $this->storeB));
        $resB->assertOk();
    }

    /**
     * TEST 6: Tidak dapat bypass urutan melalui direct URL check-in-form
     */
    public function test_6_tidak_dapat_bypass_urutan_melalui_direct_url(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        // Direct GET check-in-form stop 2 -> Redirect to route.show with error
        $res = $this->actingAs($this->sales1)->get(route('visit.check-in-form', $this->stopB->id));
        $res->assertRedirect(route('route.show', $this->routeSales->id));
        $res->assertSessionHas('error');
    }

    /**
     * TEST 8, 9, 10, 11: Check out lokasi realtime & validasi backend
     */
    public function test_8_9_10_11_checkout_lokasi_realtime_dan_validasi_backend(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->checkInPayload($this->stopA, $this->storeA))->assertOk();
        $visitA = Visit::where('route_stop_id', $this->stopA->id)->first();

        // Check out with invalid latitude
        $resInvalid = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visitA->id), [
            'latitude' => 150.0, // Invalid lat
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Selesai',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
        ]);
        $resInvalid->assertStatus(422);

        // Check out with valid coordinates
        $resValid = $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visitA->id), $this->checkOutPayload());
        $resValid->assertOk();

        $visitA->refresh();
        $this->assertEquals(-6.175392, (float) $visitA->check_out_lat);
        $this->assertEquals('completed', $visitA->status);
    }

    /**
     * TEST 13, 14, 15, 16, 17, 18: Edit route sebelum dimulai
     */
    public function test_13_sampai_18_edit_route_sebelum_dimulai(): void
    {
        // Route is still draft (not started)
        $editRes = $this->actingAs($this->sales1)->putJson(route('route.sequence', $this->routeSales->id), [
            'stops' => [
                ['store_id' => $this->storeD->id, 'sequence' => 1, 'estimated_duration_minutes' => 45, 'notes' => 'Toko Baru'],
                ['store_id' => $this->storeC->id, 'sequence' => 2, 'estimated_duration_minutes' => 30, 'notes' => 'Urutan 2'],
                ['store_id' => $this->storeB->id, 'sequence' => 3, 'estimated_duration_minutes' => 25, 'notes' => 'Urutan 3'],
            ],
        ]);

        $editRes->assertOk();

        $stops = $this->routeSales->fresh()->stops()->orderBy('sequence')->get();
        $this->assertCount(3, $stops);
        $this->assertEquals($this->storeD->id, $stops[0]->store_id);
        $this->assertEquals(1, $stops[0]->sequence);
        $this->assertEquals($this->storeC->id, $stops[1]->store_id);
        $this->assertEquals(2, $stops[1]->sequence);
        $this->assertEquals($this->storeB->id, $stops[2]->store_id);
        $this->assertEquals(3, $stops[2]->sequence);
    }

    /**
     * TEST 19 & 20: Setelah route dimulai, edit ditolak
     */
    public function test_19_20_setelah_route_dimulai_edit_ditolak(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        $editRes = $this->actingAs($this->sales1)->putJson(route('route.sequence', $this->routeSales->id), [
            'stops' => [
                ['store_id' => $this->storeD->id, 'sequence' => 1, 'estimated_duration_minutes' => 45],
            ],
        ]);

        $editRes->assertStatus(422);
    }

    /**
     * TEST 21: Sales tidak dapat mengedit route Sales lain
     */
    public function test_21_sales_tidak_dapat_mengedit_route_sales_lain(): void
    {
        $editRes = $this->actingAs($this->sales2)->putJson(route('route.sequence', $this->routeSales->id), [
            'stops' => [
                ['store_id' => $this->storeD->id, 'sequence' => 1, 'estimated_duration_minutes' => 45],
            ],
        ]);

        $editRes->assertStatus(404);
    }

    /**
     * TEST 22 & 23: Skip tanpa alasan atau tanpa foto ditolak
     */
    public function test_22_23_skip_tanpa_alasan_atau_foto_ditolak(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        // Tanpa alasan
        $resNoReason = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->routeSales->id, $this->stopA->id]), [
            'status' => 'skipped',
            'notes' => '',
            'photo' => $this->dummyPhoto,
        ]);
        $resNoReason->assertStatus(422);

        // Tanpa foto
        $resNoPhoto = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->routeSales->id, $this->stopA->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup',
            'photo' => '',
        ]);
        $resNoPhoto->assertStatus(422);
    }

    /**
     * TEST 24 & 25: Skip dengan alasan + foto berhasil dan tercatat di database
     */
    public function test_24_25_skip_dengan_alasan_dan_foto_berhasil(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        $res = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->routeSales->id, $this->stopA->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup renovasi',
            'photo' => $this->dummyPhoto,
        ]);
        $res->assertOk();

        $this->stopA->refresh();
        $this->assertEquals('skipped', $this->stopA->status);
        $this->assertEquals('Toko tutup renovasi', $this->stopA->notes);

        $this->assertDatabaseHas('visits', [
            'route_stop_id' => $this->stopA->id,
            'status' => 'completed',
        ]);
    }

    /**
     * TEST 27: Skip tidak dapat digunakan untuk melompati urutan secara sembarangan
     */
    public function test_27_skip_tidak_dapat_melompati_urutan(): void
    {
        $this->routeSales->update(['status' => 'active', 'started_at' => now()]);

        // Mencoba skip stop 2 langsung padahal stop 1 belum selesai/skipped -> Ditolak
        $res = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->routeSales->id, $this->stopB->id]), [
            'status' => 'skipped',
            'notes' => 'Mau skip stop 2',
            'photo' => $this->dummyPhoto,
        ]);
        $res->assertStatus(422);
        $this->assertStringContainsString('Toko A', $res->json('message'));
    }
}
