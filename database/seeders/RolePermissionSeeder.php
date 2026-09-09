<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard' => ['view'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'attendance' => ['view', 'create', 'edit', 'approve', 'delete'],
            'stores' => ['view', 'create', 'edit', 'delete'],
            'routes' => ['view', 'create', 'edit', 'delete'],
            'visits' => ['view', 'create', 'verify', 'delete'],
            'reports' => ['view', 'export'],
            'audit' => ['view'],
            'settings' => ['view', 'edit'],
        ];

        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'dashboard.view',
            'users.view', 'users.create', 'users.edit',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.approve',
            'stores.view', 'stores.create', 'stores.edit',
            'routes.view', 'routes.create', 'routes.edit',
            'visits.view', 'visits.verify',
            'reports.view', 'reports.export',
            'audit.view',
        ]);

        $sales = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $sales->givePermissionTo([
            'dashboard.view',
            'attendance.view', 'attendance.create',
            'visits.view', 'visits.create',
            'routes.view',
        ]);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->givePermissionTo([
            'dashboard.view',
            'attendance.view', 'attendance.create',
        ]);

        $driver = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $driver->givePermissionTo([
            'dashboard.view',
            'attendance.view', 'attendance.create',
            'visits.view', 'visits.create',
            'routes.view',
        ]);

        if (! User::where('username', 'superadmin')->exists()) {
            $user = User::create([
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'email' => 'superadmin@isatriselaras.id',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $user->assignRole('super-admin');
        }

        if (! User::where('username', 'admin')->exists()) {
            $user = User::create([
                'username' => 'admin',
                'name' => 'Administrator',
                'email' => 'admin@isatriselaras.id',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $user->assignRole('admin');
        }

        if (! User::where('username', 'karyawan')->exists()) {
            $user = User::create([
                'username' => 'karyawan',
                'name' => 'Sales Demo',
                'email' => 'karyawan@isatriselaras.id',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $user->assignRole('sales');
        }

        if (! User::where('username', 'staff')->exists()) {
            $user = User::create([
                'username' => 'staff',
                'name' => 'Staff Demo',
                'email' => 'staff@isa-smartwork.com',
                'password' => bcrypt('password123'),
                'status' => 'active',
            ]);
            $user->assignRole('staff');
        }
    }
}