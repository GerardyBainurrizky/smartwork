<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComprehensiveExportRulesAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $salesA;
    protected User $salesB;
    protected User $driverA;
    protected User $driverB;
    protected Store $store1;
    protected Store $store2;
    protected Store $store3;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin User']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->admin->assignRole('admin');

        $this->salesA = User::factory()->create(['name' => 'Sales Alpha']);
        $this->salesA->assignRole('sales');

        $this->salesB = User::factory()->create(['name' => 'Sales Beta']);
        $this->salesB->assignRole('sales');

        $this->driverA = User::factory()->create(['name' => 'Driver Delta']);
        $this->driverA->assignRole('driver');

        $this->driverB = User::factory()->create(['name' => 'Driver Epsilon']);
        $this->driverB->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Mawar',
            'code' => 'TKO-MWR',
            'address' => 'Jl. Mawar 1',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Melati',
            'code' => 'TKO-MLT',
            'address' => 'Jl. Melati 2',
            'sales_penanggung_jawab_id' => $this->salesA->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);

        $this->store3 = Store::create([
            'name' => 'Toko Anggrek',
            'code' => 'TKO-AGR',
            'address' => 'Jl. Anggrek 3',
            'sales_penanggung_jawab_id' => $this->salesB->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    public function test_driver_pdf_filter_all_vs_specific_driver(): void
    {
        // Route & Visit for Driver A
        $routeA = Route::create(['user_id' => $this->driverA->id, 'name' => 'Rute A', 'date' => '2026-09-07', 'status' => 'completed']);
        $stopA = RouteStop::create(['route_id' => $routeA->id, 'store_id' => $this->store1->id, 'sequence' => 1, 'status' => 'visited']);
        $visitA = Visit::create([
            'user_id' => $this->driverA->id,
            'route_id' => $routeA->id,
            'route_stop_id' => $stopA->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 08:00:00',
            'check_out_at' => '2026-09-07 08:30:00',
            'visit_result' => 'Driver Delta result',
        ]);

        // Route & Visit for Driver B
        $routeB = Route::create(['user_id' => $this->driverB->id, 'name' => 'Rute B', 'date' => '2026-09-07', 'status' => 'completed']);
        $stopB = RouteStop::create(['route_id' => $routeB->id, 'store_id' => $this->store2->id, 'sequence' => 1, 'status' => 'visited']);
        $visitB = Visit::create([
            'user_id' => $this->driverB->id,
            'route_id' => $routeB->id,
            'route_stop_id' => $stopB->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 09:00:00',
            'check_out_at' => '2026-09-07 09:30:00',
            'visit_result' => 'Driver Epsilon result',
        ]);

        $this->actingAs($this->superAdmin);

        // 1. All Drivers
        $viewAll = view('reports.pdf.driver-visits', [
            'title' => 'LAPORAN HASIL PENGIRIMAN DRIVER',
            'items' => collect([$visitA, $visitB]),
            'selectedUserLabel' => 'Semua Driver',
            'statusLabel' => 'Semua Status',
            'trxStatusLabel' => 'Semua',
            'printedBy' => $this->superAdmin->name,
            'printedByRole' => 'Super Admin',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('Semua Driver', $viewAll);
        $this->assertStringContainsString('Super Admin', $viewAll);
        $this->assertStringContainsString('Driver Delta', $viewAll);
        $this->assertStringContainsString('Driver Epsilon', $viewAll);

        // 2. Specific Driver A
        $viewA = view('reports.pdf.driver-visits', [
            'title' => 'LAPORAN HASIL PENGIRIMAN DRIVER',
            'items' => collect([$visitA]),
            'selectedUserLabel' => 'Driver Delta',
            'statusLabel' => 'Selesai',
            'trxStatusLabel' => 'Semua',
            'printedBy' => $this->admin->name,
            'printedByRole' => 'Admin',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        $this->assertStringContainsString('Driver Delta', $viewA);
        $this->assertStringNotContainsString('Driver Epsilon', $viewA);
        $this->assertStringContainsString('Admin User', $viewA);
    }

    public function test_sales_pdf_grouping_by_sales_and_stores(): void
    {
        // Visit 1: Sales A -> Store 1
        $rA1 = Route::create(['user_id' => $this->salesA->id, 'name' => 'Route A1', 'date' => '2026-09-07', 'status' => 'completed']);
        $sA1 = RouteStop::create(['route_id' => $rA1->id, 'store_id' => $this->store1->id, 'sequence' => 1, 'status' => 'visited']);
        $vA1 = Visit::create([
            'user_id' => $this->salesA->id,
            'route_id' => $rA1->id,
            'route_stop_id' => $sA1->id,
            'store_id' => $this->store1->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 08:00:00',
            'check_out_at' => '2026-09-07 08:45:00',
            'visit_result' => 'Order pupuk A1',
        ]);

        // Visit 2: Sales A -> Store 2
        $sA2 = RouteStop::create(['route_id' => $rA1->id, 'store_id' => $this->store2->id, 'sequence' => 2, 'status' => 'visited']);
        $vA2 = Visit::create([
            'user_id' => $this->salesA->id,
            'route_id' => $rA1->id,
            'route_stop_id' => $sA2->id,
            'store_id' => $this->store2->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 09:00:00',
            'check_out_at' => '2026-09-07 09:30:00',
            'visit_result' => 'Visit Melati A2',
        ]);

        // Visit 3: Sales B -> Store 3
        $rB = Route::create(['user_id' => $this->salesB->id, 'name' => 'Route B1', 'date' => '2026-09-07', 'status' => 'completed']);
        $sB = RouteStop::create(['route_id' => $rB->id, 'store_id' => $this->store3->id, 'sequence' => 1, 'status' => 'visited']);
        $vB = Visit::create([
            'user_id' => $this->salesB->id,
            'route_id' => $rB->id,
            'route_stop_id' => $sB->id,
            'store_id' => $this->store3->id,
            'status' => 'completed',
            'check_in_at' => '2026-09-07 10:00:00',
            'check_out_at' => '2026-09-07 10:45:00',
            'visit_result' => 'Sales B visit result',
        ]);

        $vA1->load(['transactions.payments', 'payments.transaction.payments', 'user', 'store', 'route', 'routeStop']);
        $vA2->load(['transactions.payments', 'payments.transaction.payments', 'user', 'store', 'route', 'routeStop']);
        $vB->load(['transactions.payments', 'payments.transaction.payments', 'user', 'store', 'route', 'routeStop']);

        $items = collect([$vA1, $vA2, $vB]);

        $view = view('reports.pdf.visits', [
            'title' => 'LAPORAN KUNJUNGAN SALES',
            'items' => $items,
            'selectedUserLabel' => 'Semua Sales',
            'statusLabel' => 'Semua Status',
            'printedBy' => $this->superAdmin->name,
            'printedByRole' => 'Super Admin',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
        ])->render();

        // 1. Grouping Banners
        $this->assertStringContainsString('SALES: Sales Alpha', $view);
        $this->assertStringContainsString('SALES: Sales Beta', $view);

        // 2. Order of sales: Sales Alpha appears before Sales Beta
        $posSalesAlpha = strpos($view, 'SALES: Sales Alpha');
        $posSalesBeta = strpos($view, 'SALES: Sales Beta');
        $this->assertNotFalse($posSalesAlpha);
        $this->assertNotFalse($posSalesBeta);
        $this->assertTrue($posSalesAlpha < $posSalesBeta, 'Sales Alpha must be listed before Sales Beta');

        // 3. Stores inside Sales Alpha
        $posStore1 = strpos($view, 'Toko Mawar');
        $posStore2 = strpos($view, 'Toko Melati');
        $this->assertNotFalse($posStore1);
        $this->assertNotFalse($posStore2);
        $this->assertTrue($posStore1 < $posSalesBeta, 'Toko Mawar must be under Sales Alpha');
        $this->assertTrue($posStore2 < $posSalesBeta, 'Toko Melati must be under Sales Alpha');

        // 4. Stores inside Sales Beta
        $posStore3 = strpos($view, 'Toko Anggrek');
        $this->assertNotFalse($posStore3);
        $this->assertTrue($posStore3 > $posSalesBeta, 'Toko Anggrek must be under Sales Beta');
    }
}
