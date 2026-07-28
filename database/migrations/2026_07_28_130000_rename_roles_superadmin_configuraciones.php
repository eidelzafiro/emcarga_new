<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrar usuarios del viejo SUPERADMIN a ADMIN
        $oldSuperadmin = DB::table('roles')->where('name', 'SUPERADMIN')->first();
        $admin = DB::table('roles')->where('name', 'ADMIN')->first();

        if ($oldSuperadmin && $admin) {
            $oldUserIds = DB::table('model_has_roles')
                ->where('role_id', $oldSuperadmin->id)
                ->pluck('model_id');

            foreach ($oldUserIds as $userId) {
                $exists = DB::table('model_has_roles')
                    ->where('role_id', $admin->id)
                    ->where('model_id', $userId)
                    ->where('model_type', 'App\\Models\\User')
                    ->exists();
                if (! $exists) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $admin->id,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $userId,
                    ]);
                }
            }

            DB::table('model_has_roles')->where('role_id', $oldSuperadmin->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $oldSuperadmin->id)->delete();
            DB::table('roles')->where('id', $oldSuperadmin->id)->delete();
        }

        // 2. Renombrar ADMIN → SUPERADMIN
        DB::table('roles')->where('name', 'ADMIN')->update(['name' => 'SUPERADMIN']);

        // 3. Dar TODOS los permisos al nuevo SUPERADMIN
        $nuevoSuperadmin = Role::where('name', 'SUPERADMIN')->first();
        if ($nuevoSuperadmin) {
            $todosPermisos = Permission::all()->pluck('name');
            $nuevoSuperadmin->syncPermissions($todosPermisos);
        }

        // 4. Renombrar ADMINISTRADOR → CONFIGURACIONES
        DB::table('roles')->where('name', 'ADMINISTRADOR')->update(['name' => 'CONFIGURACIONES']);
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'SUPERADMIN')->update(['name' => 'ADMIN']);
        DB::table('roles')->where('name', 'CONFIGURACIONES')->update(['name' => 'ADMINISTRADOR']);

        $superadmin = Role::firstOrCreate(['name' => 'SUPERADMIN']);
        $permisos = DB::table('permissions')->pluck('name');
        $superadmin->syncPermissions($permisos);
    }
};
