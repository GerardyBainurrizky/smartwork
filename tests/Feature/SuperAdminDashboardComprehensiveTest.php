<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\Store;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminDashboardComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'staff']);
    }

    private function makeUser(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'status' => 'active',
        ], $attributes));
        $user->assignRole($role);
        return $user;
    }

    public function test_super_admin_dashboard_renders_all_required_sections_and_metrics(): void
    {
        $superAdmin = $this->makeUser('super-admin', ['name' => 'Super Boss']);
        $admin = $this->makeUser('admin', ['name' => 'Admin Utama']);
        $sales1 = $this->makeUser('sales', ['name' => 'Sales Budi']);
        $sales2 = $this->makeUser('sales', ['name' => 'Sales Citra']);
        $driver1 = $this->makeUser('driver', ['name' => 'Driver Doni']);
        $staff1 = $this->makeUser('staff', ['name' => 'Staff Eka']);

        // Stores
        $storeA = Store::create([
            'name' => 'Toko Berkah A',
            'code' => 'TKB-001',
            'sales_penanggung_jawab_id' => $sales1->id,
            'status' => 'active',
        ]);
        $storeB = Store::create([
            'name' => 'Toko Berkah B',
            'code' => 'TKB-002',
            'sales_penanggung_jawab_id' => $sales2->id,
            'status' => 'active',
        ]);

        $today = now()->toDateString();

        // 1. Transaksi Baru Toko A hari ini: Rp 1.000.000 dengan bayar Rp 300.000
        $txA = StoreTransaction::create([
            'store_id' => $storeA->id,
            'transaction_code' => 'TRX-001',
            'transaction_date' => $today,
            'transaction_amount' => 1000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'created_by' => $superAdmin->id,
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $txA->id,
            'amount' => 300000,
            'payment_method' => 'tunai',
            'payment_date' => $today,
            'source' => 'initial_payment',
            'notes' => 'Pembayaran awal',
            'recorded_by' => $superAdmin->id,
        ]);

        // 2. Transaksi Lama Toko B (kemarin): Rp 2.000.000, bayar hari ini Rp 500.000
        $txB = StoreTransaction::create([
            'store_id' => $storeB->id,
            'transaction_code' => 'TRX-002',
            'transaction_date' => now()->subDay()->toDateString(),
            'transaction_amount' => 2000000,
            'status' => StoreTransaction::STATUS_SEBAGIAN,
            'created_by' => $superAdmin->id,
        ]);
        StoreTransactionPayment::create([
            'store_transaction_id' => $txB->id,
            'amount' => 500000,
            'payment_method' => 'transfer',
            'payment_date' => $today,
            'source' => 'manual_payment',
            'notes' => 'Cicilan piutang lama',
            'recorded_by' => $superAdmin->id,
        ]);

        // 3. Driver Visit transaksi lunas hari ini: Rp 400.000
        Visit::create([
            'user_id' => $driver1->id,
            'store_id' => $storeA->id,
            'check_in_at' => now(),
            'check_out_at' => now()->addHour(),
            'status' => 'completed',
            'transaction_status' => 'paid',
            'transaction_amount' => 400000,
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
        ]);

        // 4. Presensi
        Attendance::create([
            'user_id' => $sales1->id,
            'date' => $today,
            'clock_in' => now()->setTime(8, 0),
            'status' => Attendance::STATUS_PRESENT,
        ]);
        Attendance::create([
            'user_id' => $driver1->id,
            'date' => $today,
            'status' => Attendance::STATUS_IZIN,
            'absence_note' => 'Izin keperluan keluarga',
        ]);
        Attendance::create([
            'user_id' => $staff1->id,
            'date' => $today,
            'status' => Attendance::STATUS_SAKIT,
            'absence_note' => 'Sakit demam',
        ]);

        // Request Super Admin Dashboard
        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.super-admin');

        // Total Pengguna & Breakdown
        $response->assertSee('Total Pengguna');
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('role=sales', false);
        $response->assertSee('role=driver', false);
        $response->assertSee('role=staff', false);
        $response->assertSee('role=admin', false);
        $response->assertSee('role=super-admin', false);

        // Uang Masuk Sales Hari Ini (Rp 300.000 + Rp 500.000 = Rp 800.000)
        $response->assertSee('Uang Masuk Sales Hari Ini');
        $response->assertSee(number_format(800000, 0, ',', '.'));
        $response->assertSee(number_format(300000, 0, ',', '.')); // Transaksi baru
        $response->assertSee(number_format(500000, 0, ',', '.')); // Bayar piutang

        // Uang Masuk Driver Hari Ini (Rp 400.000)
        $response->assertSee('Uang Masuk Driver Hari Ini');
        $response->assertSee(number_format(400000, 0, ',', '.'));

        // Piutang Sales (Toko A: 700.000, Toko B: 1.500.000 -> Total: 2.200.000)
        $response->assertSee('Piutang Sales');
        $response->assertSee(number_format(2200000, 0, ',', '.'));

        // Performa Keuangan Sales
        $response->assertSee('Performa Keuangan Sales');
        $response->assertSee('Pemasukan Transaksi Baru');
        $response->assertSee('Pembayaran Piutang Lama');
        $response->assertSee('Saldo Piutang Berjalan');

        // Presensi Hari Ini
        $response->assertSee('Presensi Hari Ini');
        $response->assertSee('Hadir');
        $response->assertSee('Izin');
        $response->assertSee('Sakit');

        // Named routes verification
        $response->assertSee(route('admin.users.index'), false);
        $response->assertSee(route('admin.receivables.summary.today-payments'), false);
        $response->assertSee(route('admin.receivables.summary.by-sales'), false);
        $response->assertSee(route('admin.driver.visits.index'), false);
        $response->assertSee(route('admin.attendance.index'), false);
        $response->assertSee(route('admin.stores.index'), false);
        $response->assertSee(route('admin.routes.index'), false);
        $response->assertSee(route('admin.driver.routes.index'), false);
        $response->assertSee(route('admin.reports.visits'), false);
        $response->assertSee(route('admin.reports.driver-visits'), false);
    }
}
