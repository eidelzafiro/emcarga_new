<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Reportes de Documentos (cartas de porte y hojas de ruta): ítem de menú bajo
 * el agrupador Reportes y permiso de reportes para los perfiles que gestionan
 * esos documentos (Comercial y Operativos).
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['reportes.ver', 'reportes.generar'] as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        foreach (['COMERCIAL', 'OPERATIVOS'] as $rol) {
            $r = Role::firstOrCreate(['name' => $rol]);
            $r->givePermissionTo(['reportes.ver', 'reportes.generar']);
        }

        $padre = DB::table('menu_items')
            ->whereNull('parent_id')
            ->where('label', 'Reportes')
            ->value('id');

        if ($padre) {
            DB::table('menu_items')->updateOrInsert(
                ['route' => 'reportes.documentos'],
                [
                    'parent_id' => $padre,
                    'label' => 'Documentos',
                    'icon' => 'pi pi-file-edit',
                    'route' => 'reportes.documentos',
                    'permission' => 'reportes.ver',
                    'orden' => 4,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->where('route', 'reportes.documentos')->delete();
    }
};
