<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina el módulo "Categorías Productos": tabla, ítem de menú y permisos.
 * El CRUD (controlador, modelo, policy y ruta) se retiró del código.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('categorias_productos');

        DB::table('menu_items')->where('route', 'categorias-productos.index')->delete();

        $permisos = DB::table('permissions')
            ->where('name', 'like', 'categorias-productos.%')
            ->pluck('id');

        if ($permisos->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permisos)->delete();

            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')->whereIn('permission_id', $permisos)->delete();
            }

            DB::table('permissions')->whereIn('id', $permisos)->delete();
        }
    }

    public function down(): void
    {
        // Eliminación destructiva; no se revierte.
    }
};
