<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileEmailStrictValidationAllRolesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $sales;
    protected User $staff;
    protected User $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'username' => 'superadmin', 'email' => 'superadmin@perusahaan.id', 'phone' => '081234567890']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin User', 'username' => 'adminuser', 'email' => 'admin@perusahaan.id', 'phone' => '081234567890']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['name' => 'Sales User', 'username' => 'salesuser', 'email' => 'sales@perusahaan.id', 'phone' => '081234567890']);
        $this->sales->assignRole('sales');

        $this->staff = User::factory()->create(['name' => 'Staff User', 'username' => 'staffuser', 'email' => 'staff@perusahaan.id', 'phone' => '081234567890']);
        $this->staff->assignRole('staff');

        $this->driver = User::factory()->create(['name' => 'Driver User', 'username' => 'driveruser', 'email' => 'driver@perusahaan.id', 'phone' => '081234567890']);
        $this->driver->assignRole('driver');
    }

    /**
     * Helper to get all 5 test users
     * @return array<string, User>
     */
    private function getAllRoleUsers(): array
    {
        return [
            'super-admin' => $this->superAdmin,
            'admin' => $this->admin,
            'sales' => $this->sales,
            'staff' => $this->staff,
            'driver' => $this->driver,
        ];
    }

    /**
     * TEST 1: Valid email formats must PASS for ALL 5 roles
     */
    public function test_valid_emails_pass_for_all_five_roles(): void
    {
        $validTemplates = [
            'user_%s@gmail.com',
            'user_%s@yahoo.com',
            'user_%s@outlook.com',
            'nama_%s@perusahaan.id',
            'nama_%s@perusahaan.co.id',
            'nama_%s@universitas.ac.id',
            'nama_%s@ptmaju.co.id',
            'nama_%s@tokomaju.co.id',
            'nama_%s@kampus.ac.id',
            'nama_%s@kampusabc.ac.id',
            'nama_%s@kampus.id',
            'nama_%s@organisasi.id',
            'nama_%s@mail.perusahaan.co.id',
        ];

        foreach ($this->getAllRoleUsers() as $roleName => $user) {
            foreach ($validTemplates as $template) {
                $email = sprintf($template, str_replace('-', '', $roleName) . uniqid());
                $response = $this
                    ->actingAs($user)
                    ->patch('/profile', [
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $email,
                        'phone' => '081234567890',
                    ]);

                $response->assertSessionHasNoErrors();
                $user->refresh();
                $this->assertSame(strtolower($email), $user->email, "Valid email '{$email}' failed for role: {$roleName}");
            }
        }
    }

    /**
     * TEST 2: Invalid email formats must FAIL for ALL 5 roles with custom error message
     */
    public function test_invalid_emails_fail_for_all_five_roles(): void
    {
        $invalidEmails = [
            'asoy.coy',
            'gerar@gamail.coy',
            'nama@gmail',
            'nama@gmail.cvl',
            'nama@gmail.ok',
            'nama@gmail.oi',
            'nama@gmail.op',
            'nama@gmail.po',
            'nama@abc.com',
            '@i.com',
            'l@l.com',
            'a@gmail.com',
            'nama@@gmail.com',
            'nama@gmail..com',
            'nama@.com',
            'nama@.id',
            'nama@i.id',
            'nama@i.co.id',
            'nama@i.ac.id',
            'nama gmail.com',
            'nama@gmail .com',
        ];

        foreach ($this->getAllRoleUsers() as $roleName => $user) {
            foreach ($invalidEmails as $email) {
                $response = $this
                    ->actingAs($user)
                    ->patch('/profile', [
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $email,
                        'phone' => '081234567890',
                    ]);

                $response->assertSessionHasErrors('email', "Invalid email '{$email}' unexpectedly passed for role: {$roleName}");
                $errors = session('errors')->get('email');
                $this->assertContains('Format email tidak valid. Gunakan email seperti nama@gmail.com atau nama@perusahaan.co.id.', $errors);
            }
        }
    }
}
