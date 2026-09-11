<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Vistas de trabajo con pestañas (CRUD embebidos): permisos, asignación a los
 * perfiles Operativos/Comercial e ítems de menú bajo el grupo Comercial.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['operativos.ver', 'comercial.ver'] as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'OPERATIVOS'])->givePermissionTo('operativos.ver');
        Role::firstOrCreate(['name' => 'COMERCIAL'])->givePermissionTo('comercial.ver');

        $padre = DB::table('menu_items')
            ->whereNull('parent_id')
            ->where('label', 'Comercial')
            ->value('id');

        if ($padre) {
            $items = [
                ['route' => 'operativos.operaciones', 'label' => 'Operaciones', 'icon' => 'pi pi-briefcase', 'permission' => 'operativos.ver', 'orden' => 27],
                ['route' => 'comercial.operaciones', 'label' => 'Comercial', 'icon' => 'pi pi-th-large', 'permission' => 'comercial.ver', 'orden' => 28],
            ];

            foreach ($items as $item) {
                DB::table('menu_items')->updateOrInsert(
                    ['route' => $item['route']],
                    array_merge($item, [
                        'parent_id' => $padre,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->whereIn('route', ['operativos.operaciones', 'comercial.operaciones'])->delete();

        $permisos = DB::table('permissions')->whereIn('name', ['operativos.ver', 'comercial.ver'])->pluck('id');
        if ($permisos->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permisos)->delete();
            DB::table('permissions')->whereIn('id', $permisos)->delete();
        }
    }
};
