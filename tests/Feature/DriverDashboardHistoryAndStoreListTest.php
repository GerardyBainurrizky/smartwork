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

class DriverDashboardHistoryAndStoreListTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->driver = User::factory()->create(['name' => 'Driver Budi']);
        $this->driver->assignRole('driver');

        $this->sales = User::factory()->create(['name' => 'Sales Ahmad']);
        $this->sales->assignRole('sales');
    }

    public function test_driver_dashboard_does_not_render_total_transaksi_card(): void
    {
        $response = $this->actingAs($this->driver)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Total Pengiriman');
        $response->assertSee('Pengiriman Selesai');
        $response->assertSee('Pengiriman Bulan Ini');
        $response->assertSee('Uang Masuk Pengiriman');

        // Pastikan TIDAK ada card 'Total Transaksi' di dashboard driver
        $response->assertDontSee('Total Transaksi');
    }

    public function test_sales_dashboard_still_renders_total_transaksi(): void
    {
        $response = $this->actingAs($this->sales)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Total Transaksi');
        $response->assertSee('Kunjungan');
    }

    public function test_driver_history_does_not_render_total_transaksi_card(): void
    {
        $response = $this->actingAs($this->driver)->get(route('visit.history'));

        $response->assertStatus(200);
        $response->assertSee('Total Pengiriman');
        $response->assertSee('Pengiriman Selesai');
        $response->assertSee('Pengiriman Bulan Ini');
        $response->assertSee('Uang Masuk Pengiriman');

        // Pastikan TIDAK ada teks 'Total Transaksi' di history driver
        $response->assertDontSee('Total Transaksi');
        $response->assertDontSee('Piutang dari Transaksi Bulan Ini');
    }

    public function test_sales_history_still_renders_total_transaksi_and_piutang(): void
    {
        $response = $this->actingAs($this->sales)->get(route('visit.history'));

        $response->assertStatus(200);
        $response->assertSee('Total Kunjungan');
        $response->assertSee('Total Transaksi Baru Bulan Ini');
        $response->assertSee('Total Saldo Piutang');
        $response->assertSee('Pembayaran Piutang Lama Bulan Ini');
    }

    public function test_driver_can_access_store_list_and_view_count(): void
    {
        Store::create([
            'name' => 'Toko Subur Makmur',
            'code' => 'TKS-001',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        Store::create([
            'name' => 'Toko Rejeki Abadi',
            'code' => 'TKS-002',
            'address' => 'Jl. Sudirman No. 25',
            'city' => 'Bandung',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        // Toko non-delivery / non-aktif tidak boleh dihitung
        Store::create([
            'name' => 'Toko Non Delivery',
            'code' => 'TKS-003',
            'address' => 'Jl. Lain',
            'is_delivery_destination' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->driver)->get(route('driver.stores.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Toko');
        $response->assertSee('Toko Subur Makmur');
        $response->assertSee('Toko Rejeki Abadi');
        $response->assertDontSee('Toko Non Delivery');

        // Total count harus 2
        $response->assertSee('2');
        $response->assertSee('Total Toko');
    }

    public function test_sales_and_guest_cannot_access_driver_store_list(): void
    {
        // Guest -> Redirect login
        $guestRes = $this->get(route('driver.stores.index'));
        $guestRes->assertRedirect(route('login'));

        // Sales -> Redirect dashboard (sesuai CheckRole middleware aplikasi)
        $salesRes = $this->actingAs($this->sales)->get(route('driver.stores.index'));
        $salesRes->assertRedirect(route('dashboard'));
    }

    public function test_driver_store_search_functionality(): void
    {
        Store::create([
            'name' => 'Toko Kelontong Berkah',
            'code' => 'TKS-BKH',
            'address' => 'Jl. Pahlawan No. 5',
            'city' => 'Surabaya',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        Store::create([
            'name' => 'Apotek Sehat Jaya',
            'code' => 'TKS-SHT',
            'address' => 'Jl. Veteran No. 12',
            'city' => 'Malang',
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        // Search by name
        $searchNameRes = $this->actingAs($this->driver)->get(route('driver.stores.index', ['search' => 'Berkah']));
        $searchNameRes->assertStatus(200);
        $searchNameRes->assertSee('Toko Kelontong Berkah');
        $searchNameRes->assertDontSee('Apotek Sehat Jaya');

        // Search by code
        $searchCodeRes = $this->actingAs($this->driver)->get(route('driver.stores.index', ['search' => 'TKS-SHT']));
        $searchCodeRes->assertStatus(200);
        $searchCodeRes->assertSee('Apotek Sehat Jaya');
        $searchCodeRes->assertDontSee('Toko Kelontong Berkah');

        // Search by address/city
        $searchCityRes = $this->actingAs($this->driver)->get(route('driver.stores.index', ['search' => 'Surabaya']));
        $searchCityRes->assertStatus(200);
        $searchCityRes->assertSee('Toko Kelontong Berkah');
        $searchCityRes->assertDontSee('Apotek Sehat Jaya');

        // Search not found -> Empty search state
        $searchNotFoundRes = $this->actingAs($this->driver)->get(route('driver.stores.index', ['search' => 'Zzzzz']));
        $searchNotFoundRes->assertStatus(200);
        $searchNotFoundRes->assertSee('Toko Tidak Ditemukan');
        $searchNotFoundRes->assertSee('Belum ada toko yang sesuai dengan kata kunci');
    }

    public function test_driver_store_google_maps_links_priorities(): void
    {
        // 1. Toko dengan Koordinat
        $storeWithCoords = Store::create([
            'name' => 'Toko Koordinat Lengkap',
            'code' => 'TKS-CRD',
            'address' => 'Jl. Titik GPS',
            'city' => 'Jakarta Pusat',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        // 2. Toko hanya Alamat (tanpa koordinat)
        $storeOnlyAddress = Store::create([
            'name' => 'Toko Hanya Alamat',
            'code' => 'TKS-ALM',
            'address' => 'Jl. Kebon Sirih No. 45 & Gang 2',
            'city' => 'Jakarta Pusat',
            'latitude' => null,
            'longitude' => null,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        // 3. Toko tanpa Alamat dan tanpa Koordinat
        $storeNoLocation = Store::create([
            'name' => 'Toko Tanpa Lokasi',
            'code' => 'TKS-NOLOC',
            'address' => null,
            'city' => null,
            'province' => null,
            'latitude' => null,
            'longitude' => null,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->driver)->get(route('driver.stores.index'));
        $response->assertStatus(200);

        // Toko 1: Link Google Maps menggunakan query -6.175392,106.827153
        $response->assertSee('https://www.google.com/maps/search/?api=1&amp;query=-6.175392,106.827153', false);

        // Toko 2: Link Google Maps menggunakan URL encoded query dari alamat
        $expectedAddressQuery = urlencode('Toko Hanya Alamat, Jl. Kebon Sirih No. 45 & Gang 2, Jakarta Pusat');
        $expectedAddressUrl = 'https://www.google.com/maps/search/?api=1&amp;query=' . $expectedAddressQuery;
        $response->assertSee($expectedAddressUrl, false);

        // Toko 3: Menampilkan 'Alamat belum tersedia' dan 'Lokasi Belum Tersedia'
        $response->assertSee('Alamat belum tersedia');
        $response->assertSee('Lokasi Belum Tersedia');

        // Pastikan tidak ada data piutang atau sales
        $response->assertDontSee('Total Piutang');
        $response->assertDontSee('Sales Penanggung Jawab');
    }
}
