<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alinea `otros_agregados` con el legacy `tec_otrosagregados`:
     * - id_tractivo: vínculo que determina la entidad (regla 2026-08-28).
     * - fecha_instalado (finstalado), km_acumulados (kacumulados),
     *   km_retirarse (kretirarse), notas (notas).
     */
    public function up(): void
    {
        Schema::table('otros_agregados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tractivo')->nullable()->after('id_entidad');
            $table->date('fecha_instalado')->nullable()->after('id_tractivo');
            $table->integer('km_acumulados')->nullable()->after('fecha_instalado');
            $table->integer('km_retirarse')->nullable()->after('km_acumulados');
            $table->text('notas')->nullable()->after('km_retirarse');

            $table->index('id_tractivo');
        });
    }

    public function down(): void
    {
        Schema::table('otros_agregados', function (Blueprint $table) {
            $table->dropIndex(['id_tractivo']);
            $table->dropColumn(['id_tractivo', 'fecha_instalado', 'km_acumulados', 'km_retirarse', 'notas']);
        });
    }
};
