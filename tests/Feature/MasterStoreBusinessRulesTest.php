<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreSubmission;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterStoreBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $sales1;
    protected User $sales2;
    protected User $driver1;
    protected User $driver2;

    protected Store $tokoA;
    protected Store $tokoB;
    protected Store $tokoC;
    protected Store $tokoD;
    protected Store $tokoE;
    protected Store $tokoX;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->sales1 = User::factory()->create(['status' => 'active', 'name' => 'Sales Satu']);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['status' => 'active', 'name' => 'Sales Dua']);
        $this->sales2->assignRole('sales');

        $this->driver1 = User::factory()->create(['status' => 'active', 'name' => 'Driver Satu']);
        $this->driver1->assignRole('driver');

        $this->driver2 = User::factory()->create(['status' => 'active', 'name' => 'Driver Dua']);
        $this->driver2->assignRole('driver');

        // Setup Toko
        // Toko A, B, C -> Sales 1, Pengiriman = Ya
        $this->tokoA = Store::create([
            'code' => 'ST-001',
            'name' => 'Toko A',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat A',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);
        $this->tokoB = Store::create([
            'code' => 'ST-002',
            'name' => 'Toko B',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat B',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);
        $this->tokoC = Store::create([
            'code' => 'ST-003',
            'name' => 'Toko C',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat C',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);

        // Toko D, E -> Sales 2, Pengiriman = Ya
        $this->tokoD = Store::create([
            'code' => 'ST-004',
            'name' => 'Toko D',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat D',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);
        $this->tokoE = Store::create([
            'code' => 'ST-005',
            'name' => 'Toko E',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat E',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);

        // Toko X -> Sales NULL, Pengiriman = Ya
        $this->tokoX = Store::create([
            'code' => 'ST-006',
            'name' => 'Toko X',
            'sales_penanggung_jawab_id' => null,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat X',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);
    }

    /**
     * TEST 1, 2, 3: Toko milik Sales 1 -> Driver 1 & Driver 2 dapat memilih toko, dan dapat mengirim berkali-kali
     */
    public function test_driver_1_and_driver_2_can_deliver_to_sales_store_and_deliver_multiple_times(): void
    {
        // Driver 1 memilih Toko A (milik Sales 1)
        $resDriver1 = $this->actingAs($this->driver1)->postJson(route('route.store'), [
            'name' => 'Pengiriman Driver 1 Hari 1',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoA->id, 'sequence' => 1],
            ],
        ]);
        $resDriver1->assertStatus(200);

        // Driver 2 memilih Toko A (milik Sales 1)
        $resDriver2 = $this->actingAs($this->driver2)->postJson(route('route.store'), [
            'name' => 'Pengiriman Driver 2 Hari 1',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoA->id, 'sequence' => 1],
            ],
        ]);
        $resDriver2->assertStatus(200);

        // Driver 1 dapat mengirim ke Toko A berkali-kali di tanggal/rute berbeda
        $resDriver1Day2 = $this->actingAs($this->driver1)->postJson(route('route.store'), [
            'name' => 'Pengiriman Driver 1 Hari 2',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoA->id, 'sequence' => 1],
            ],
        ]);
        $resDriver1Day2->assertStatus(200);
    }

    /**
     * TEST 4 & 5: Toko milik Sales 1 -> Sales 1 boleh kunjungan, Sales 2 tidak boleh
     */
    public function test_sales_1_can_visit_own_store_and_sales_2_rejected(): void
    {
        // Sales 1 boleh membuat kunjungan ke Toko A
        $resSales1 = $this->actingAs($this->sales1)->postJson(route('route.store'), [
            'name' => 'Rute Kunjungan Sales 1',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resSales1->assertStatus(200);

        // Sales 2 tidak boleh membuat kunjungan ke Toko A
        $resSales2 = $this->actingAs($this->sales2)->postJson(route('route.store'), [
            'name' => 'Rute Ilegal Sales 2 ke Toko A',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resSales2->assertStatus(422);
    }

    /**
     * TEST 6 & 7: Toko Umum + Delivery True vs Toko Umum + Delivery False
     */
    public function test_unassigned_store_delivery_true_and_false(): void
    {
        // Toko Umum + Delivery True (Toko X) -> Driver dapat memilih
        $resDriverX = $this->actingAs($this->driver1)->postJson(route('route.store'), [
            'name' => 'Pengiriman Toko X',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoX->id, 'sequence' => 1],
            ],
        ]);
        $resDriverX->assertStatus(200);

        // Toko Umum + Delivery False (Toko Y) -> Driver tidak dapat memilih
        $tokoY = Store::create([
            'code' => 'ST-007',
            'name' => 'Toko Y',
            'sales_penanggung_jawab_id' => null,
            'is_delivery_destination' => false,
            'status' => 'active',
            'address' => 'Alamat Y',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);

        $resDriverY = $this->actingAs($this->driver1)->postJson(route('route.store'), [
            'name' => 'Pengiriman Toko Y',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $tokoY->id, 'sequence' => 1],
            ],
        ]);
        $resDriverY->assertStatus(422);
    }

    /**
     * TEST 8: Admin membuat toko dan memilih Sales -> is_delivery_destination otomatis menjadi true
     */
    public function test_admin_creates_store_with_sales_auto_sets_delivery_true(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.stores.store'), [
            'name' => 'Toko Baru Sales 1',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => 0, // Admin mencoba set 0 secara sengaja/tidak sengaja
            'address' => 'Jl. Otista No. 12',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseHas('stores', [
            'name' => 'Toko Baru Sales 1',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 9: Admin mengedit toko yang memiliki Sales -> tidak dapat membuat is_delivery_destination false
     */
    public function test_admin_editing_store_with_sales_cannot_make_delivery_false(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.stores.update', $this->tokoA->id), [
            'name' => 'Toko A Renamed',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => 0, // Coba dipaksa 0
            'address' => $this->tokoA->address,
            'city' => $this->tokoA->city,
            'province' => $this->tokoA->province,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.stores.index'));

        $this->tokoA->refresh();
        $this->assertTrue((bool) $this->tokoA->is_delivery_destination);
        $this->assertEquals('Toko A Renamed', $this->tokoA->name);
    }

    /**
     * TEST 10: Admin mengubah toko dari Sales menjadi Toko Umum -> Admin kembali dapat menentukan is_delivery_destination true/false
     */
    public function test_admin_changing_store_to_unassigned_can_set_delivery_false_or_true(): void
    {
        // Ubah Toko B dari Sales 1 menjadi Toko Umum dengan delivery = false
        $responseFalse = $this->actingAs($this->admin)->put(route('admin.stores.update', $this->tokoB->id), [
            'name' => $this->tokoB->name,
            'sales_penanggung_jawab_id' => '', // Menjadi NULL
            'is_delivery_destination' => 0, // Set False
            'address' => $this->tokoB->address,
            'city' => $this->tokoB->city,
            'province' => $this->tokoB->province,
            'status' => 'active',
        ]);

        $responseFalse->assertRedirect(route('admin.stores.index'));
        $this->tokoB->refresh();
        $this->assertNull($this->tokoB->sales_penanggung_jawab_id);
        $this->assertFalse((bool) $this->tokoB->is_delivery_destination);

        // Ubah kembali menjadi delivery = true
        $responseTrue = $this->actingAs($this->admin)->put(route('admin.stores.update', $this->tokoB->id), [
            'name' => $this->tokoB->name,
            'sales_penanggung_jawab_id' => '', // Tetap NULL
            'is_delivery_destination' => 1, // Set True
            'address' => $this->tokoB->address,
            'city' => $this->tokoB->city,
            'province' => $this->tokoB->province,
            'status' => 'active',
        ]);

        $responseTrue->assertRedirect(route('admin.stores.index'));
        $this->tokoB->refresh();
        $this->assertNull($this->tokoB->sales_penanggung_jawab_id);
        $this->assertTrue((bool) $this->tokoB->is_delivery_destination);
    }

    /**
     * TEST 7, 8, 9: Sales & Driver tidak boleh mengelola / memodifikasi Master Toko secara langsung
     */
    public function test_sales_and_driver_cannot_modify_master_store(): void
    {
        // Sales cannot access admin stores
        $this->actingAs($this->sales1)->get(route('admin.stores.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($this->sales1)->post(route('admin.stores.store'), [
            'name' => 'Toko Liar',
            'address' => 'Alamat',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'is_delivery_destination' => true,
        ])->assertRedirect(route('dashboard'));

        // Driver cannot access admin stores
        $this->actingAs($this->driver1)->get(route('admin.stores.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($this->driver1)->post(route('admin.stores.store'), [
            'name' => 'Toko Liar',
            'address' => 'Alamat',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'is_delivery_destination' => true,
        ])->assertRedirect(route('dashboard'));
    }

    /**
     * TEST 10 & 11: Sales mengajukan toko baru (tidak langsung masuk Master Toko), Driver tidak punya akses pengajuan
     */
    public function test_sales_can_submit_store_and_driver_cannot(): void
    {
        // Driver cannot access store submission
        $this->actingAs($this->driver1)->get(route('stores.submissions.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($this->driver1)->post(route('stores.submissions.store'), [
            'name' => 'Toko Driver',
            'address' => 'Alamat',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ])->assertRedirect(route('dashboard'));

        // Sales can submit
        $salesRes = $this->actingAs($this->sales1)->post(route('stores.submissions.store'), [
            'name' => 'Toko Pengajuan Sales 1',
            'owner' => 'Pak Budi',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'notes' => 'Toko prospek bagus',
        ]);
        $salesRes->assertRedirect(route('stores.submissions.index'));

        $this->assertDatabaseHas('store_submissions', [
            'name' => 'Toko Pengajuan Sales 1',
            'user_id' => $this->sales1->id,
            'status' => 'pending',
        ]);

        // Toko TIDAK LANGSUNG masuk ke tabel stores
        $this->assertDatabaseMissing('stores', [
            'name' => 'Toko Pengajuan Sales 1',
        ]);
    }

    /**
     * TEST 12: Admin menyetujui toko baru, menentukan sales penanggung jawab & tujuan pengiriman, master toko terbuat
     */
    public function test_admin_approves_submission_and_creates_master_store(): void
    {
        $sub = StoreSubmission::create([
            'user_id' => $this->sales1->id,
            'name' => 'Toko Calon Mitra',
            'address' => 'Jl. Jenderal Sudirman No. 5',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'status' => 'pending',
        ]);

        // Admin approves and assigns to Sales 2, delivery = Ya
        $approveRes = $this->actingAs($this->admin)->post(route('admin.stores.submissions.approve', $sub->id), [
            'name' => 'Toko Calon Mitra',
            'address' => 'Jl. Jenderal Sudirman No. 5',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => 1,
            'status' => 'active',
        ]);

        $approveRes->assertRedirect(route('admin.stores.submissions.index'));

        // Master Toko terbuat
        $this->assertDatabaseHas('stores', [
            'name' => 'Toko Calon Mitra',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => 1,
            'status' => 'active',
        ]);

        // Status submission menjadi approved
        $this->assertDatabaseHas('store_submissions', [
            'id' => $sub->id,
            'status' => 'approved',
            'reviewed_by' => $this->admin->id,
        ]);
    }

    /**
     * TEST 13: Admin/Super Admin create route: toko harus sesuai sales yang dipilih, tolak toko sales lain
     */
    public function test_admin_and_super_admin_create_route_validates_store_ownership_per_sales(): void
    {
        // Admin membuat route untuk Sales 1 dengan Toko A (milik Sales 1) -> Sukses
        $resAdmin = $this->actingAs($this->admin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->sales1->id,
            'name' => 'Rute Sales 1 oleh Admin',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resAdmin->assertOk();

        // Admin mencoba membuat route untuk Sales 1 dengan Toko D (milik Sales 2) -> Tolak (422)
        $resAdminMismatch = $this->actingAs($this->admin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->sales1->id,
            'name' => 'Rute Ilegal Mismatch Sales',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoD->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resAdminMismatch->assertStatus(422);

        // Super Admin membuat route untuk Sales 2 dengan Toko D (milik Sales 2) -> Sukses
        $resSuperAdmin = $this->actingAs($this->superAdmin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->sales2->id,
            'name' => 'Rute Sales 2 oleh Super Admin',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoD->id, 'sequence' => 1, 'estimated_duration_minutes' => 45],
            ],
        ]);
        $resSuperAdmin->assertOk();

        // Super Admin mencoba membuat route untuk Sales 2 dengan Toko A (milik Sales 1) -> Tolak (422)
        $resSuperMismatch = $this->actingAs($this->superAdmin)->postJson(route('admin.routes.store'), [
            'user_id' => $this->sales2->id,
            'name' => 'Rute Ilegal Super Admin',
            'date' => now()->addDay()->toDateString(),
            'stops' => [
                ['store_id' => $this->tokoA->id, 'sequence' => 1, 'estimated_duration_minutes' => 45],
            ],
        ]);
        $resSuperMismatch->assertStatus(422);
    }

    /**
     * TEST 14: Driver Check-Out Pengiriman secara dinamis me-redirect ke Detail Rute Pengiriman yang sama
     */
    public function test_driver_check_out_dynamically_redirects_to_same_delivery_route(): void
    {
        $dummySelfie = 'data:image/jpeg;base64,' . base64_encode('fake-image');

        // Route Pengiriman A untuk Driver 1
        $routeA = Route::create([
            'user_id' => $this->driver1->id,
            'name' => 'Rute Pengiriman Driver A',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stopA = RouteStop::create([
            'route_id' => $routeA->id,
            'store_id' => $this->tokoA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Route Pengiriman B untuk Driver 1 (Pengiriman berulang ke Toko A di rute berbeda)
        $routeB = Route::create([
            'user_id' => $this->driver1->id,
            'name' => 'Rute Pengiriman Driver B',
            'date' => now()->addDay()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);
        $stopB = RouteStop::create([
            'route_id' => $routeB->id,
            'store_id' => $this->tokoA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // Check-In Stop A
        $this->actingAs($this->driver1)->postJson(route('visit.store'), [
            'route_stop_id' => $stopA->id,
            'route_id' => $stopA->route_id,
            'store_id' => $stopA->store_id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Pengiriman A',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
            'selfie' => $dummySelfie,
            'initial_notes' => 'Tiba di lokasi A',
        ])->assertOk();
        $visitA = Visit::where('route_stop_id', $stopA->id)->first();

        // Check-Out Visit A -> Redirect dinamis ke Route A (/route/{routeA_id})
        $resCheckOutA = $this->actingAs($this->driver1)->postJson(route('visit.check-out', $visitA->id), [
            'latitude' => -6.2090,
            'longitude' => 106.8460,
            'address' => 'Jl. Pengiriman A',
            'maps_url' => 'https://maps.google.com/?q=-6.2090,106.8460',
            'visit_result' => 'Barang diterima lengkap oleh pemilik toko',
            'delivered_goods_summary' => '10 Karton Produk',
            'transaction_status' => 'none',
            'selfie' => $dummySelfie,
        ]);
        $resCheckOutA->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.route_id', $routeA->id)
            ->assertJsonPath('data.redirect_url', route('route.show', $routeA->id));

        // Setelah Visit A selesai, sekarang Driver bisa Check-In Stop B
        $this->actingAs($this->driver1)->postJson(route('visit.store'), [
            'route_stop_id' => $stopB->id,
            'route_id' => $stopB->route_id,
            'store_id' => $stopB->store_id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jl. Pengiriman B',
            'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
            'selfie' => $dummySelfie,
            'initial_notes' => 'Tiba di lokasi B',
        ])->assertOk();
        $visitB = Visit::where('route_stop_id', $stopB->id)->first();

        // Check-Out Visit B -> Redirect dinamis ke Route B (/route/{routeB_id})
        $resCheckOutB = $this->actingAs($this->driver1)->postJson(route('visit.check-out', $visitB->id), [
            'latitude' => -6.2090,
            'longitude' => 106.8460,
            'address' => 'Jl. Pengiriman B',
            'maps_url' => 'https://maps.google.com/?q=-6.2090,106.8460',
            'visit_result' => 'Barang diterima',
            'delivered_goods_summary' => '5 Karton Produk',
            'transaction_status' => 'none',
            'selfie' => $dummySelfie,
        ]);
        $resCheckOutB->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.route_id', $routeB->id)
            ->assertJsonPath('data.redirect_url', route('route.show', $routeB->id));
    }
}
