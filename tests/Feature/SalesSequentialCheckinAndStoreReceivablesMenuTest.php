<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesSequentialCheckinAndStoreReceivablesMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $sales1;
    private User $sales2;
    private User $driver;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeD;
    private Route $routeSales1;
    private RouteStop $stopA;
    private RouteStop $stopB;
    private RouteStop $stopC;
    private string $dummyPhoto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales1 = User::factory()->create(['name' => 'Sales Budi']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Sales Citra']);
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Agus']);
        $this->driver->assignRole('driver');

        $this->storeA = Store::create([
            'name' => 'Toko A',
            'code' => 'TKA-01',
            'address' => 'Jl. Mawar No. 1',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko B',
            'code' => 'TKB-02',
            'address' => 'Jl. Melati No. 2',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeC = Store::create([
            'name' => 'Toko C',
            'code' => 'TKC-03',
            'address' => 'Jl. Anggrek No. 3',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->storeD = Store::create([
            'name' => 'Toko D Milik Sales 2',
            'code' => 'TKD-04',
            'address' => 'Jl. Kenanga No. 4',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);

        $this->routeSales1 = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Kunjungan Sales 1',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->stopA = RouteStop::create([
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $this->stopB = RouteStop::create([
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        $this->stopC = RouteStop::create([
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeC->id,
            'sequence' => 3,
            'status' => 'pending',
        ]);

        $this->dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('sample_image');
    }

    /**
     * TEST A: Sales dapat Check In kunjungan pertama
     */
    public function test_a_sales_dapat_check_in_kunjungan_pertama(): void
    {
        $res = $this->actingAs($this->sales1)->postJson(route('visit.store'), [
            'route_stop_id' => $this->stopA->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeA->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ]);

        $res->assertOk();
        $this->assertDatabaseHas('visits', [
            'route_stop_id' => $this->stopA->id,
            'user_id' => $this->sales1->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * TEST B & C & D: Sales tidak dapat Check In ke Toko B jika Toko A belum Check Out (Pesan jelas & Visit kedua tidak dibuat)
     */
    public function test_b_c_d_sales_tidak_dapat_check_in_sebelum_kunjungan_sebelumnya_checkout(): void
    {
        // 1. Check in Toko A
        $this->actingAs($this->sales1)->postJson(route('visit.store'), [
            'route_stop_id' => $this->stopA->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeA->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ])->assertOk();

        // 2. Attempt check in Toko B (Form redirect & API reject)
        $resFormB = $this->actingAs($this->sales1)->get(route('visit.check-in-form', $this->stopB->id));
        $resFormB->assertRedirect(route('route.show', $this->routeSales1->id));
        $resFormB->assertSessionHas('error');

        $resApiB = $this->actingAs($this->sales1)->postJson(route('visit.store'), [
            'route_stop_id' => $this->stopB->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeB->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ]);

        $resApiB->assertStatus(422);
        $resApiB->assertJsonFragment([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('Toko A', $resApiB->json('message'));
        $this->assertEquals(0, Visit::where('route_stop_id', $this->stopB->id)->count());
    }

    /**
     * TEST E & F: Setelah Check Out Toko A, Check In Toko B berhasil; Toko C tetap menunggu sampai Toko B selesai
     */
    public function test_e_f_kunjungan_berikutnya_dapat_check_in_setelah_checkout(): void
    {
        // 1. Check in & Check out Toko A
        $visitA = Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $this->stopA->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jakarta',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'selfieA.jpg',
        ]);

        $this->actingAs($this->sales1)->postJson(route('visit.check-out', $visitA->id), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'visit_result' => 'Beres',
            'transaction_status' => 'none',
            'selfie' => $this->dummyPhoto,
            'final_store_photo' => $this->dummyPhoto,
        ])->assertOk();

        // 2. Check in Toko B -> Berhasil
        $resB = $this->actingAs($this->sales1)->postJson(route('visit.store'), [
            'route_stop_id' => $this->stopB->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeB->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ]);
        $resB->assertOk();

        // 3. Check in Toko C saat Toko B belum checkout -> Ditolak
        $resC = $this->actingAs($this->sales1)->postJson(route('visit.store'), [
            'route_stop_id' => $this->stopC->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeC->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ]);
        $resC->assertStatus(422);
    }

    /**
     * TEST G: Visit lama dari tanggal kemarin tidak mengunci route hari ini
     */
    public function test_g_visit_lama_kemarin_tidak_mengunci_route_hari_ini(): void
    {
        // 1. Rute kemarin dengan Toko A (sudah selesai/in_progress kemarin)
        $routeKemarin = Route::create([
            'user_id' => $this->sales1->id,
            'name' => 'Rute Kemarin',
            'date' => now()->subDays(2)->toDateString(),
            'status' => 'active',
            'started_at' => now()->subDays(2),
        ]);
        $stopKemarin = RouteStop::create([
            'route_id' => $routeKemarin->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $stopKemarin->id,
            'route_id' => $routeKemarin->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subDays(2),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jakarta',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'old_selfie.jpg',
        ]);

        // 2. Rute hari ini mulai dari kunjungan pertama (stopA)
        $resA = $this->actingAs($this->sales1)->postJson(route('visit.store'), [
            'route_stop_id' => $this->stopA->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeA->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Jakarta',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ]);

        $resA->assertOk();
    }

    /**
     * TEST K: Sales lain tidak terkena blocker milik Sales 1
     */
    public function test_k_sales_lain_tidak_terkena_blocker(): void
    {
        // Sales 1 sedang in_progress di Toko A
        Visit::create([
            'user_id' => $this->sales1->id,
            'route_stop_id' => $this->stopA->id,
            'route_id' => $this->routeSales1->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'check_in_address' => 'Jakarta',
            'check_in_maps_url' => 'https://maps.google.com',
            'selfie' => 'selfieA.jpg',
        ]);

        // Route Sales 2 di Toko D
        $routeSales2 = Route::create([
            'user_id' => $this->sales2->id,
            'name' => 'Rute Sales 2',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stopD = RouteStop::create([
            'route_id' => $routeSales2->id,
            'store_id' => $this->storeD->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Sales 2 check in -> Berhasil
        $resSales2 = $this->actingAs($this->sales2)->postJson(route('visit.store'), [
            'route_stop_id' => $stopD->id,
            'route_id' => $routeSales2->id,
            'store_id' => $this->storeD->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Bandung',
            'maps_url' => 'https://maps.google.com',
            'storefront_photo' => $this->dummyPhoto,
            'selfie' => $this->dummyPhoto,
        ]);

        $resSales2->assertOk();
    }

    /**
     * TEST L & M & N & O: Sales dapat membuka menu Toko & Piutang, Driver ditolak, Sales hanya melihat toko miliknya
     */
    public function test_l_m_n_o_menu_toko_dan_piutang_sales_dan_isolasi_driver(): void
    {
        // 1. Driver ditolak
        $this->actingAs($this->driver)->get(route('sales.stores.index'))->assertRedirect();

        // 2. Sales 1 dapat akses
        $res = $this->actingAs($this->sales1)->get(route('sales.stores.index'));
        $res->assertOk();
        $res->assertSee('Toko &amp; Piutang', false);
        $res->assertSee('Toko A');
        $res->assertSee('Toko B');
        $res->assertSee('Toko C');
        $res->assertDontSee('Toko D Milik Sales 2');
    }

    /**
     * TEST P, Q, R, S, T, U: Verifikasi metrik KPI Sales & Total piutang gabungan per toko (250.000 + 5.000.000 = 5.250.000)
     */
    public function test_p_q_r_s_t_u_metrik_kpi_dan_total_piutang_gabungan(): void
    {
        // Toko A memiliki 2 transaksi: 250.000 + 5.000.000 = 5.250.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 250000,
        ], $this->sales1);

        StoreReceivableService::createTransaction([
            'store_id' => $this->storeA->id,
            'transaction_amount' => 5000000,
        ], $this->sales1);

        // Toko B: 0 piutang
        // Toko C: 1 transaksi 750.000
        StoreReceivableService::createTransaction([
            'store_id' => $this->storeC->id,
            'transaction_amount' => 750000,
        ], $this->sales1);

        $res = $this->actingAs($this->sales1)->get(route('sales.stores.index'));
        $res->assertOk();
        $res->assertSee('3'); // Total toko
        $res->assertSee('2'); // Toko berpiutang
        $res->assertSee('Rp 6.000.000'); // Total piutang Sales 1 (5.250.000 + 750.000)
        $res->assertSee('Rp 5.250.000'); // Total Toko A
        $res->assertSee('Tidak Ada Piutang'); // Toko B
    }

    /**
     * TEST Y: Sales tidak dapat mengakses detail toko milik Sales lain
     */
    public function test_y_sales_tidak_dapat_mengakses_detail_toko_sales_lain(): void
    {
        $this->actingAs($this->sales1)->get(route('sales.stores.show', $this->storeD->id))->assertNotFound();
    }
}
