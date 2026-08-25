<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalización de tipo_equipo (texto -> id) y ubicación del combustible.
     *
     * - tipos_tractivos.tipo_equipo (texto) -> id_tipo_equipo (FK tipos_equipos).
     * - Fusiona duplicados EXACTOS de tipos_tractivos y tipos_arrastres.
     * - tipos_arrastres: repara FK huérfana de id_tipo_equipo (catalogo_items -> tipos_equipos)
     *   y elimina id_tipo_combustible (el combustible vive en tractivos).
     * - tractivos: añade id_tipo_equipo (derivado de tipos_tractivos) e
     *   id_tipo_combustible (solo tractores; los arrastres no consumen combustible).
     */
    public function up(): void
    {
        // 1) tipos_tractivos: añadir id_tipo_equipo y poblarlo desde el texto.
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            $table->foreignId('id_tipo_equipo')
                ->nullable()
                ->after('tipo_equipo')
                ->constrained('tipos_equipos')
                ->nullOnDelete();
        });

        DB::statement("UPDATE tipos_tractivos SET id_tipo_equipo = CASE tipo_equipo
            WHEN 'CAMION PLATAFORMA' THEN 16
            WHEN 'CAMIONETA'         THEN 18
            WHEN 'OMNIBUS'           THEN 19
            WHEN 'FURGON'            THEN 17
            WHEN 'CISTERNA'          THEN 24
            WHEN 'MOTOS'             THEN 27
            WHEN 'JEEP'              THEN 28
            WHEN 'GRUA'              THEN 37
            WHEN 'CAMION'            THEN 40
            WHEN 'AUTO LIGERO'       THEN 15
            WHEN 'AUTO ESPECIAL'     THEN 15
            WHEN 'CAMION VOLTEO'     THEN 23
            WHEN 'S/R VOLTEO'        THEN 23
            WHEN 'CAMION GRUA'       THEN 37
            WHEN 'CAMION CISTERNA AGUA' THEN 24
            WHEN 'CUÑA TRACTORA'     THEN 31
            WHEN 'CUÑAS TRACTORAS'   THEN 31
            ELSE NULL END");

        // 2) Fusionar duplicados EXACTOS de tipos_tractivos (repuntar tractivos primero).
        DB::statement("UPDATE tractivos SET id_tipo_vehiculo = 188 WHERE id_tipo_vehiculo = 189");
        DB::statement("UPDATE tractivos SET id_tipo_vehiculo = 218 WHERE id_tipo_vehiculo = 291");
        DB::table('tipos_tractivos')->whereIn('id', [189, 291])->delete();

        // 3) Fusionar duplicados EXACTOS de tipos_arrastres (no tiene referencias FK).
        DB::table('tipos_arrastres')
            ->whereIn('id', [157, 188, 113, 114, 145, 191, 130])
            ->delete();

        // 4) tipos_arrastres: reparar id_tipo_equipo (FK huérfana) y quitar combustible.
        DB::statement("UPDATE tipos_arrastres
            SET id_tipo_equipo = NULL
            WHERE id_tipo_equipo IS NOT NULL
              AND id_tipo_equipo NOT IN (SELECT id FROM tipos_equipos)");

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            $table->dropForeign('fk_tipos_arrastres_id_tipo_equipo_catalogo');
            $table->foreign('id_tipo_equipo')
                ->references('id')->on('tipos_equipos')
                ->nullOnDelete();
            $table->dropForeign('tipos_arrastres_id_tipo_combustible_foreign');
            $table->dropColumn('id_tipo_combustible');
        });

        // 5) tractivos: id_tipo_equipo e id_tipo_combustible.
        Schema::table('tractivos', function (Blueprint $table) {
            $table->foreignId('id_tipo_equipo')
                ->nullable()
                ->after('id_tipo_vehiculo')
                ->constrained('tipos_equipos')
                ->nullOnDelete();
            $table->foreignId('id_tipo_combustible')
                ->nullable()
                ->after('id_tipo_equipo')
                ->constrained('tipos_combustibles')
                ->nullOnDelete();
        });

        // Equipo: derivado del tipo de tractivo (cubre tractores y arrastres).
        DB::statement("UPDATE tractivos t
            JOIN tipos_tractivos tt ON tt.id = t.id_tipo_vehiculo
            SET t.id_tipo_equipo = tt.id_tipo_equipo
            WHERE tt.id_tipo_equipo IS NOT NULL");

        // Combustible: solo tractores (mapeo 177-180 -> 14-20 de tipos_combustibles).
        // Los arrastres (presentes en arrastre_tractivo.id_arrastre) quedan en NULL.
        DB::statement("UPDATE tractivos t
            JOIN tipos_tractivos tt ON tt.id = t.id_tipo_vehiculo
            SET t.id_tipo_combustible = CASE tt.id_tipo_combustible
                WHEN 177 THEN 14
                WHEN 178 THEN 18
                WHEN 179 THEN 19
                WHEN 180 THEN 20
                ELSE NULL END
            WHERE tt.id_tipo_combustible IS NOT NULL
              AND t.id NOT IN (SELECT id_arrastre FROM arrastre_tractivo)");
    }

    public function down(): void
    {
        // Reversión estructural. Los duplicados fusionados no se restauran
        // automáticamente (usar la salva de BD para eso).

        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropForeign('tractivos_id_tipo_combustible_foreign');
            $table->dropForeign('tractivos_id_tipo_equipo_foreign');
            $table->dropColumn(['id_tipo_equipo', 'id_tipo_combustible']);
        });

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            $table->dropForeign('tipos_arrastres_id_tipo_equipo_foreign');
            $table->foreignId('id_tipo_combustible')
                ->nullable()
                ->constrained('tipos_combustibles')
                ->nullOnDelete();
            $table->foreign('id_tipo_equipo')
                ->references('id')->on('catalogo_items')
                ->nullOnDelete();
        });

        Schema::table('tipos_tractivos', function (Blueprint $table) {
            $table->dropForeign('tipos_tractivos_id_tipo_equipo_foreign');
            $table->dropColumn('id_tipo_equipo');
        });
    }
};
