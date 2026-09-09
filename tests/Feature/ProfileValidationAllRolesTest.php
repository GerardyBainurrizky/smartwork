<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileValidationAllRolesTest extends TestCase
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

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'username' => 'superadmin', 'email' => 'superadmin@company.id', 'phone' => '081234567890']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin User', 'username' => 'adminuser', 'email' => 'admin@company.id', 'phone' => '081234567890']);
        $this->admin->assignRole('admin');

        $this->sales = User::factory()->create(['name' => 'Sales User', 'username' => 'salesuser', 'email' => 'sales@company.id', 'phone' => '081234567890']);
        $this->sales->assignRole('sales');

        $this->staff = User::factory()->create(['name' => 'Staff User', 'username' => 'staffuser', 'email' => 'staff@company.id', 'phone' => '081234567890']);
        $this->staff->assignRole('staff');

        $this->driver = User::factory()->create(['name' => 'Driver User', 'username' => 'driveruser', 'email' => 'driver@company.id', 'phone' => '081234567890']);
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
     * TEST: Valid email formats must PASS for ALL 5 roles
     */
    public function test_valid_emails_pass_for_all_roles(): void
    {
        $validEmailTemplates = [
            '%s@gmail.com',
            '%s@yahoo.com',
            '%s@outlook.com',
            '%s@perusahaan.id',
            '%s@perusahaan.co.id',
            '%s@universitas.ac.id',
            '%s@ptmaju.co.id',
            '%s@tokomaju.co.id',
            '%s@kampus.ac.id',
            '%s@kampusabc.ac.id',
            '%s@kampus.id',
            '%s@organisasi.id',
            '%s@mail.perusahaan.co.id',
        ];

        foreach ($this->getAllRoleUsers() as $roleName => $user) {
            foreach ($validEmailTemplates as $template) {
                $email = sprintf($template, "user_{$roleName}_" . uniqid());
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
     * TEST: Invalid email formats must FAIL for ALL 5 roles with custom error message
     */
    public function test_invalid_emails_fail_for_all_roles(): void
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

    /**
     * TEST: Valid phone numbers (12-13 digits with 08 prefix) and empty phone must PASS for ALL 5 roles
     */
    public function test_valid_phone_numbers_pass_for_all_roles(): void
    {
        $validPhones = [
            '081234567890',  // 12 digits
            '0812345678901', // 13 digits
            '',              // Empty string (nullable)
        ];

        foreach ($this->getAllRoleUsers() as $roleName => $user) {
            foreach ($validPhones as $phone) {
                $response = $this
                    ->actingAs($user)
                    ->patch('/profile', [
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'phone' => $phone,
                    ]);

                $response->assertSessionHasNoErrors("Valid phone '{$phone}' failed for role: {$roleName}");
                $user->refresh();
                if ($phone === '') {
                    $this->assertNull($user->phone);
                } else {
                    $this->assertSame($phone, $user->phone);
                }
            }
        }
    }

    /**
     * TEST: Invalid phone numbers must FAIL for ALL 5 roles with custom error message
     */
    public function test_invalid_phone_numbers_fail_for_all_roles(): void
    {
        $invalidPhones = [
            '08',
            '0812',
            '081234',
            '0812345678',   // 10 digits
            '08123456789',  // 11 digits
            '08123456789012', // 14 digits
            '08abc123',
            '0812 3456 7890',
            'abc081234567890',
            '+6281234567890',
            '0812-3456-7890',
        ];

        foreach ($this->getAllRoleUsers() as $roleName => $user) {
            foreach ($invalidPhones as $phone) {
                $response = $this
                    ->actingAs($user)
                    ->patch('/profile', [
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'phone' => $phone,
                    ]);

                $response->assertSessionHasErrors('phone', "Invalid phone '{$phone}' unexpectedly passed for role: {$roleName}");
                $errors = session('errors')->get('phone');
                $this->assertContains('Nomor telepon harus terdiri dari 12–13 digit angka.', $errors);
            }
        }
    }
}
