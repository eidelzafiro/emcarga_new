<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diferenciales legacy (tec_diferenciales): id_tractivo=0 (sin tractivo)
     * se hace nullable. Se añaden las columnas de ficha técnica del diferencial
     * (decisión usuario 2026-07-31).
     */
    public function up(): void
    {
        Schema::table('diferenciales', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable()->change();
        });

        Schema::table('diferenciales', function (Blueprint $table) {
            $table->integer('durabilidad')->nullable()->after('estado');
            $table->integer('relacion')->nullable()->after('durabilidad');
            $table->integer('ancho')->nullable()->after('relacion');
            $table->integer('cantidad_lubricante')->nullable()->after('ancho');
            $table->integer('cantidad')->nullable()->after('cantidad_lubricante');
            $table->integer('kms_acumulados')->nullable()->after('cantidad');
            $table->integer('capacidad_carter')->nullable()->after('kms_acumulados');
        });
    }

    public function down(): void
    {
        Schema::table('diferenciales', function (Blueprint $table) {
            $table->dropColumn([
                'durabilidad', 'relacion', 'ancho', 'cantidad_lubricante',
                'cantidad', 'kms_acumulados', 'capacidad_carter',
            ]);
            $table->foreignId('id_tractivo')->nullable(false)->change();
        });
    }
};
