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

class MasterStoreMonitoringAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $superAdmin;
    protected User $sales1;
    protected User $sales2;
    protected User $driver1;
    protected User $driver2;

    protected Store $storeA;
    protected Store $storeB;
    protected Store $storeC;
    protected Store $storeUnassigned;

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

        // Store A: Sales 1, Delivery Ya
        $this->storeA = Store::create([
            'code' => 'ST-001',
            'name' => 'Toko A',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat A',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);

        // Store B: Sales 1, Delivery Ya
        $this->storeB = Store::create([
            'code' => 'ST-002',
            'name' => 'Toko B',
            'sales_penanggung_jawab_id' => $this->sales1->id,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat B',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);

        // Store C: Sales 2, Delivery Tidak
        $this->storeC = Store::create([
            'code' => 'ST-003',
            'name' => 'Toko C',
            'sales_penanggung_jawab_id' => $this->sales2->id,
            'is_delivery_destination' => false,
            'status' => 'active',
            'address' => 'Alamat C',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);

        // Store Unassigned: Sales NULL, Delivery Ya
        $this->storeUnassigned = Store::create([
            'code' => 'ST-004',
            'name' => 'Toko Tanpa Sales',
            'sales_penanggung_jawab_id' => null,
            'is_delivery_destination' => true,
            'status' => 'active',
            'address' => 'Alamat D',
            'city' => 'Subang',
            'province' => 'Jawa Barat',
        ]);
    }

    /**
     * TEST A: Toko A (Sales 1, Delivery Ya) -> Sales 1 kunjungan (valid), Sales 2 kunjungan (reject), Driver 1 & 2 pengiriman (valid)
     */
    public function test_business_rule_test_a(): void
    {
        // Sales 1 can plan visit to Toko A
        $resSales1 = $this->actingAs($this->sales1)->postJson(route('route.store'), [
            'name' => 'Rencana Kunjungan Sales 1',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resSales1->assertOk();

        // Sales 2 rejected for Toko A
        $resSales2 = $this->actingAs($this->sales2)->postJson(route('route.store'), [
            'name' => 'Rencana Kunjungan Sales 2',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeA->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resSales2->assertStatus(422);

        // Driver 1 can plan delivery to Toko A
        $resDriver1 = $this->actingAs($this->driver1)->postJson(route('route.store'), [
            'name' => 'Rencana Pengiriman Driver 1',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeA->id, 'sequence' => 1],
            ],
        ]);
        $resDriver1->assertOk();

        // Driver 2 can plan delivery to Toko A
        $resDriver2 = $this->actingAs($this->driver2)->postJson(route('route.store'), [
            'name' => 'Rencana Pengiriman Driver 2',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeA->id, 'sequence' => 1],
            ],
        ]);
        $resDriver2->assertOk();
    }

    /**
     * TEST B: Toko B (Sales NULL, Delivery Ya) -> Sales rejected, Driver valid
     */
    public function test_business_rule_test_b(): void
    {
        // Sales 1 rejected
        $resSales = $this->actingAs($this->sales1)->postJson(route('route.store'), [
            'name' => 'Kunjungan Toko Unassigned',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeUnassigned->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resSales->assertStatus(422);

        // Driver 1 valid
        $resDriver = $this->actingAs($this->driver1)->postJson(route('route.store'), [
            'name' => 'Pengiriman Toko Unassigned',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeUnassigned->id, 'sequence' => 1],
            ],
        ]);
        $resDriver->assertOk();
    }

    /**
     * TEST C: Toko C (Sales 2, Delivery Tidak) -> Sales 2 valid, Driver rejected
     */
    public function test_business_rule_test_c(): void
    {
        // Sales 2 can plan
        $resSales2 = $this->actingAs($this->sales2)->postJson(route('route.store'), [
            'name' => 'Kunjungan Toko C',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeC->id, 'sequence' => 1, 'estimated_duration_minutes' => 30],
            ],
        ]);
        $resSales2->assertOk();

        // Driver 1 rejected because is_delivery_destination = false
        $resDriver = $this->actingAs($this->driver1)->postJson(route('route.store'), [
            'name' => 'Pengiriman Toko C',
            'date' => now()->toDateString(),
            'stops' => [
                ['store_id' => $this->storeC->id, 'sequence' => 1],
            ],
        ]);
        $resDriver->assertStatus(422);
    }

    /**
     * TEST D: Toko A menerima pengiriman driver -> Statistik Pengiriman bertambah, Kunjungan tetap 0
     */
    public function test_business_rule_test_d_delivery_statistics_isolated_from_visits(): void
    {
        // Create 2 completed deliveries by Driver 1 & Driver 2 to Toko A
        $route1 = Route::create(['user_id' => $this->driver1->id, 'name' => 'Rute D1', 'date' => now()->toDateString(), 'status' => 'completed']);
        $stop1 = RouteStop::create(['route_id' => $route1->id, 'store_id' => $this->storeA->id, 'sequence' => 1, 'status' => 'visited']);
        Visit::create(['user_id' => $this->driver1->id, 'route_id' => $route1->id, 'route_stop_id' => $stop1->id, 'store_id' => $this->storeA->id, 'status' => 'completed', 'check_in_at' => now(), 'check_out_at' => now()]);

        $route2 = Route::create(['user_id' => $this->driver2->id, 'name' => 'Rute D2', 'date' => now()->toDateString(), 'status' => 'completed']);
        $stop2 = RouteStop::create(['route_id' => $route2->id, 'store_id' => $this->storeA->id, 'sequence' => 1, 'status' => 'visited']);
        Visit::create(['user_id' => $this->driver2->id, 'route_id' => $route2->id, 'route_stop_id' => $stop2->id, 'store_id' => $this->storeA->id, 'status' => 'completed', 'check_in_at' => now(), 'check_out_at' => now()]);

        // Access Store Detail Monitoring page
        $res = $this->actingAs($this->admin)->get(route('admin.stores.show', $this->storeA->id));
        $res->assertOk();

        // Verify stats in view
        $res->assertViewHas('stats', function ($stats) {
            return $stats['total_sales_visits'] === 0
                && $stats['total_driver_deliveries'] === 2;
        });
    }

    /**
     * TEST E: Toko A berpindah Sales 1 -> Sales 2. Histori kunjungan lama tidak berubah
     */
    public function test_business_rule_test_e_assignment_change_preserves_old_history(): void
    {
        // 1. Sales 1 visits Toko A
        $routeSales1 = Route::create(['user_id' => $this->sales1->id, 'name' => 'Rute Sales 1', 'date' => now()->subDay()->toDateString(), 'status' => 'completed']);
        $stopSales1 = RouteStop::create(['route_id' => $routeSales1->id, 'store_id' => $this->storeA->id, 'sequence' => 1, 'status' => 'visited']);
        $oldVisit = Visit::create(['user_id' => $this->sales1->id, 'route_id' => $routeSales1->id, 'route_stop_id' => $stopSales1->id, 'store_id' => $this->storeA->id, 'status' => 'completed', 'check_in_at' => now()->subDay(), 'check_out_at' => now()->subDay()]);

        // 2. Admin reassigns Toko A to Sales 2
        $this->storeA->update(['sales_penanggung_jawab_id' => $this->sales2->id]);

        // 3. Old visit record remains owned by Sales 1
        $oldVisit->refresh();
        $this->assertEquals($this->sales1->id, $oldVisit->user_id);

        // 4. Sales 1 monitoring shows Toko A is no longer in Sales 1 jangkauan
        $resSales1 = $this->actingAs($this->admin)->get(route('admin.stores.index', ['sales_id' => $this->sales1->id]));
        $resSales1->assertOk();
        $resSales1->assertViewHas('salesMonitoring', function ($mon) {
            return $mon['total_assigned'] === 1; // Only Toko B remains assigned to Sales 1
        });
    }

    /**
     * Monitoring per Sales: Cakupan Jangkauan, Sudah/Belum Dikunjungi, Sering Dikunjungi
     */
    public function test_monitoring_per_sales_computes_accurate_metrics(): void
    {
        // Sales 1 has Toko A & Toko B.
        // Create 3 visits to Toko A by Sales 1. Toko B is unvisited.
        for ($i = 0; $i < 3; $i++) {
            $r = Route::create(['user_id' => $this->sales1->id, 'name' => "Rute {$i}", 'date' => now()->subDays($i)->toDateString(), 'status' => 'completed']);
            $s = RouteStop::create(['route_id' => $r->id, 'store_id' => $this->storeA->id, 'sequence' => 1, 'status' => 'visited']);
            Visit::create(['user_id' => $this->sales1->id, 'route_id' => $r->id, 'route_stop_id' => $s->id, 'store_id' => $this->storeA->id, 'status' => 'completed', 'check_in_at' => now()->subDays($i), 'check_out_at' => now()->subDays($i)]);
        }

        $res = $this->actingAs($this->admin)->get(route('admin.stores.index', ['sales_id' => $this->sales1->id]));
        $res->assertOk();

        $res->assertViewHas('salesMonitoring', function ($mon) {
            return $mon['total_assigned'] === 2
                && $mon['visited_count'] === 1
                && $mon['unvisited_count'] === 1
                && $mon['total_visits'] === 3
                && $mon['top_visited']->first()->id === $this->storeA->id
                && $mon['unvisited_stores']->first()->id === $this->storeB->id;
        });
    }

    /**
     * Laporan Master Toko Web, PDF, and Excel export
     */
    public function test_store_reports_and_export(): void
    {
        // Web report filter by sales
        $webRes = $this->actingAs($this->admin)->get(route('admin.reports.stores', ['sales_id' => $this->sales1->id]));
        $webRes->assertOk();
        $webRes->assertSee('Toko A');
        $webRes->assertSee('Toko B');
        $webRes->assertDontSee('Toko C');

        // PDF export
        $pdfRes = $this->actingAs($this->admin)->get(route('admin.reports.export-pdf', ['type' => 'stores', 'sales_id' => $this->sales1->id]));
        $pdfRes->assertOk();
        $this->assertEquals('application/pdf', $pdfRes->headers->get('content-type'));

        // Excel export
        $excelRes = $this->actingAs($this->admin)->get(route('admin.reports.export-excel', ['type' => 'stores', 'sales_id' => $this->sales1->id]));
        $excelRes->assertOk();
    }
}
