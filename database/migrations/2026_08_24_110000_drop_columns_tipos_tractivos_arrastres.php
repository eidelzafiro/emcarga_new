<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * tipos_tractivos y tipos_arrastres dejan de depender de columnas legacy
     * muertas (codigo/nombre en tractivos; codigo/nombre/descripcion/
     * capacidad_toneladas en arrastres) y salen del catálogo unificado.
     */
    public function up(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            if (Schema::hasColumn('tipos_tractivos', 'codigo')) {
                $table->dropColumn('codigo');
            }
            if (Schema::hasColumn('tipos_tractivos', 'nombre')) {
                $table->dropColumn('nombre');
            }
        });

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            foreach (['codigo', 'nombre', 'descripcion', 'capacidad_toneladas'] as $col) {
                if (Schema::hasColumn('tipos_arrastres', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Salir del catálogo unificado: eliminar las filas sincronizadas.
        // Se desactivan los chequeos de FK porque otros catálogos unificados
        // (fk_*_catalogo) pudieran apuntar a estos ítems ya retirados.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('catalogo_items')
            ->whereIn('tipo', ['tipos_equipos', 'tipos_arrastres'])
            ->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 255)->nullable();
        });

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('capacidad_toneladas', 10, 2)->nullable();
        });
    }
};
