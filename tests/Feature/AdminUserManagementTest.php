<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeRole(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function adminUser(): User
    {
        $this->makeRole('admin');
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $this->makeRole($role);
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_user_summary_cards_show_realtime_counts(): void
    {
        $admin = $this->adminUser();
        $this->makeRole('super-admin');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $this->userWithRole('sales');
        $this->userWithRole('sales');
        $this->userWithRole('staff');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Total Pengguna', '5']);
        $response->assertSeeInOrder(['Administrator', '2']);
        $response->assertSeeInOrder(['Sales', '2']);
        $response->assertSeeInOrder(['Staff', '1']);
    }

    public function test_admin_user_search_finds_by_name_email_and_username(): void
    {
        $admin = $this->adminUser();
        $this->userWithRole('sales', ['name' => 'Hadi Saputra', 'email' => 'hadi@example.com', 'username' => 'hadi_01']);
        $this->userWithRole('sales', ['name' => 'Rina Wati', 'email' => 'rina@example.com', 'username' => 'rina_01']);

        $byName = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Hadi']));
        $byName->assertOk()->assertSee('Hadi Saputra')->assertDontSee('Rina Wati');

        $byEmail = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'rina@example.com']));
        $byEmail->assertOk()->assertSee('Rina Wati')->assertDontSee('Hadi Saputra');

        $byUsername = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'hadi_01']));
        $byUsername->assertOk()->assertSee('Hadi Saputra')->assertDontSee('Rina Wati');
    }

    public function test_admin_user_status_filter_works(): void
    {
        $admin = $this->adminUser();
        $active = $this->userWithRole('sales', ['name' => 'Sales Aktif']);
        $inactive = $this->userWithRole('staff', ['name' => 'Staff Nonaktif', 'status' => 'inactive']);

        $activeResponse = $this->actingAs($admin)->get(route('admin.users.index', ['status' => 'active']));
        $activeResponse->assertOk()->assertSee($active->name)->assertDontSee($inactive->name);

        $inactiveResponse = $this->actingAs($admin)->get(route('admin.users.index', ['status' => 'inactive']));
        $inactiveResponse->assertOk()->assertSee($inactive->name)->assertDontSee($active->name);
    }

    public function test_admin_user_role_filter_works(): void
    {
        $admin = $this->adminUser();
        $superAdmin = $this->userWithRole('super-admin', ['name' => 'Super Boss']);
        $sales = $this->userWithRole('sales', ['name' => 'Andi Sales']);
        $staff = $this->userWithRole('staff', ['name' => 'Dewi Staff']);

        $adminFilter = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'admin']));
        $adminFilter->assertOk()->assertSee($superAdmin->name)->assertSee($admin->name)
            ->assertDontSee($sales->name)->assertDontSee($staff->name);

        $salesFilter = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'sales']));
        $salesFilter->assertOk()->assertSee($sales->name)->assertDontSee($staff->name)->assertDontSee($superAdmin->name);

        $staffFilter = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'staff']));
        $staffFilter->assertOk()->assertSee($staff->name)->assertDontSee($sales->name)->assertDontSee($superAdmin->name);
    }

    public function test_admin_user_combined_filters_work(): void
    {
        $admin = $this->adminUser();
        $this->userWithRole('sales', ['name' => 'Hadi Firmansyah', 'status' => 'active']);
        $this->userWithRole('sales', ['name' => 'Budi Santoso', 'status' => 'active']);
        $this->userWithRole('staff', ['name' => 'Hadi Kurniawan', 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'role' => 'sales',
            'status' => 'active',
            'search' => 'Hadi',
        ]));

        $response->assertOk();
        $response->assertSee('Hadi Firmansyah');
        $response->assertDontSee('Budi Santoso');
        $response->assertDontSee('Hadi Kurniawan');
    }

    public function test_admin_user_pagination_is_10_per_page_and_preserves_filters(): void
    {
        $admin = $this->userWithRole('admin', ['name' => 'Admin Utama']);

        for ($i = 1; $i <= 12; $i++) {
            $this->userWithRole('sales', ['name' => sprintf('Sales %02d', $i)]);
        }

        $pageOne = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Sales']));
        $pageOne->assertOk();
        $pageOne->assertSeeInOrder(['Menampilkan', '1', '-', '10', 'dari', '12']);

        $pageTwo = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Sales', 'page' => 2]));
        $pageTwo->assertOk();
        $pageTwo->assertSeeInOrder(['Menampilkan', '11', '-', '12', 'dari', '12']);
        $pageTwo->assertSee('search=Sales');
    }

    public function test_admin_user_index_renders_delete_button_and_modal(): void
    {
        $admin = $this->adminUser();
        $this->userWithRole('sales', ['name' => 'Dewi Sales']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Hapus');
        $response->assertSee('Hapus Pengguna');
        $response->assertSee('Yakin ingin menghapus pengguna ini?');
    }

    public function test_admin_can_permanently_delete_user_without_relations(): void
    {
        $admin = $this->adminUser();
        $target = $this->userWithRole('sales', ['name' => 'Calon Dihapus']);

        $response = $this->actingAs($admin)->delete(route('admin.users.force-destroy', $target->id));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Pengguna berhasil dihapus secara permanen.');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_user_with_related_data(): void
    {
        $admin = $this->adminUser();
        $target = $this->userWithRole('sales', ['name' => 'Roni Sales']);
        Attendance::create([
            'user_id' => $target->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.force-destroy', $target->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseHas('attendances', ['user_id' => $target->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->delete(route('admin.users.force-destroy', $admin->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_user_create_form_renders_all_fields(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('Tambah Pengguna');
        $response->assertSee('name="username"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="role"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('name="status"', false);
    }

    public function test_admin_user_store_shows_validation_errors(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => '',
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
            'status' => 'weird',
        ]);

        $response->assertSessionHasErrors(['username', 'name', 'email', 'password', 'status']);

        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertSee('Username wajib diisi.')
            ->assertSee('Nama wajib diisi.')
            ->assertSee('Format email tidak valid.')
            ->assertSee('Password minimal 8 karakter.')
            ->assertSee('Status tidak valid.');
    }

    public function test_admin_user_edit_form_renders_all_fields(): void
    {
        $admin = $this->adminUser();
        $target = $this->userWithRole('sales', ['name' => 'Edit Saya']);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $target->id));

        $response->assertOk();
        $response->assertSee('Edit Pengguna');
        $response->assertSee('value="PUT"', false);
        $response->assertSee('name="username"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="role"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('Simpan Perubahan');
    }

    public function test_admin_edit_user_valid_emails_pass(): void
    {
        $admin = $this->adminUser();
        $target = $this->userWithRole('sales', ['name' => 'Target User', 'email' => 'target@gmail.com']);

        $validEmails = [
            'nama1@gmail.com',
            'nama2@yahoo.com',
            'nama3@outlook.com',
            'nama4@perusahaan.id',
            'nama5@perusahaan.co.id',
            'nama6@universitas.ac.id',
            'admin1@ptmaju.co.id',
            'nama7@tokomaju.co.id',
            'nama8@kampus.ac.id',
            'nama9@kampus.id',
            'nama10@organisasi.id',
            'abc@gmail.com',
        ];

        foreach ($validEmails as $email) {
            $response = $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
                'username' => 'target_sales',
                'name' => 'Target Sales User',
                'email' => $email,
                'role' => 'sales',
                'phone' => '081234567890',
                'status' => 'active',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('admin.users.index'));
            $this->assertDatabaseHas('users', ['id' => $target->id, 'email' => strtolower($email)]);
        }
    }

    public function test_admin_edit_user_invalid_emails_rejected(): void
    {
        $admin = $this->adminUser();
        $target = $this->userWithRole('sales', ['name' => 'Target User', 'email' => 'target_stable@gmail.com']);

        $invalidEmails = [
            'asoy.coy',
            'nama@gmail',
            'nama@gmail.cah',
            'nama@gmail.cvl',
            'nama@gmail.ok',
            'nama@gmail.oi',
            'nama@gmail.op',
            'nama@gmail.po',
            'nama@abc.com',
            'nama@perusahaan.com',
            '@i.com',
            'l@l.com',
            'a@gmail.com',
            'ab@gmail.com',
            'nama@@gmail.com',
            'nama@gmail..com',
            'nama@.com',
            'nama@.id',
            'nama@.co.id',
            'nama@.ac.id',
            'nama@i.id',
            'nama@i.co.id',
            'nama@i.ac.id',
            'nama gmail.com',
            'nama@gmail .com',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
                'username' => 'target_sales',
                'name' => 'Target Sales User',
                'email' => $email,
                'role' => 'sales',
                'phone' => '081234567890',
                'status' => 'active',
            ]);

            $response->assertSessionHasErrors('email');
            $this->assertDatabaseHas('users', ['id' => $target->id, 'email' => 'target_stable@gmail.com']);
        }
    }

    public function test_admin_edit_user_valid_phone_pass(): void
    {
        $admin = $this->adminUser();
        $target = $this->userWithRole('sales', ['name' => 'Target User', 'email' => 'target_phone@gmail.com']);

        // 12 digits
        $response12 = $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
            'username' => 'target_phone',
            'name' => 'Target Phone User',
            'email' => 'target_phone@gmail.com',
            'role' => 'sales',
            'phone' => '081234567890',
            'status' => 'active',
        ]);
        $response12->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'phone' => '081234567890']);

        // 13 digits
        $response13 = $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
            'username' => 'target_phone',
            'name' => 'Target Phone User',
            'email' => 'target_phone@gmail.com',
            'role' => 'sales',
            'phone' => '0812345678901',
            'status' => 'active',
        ]);
        $response13->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'phone' => '0812345678901']);

        // Nullable (empty phone)
        $responseEmpty = $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
            'username' => 'target_phone',
            'name' => 'Target Phone User',
            'email' => 'target_phone@gmail.com',
            'role' => 'sales',
            'phone' => '',
            'status' => 'active',
        ]);
        $responseEmpty->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'phone' => null]);
    }

    public function test_admin_edit_user_invalid_phone_rejected(): void
    {
        $admin = $this->adminUser();
        $target = $this->userWithRole('sales', ['name' => 'Target User', 'email' => 'target_phone_inv@gmail.com', 'phone' => '081234567890']);

        $invalidPhones = [
            '08',
            '0812',
            '081234',
            '08abc123',
            '0812 3456 7890',
            'abc081234567890',
            '+6281234567890',
            '08123456789012',
        ];

        foreach ($invalidPhones as $phone) {
            $response = $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
                'username' => 'target_phone_inv',
                'name' => 'Target Phone User',
                'email' => 'target_phone_inv@gmail.com',
                'role' => 'sales',
                'phone' => $phone,
                'status' => 'active',
            ]);

            $response->assertSessionHasErrors('phone');
            $this->assertDatabaseHas('users', ['id' => $target->id, 'phone' => '081234567890']);
        }
    }
}
