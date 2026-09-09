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

class SalesAndDriverVisitHistoryExportIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected User $driver;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->sales = User::factory()->create(['name' => 'Budi Sales', 'username' => 'budisales', 'email' => 'sales@company.id', 'phone' => '081234567890']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Doni Driver', 'username' => 'donidriver', 'email' => 'driver@company.id', 'phone' => '081234567890']);
        $this->driver->assignRole('driver');

        $this->store = Store::create([
            'name' => 'Toko Barokah Jaya',
            'code' => 'TKO-BJ-001',
            'address' => 'Jl. Barokah No. 1',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    /**
     * TEST: Sales visit history page renders export buttons and modern Alpine.js modal
     */
    public function test_sales_visit_history_page_renders_export_modal_and_alpine_component(): void
    {
        $response = $this
            ->actingAs($this->sales)
            ->get(route('visit.history'));

        $response->assertOk();
        $response->assertSee('visitHistoryExport()', false);
        $response->assertSee('openExport(\'pdf\')', false);
        $response->assertSee('openExport(\'excel\')', false);
        $response->assertSee('Sedang menyiapkan file...', false);
        $response->assertSee('Laporan berhasil diunduh.', false);
        $response->assertSee('Mengunduh...', false);
    }

    /**
     * TEST: Driver delivery history page renders export buttons and modern Alpine.js modal
     */
    public function test_driver_delivery_history_page_renders_export_modal_and_alpine_component(): void
    {
        $response = $this
            ->actingAs($this->driver)
            ->get(route('visit.history'));

        $response->assertOk();
        $response->assertSee('visitHistoryExport()', false);
        $response->assertSee('openExport(\'pdf\')', false);
        $response->assertSee('openExport(\'excel\')', false);
        $response->assertSee('Sedang menyiapkan file...', false);
        $response->assertSee('Laporan berhasil diunduh.', false);
        $response->assertSee('Mengunduh...', false);
    }

    /**
     * TEST: Sales PDF & Excel export succeed with filters
     */
    public function test_sales_export_pdf_and_excel_succeed_with_filters(): void
    {
        $route = Route::create([
            'name' => 'Rute Sales 1',
            'user_id' => $this->sales->id,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'user_id' => $this->sales->id,
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'check_in_lat' => -6.200000,
            'check_in_lng' => 106.816666,
            'status' => 'completed',
        ]);

        $from = now()->subDays(1)->toDateString();
        $to = now()->toDateString();

        // PDF Export
        $pdfRes = $this
            ->actingAs($this->sales)
            ->get(route('visit.pdf.rekap', ['from_date' => $from, 'to_date' => $to, 'filename' => 'Rekap_Sales_Test']));
        $pdfRes->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));

        // Excel Export
        $excelRes = $this
            ->actingAs($this->sales)
            ->get(route('visit.excel.rekap', ['from_date' => $from, 'to_date' => $to, 'filename' => 'Rekap_Sales_Test']));
        $excelRes->assertOk();
    }

    /**
     * TEST: Driver PDF & Excel export succeed with filters
     */
    public function test_driver_export_pdf_and_excel_succeed_with_filters(): void
    {
        $route = Route::create([
            'name' => 'Rute Driver 1',
            'user_id' => $this->driver->id,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'user_id' => $this->driver->id,
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'check_in_lat' => -6.200000,
            'check_in_lng' => 106.816666,
            'status' => 'completed',
        ]);

        $from = now()->subDays(1)->toDateString();
        $to = now()->toDateString();

        // PDF Export
        $pdfRes = $this
            ->actingAs($this->driver)
            ->get(route('visit.pdf.rekap', ['from_date' => $from, 'to_date' => $to, 'filename' => 'Rekap_Driver_Test']));
        $pdfRes->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));

        // Excel Export
        $excelRes = $this
            ->actingAs($this->driver)
            ->get(route('visit.excel.rekap', ['from_date' => $from, 'to_date' => $to, 'filename' => 'Rekap_Driver_Test']));
        $excelRes->assertOk();
    }

    /**
     * TEST: Export without filter returns 422 error JSON cleanly
     */
    public function test_export_without_filter_returns_422_json_error(): void
    {
        $resPdf = $this
            ->actingAs($this->sales)
            ->get(route('visit.pdf.rekap'));
        $resPdf->assertStatus(422);
        $resPdf->assertJson(['status' => 'error']);

        $resExcel = $this
            ->actingAs($this->driver)
            ->get(route('visit.excel.rekap'));
        $resExcel->assertStatus(422);
        $resExcel->assertJson(['status' => 'error']);
    }
}
