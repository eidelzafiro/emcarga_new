<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía `cajas` con paridad del legacy CI3 `tec_cajas` (ficha técnica completa):
 * durabilidad, velocidades, cantidad de lubricante, kms acumulados y capacidad
 * de cárter. La tabla ya tiene 103 filas (ETL previo), se añaden con ALTER.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->integer('durabilidad')->nullable()->after('numero_serie');
            $table->integer('velocidades')->nullable()->after('durabilidad');
            $table->integer('cantidad_lubricante')->nullable()->after('velocidades');
            $table->integer('kms_acumulados')->nullable()->after('cantidad_lubricante');
            $table->integer('capacidad_carter')->nullable()->after('kms_acumulados');
            $table->foreignId('id_lubricante')->nullable()->after('capacidad_carter')->constrained('lubricantes');
            $table->foreignId('id_pais')->nullable()->after('id_lubricante')->constrained('paises');
            $table->date('fecha_instalacion')->nullable()->after('id_pais');
            $table->date('fecha_baja')->nullable()->after('fecha_instalacion');
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_pais');
            $table->dropConstrainedForeignId('id_lubricante');
            $table->dropColumn('fecha_baja');
            $table->dropColumn('fecha_instalacion');
            $table->dropColumn('capacidad_carter');
            $table->dropColumn('kms_acumulados');
            $table->dropColumn('cantidad_lubricante');
            $table->dropColumn('velocidades');
            $table->dropColumn('durabilidad');
        });
    }
};
