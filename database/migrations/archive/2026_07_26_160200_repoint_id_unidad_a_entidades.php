<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Re-apunta las FK id_unidad (que referenciaban a la tabla `unidades`,
 * inexistente en el legacy) hacia `entidades`, renombrándolas id_entidad.
 * Decisión: solo existe una tabla de entidades (rh_entidades → entidades).
 */
return new class extends Migration
{
    private array $tablas = ['medidores', 'historial_tractivos'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['id_unidad']);
                $table->renameColumn('id_unidad', 'id_entidad');
                $table->foreign('id_entidad')->references('id')->on('entidades')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['id_entidad']);
                $table->renameColumn('id_entidad', 'id_unidad');
                $table->foreign('id_unidad')->references('id')->on('unidades')->nullOnDelete();
            });
        }
    }
};
