<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Página de reportes AGRUPADOS de Facturación (autorizado por EIDEL el
 * 2026-09-12). Añade el ítem "Facturación" al agrupador Reportes con acceso
 * para CONTABILIDAD, COMERCIAL y SUPERADMIN.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'reportes-facturacion.ver', 'guard_name' => 'web']);

        foreach (['CONTABILIDAD', 'COMERCIAL', 'SUPERADMIN'] as $rol) {
            $r = Role::firstOrCreate(['name' => $rol]);
            $r->givePermissionTo('reportes-facturacion.ver');
        }

        $padre = DB::table('menu_items')
            ->whereNull('parent_id')
            ->where('label', 'Reportes')
            ->value('id');

        if ($padre) {
            DB::table('menu_items')->updateOrInsert(
                ['route' => 'reportes.facturacion'],
                [
                    'parent_id' => $padre,
                    'label' => 'Facturación',
                    'icon' => 'pi pi-file',
                    'route' => 'reportes.facturacion',
                    'permission' => 'reportes-facturacion.ver',
                    'orden' => 7,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->where('route', 'reportes.facturacion')->delete();
        Permission::where('name', 'reportes-facturacion.ver')->delete();
    }
};
