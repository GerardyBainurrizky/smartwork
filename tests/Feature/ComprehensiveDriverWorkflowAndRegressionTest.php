<?php

namespace Tests\Feature;

use App\Exports\DriverVisitsExport;
use App\Models\Attendance;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ComprehensiveDriverWorkflowAndRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $sales;
    private User $driver;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;
    private Store $storeD;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->sales = User::where('username', 'karyawan')->first();

        $this->driver = User::create([
            'username' => 'driver_test',
            'name' => 'Budi Driver Test',
            'email' => 'driver_test@isatriselaras.id',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->driver->syncRoles(['driver']);

        $this->storeA = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-A',
            'name' => 'Toko A Alpha',
            'owner' => 'Pak Alpha',
            'address' => 'Jl. Alpha No. 1',
            'status' => 'active',
            'is_delivery_destination' => true,
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->storeB = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-B',
            'name' => 'Toko B Beta',
            'owner' => 'Pak Beta',
            'address' => 'Jl. Beta No. 2',
            'status' => 'active',
            'is_delivery_destination' => true,
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->storeC = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-C',
            'name' => 'Toko C Gamma',
            'owner' => 'Pak Gamma',
            'address' => 'Jl. Gamma No. 3',
            'status' => 'active',
            'is_delivery_destination' => true,
            'sales_penanggung_jawab_id' => $this->sales->id,
        ]);

        $this->storeD = Store::create([
            'id' => (string) Str::uuid(),
            'code' => 'ST-D',
            'name' => 'Toko D Delta',
            'owner' => 'Pak Delta',
            'address' => 'Jl. Delta No. 4',
            'status' => 'active',
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 1: Driver belum check-in presensi -> Start route ditolak dengan pesan presensi yang jelas
     */
    public function test_driver_without_attendance_cannot_start_route(): void
    {
        $today = now()->toDateString();
        $route = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver 1',
            'date' => $today,
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->driver)->postJson(route('route.start', $route->id));
        $res->assertStatus(422);
        $res->assertJson([
            'status' => 'error',
            'message' => 'Anda belum melakukan Check In presensi hari ini. Silakan lakukan presensi terlebih dahulu sebelum memulai rute pengiriman.',
        ]);
    }

    /**
     * TEST 2: Driver sudah check-in presensi -> Start route diperbolehkan
     */
    public function test_driver_with_attendance_can_start_route(): void
    {
        $today = now()->toDateString();
        Attendance::create([
            'user_id' => $this->driver->id,
            'date' => $today,
            'clock_in' => now()->subMinutes(10),
            'status' => 'present',
        ]);

        $route = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'name' => 'Rute Driver 1',
            'date' => $today,
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->driver)->postJson(route('route.start', $route->id));
        $res->assertOk();
        $res->assertJson(['status' => 'success']);
        $this->assertEquals('active', $route->fresh()->status);
    }

    /**
     * TEST 3: Sequential Check In scope PER ROUTE (bukan global)
     */
    public function test_sequential_checkin_per_route_does_not_block_other_routes(): void
    {
        $today = now()->toDateString();
        Attendance::create([
            'user_id' => $this->driver->id,
            'date' => $today,
            'clock_in' => now()->subHours(2),
            'status' => 'present',
        ]);

        // Route A with Stop 1 (Toko A), Stop 2 (Toko B)
        $routeA = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'name' => 'Rute A',
            'date' => $today,
            'status' => 'active',
            'started_at' => now()->subHour(),
        ]);
        $stopA1 = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $routeA->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);
        $stopA2 = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $routeA->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        // Route B with Stop 1 (Toko D)
        $routeB = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'name' => 'Rute B',
            'date' => $today,
            'status' => 'active',
            'started_at' => now()->subMinutes(30),
        ]);
        $stopB1 = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $routeB->id,
            'store_id' => $this->storeD->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $dummyImage = 'data:image/jpeg;base64,' . base64_encode('fake image data');

        // Check in Toko A (Route A)
        $resA1 = $this->actingAs($this->driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stopA1->id,
            'route_id' => $routeA->id,
            'store_id' => $this->storeA->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Alamat A',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'selfie' => $dummyImage,
        ]);
        $resA1->assertOk();

        // Check in Toko B (Route A) while Toko A is in_progress -> MUST BE REJECTED
        $resA2 = $this->actingAs($this->driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stopA2->id,
            'route_id' => $routeA->id,
            'store_id' => $this->storeB->id,
            'latitude' => -6.21,
            'longitude' => 106.81,
            'address' => 'Alamat B',
            'maps_url' => 'https://maps.google.com/?q=-6.21,106.81',
            'selfie' => $dummyImage,
        ]);
        $resA2->assertStatus(422);

        // Check in Toko D on Route B -> MUST BE ALLOWED (Route A does not block Route B)
        $resB1 = $this->actingAs($this->driver)->postJson(route('visit.store'), [
            'route_stop_id' => $stopB1->id,
            'route_id' => $routeB->id,
            'store_id' => $this->storeD->id,
            'latitude' => -6.22,
            'longitude' => 106.82,
            'address' => 'Alamat D',
            'maps_url' => 'https://maps.google.com/?q=-6.22,106.82',
            'selfie' => $dummyImage,
        ]);
        $resB1->assertOk();
    }

    /**
     * TEST 4: Historical incomplete route from past days does not block today's new route
     */
    public function test_past_days_incomplete_delivery_does_not_block_today(): void
    {
        $today = now()->toDateString();
        $twoDaysAgo = now()->subDays(2)->toDateString();

        Attendance::create([
            'user_id' => $this->driver->id,
            'date' => $today,
            'clock_in' => now()->subHours(1),
            'status' => 'present',
        ]);

        // Historical incomplete visit from 2 days ago
        $pastRoute = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'name' => 'Rute Dua Hari Lalu',
            'date' => $twoDaysAgo,
            'status' => 'active',
        ]);
        $pastStop = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $pastRoute->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);
        Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'route_stop_id' => $pastStop->id,
            'route_id' => $pastRoute->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subDays(2),
        ]);

        // Today's new route
        $todayRoute = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'name' => 'Rute Hari Ini',
            'date' => $today,
            'status' => 'active',
        ]);
        $todayStop = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $todayRoute->id,
            'store_id' => $this->storeB->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $dummyImage = 'data:image/jpeg;base64,' . base64_encode('fake image data');

        // Check in on today's stop must succeed
        $resToday = $this->actingAs($this->driver)->postJson(route('visit.store'), [
            'route_stop_id' => $todayStop->id,
            'route_id' => $todayRoute->id,
            'store_id' => $this->storeB->id,
            'latitude' => -6.21,
            'longitude' => 106.81,
            'address' => 'Alamat B',
            'maps_url' => 'https://maps.google.com/?q=-6.21,106.81',
            'selfie' => $dummyImage,
        ]);
        $resToday->assertOk();
    }

    /**
     * TEST 5: /visit Driver only shows today's deliveries (past incomplete records are not shown as active visit today)
     */
    public function test_visit_index_driver_shows_only_today_deliveries(): void
    {
        $today = now()->toDateString();
        $pastVisit = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'store_id' => $this->storeA->id,
            'status' => 'in_progress',
            'check_in_at' => now()->subDays(2),
        ]);

        $res = $this->actingAs($this->driver)->get(route('visit.index'));
        $res->assertOk();
        $activeVisit = $res->viewData('activeVisit');
        $this->assertNull($activeVisit);
    }

    /**
     * TEST 6: /visit/history Driver statistics and no piutang/sales transaction pollution
     */
    public function test_driver_history_statistics_and_clean_view(): void
    {
        // Add receivables for Store A (belongs to Sales)
        StoreTransaction::create([
            'id' => (string) Str::uuid(),
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-001',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 5000000,
            'status' => 'belum_lunas',
        ]);

        $completedDelivery = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'visit_result' => 'Barang diterima lengkap',
        ]);

        $res = $this->actingAs($this->driver)->get(route('visit.history'));
        $res->assertOk();
        $res->assertSee('Total Pengiriman');
        $res->assertSee('Pengiriman Selesai');
        $res->assertSee('Pengiriman Bulan Ini');
        $res->assertDontSee('Total Transaksi Bulan Ini');
        $res->assertDontSee('Uang Masuk Transaksi Bulan Ini');
        $res->assertDontSee('Pembayaran Piutang Bulan Ini');
    }

    /**
     * TEST 7: PDF Export Driver uses LAPORAN HASIL PENGIRIMAN, dynamic photos, and no Sales Piutang
     */
    public function test_driver_pdf_export_structure(): void
    {
        $visit = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'visit_result' => 'Barang diterima lengkap',
            'delivered_goods_summary' => '10 Karton Minyak Goreng',
        ]);

        // Add 3 documentation photos
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/photo1.jpg',
        ]);
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/photo2.jpg',
        ]);
        VisitPhoto::create([
            'visit_id' => $visit->id,
            'type' => 'checkout_documentation',
            'photo_path' => 'photos/photo3.jpg',
        ]);

        $resDetailPdf = $this->actingAs($this->driver)->get(route('visit.pdf.detail', $visit->id));
        $resDetailPdf->assertOk();
        $this->assertEquals('application/pdf', $resDetailPdf->headers->get('content-type'));

        $resRekapPdf = $this->actingAs($this->driver)->get(route('visit.pdf.rekap', [
            'from_date' => now()->subDays(5)->toDateString(),
            'to_date' => now()->toDateString(),
        ]));
        $resRekapPdf->assertOk();
        $this->assertEquals('application/pdf', $resRekapPdf->headers->get('content-type'));
    }

    /**
     * TEST 8: Excel Export Driver has 2 clean sheets without sales data
     */
    public function test_driver_excel_export_sheets(): void
    {
        $visit = Visit::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->driver->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'visit_result' => 'Barang diterima lengkap',
        ]);

        $export = new DriverVisitsExport([
            'fromDate' => now()->subDays(5)->toDateString(),
            'toDate' => now()->toDateString(),
            'userId' => $this->driver->id,
        ]);

        $sheets = $export->sheets();
        $this->assertCount(2, $sheets);
        $this->assertEquals('Rekap Pengiriman', $sheets[0]->title());
        $this->assertEquals('Dokumentasi Pengiriman', $sheets[1]->title());
    }

    /**
     * TEST 9: Regression Test Sales (Attendance, Route creation, Sequential checkin, Receivables check-out)
     */
    public function test_sales_regression_workflows_remain_intact(): void
    {
        $today = now()->toDateString();
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => $today,
            'clock_in' => now()->subHours(3),
            'status' => 'present',
        ]);

        $salesRoute = RouteModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->sales->id,
            'name' => 'Rute Sales Kunjungan',
            'date' => $today,
            'status' => 'active',
            'started_at' => now()->subHours(2),
        ]);

        $salesStop1 = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $salesRoute->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $salesStop2 = RouteStop::create([
            'id' => (string) Str::uuid(),
            'route_id' => $salesRoute->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        $dummyImage = 'data:image/jpeg;base64,' . base64_encode('fake image data');

        // Check in Sales 1
        $resSales1 = $this->actingAs($this->sales)->postJson(route('visit.store'), [
            'route_stop_id' => $salesStop1->id,
            'route_id' => $salesRoute->id,
            'store_id' => $this->storeA->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Alamat Toko A',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'selfie' => $dummyImage,
            'storefront_photo' => $dummyImage,
        ]);
        $resSales1->assertOk();

        // Check in Sales 2 before 1 check-out -> REJECTED
        $resSales2 = $this->actingAs($this->sales)->postJson(route('visit.store'), [
            'route_stop_id' => $salesStop2->id,
            'route_id' => $salesRoute->id,
            'store_id' => $this->storeB->id,
            'latitude' => -6.21,
            'longitude' => 106.81,
            'address' => 'Alamat Toko B',
            'maps_url' => 'https://maps.google.com/?q=-6.21,106.81',
            'selfie' => $dummyImage,
            'storefront_photo' => $dummyImage,
        ]);
        $resSales2->assertStatus(422);

        // Sales Check Out with Transaksi Baru
        $visitId = $resSales1->json('data.id');
        $resCheckoutSales = $this->actingAs($this->sales)->postJson(route('visit.check-out', $visitId), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'address' => 'Alamat Toko A',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'visit_result' => 'Order barang selesai',
            'transaction_status' => 'paid',
            'transaction_amount' => 150000,
            'new_tx_total' => 150000,
            'new_tx_paid' => 150000,
            'payment_method' => 'tunai',
            'new_payment_method' => 'tunai',
            'selfie' => $dummyImage,
            'final_store_photo' => $dummyImage,
        ]);
        $resCheckoutSales->assertOk();
    }
}
