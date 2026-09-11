<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade `siempre_visible` a menu_items: los grupos con este flag se muestran
 * aunque el perfil activo no tenga permiso sobre ninguno de sus hijos. Así el
 * agrupador "Reportes" es visible en todos los módulos, y qué reportes ve cada
 * perfil se controla con los permisos reportes-*.ver (pantalla Perfiles).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('menu_items', 'siempre_visible')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->boolean('siempre_visible')->default(false)->after('activo');
            });
        }

        DB::table('menu_items')
            ->whereNull('parent_id')
            ->where('label', 'Reportes')
            ->update(['siempre_visible' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('menu_items', 'siempre_visible')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropColumn('siempre_visible');
            });
        }
    }
};
