<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesSequentialRouteCheckInValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeD;
    private Store $storeE;
    private Route $routeA;
    private Route $routeB;
    private RouteStop $stopA1;
    private RouteStop $stopA2;
    private RouteStop $stopA3;
    private RouteStop $stopB1;
    private RouteStop $stopB2;
    private string $dummyPhoto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales1 = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Citra']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Doni']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko A Alpha',
            'code' => 'TKA-01',
            'address' => 'Jl. Mawar No. 1',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko B Beta',
            'code' => 'TKB-02',
            'address' => 'Jl. Melati No. 2',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeC = Store::create([
            'name' => 'Toko C Gamma',
            'code' => 'TKC-03',
            'address' => 'Jl. Anggrek No. 3',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeD = Store::create([
            'name' => 'Toko D Delta',
            'code' => 'TKD-04',
            'address' => 'Jl. Kenanga No. 4',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeE = Store::create([
            'name' => 'Toko E Epsilon',
            'code' => 'TKE-05',
            'address' => 'Jl. Cempaka No. 5',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        // Route A milik Sales 1 (Senin)
        $this->routeA = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute A Senin',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stopA1 = RouteStop::create([
            'route_id' => $this->routeA->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $this->stopA2 = RouteStop::create([
            'route_id' => $this->routeA->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        $this->stopA3 = RouteStop::create([
            'route_id' => $this->routeA->id,
            'store_id' => $this->storeC->id,
            'sequence' => 3,
            'status' => 'pending',
        ]);

        // Route B milik Sales 1 (Selasa / Rute berbeda)
        $this->routeB = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute B Selasa',
            'date' => now()->addDay()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stopB1 = RouteStop::create([
            'route_id' => $this->routeB->id,
            'store_id' => $this->storeD->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $this->stopB2 = RouteStop::create([
            'route_id' => $this->routeB->id,
            'store_id' => $this->storeE->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('sample_image');
    }

    private function payload(RouteStop $stop, Store $store): array
    {
        return [
            'route_stop_id' => $stop->id,
            'route_id' => $stop->route_id,
            'store_id' => $store->id,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'address' => 'Jl. Test No. 123, Jakarta',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
            'initial_notes' => 'Catatan check in',
        ];
    }

    /**
     * TEST 1: Route A Visit 1 belum selesai, Sales mencoba Check In Route B Visit 1 -> SUCCESS (Per-Route isolation)
     */
    public function test_1_route_a_belum_selesai_route_b_visit_1_dapat_check_in(): void
    {
        // Route A Visit 1 belum selesai (bahkan belum check in atau sedang in progress)
        // Check in Route B Visit 1
        $res = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopB1, $this->storeD));

        $res->assertOk();
        $this->assertDatabaseHas('visits', [
            'route_stop_id' => $this->stopB1->id,
            'route_id' => $this->routeB->id,
            'store_id' => $this->storeD->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * TEST 2: Route A Visit 1 belum selesai (belum mulai), Sales mencoba Check In Route A Visit 2 -> REJECT
     */
    public function test_2_route_a_visit_1_belum_mulai_sales_mencoba_visit_2_ditolak(): void
    {
        // Visit 1 Route A masih pending belum check in
        $res = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA2, $this->storeB));

        $res->assertStatus(422);
        $res->assertJsonFragment(['status' => 'error']);
        $this->assertStringContainsString('Toko A Alpha', $res->json('message'));
        $this->assertStringContainsString('Toko B Beta', $res->json('message'));

        // Form direct access juga redirect dengan error
        $resForm = $this->actingAs($this->sales1)->get(route('visit.check-in-form', $this->stopA2->id));
        $resForm->assertRedirect(route('route.show', $this->routeA->id));
        $resForm->assertSessionHas('error');
        $resForm->assertSessionHas('error_stop_id', $this->stopA2->id);
    }

    /**
     * TEST 3: Route A Visit 1 CHECK IN tetapi belum CHECK OUT, Sales mencoba Visit 2 -> REJECT
     */
    public function test_3_route_a_visit_1_check_in_belum_check_out_sales_mencoba_visit_2_ditolak(): void
    {
        // Check in stop A1
        $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA1, $this->storeA))->assertOk();

        // Coba check in stop A2
        $res = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA2, $this->storeB));

        $res->assertStatus(422);
        $res->assertJsonFragment(['status' => 'error']);
        $this->assertStringContainsString('Toko A Alpha', $res->json('message'));
        $this->assertStringContainsString('Check Out', $res->json('message'));
    }

    /**
     * TEST 4: Route A Visit 1 CHECK OUT, Sales mencoba Visit 2 -> SUCCESS
     */
    public function test_4_route_a_visit_1_check_out_sales_mencoba_visit_2_berhasil(): void
    {
        // Check in stop A1
        $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA1, $this->storeA))->assertOk();
        $visitA1 = Visit::where('route_stop_id', $this->stopA1->id)->first();

        // Check out stop A1
        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visitA1->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Selesai transaksi',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        // Check in stop A2 -> Berhasil
        $res2 = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA2, $this->storeB));
        $res2->assertOk();
        $this->assertDatabaseHas('visits', [
            'route_stop_id' => $this->stopA2->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * TEST 5: Route A Visit 1 DILEWATI, Sales mencoba Visit 2 -> SUCCESS
     */
    public function test_5_route_a_visit_1_dilewati_sales_mencoba_visit_2_berhasil(): void
    {
        // Lewati stop A1 dengan alasan dan foto
        $skipRes = $this->actingAs($this->sales1)->patchJson(route('route.stop.update', [$this->routeA->id, $this->stopA1->id]), [
            'status' => 'skipped',
            'notes' => 'Toko tutup hari libur',
            'photo' => $this->dummyPhoto,
        ]);
        $skipRes->assertOk();

        // Check in stop A2 -> Berhasil
        $res2 = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA2, $this->storeB));
        $res2->assertOk();
        $this->assertDatabaseHas('visits', [
            'route_stop_id' => $this->stopA2->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * TEST 6: Route A Visit 1 selesai, Visit 2 CHECK IN, Visit 3 belum mulai. Sales mencoba Visit 3 -> REJECT menyebut Toko B
     */
    public function test_6_route_a_visit_1_selesai_visit_2_check_in_sales_mencoba_visit_3_ditolak_menyebut_toko_b(): void
    {
        // 1. Visit 1 selesai (check in & check out)
        $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA1, $this->storeA))->assertOk();
        $visitA1 = Visit::where('route_stop_id', $this->stopA1->id)->first();
        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visitA1->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Selesai',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        // 2. Visit 2 Check In (sedang in_progress)
        $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA2, $this->storeB))->assertOk();

        // 3. Sales mencoba Check In Visit 3 -> Ditolak
        $res3 = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA3, $this->storeC));
        $res3->assertStatus(422);
        $res3->assertJsonFragment(['status' => 'error']);

        // Pesan harus menyebut Toko B Beta dan proses / check out
        $this->assertStringContainsString('Toko B Beta', $res3->json('message'));
        $this->assertStringContainsString('Toko C Gamma', $res3->json('message'));
        $this->assertStringContainsString('Check Out', $res3->json('message'));
    }

    /**
     * TEST 7: Route A belum selesai (Toko A1 in progress), Route B boleh berjalan (Check In Toko B1 lalu B2)
     */
    public function test_7_route_a_belum_selesai_route_b_tetap_dapat_berjalan_sequential(): void
    {
        // Route A Toko A1 Check In (belum check out)
        $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopA1, $this->storeA))->assertOk();

        // Route B Toko B1 Check In -> Harus Berhasil (tidak diblokir Route A)
        $resB1 = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopB1, $this->storeD));
        $resB1->assertOk();

        // Route B Toko B2 Check In saat Toko B1 belum checkout -> Ditolak oleh aturan sequential internal Route B
        $resB2 = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopB2, $this->storeE));
        $resB2->assertStatus(422);
        $this->assertStringContainsString('Toko D Delta', $resB2->json('message'));

        // Check out Route B Toko B1
        $visitB1 = Visit::where('route_stop_id', $this->stopB1->id)->first();
        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visitB1->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Selesai delivery',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        // Route B Toko B2 Check In setelah Toko B1 checkout -> Berhasil
        $resB2Success = $this->actingAs($this->sales1)->postJson(route('visit.store'), $this->payload($this->stopB2, $this->storeE));
        $resB2Success->assertOk();
    }

    /**
     * SECURITY TEST: Sales tidak dapat memanipulasi route_id, akses route sales lain, atau bypass order
     */
    public function test_security_sales_tidak_dapat_bypass_atau_akses_route_lain(): void
    {
        // 1. Sales 2 tidak dapat check in di Route A milik Sales 1
        $resForeignUser = $this->actingAs($this->sales2)->postJson(route('visit.store'), $this->payload($this->stopA1, $this->storeA));
        $resForeignUser->assertStatus(403);

        // 2. Manipulasi route_id (mengirim stopA1 dengan routeB)
        $payloadTampered = $this->payload($this->stopA1, $this->storeA);
        $payloadTampered['route_id'] = $this->routeB->id;
        $resTampered = $this->actingAs($this->sales1)->postJson(route('visit.store'), $payloadTampered);
        $resTampered->assertStatus(422);

        // 3. Direct GET check-in form route milik orang lain -> 403
        $this->actingAs($this->sales2)->get(route('visit.check-in-form', $this->stopA1->id))->assertForbidden();
    }
}
