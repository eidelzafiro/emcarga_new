<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Alta del módulo Productos: permisos, asignación a COMERCIAL e ítem de menú
 * bajo Catálogos. El CRUD (controlador, policy, ruta y vista genérica) vive en
 * el código; el menú y los permisos son datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $acciones = ['ver', 'crear', 'editar', 'eliminar'];
        foreach ($acciones as $accion) {
            Permission::firstOrCreate(['name' => "productos.{$accion}", 'guard_name' => 'web']);
        }

        $comercial = Role::firstOrCreate(['name' => 'COMERCIAL']);
        $comercial->givePermissionTo(array_map(fn ($a) => "productos.{$a}", $acciones));

        $padre = DB::table('menu_items')
            ->whereNull('parent_id')
            ->where('label', 'Catálogos')
            ->value('id');

        if ($padre) {
            DB::table('menu_items')->updateOrInsert(
                ['route' => 'productos.index'],
                [
                    'parent_id' => $padre,
                    'label' => 'Productos',
                    'icon' => 'pi pi-circle-fill',
                    'permission' => 'productos.ver',
                    'orden' => 22,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->where('route', 'productos.index')->delete();

        $permisos = DB::table('permissions')->where('name', 'like', 'productos.%')->pluck('id');
        if ($permisos->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permisos)->delete();
            DB::table('permissions')->whereIn('id', $permisos)->delete();
        }
    }
};
