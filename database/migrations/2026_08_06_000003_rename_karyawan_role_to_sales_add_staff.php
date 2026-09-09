<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');
        $roles = $tables['roles'];
        $modelHasRoles = $tables['model_has_roles'];
        $roleHasPermissions = $tables['role_has_permissions'];
        $rolePivot = config('permission.column_names.role_pivot_key') ?? 'role_id';

        $karyawan = DB::table($roles)->where('name', 'karyawan')->where('guard_name', 'web')->first();
        $sales = DB::table($roles)->where('name', 'sales')->where('guard_name', 'web')->first();

        if ($karyawan) {
            if (! $sales) {
                // Simple rename: model_has_roles & role_has_permissions reference role_id,
                // so users keep their role automatically.
                DB::table($roles)->where('id', $karyawan->id)->update([
                    'name' => 'sales',
                    'updated_at' => now(),
                ]);
            } else {
                // Both roles exist: merge users and permissions into the sales role,
                // then remove the legacy karyawan role (no user loses access).
                $existingModels = DB::table($modelHasRoles)
                    ->where($rolePivot, $sales->id)
                    ->pluck('model_id')
                    ->all();

                if (! empty($existingModels)) {
                    DB::table($modelHasRoles)
                        ->where($rolePivot, $karyawan->id)
                        ->whereIn('model_id', $existingModels)
                        ->delete();
                }

                DB::table($modelHasRoles)
                    ->where($rolePivot, $karyawan->id)
                    ->update([$rolePivot => $sales->id]);

                $permissionIds = DB::table($roleHasPermissions)
                    ->where($rolePivot, $karyawan->id)
                    ->pluck('permission_id');

                foreach ($permissionIds as $permissionId) {
                    $exists = DB::table($roleHasPermissions)
                        ->where($rolePivot, $sales->id)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (! $exists) {
                        DB::table($roleHasPermissions)->insert([
                            $rolePivot => $sales->id,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }

                DB::table($roles)->where('id', $karyawan->id)->delete();
            }
        }

        // Create the new staff role (idempotent).
        if (! DB::table($roles)->where('name', 'staff')->where('guard_name', 'web')->exists()) {
            DB::table($roles)->insert([
                'name' => 'staff',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $tables = config('permission.table_names');
        $roles = $tables['roles'];

        $sales = DB::table($roles)->where('name', 'sales')->where('guard_name', 'web')->first();
        $karyawan = DB::table($roles)->where('name', 'karyawan')->where('guard_name', 'web')->first();

        if ($sales && ! $karyawan) {
            DB::table($roles)->where('id', $sales->id)->update([
                'name' => 'karyawan',
                'updated_at' => now(),
            ]);
        }

        DB::table($roles)->where('name', 'staff')->where('guard_name', 'web')->delete();
    }
};
