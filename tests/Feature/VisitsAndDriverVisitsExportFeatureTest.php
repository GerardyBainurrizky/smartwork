<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitsAndDriverVisitsExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $superAdmin;
    protected User $sales;
    protected User $driver;
    protected Store $store1;
    protected Store $store2;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['name' => 'Admin User', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin User', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');

        $this->sales = User::factory()->create(['name' => 'Sales Budi', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Joko', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->store1 = Store::create([
            'name' => 'Toko Barokah Jaya',
            'code' => 'TBJ-001',
            'address' => 'Jl. Merdeka No. 10',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Sentosa',
            'code' => 'TS-002',
            'address' => 'Jl. Sudirman No. 20',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        // Create Sales Route & Visit
        $salesRoute = RouteModel::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Sales Bandung',
            'date' => Carbon::now()->toDateString(),
            'status' => 'active',
            'created_by' => $this->sales->id,
        ]);

        $salesStop = RouteStop::create([
            'route_id' => $salesRoute->id,
            'store_id' => $this->store1->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'user_id' => $this->sales->id,
            'store_id' => $this->store1->id,
            'route_id' => $salesRoute->id,
            'route_stop_id' => $salesStop->id,
            'check_in_at' => Carbon::now()->subHour(),
            'check_out_at' => Carbon::now(),
            'status' => 'completed',
            'transaction_status' => 'paid',
            'transaction_amount' => 150000,
            'visit_result' => 'Sales Toko Barokah Selesai',
        ]);

        // Create Driver Route & Visit
        $driverRoute = RouteModel::create([
            'user_id' => $this->driver->id,
            'name' => 'Rute Pengiriman Driver',
            'date' => Carbon::now()->toDateString(),
            'status' => 'active',
            'created_by' => $this->driver->id,
        ]);

        $driverStop = RouteStop::create([
            'route_id' => $driverRoute->id,
            'store_id' => $this->store2->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'user_id' => $this->driver->id,
            'store_id' => $this->store2->id,
            'route_id' => $driverRoute->id,
            'route_stop_id' => $driverStop->id,
            'check_in_at' => Carbon::now()->subMinutes(30),
            'check_out_at' => Carbon::now(),
            'status' => 'completed',
            'transaction_status' => 'paid',
            'transaction_amount' => 300000,
            'delivered_goods_summary' => '10 Karton Minuman Segar',
            'visit_result' => 'Barang Driver Diterima Lengkap',
        ]);
    }

    public function test_sales_visits_page_renders_export_buttons_and_modal(): void
    {
        foreach ([$this->admin, $this->superAdmin] as $user) {
            $resp = $this->actingAs($user)->get(route('admin.reports.visits'));
            $resp->assertOk();
            $content = $resp->getContent();

            $this->assertStringContainsString('Unduh PDF', $content);
            $this->assertStringContainsString('Unduh Excel', $content);
            $this->assertStringContainsString('Nama File Laporan', $content);
            $this->assertStringContainsString('reportDownload()', $content);
            $this->assertStringContainsString('type=visits', $content);
        }
    }

    public function test_driver_visits_page_renders_export_buttons_and_modal(): void
    {
        foreach ([$this->admin, $this->superAdmin] as $user) {
            $resp = $this->actingAs($user)->get(route('admin.reports.driver-visits'));
            $resp->assertOk();
            $content = $resp->getContent();

            $this->assertStringContainsString('Unduh PDF', $content);
            $this->assertStringContainsString('Unduh Excel', $content);
            $this->assertStringContainsString('Nama File Laporan', $content);
            $this->assertStringContainsString('reportDownload()', $content);
            $this->assertStringContainsString('type=driver-visits', $content);
        }
    }

    public function test_sales_visits_pdf_and_excel_export_download(): void
    {
        $this->actingAs($this->admin);

        // PDF
        $pdf = $this->get(route('admin.reports.export-pdf', [
            'type' => 'visits',
            'period' => 'this_month',
            'filename' => 'Custom_Sales_Visits_Report',
        ]));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringContainsString('Custom_Sales_Visits_Report.pdf', $pdf->headers->get('content-disposition'));

        // Excel
        $excel = $this->get(route('admin.reports.export-excel', [
            'type' => 'visits',
            'period' => 'this_month',
            'filename' => 'Custom_Sales_Visits_Report',
        ]));
        $excel->assertOk();
        $this->assertStringContainsString('Custom_Sales_Visits_Report.xlsx', $excel->headers->get('content-disposition'));
    }

    public function test_driver_visits_pdf_and_excel_export_download(): void
    {
        $this->actingAs($this->superAdmin);

        // PDF
        $pdf = $this->get(route('admin.reports.export-pdf', [
            'type' => 'driver-visits',
            'period' => 'this_month',
            'filename' => 'Custom_Driver_Visits_Report',
        ]));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringContainsString('Custom_Driver_Visits_Report.pdf', $pdf->headers->get('content-disposition'));

        // Excel
        $excel = $this->get(route('admin.reports.export-excel', [
            'type' => 'driver-visits',
            'period' => 'this_month',
            'filename' => 'Custom_Driver_Visits_Report',
        ]));
        $excel->assertOk();
        $this->assertStringContainsString('Custom_Driver_Visits_Report.xlsx', $excel->headers->get('content-disposition'));
    }

    public function test_export_data_isolation_between_sales_and_driver(): void
    {
        $this->actingAs($this->admin);

        // Sales PDF Export
        $salesPdf = $this->get(route('admin.reports.export-pdf', ['type' => 'visits']));
        $salesPdf->assertOk();

        // Driver PDF Export
        $driverPdf = $this->get(route('admin.reports.export-pdf', ['type' => 'driver-visits']));
        $driverPdf->assertOk();

        // Non admin/super-admin cannot access (redirects to dashboard / unauthorized)
        $this->actingAs($this->sales)->get(route('admin.reports.export-pdf', ['type' => 'visits']))->assertRedirect();
        $this->actingAs($this->driver)->get(route('admin.reports.export-pdf', ['type' => 'driver-visits']))->assertRedirect();
    }
}
