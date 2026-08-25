<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repoblación de id_tipo_equipo para los textos 'CUÑA TRACTORA'/'CUÑAS TRACTORAS'
     * que no estaban en el CASE de la migración anterior (tipos_equipos.id = 31).
     * También deriva id_tipo_equipo a los tractivos que dependen de esos tipos.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tipos_tractivos', 'id_tipo_equipo')) {
            return;
        }

        DB::statement("UPDATE tipos_tractivos
            SET id_tipo_equipo = 31
            WHERE id_tipo_equipo IS NULL
              AND tipo_equipo IN ('CUÑA TRACTORA', 'CUÑAS TRACTORAS')");

        DB::statement("UPDATE tractivos t
            JOIN tipos_tractivos tt ON tt.id = t.id_tipo_vehiculo
            SET t.id_tipo_equipo = tt.id_tipo_equipo
            WHERE t.id_tipo_equipo IS NULL
              AND tt.id_tipo_equipo IS NOT NULL");
    }

    public function down(): void
    {
        // No se revierte: la corrección es de calidad de datos.
    }
};
