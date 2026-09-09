<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreReceivable;
use App\Models\User;
use App\Services\StoreReceivableService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReceivableManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $sales1;
    private User $sales2;
    private User $driver;
    private User $staff;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->sales1 = User::factory()->create();
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create();
        $this->sales2->assignRole('sales');

        $this->driver = User::factory()->create();
        $this->driver->assignRole('driver');

        $this->staff = User::factory()->create();
        $this->staff->assignRole('staff');

        $this->store1 = Store::create([
            'name' => 'Toko Subur',
            'code' => 'SB-01',
            'address' => 'Jl. Mawar',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales1->id,
        ]);

        $this->store2 = Store::create([
            'name' => 'Toko Makmur',
            'code' => 'MK-02',
            'address' => 'Jl. Melati',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'status' => 'active',
            'sales_penanggung_jawab_id' => $this->sales2->id,
        ]);
    }

    public function test_admin_and_super_admin_can_access_receivables_monitoring(): void
    {
        $this->actingAs($this->admin)->get(route('admin.receivables.index'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('admin.receivables.index'))->assertOk();
    }

    public function test_sales_driver_and_staff_are_blocked_from_admin_receivables(): void
    {
        $this->actingAs($this->sales1)->get(route('admin.receivables.index'))->assertRedirect();
        $this->actingAs($this->driver)->get(route('admin.receivables.index'))->assertRedirect();
        $this->actingAs($this->staff)->get(route('admin.receivables.index'))->assertRedirect();
    }

    public function test_receivable_ledger_balance_calculation_and_adjustment(): void
    {
        StoreReceivableService::addReceivable([
            'store_id' => $this->store1->id,
            'type' => StoreReceivableService::TYPE_OPENING,
            'amount' => 100000,
        ]);

        $this->assertSame('100000.00', StoreReceivableService::balanceForStore($this->store1->id));

        StoreReceivableService::recordPayment([
            'store_id' => $this->store1->id,
            'amount' => 40000,
            'payment_method' => 'tunai',
        ], $this->sales1);

        $this->assertSame('60000.00', StoreReceivableService::balanceForStore($this->store1->id));

        $res = $this->actingAs($this->admin)->postJson(route('admin.receivables.adjust', $this->store1->id), [
            'amount' => -10000,
            'notes' => 'Koreksi kelebihan tagihan',
        ]);
        $res->assertCreated();

        $this->assertSame('50000.00', StoreReceivableService::balanceForStore($this->store1->id));
    }

    public function test_driver_cannot_access_receivable_service(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        StoreReceivableService::assertCanAccessStore($this->driver, $this->store1);
    }
}
