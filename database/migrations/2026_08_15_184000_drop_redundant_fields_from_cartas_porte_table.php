<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4d (2da pasada) — Elimina de `cartas_porte` los campos redundantes:
 *  - Lugares y moneda: ya viven en `solicitudes_servicio` (derivados).
 *  - Fletes/kms: ya viven en `aforos`.
 *  - Buque/turno: huérfanos, no usados.
 */
return new class extends Migration
{
    public function up(): void
    {
        // FKs de los lugares impiden dropear las columnas; se eliminan primero.
        foreach (['id_lugar_origen', 'id_lugar_destino'] as $columna) {
            if (Schema::hasColumn('cartas_porte', $columna)) {
                try {
                    Schema::table('cartas_porte', function (Blueprint $table) use ($columna) {
                        $table->dropForeign([$columna]);
                    });
                } catch (\Throwable $e) {
                    // FK no presente; se ignora.
                }
            }
        }

        Schema::table('cartas_porte', function (Blueprint $table) {
            $columnas = [
                'id_lugar_origen',
                'id_lugar_destino',
                'id_buque',
                'id_turno',
                'id_moneda',
                'tarifa_km',
                'total_flete',
                'ingreso_mt',
                'flete_mt',
                'kms1',
                'kms2',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('cartas_porte', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->unsignedBigInteger('id_lugar_origen')->nullable()->after('id_solicitud');
            $table->unsignedBigInteger('id_lugar_destino')->nullable()->after('id_lugar_origen');
            $table->unsignedBigInteger('id_buque')->nullable();
            $table->unsignedBigInteger('id_turno')->nullable();
            $table->unsignedBigInteger('id_moneda')->nullable();
            $table->decimal('tarifa_km', 10, 2)->nullable();
            $table->decimal('total_flete', 12, 2)->nullable();
            $table->decimal('ingreso_mt', 12, 2)->nullable();
            $table->decimal('flete_mt', 12, 2)->nullable();
            $table->unsignedInteger('kms1')->nullable();
            $table->unsignedInteger('kms2')->nullable();
        });
    }
};
