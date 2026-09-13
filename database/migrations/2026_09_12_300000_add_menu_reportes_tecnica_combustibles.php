<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Páginas de reportes AGRUPADOS de Técnica y Combustible (autorizado por EIDEL
 * el 2026-09-12). Se añaden dos ítems al agrupador "Reportes" del menú:
 *   - Técnica      → reportes.tecnicas      (permiso reportes-tecnico.ver)
 *   - Combustibles → reportes.combustibles  (permiso reportes-combustible.ver)
 *
 * Acceso (instrucción del usuario):
 *   - Técnica:      TECNICA + SUPERADMIN.
 *   - Combustibles: TECNICA, CONTABILIDAD, COMERCIAL + SUPERADMIN. (Los reportes
 *     de combustible son del dominio de CONTABILIDAD; el permiso se concede
 *     también a los demás perfiles indicados.)
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['reportes-tecnico.ver', 'reportes-combustible.ver'] as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $asignacion = [
            'TECNICA'      => ['reportes-tecnico.ver', 'reportes-combustible.ver'],
            'CONTABILIDAD' => ['reportes-combustible.ver'],
            'COMERCIAL'    => ['reportes-combustible.ver'],
            'SUPERADMIN'   => ['reportes-tecnico.ver', 'reportes-combustible.ver'],
        ];

        foreach ($asignacion as $rol => $permisos) {
            $r = Role::firstOrCreate(['name' => $rol]);
            $r->givePermissionTo($permisos);
        }

        $padre = DB::table('menu_items')
            ->whereNull('parent_id')
            ->where('label', 'Reportes')
            ->value('id');

        if ($padre) {
            DB::table('menu_items')->updateOrInsert(
                ['route' => 'reportes.tecnicas'],
                [
                    'parent_id' => $padre,
                    'label' => 'Técnica',
                    'icon' => 'pi pi-wrench',
                    'route' => 'reportes.tecnicas',
                    'permission' => 'reportes-tecnico.ver',
                    'orden' => 5,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('menu_items')->updateOrInsert(
                ['route' => 'reportes.combustibles'],
                [
                    'parent_id' => $padre,
                    'label' => 'Combustibles',
                    'icon' => 'pi pi-filter',
                    'route' => 'reportes.combustibles',
                    'permission' => 'reportes-combustible.ver',
                    'orden' => 6,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->whereIn('route', ['reportes.tecnicas', 'reportes.combustibles'])->delete();
        Permission::where('name', 'reportes-combustible.ver')->delete();
    }
};
