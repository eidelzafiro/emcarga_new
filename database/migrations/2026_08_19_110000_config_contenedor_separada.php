<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa la config de CONTENEDORES de la de CARGA en `configuraciones_tarifa`
     * (decisión de negocio 2026-08-19, fase 5.2). El legacy usaba dos tablas
     * independientes: `com_tarconfigcarga`/`com_tarconfigcarga46` y
     * `com_tarconfigcont`/`com_tarconfigcont46`. La fila unificada solo contenía
     * valores de carga, por lo que los cálculos de contenedores (tc5 TH y
     * tc3/4 demora) discrepaban del legacy (ej. tc5 tarifa_horaria 420 vs 168,
     * tc3/4 demora 350/385 vs 280/315).
     *
     * Valores semilla:
     *  - Vigente (anio NULL): `com_tarconfigcont` (demora 500/550, kmsvacio
     *    1.80/0.99, tarhor1 600).
     *  - Histórica 2026: `com_tarconfigcont46` (demora 350/385, kmsvacio
     *    37.80/0.99, tarhor1 420).
     *
     * Nota: la config contenedor NO tiene `tarhor2` (el legacy `calcular_th`
     * referencia `tarhor2` pero la tabla no lo define; los aforos migrados
     * usaron siempre `tarhor1`).
     */
    public function up(): void
    {
        Schema::table('configuraciones_tarifa', function (Blueprint $table) {
            $table->decimal('demora_cont_1', 10, 2)->default(0)->after('demora_2');
            $table->decimal('demora_cont_2', 10, 2)->default(0)->after('demora_cont_1');
            $table->decimal('kms_vacio_cont_1', 10, 2)->default(0)->after('kms_vacio_2');
            $table->decimal('kms_vacio_cont_2', 10, 2)->default(0)->after('kms_vacio_cont_1');
            $table->decimal('tarifa_horaria_cont_1', 10, 2)->default(0)->after('tarifa_horaria_2');
        });

        // Vigente (anio NULL) = com_tarconfigcont
        DB::table('configuraciones_tarifa')
            ->whereNull('anio')
            ->update([
                'demora_cont_1' => 500.00,
                'demora_cont_2' => 550.00,
                'kms_vacio_cont_1' => 1.80,
                'kms_vacio_cont_2' => 0.99,
                'tarifa_horaria_cont_1' => 600.00,
            ]);

        // Histórica 2026 = com_tarconfigcont46
        DB::table('configuraciones_tarifa')
            ->where('anio', 2026)
            ->update([
                'demora_cont_1' => 350.00,
                'demora_cont_2' => 385.00,
                'kms_vacio_cont_1' => 37.80,
                'kms_vacio_cont_2' => 0.99,
                'tarifa_horaria_cont_1' => 420.00,
            ]);
    }

    public function down(): void
    {
        Schema::table('configuraciones_tarifa', function (Blueprint $table) {
            $table->dropColumn([
                'demora_cont_1', 'demora_cont_2',
                'kms_vacio_cont_1', 'kms_vacio_cont_2',
                'tarifa_horaria_cont_1',
            ]);
        });
    }
};