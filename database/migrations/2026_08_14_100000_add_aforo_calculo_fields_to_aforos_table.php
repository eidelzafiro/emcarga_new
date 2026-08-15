<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amplía `aforos` con el desglose completo del cálculo legacy (com_aforo):
     * tarifas 1-5, descuentos 1-8, almacenaje, demora, recargos, tiempos,
     * salario/tasa y los indicadores de las filas 1-2 + totales.
     * Las filas 3-5 de indicadores viven en la tabla `indicadores` (paridad legacy).
     */
    public function up(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            // Tarifas por línea (1-5)
            $table->decimal('tarifa_mt_1', 10, 2)->default(0)->after('descuento');
            $table->decimal('tarifa_mt_2', 10, 2)->default(0)->after('tarifa_mt_1');
            $table->decimal('tarifa_mt_3', 10, 2)->default(0)->after('tarifa_mt_2');
            $table->decimal('tarifa_mt_4', 10, 2)->default(0)->after('tarifa_mt_3');
            $table->decimal('tarifa_mt_5', 10, 2)->default(0)->after('tarifa_mt_4');

            // Fletes por línea (1-5) MN y MLC
            $table->decimal('flete_mt_1', 12, 2)->default(0)->after('tarifa_mt_5');
            $table->decimal('flete_mt_2', 12, 2)->default(0)->after('flete_mt_1');
            $table->decimal('flete_mt_3', 12, 2)->default(0)->after('flete_mt_2');
            $table->decimal('flete_mt_4', 12, 2)->default(0)->after('flete_mt_3');
            $table->decimal('flete_mt_5', 12, 2)->default(0)->after('flete_mt_4');

            $table->decimal('flete_mlc_1', 12, 2)->default(0)->after('flete_mt_5');
            $table->decimal('flete_mlc_2', 12, 2)->default(0)->after('flete_mlc_1');
            $table->decimal('flete_mlc_3', 12, 2)->default(0)->after('flete_mlc_2');
            $table->decimal('flete_mlc_4', 12, 2)->default(0)->after('flete_mlc_3');
            $table->decimal('flete_mlc_5', 12, 2)->default(0)->after('flete_mlc_4');

            // Pesos cobrados por línea (1-5)
            $table->decimal('peso_cobrar_1', 10, 3)->default(0)->after('flete_mlc_5');
            $table->decimal('peso_cobrar_2', 10, 3)->default(0)->after('peso_cobrar_1');
            $table->decimal('peso_cobrar_3', 10, 3)->default(0)->after('peso_cobrar_2');
            $table->decimal('peso_cobrar_4', 10, 3)->default(0)->after('peso_cobrar_3');
            $table->decimal('peso_cobrar_5', 10, 3)->default(0)->after('peso_cobrar_4');

            // Descuentos por línea/almacenaje/demora (desc1-8)
            $table->decimal('desc_1', 6, 2)->default(0)->after('peso_cobrar_5');
            $table->decimal('desc_2', 6, 2)->default(0)->after('desc_1');
            $table->decimal('desc_3', 6, 2)->default(0)->after('desc_2');
            $table->decimal('desc_4', 6, 2)->default(0)->after('desc_3');
            $table->decimal('desc_5', 6, 2)->default(0)->after('desc_4');
            $table->decimal('desc_6', 6, 2)->default(0)->after('desc_5');
            $table->decimal('desc_7', 6, 2)->default(0)->after('desc_6');
            $table->decimal('desc_8', 6, 2)->default(0)->after('desc_7');

            // Almacenaje
            $table->decimal('almacenaje_peso', 6, 2)->default(0)->after('desc_8');
            $table->decimal('almacenaje_horas', 8, 2)->default(0)->after('almacenaje_peso');
            $table->decimal('almacenaje_tarifa', 10, 2)->default(0)->after('almacenaje_horas');
            $table->decimal('almacenaje_flete', 12, 2)->default(0)->after('almacenaje_tarifa');

            // Demora
            $table->decimal('tar_dem_1', 10, 2)->default(0)->after('almacenaje_flete');
            $table->decimal('tar_dem_2', 10, 2)->default(0)->after('tar_dem_1');
            $table->decimal('flete_dem_1', 12, 2)->default(0)->after('tar_dem_2');
            $table->decimal('flete_dem_2', 12, 2)->default(0)->after('flete_dem_1');
            $table->decimal('dem_carga', 8, 2)->default(0)->after('flete_dem_2');
            $table->decimal('dem_descarga', 8, 2)->default(0)->after('dem_carga');
            $table->decimal('dem_total', 8, 2)->default(0)->after('dem_descarga');
            $table->date('fecha_carga')->nullable()->after('dem_total');
            $table->string('hora_carga_1', 15)->nullable()->after('fecha_carga');
            $table->string('hora_carga_2', 15)->nullable()->after('hora_carga_1');
            $table->date('fecha_descarga')->nullable()->after('hora_carga_2');
            $table->string('hora_descarga_1', 15)->nullable()->after('fecha_descarga');
            $table->string('hora_descarga_2', 15)->nullable()->after('hora_descarga_1');

            // Tiempos salario
            $table->decimal('tiempo_otros', 8, 2)->default(0)->after('hora_descarga_2');
            $table->decimal('tiempo_movimiento', 8, 2)->default(0)->after('tiempo_otros');
            $table->decimal('tiempo_carga', 8, 2)->default(0)->after('tiempo_movimiento');
            $table->decimal('tiempo_descarga', 8, 2)->default(0)->after('tiempo_carga');
            $table->decimal('tiempo_total', 8, 2)->default(0)->after('tiempo_descarga');
            $table->decimal('tiempo_feriado', 8, 2)->default(0)->after('tiempo_total');

            // Recargos (otros ingresos)
            $table->decimal('recargo_1', 10, 2)->default(0)->after('tiempo_feriado');
            $table->decimal('recargo_2', 10, 2)->default(0)->after('recargo_1');
            $table->decimal('recargo_3', 10, 2)->default(0)->after('recargo_2');
            $table->decimal('recargo_4', 10, 2)->default(0)->after('recargo_3');
            $table->decimal('recargo_5', 10, 2)->default(0)->after('recargo_4');

            // Salario / coeficiente
            $table->foreignId('id_tasa')->nullable()->after('recargo_5');
            $table->decimal('tasa', 12, 6)->default(0)->after('id_tasa');
            $table->decimal('salario', 12, 2)->default(0)->after('tasa');

            // Indicadores: filas 1-2 + totales (las 3-5 viven en `indicadores`)
            $table->integer('viajes')->default(1)->after('salario');
            $table->tinyInteger('tipo_indicadores')->default(1)->after('viajes');
            $table->decimal('tn_pos_1', 10, 2)->default(0)->after('tipo_indicadores');
            $table->decimal('tn_pos_2', 10, 2)->default(0)->after('tn_pos_1');
            $table->decimal('tn_pos_total', 10, 2)->default(0)->after('tn_pos_2');
            $table->decimal('tn_real_1', 10, 2)->default(0)->after('tn_pos_total');
            $table->decimal('tn_real_2', 10, 2)->default(0)->after('tn_real_1');
            $table->decimal('tn_real_total', 10, 2)->default(0)->after('tn_real_2');
            $table->decimal('km_carga_1', 10, 2)->default(0)->after('tn_real_total');
            $table->decimal('km_carga_2', 10, 2)->default(0)->after('km_carga_1');
            $table->decimal('km_carga_total', 10, 2)->default(0)->after('km_carga_2');
            $table->decimal('km_vacio_1', 10, 2)->default(0)->after('km_carga_total');
            $table->decimal('km_vacio_2', 10, 2)->default(0)->after('km_vacio_1');
            $table->decimal('km_vacio_total', 10, 2)->default(0)->after('km_vacio_2');
            $table->decimal('km_total_1', 10, 2)->default(0)->after('km_vacio_total');
            $table->decimal('km_total_2', 10, 2)->default(0)->after('km_total_1');
            $table->decimal('km_total_total', 10, 2)->default(0)->after('km_total_2');
            $table->decimal('traf_pos_1', 10, 2)->default(0)->after('km_total_total');
            $table->decimal('traf_pos_2', 10, 2)->default(0)->after('traf_pos_1');
            $table->decimal('traf_pos_total', 10, 2)->default(0)->after('traf_pos_2');
            $table->decimal('traf_real_1', 10, 2)->default(0)->after('traf_pos_total');
            $table->decimal('traf_real_2', 10, 2)->default(0)->after('traf_real_1');
            $table->decimal('traf_real_total', 10, 2)->default(0)->after('traf_real_2');
            $table->date('fecha_aforada')->nullable()->after('traf_real_total');
        });
    }

    public function down(): void
    {
        $cols = [
            'tarifa_mt_1', 'tarifa_mt_2', 'tarifa_mt_3', 'tarifa_mt_4', 'tarifa_mt_5',
            'flete_mt_1', 'flete_mt_2', 'flete_mt_3', 'flete_mt_4', 'flete_mt_5',
            'flete_mlc_1', 'flete_mlc_2', 'flete_mlc_3', 'flete_mlc_4', 'flete_mlc_5',
            'peso_cobrar_1', 'peso_cobrar_2', 'peso_cobrar_3', 'peso_cobrar_4', 'peso_cobrar_5',
            'desc_1', 'desc_2', 'desc_3', 'desc_4', 'desc_5', 'desc_6', 'desc_7', 'desc_8',
            'almacenaje_peso', 'almacenaje_horas', 'almacenaje_tarifa', 'almacenaje_flete',
            'tar_dem_1', 'tar_dem_2', 'flete_dem_1', 'flete_dem_2',
            'dem_carga', 'dem_descarga', 'dem_total',
            'fecha_carga', 'hora_carga_1', 'hora_carga_2',
            'fecha_descarga', 'hora_descarga_1', 'hora_descarga_2',
            'tiempo_otros', 'tiempo_movimiento', 'tiempo_carga', 'tiempo_descarga',
            'tiempo_total', 'tiempo_feriado',
            'recargo_1', 'recargo_2', 'recargo_3', 'recargo_4', 'recargo_5',
            'id_tasa', 'tasa', 'salario',
            'viajes', 'tipo_indicadores',
            'tn_pos_1', 'tn_pos_2', 'tn_pos_total',
            'tn_real_1', 'tn_real_2', 'tn_real_total',
            'km_carga_1', 'km_carga_2', 'km_carga_total',
            'km_vacio_1', 'km_vacio_2', 'km_vacio_total',
            'km_total_1', 'km_total_2', 'km_total_total',
            'traf_pos_1', 'traf_pos_2', 'traf_pos_total',
            'traf_real_1', 'traf_real_2', 'traf_real_total',
            'fecha_aforada',
        ];

        Schema::table('aforos', function (Blueprint $table) use ($cols) {
            foreach ($cols as $col) {
                $table->dropColumn($col);
            }
        });
    }
};
