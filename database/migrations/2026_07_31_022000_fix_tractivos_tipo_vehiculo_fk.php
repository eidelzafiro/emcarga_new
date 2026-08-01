<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tractivos.id_tipo_vehiculo apuntaba a tipos_vehiculos (catálogo unificado,
     * vacía). El legacy (tec_tractivos.idtipotractivos) referencia tec_tipotractivos,
     * que migra a tipos_tractivos (ficha técnica). Se redirige la FK.
     */
    public function up(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_vehiculo']);
        });

        Schema::table('tractivos', function (Blueprint $table) {
            $table->foreignId('id_tipo_vehiculo')->nullable()->change()->constrained('tipos_tractivos');
        });

        // Los 6 tipos "BIEL" (fabricacion en texto) no cupieron en el int de la
        // tabla nueva durante la migración genérica. Se reinsertan con sus ids legacy.
        $biel = [
            94 => ['nombre' => 'BIEL', 'id_marca' => 6, 'id_modelo' => 6, 'id_pais' => 79, 'id_tipo_combustible' => 18],
            95 => ['nombre' => 'BIEL', 'id_marca' => 6, 'id_modelo' => 6, 'id_pais' => 79, 'id_tipo_combustible' => 19],
            96 => ['nombre' => 'BIEL', 'id_marca' => 6, 'id_modelo' => 7, 'id_pais' => 79, 'id_tipo_combustible' => 18],
            97 => ['nombre' => 'BIEL', 'id_marca' => 7, 'id_modelo' => 8, 'id_pais' => 79, 'id_tipo_combustible' => 18],
            166 => ['nombre' => 'BIEL', 'id_marca' => 40, 'id_modelo' => 76, 'id_pais' => 79, 'id_tipo_combustible' => 18],
            167 => ['nombre' => 'BIEL', 'id_marca' => 39, 'id_modelo' => 29, 'id_pais' => 79, 'id_tipo_combustible' => 19],
        ];

        foreach ($biel as $id => $datos) {
            DB::table('tipos_tractivos')->updateOrInsert(
                ['id' => $id],
                array_merge($datos, [
                    'fabricacion' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_vehiculo']);
        });

        Schema::table('tractivos', function (Blueprint $table) {
            $table->foreignId('id_tipo_vehiculo')->nullable()->change()->constrained('tipos_vehiculos');
        });

        DB::table('tipos_tractivos')->whereIn('id', [94, 95, 96, 97, 166, 167])->delete();
    }
};
