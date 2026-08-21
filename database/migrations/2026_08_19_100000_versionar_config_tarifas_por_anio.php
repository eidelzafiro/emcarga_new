<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versiona `configuraciones_tarifa` por año (decisión de negocio 2026-08-19,
     * opción C). La fila existente pasa a ser la VIGENTE (anio NULL) y se crea
     * la fila histórica 2026 con los valores de `com_tarconfigcarga46` del dump
     * `emcarga-20260721_020001.sql` (los aforos de 2026-01 se calcularon con esa
     * config; las tablas `com_tarconfigcarga46`/`com_tarconfigcont46` ya NO
     * existen en la BD legacy actual).
     */
    public function up(): void
    {
        Schema::table('configuraciones_tarifa', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio')->nullable()->after('id');
        });

        // Fila histórica 2026 (valores de `com_tarconfigcarga46`, dump 2026-07-21)
        $existe2026 = DB::table('configuraciones_tarifa')->where('anio', 2026)->exists();
        if (! $existe2026) {
            DB::table('configuraciones_tarifa')->insert([
                'anio' => 2026,
                'demora_1' => 280.00,
                'demora_2' => 315.00,
                'kms_vacio_1' => 7.70,
                'kms_vacio_2' => 21.00,
                'tarifa_horaria_1' => 168.00,
                'tarifa_horaria_2' => 210.00,
                'kms_adicionales_1' => 16.80,
                'kms_adicionales_2' => 25.70,
                'almacenaje' => 280.00,
                'recargo_1' => 210.00,
                'recargo_2' => 280.00,
                'recargo_3_1' => 805.00,
                'recargo_3_2' => 1680.00,
                'recargo_3_3' => 2450.00,
                'recargo_4' => 210.00,
                'recargo_5' => 1750.00,
                'hora_1' => 0,
                'hora_2' => 10,
                'hora_3' => 20,
                'izaje_1' => 70.00,
                'izaje_2' => 50.00,
                'valor_izaje_mt' => 0,
                'valor_izaje_me' => 0,
                'valor_almacenaje' => 0,
                'plazo_libre_exp' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('configuraciones_tarifa')->where('anio', 2026)->delete();
        Schema::table('configuraciones_tarifa', function (Blueprint $table) {
            $table->dropColumn('anio');
        });
    }
};
