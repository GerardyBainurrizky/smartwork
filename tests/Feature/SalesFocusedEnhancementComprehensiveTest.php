<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesFocusedEnhancementComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $driver;
    private User $staff;
    private User $admin;
    private Store $storeA;
    private Store $storeB;
    private Store $storeC;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->sales = User::factory()->create(['name' => 'Sales Test', 'status' => 'active']);
        $this->sales->assignRole('sales');

        $this->driver = User::factory()->create(['name' => 'Driver Test', 'status' => 'active']);
        $this->driver->assignRole('driver');

        $this->staff = User::factory()->create(['name' => 'Staff Test', 'status' => 'active']);
        $this->staff->assignRole('staff');

        $this->admin = User::factory()->create(['name' => 'Admin Test', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->storeA = Store::create([
            'name' => 'Toko A',
            'code' => 'TKO-A',
            'address' => 'Jl. A No. 1',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->storeB = Store::create([
            'name' => 'Toko B',
            'code' => 'TKO-B',
            'address' => 'Jl. B No. 2',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);

        $this->storeC = Store::create([
            'name' => 'Toko C',
            'code' => 'TKO-C',
            'address' => 'Jl. C No. 3',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
        ]);
    }

    /**
     * TEST 1: Sales Belum Presensi -> Mulai Rute DITOLAK
     */
    public function test_sales_cannot_start_route_without_attendance(): void
    {
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Pagi',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('route.start', $route->id));

        $res->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Anda belum melakukan Check In Presensi hari ini. Silakan lakukan presensi terlebih dahulu sebelum memulai rute.',
            ]);

        $this->assertEquals('draft', $route->fresh()->status);
    }

    /**
     * TEST 2: Presensi Kemarin Ada, Hari Ini Belum Presensi -> Mulai Rute DITOLAK
     */
    public function test_sales_cannot_start_route_with_yesterday_attendance_only(): void
    {
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => now()->subDay()->toDateString(),
            'clock_in' => now()->subDay()->setTime(8, 0),
            'status' => 'present',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Pagi',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('route.start', $route->id));

        $res->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Anda belum melakukan Check In Presensi hari ini. Silakan lakukan presensi terlebih dahulu sebelum memulai rute.',
            ]);

        $this->assertEquals('draft', $route->fresh()->status);
    }

    /**
     * TEST 3: Sales Izin Hari Ini -> Mulai Rute DITOLAK
     */
    public function test_sales_cannot_start_route_when_izin_today(): void
    {
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_IZIN,
            'clock_in' => now(), // submit absence sets clock_in
            'absence_note' => 'Ada urusan keluarga',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Pagi',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('route.start', $route->id));

        $res->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Presensi hari ini tercatat sebagai Izin sehingga rute tidak dapat dimulai.',
            ]);

        $this->assertEquals('draft', $route->fresh()->status);
    }

    /**
     * TEST 4: Sales Sakit Hari Ini -> Mulai Rute DITOLAK
     */
    public function test_sales_cannot_start_route_when_sakit_today(): void
    {
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_SAKIT,
            'clock_in' => now(),
            'absence_note' => 'Demam flu',
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Pagi',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('route.start', $route->id));

        $res->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Presensi hari ini tercatat sebagai Sakit sehingga rute tidak dapat dimulai.',
            ]);

        $this->assertEquals('draft', $route->fresh()->status);
    }

    /**
     * TEST 5: Sales Hadir & Check In Hari Ini -> Mulai Rute BERHASIL
     */
    public function test_sales_can_start_route_when_checked_in_today(): void
    {
        Attendance::create([
            'user_id' => $this->sales->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
            'clock_in' => now(),
        ]);

        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Pagi',
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $res = $this->actingAs($this->sales)->postJson(route('route.start', $route->id));

        $res->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Rute dimulai.',
            ]);

        $this->assertEquals('active', $route->fresh()->status);
        $this->assertNotNull($route->fresh()->started_at);
    }

    /**
     * TEST 7: Riwayat Transaksi Toko - Terpisah & Paginasi dengan Query String
     */
    public function test_store_transaction_history_is_paginated_and_separated(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            StoreTransaction::create([
                'store_id' => $this->storeA->id,
                'transaction_code' => sprintf('TRX-20260901-%04d', $i),
                'transaction_date' => now()->toDateString(),
                'transaction_amount' => 1000000,
                'total_paid' => 500000,
                'remaining_amount' => 500000,
                'status' => StoreTransaction::STATUS_SEBAGIAN,
                'created_by' => $this->sales->id,
            ]);
        }

        $resPage1 = $this->actingAs($this->sales)->get(route('sales.stores.show', $this->storeA->id));
        $resPage1->assertStatus(200);
        $resPage1->assertSee('TRX-20260901-0001');
        $resPage1->assertSee('15 Transaksi');

        $resPage2 = $this->actingAs($this->sales)->get(route('sales.stores.show', [$this->storeA->id, 'page' => 2]));
        $resPage2->assertStatus(200);
        $resPage2->assertSee('TRX-20260901-0015');
    }

    /**
     * TEST 8: /visit/history Menampilkan 3 Statistik yang Benar dan Data Tabel Finansial Terperinci
     */
    public function test_visit_history_statistics_and_financial_activity_table(): void
    {
        // 1. Transaksi bulan ini: Rp 5.000.000
        $trx = StoreTransaction::create([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-VHIST-01',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 5000000,
            'status' => StoreTransaction::STATUS_BELUM_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        // 2. Pembayaran piutang bulan ini: Rp 1.500.000 -> sisa Rp 3.500.000
        $payment = StoreTransactionPayment::create([
            'store_transaction_id' => $trx->id,
            'store_id' => $this->storeA->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'source' => 'visit',
            'recorded_by' => $this->sales->id,
        ]);

        // 3. Buat Visit yang mereferensikan transaksi dan pembayaran ini
        $route = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Test History',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);
        $visit = Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'transaction_amount' => 5000000,
            'transaction_status' => 'mixed',
        ]);

        $payment->update(['source_id' => $visit->id]);
        $trx->update(['reference_id' => $visit->id]);

        $res = $this->actingAs($this->sales)->get(route('visit.history'));
        $res->assertStatus(200);

        // Header cards
        $res->assertSee('Total Transaksi Bulan Ini');
        $res->assertSee('Piutang dari Transaksi Bulan Ini');
        $res->assertSee('Pembayaran Piutang Bulan Ini');
        $res->assertSee('Rp 5.000.000');
        $res->assertSee('Rp 3.500.000');
        $res->assertSee('Rp 1.500.000');

        // Table headers and columns
        $res->assertSee('Transaksi Baru');
        $res->assertSee('Bayar Piutang');
        $res->assertSee('Sisa Piutang');
        $res->assertDontSee('Aktivitas Keuangan');
        $res->assertDontSee('Piutang Setelah Kunjungan');
        $res->assertSee('Rp 5.000.000');
        $res->assertSee('Rp 1.500.000');
        $res->assertSee('Rp 3.500.000');
    }

    /**
     * TEST 9: Dashboard Sales 4 Cards di Bawah - Perhitungan Tingkat Penyelesaian & Masa Depan
     */
    public function test_sales_dashboard_bottom_4_cards_and_future_visits(): void
    {
        // Route Hari Ini (Jatuh Tempo) - 2 Stops: 1 visited/completed, 1 skipped
        $routeToday = Route::create([
            'user_id' => $this->sales->id,
            'name' => 'Rute Hari Ini',
            'date' => now()->toDateString(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        $stop1 = RouteStop::create([
            'route_id' => $routeToday->id,
            'store_id' => $this->storeA->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        Visit::create([
            'user_id' => $this->sales->id,
            'route_stop_id' => $stop1->id,
            'route_id' => $routeToday->id,
            'store_id' => $this->storeA->id,
            'status' => 'completed',
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'transaction_amount' => 10000000,
        ]);

        // Stop 2 skipped (bukan completed checkout)
        $stop2 = RouteStop::create([
            'route_id' => $routeToday->id,
            'store_id' => $this->storeB->id,
            'sequence' => 2,
            'status' => 'skipped',
        ]);

        // Route Masa Depan (Misal 5 hari ke depan dalam bulan ini) - 2 Stops (BELUM JATUH TEMPO)
        // Tidak boleh masuk ke denominator completion rate!
        $futureDate = now()->addDays(2)->month === now()->month ? now()->addDays(2)->toDateString() : now()->toDateString();
        if ($futureDate !== now()->toDateString()) {
            $futureRoute = Route::create([
                'user_id' => $this->sales->id,
                'name' => 'Rute Masa Depan',
                'date' => $futureDate,
                'status' => 'draft',
            ]);

            RouteStop::create([
                'route_id' => $futureRoute->id,
                'store_id' => $this->storeC->id,
                'sequence' => 1,
                'status' => 'pending',
            ]);
        }

        // StoreTransaction bulan berjalan
        StoreTransaction::create([
            'store_id' => $this->storeA->id,
            'transaction_code' => 'TRX-DASH-01',
            'transaction_date' => now()->toDateString(),
            'transaction_amount' => 10000000,
            'total_paid' => 10000000,
            'remaining_amount' => 0,
            'status' => StoreTransaction::STATUS_LUNAS,
            'created_by' => $this->sales->id,
        ]);

        $res = $this->actingAs($this->sales)->get(route('dashboard'));
        $res->assertStatus(200);

        // 4 Card Labels
        $res->assertSee('Rencana Bulan Ini');
        $res->assertSee('Kunjungan Bulan Ini');
        $res->assertSee('Total Transaksi Bulan Ini');
        $res->assertSee('Tingkat Penyelesaian Bulan Ini');

        // Due visits = 2 (stop1 dan stop2 dari route hari ini). Completed = 1 (stop1).
        // Completion rate = 1 / 2 * 100 = 50%
        // Stop masa depan TIDAK menurunkan completion rate.
        $res->assertSee('50%');
        $res->assertSee('Rp 10.000.000');
    }

    /**
     * TEST 10: Denominator 0 (Tidak ada kunjungan jatuh tempo) -> 0%, No NaN / Error
     */
    public function test_sales_dashboard_zero_due_visits_renders_zero_percent(): void
    {
        $res = $this->actingAs($this->sales)->get(route('dashboard'));
        $res->assertStatus(200);
        $res->assertSee('0%');
        $res->assertDontSee('NaN');
        $res->assertDontSee('Infinity');
    }
}
