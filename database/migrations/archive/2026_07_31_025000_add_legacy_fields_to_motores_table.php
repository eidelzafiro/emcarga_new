<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recupera los campos originales de tec_motores que el esquema nuevo no
     * tenía (ficha técnica). Los ids de lubricante/país se guardan como
     * columnas simples: los catálogos legacy no mapean 1:1 al nuevo esquema.
     */
    public function up(): void
    {
        Schema::table('motores', function (Blueprint $table) {
            $table->string('cpl', 100)->nullable()->after('numero_serie');
            $table->integer('caballaje')->nullable()->after('cpl');
            $table->integer('cantidad_lubricante')->nullable()->after('caballaje');
            $table->integer('numero_tiempos')->nullable()->after('cantidad_lubricante');
            $table->integer('numero_cilindros')->nullable()->after('numero_tiempos');
            $table->integer('kms_acumulados')->nullable()->after('numero_cilindros');
            $table->integer('capacidad_carter')->nullable()->after('kms_acumulados');
            $table->date('fecha_instalacion')->nullable()->after('capacidad_carter');
            $table->date('fecha_baja')->nullable()->after('fecha_instalacion');
            $table->unsignedBigInteger('id_lubricante')->nullable()->after('fecha_baja');
            $table->unsignedBigInteger('id_pais')->nullable()->after('id_lubricante');
        });
    }

    public function down(): void
    {
        Schema::table('motores', function (Blueprint $table) {
            $table->dropColumn([
                'cpl', 'caballaje', 'cantidad_lubricante', 'numero_tiempos',
                'numero_cilindros', 'kms_acumulados', 'capacidad_carter',
                'fecha_instalacion', 'fecha_baja', 'id_lubricante', 'id_pais',
            ]);
        });
    }
};
