<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D1 (auditoría) — Etapa 5: elimina las columnas repetidas de `aforos`.
 *
 * Tras normalizar las líneas de tarifa (1-5) en `aforo_lineas` y las filas de
 * indicadores (1-7) en `aforo_indicadores`, se eliminan de `aforos` las
 * columnas que representaban esos bloques repetidos.
 *
 * SE CONSERVAN en `aforos`: totales de indicadores (*_total), desc_6/7/8,
 * recargo_1..5, tar_dem/flete_dem, dem, hora, fecha y tiempo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columnas = [
            // Líneas de tarifa (1-5) → aforo_lineas
            'id_tipo_carga_1', 'id_tipo_carga_2', 'id_tipo_carga_3', 'id_tipo_carga_4', 'id_tipo_carga_5',
            'distancia_1', 'distancia_2', 'distancia_3', 'distancia_4', 'distancia_5',
            'tarifa_mt_1', 'tarifa_mt_2', 'tarifa_mt_3', 'tarifa_mt_4', 'tarifa_mt_5',
            'flete_mt_1', 'flete_mt_2', 'flete_mt_3', 'flete_mt_4', 'flete_mt_5',
            'flete_mlc_1', 'flete_mlc_2', 'flete_mlc_3', 'flete_mlc_4', 'flete_mlc_5',
            'peso_cobrar_1', 'peso_cobrar_2', 'peso_cobrar_3', 'peso_cobrar_4', 'peso_cobrar_5',
            'desc_1', 'desc_2', 'desc_3', 'desc_4', 'desc_5',

            // Filas de indicadores 1-2 → aforo_indicadores (se conservan *_total)
            'tn_pos_1', 'tn_pos_2', 'tn_real_1', 'tn_real_2',
            'km_carga_1', 'km_carga_2', 'km_vacio_1', 'km_vacio_2',
            'km_total_1', 'km_total_2', 'traf_pos_1', 'traf_pos_2',
            'traf_real_1', 'traf_real_2',
        ];

        Schema::table('aforos', function (Blueprint $table) use ($columnas) {
            foreach ($columnas as $columna) {
                if (Schema::hasColumn('aforos', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            $cols = [
                'id_tipo_carga_1', 'id_tipo_carga_2', 'id_tipo_carga_3', 'id_tipo_carga_4', 'id_tipo_carga_5',
                'distancia_1', 'distancia_2', 'distancia_3', 'distancia_4', 'distancia_5',
                'tarifa_mt_1', 'tarifa_mt_2', 'tarifa_mt_3', 'tarifa_mt_4', 'tarifa_mt_5',
                'flete_mt_1', 'flete_mt_2', 'flete_mt_3', 'flete_mt_4', 'flete_mt_5',
                'flete_mlc_1', 'flete_mlc_2', 'flete_mlc_3', 'flete_mlc_4', 'flete_mlc_5',
                'peso_cobrar_1', 'peso_cobrar_2', 'peso_cobrar_3', 'peso_cobrar_4', 'peso_cobrar_5',
                'desc_1', 'desc_2', 'desc_3', 'desc_4', 'desc_5',
                'tn_pos_1', 'tn_pos_2', 'tn_real_1', 'tn_real_2',
                'km_carga_1', 'km_carga_2', 'km_vacio_1', 'km_vacio_2',
                'km_total_1', 'km_total_2', 'traf_pos_1', 'traf_pos_2',
                'traf_real_1', 'traf_real_2',
            ];
            foreach ($cols as $col) {
                if (! Schema::hasColumn('aforos', $col)) {
                    $table->decimal($col, 12, 2)->nullable();
                }
            }
        });
    }
};
