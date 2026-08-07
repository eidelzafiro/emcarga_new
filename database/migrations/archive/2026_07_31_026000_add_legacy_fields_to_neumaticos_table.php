<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ficha técnica del neumático legacy (tec_neumaticos): precios, posición,
     * año de fabricación, balanceada, kms promedio, fechas planificadas.
     */
    public function up(): void
    {
        Schema::table('neumaticos', function (Blueprint $table) {
            $table->decimal('precio_mn', 12, 2)->nullable()->after('kilometraje');
            $table->decimal('precio_me', 12, 2)->nullable()->after('precio_mn');
            $table->foreignId('id_posicion')->nullable()->constrained('posiciones_neumaticos')->after('precio_me');
            $table->date('fecha_fabricacion')->nullable()->after('id_posicion');
            $table->string('balanceada', 10)->nullable()->after('fecha_fabricacion');
            $table->integer('profinicial')->nullable()->after('balanceada');
            $table->decimal('explotacion_anterior', 12, 2)->nullable()->after('profinicial');
            $table->decimal('kms_promedio', 12, 2)->nullable()->after('explotacion_anterior');
            $table->date('fecha_plan_retiro')->nullable()->after('kms_promedio');
            $table->date('fecha_plan_aviso')->nullable()->after('fecha_plan_retiro');
        });
    }

    public function down(): void
    {
        Schema::table('neumaticos', function (Blueprint $table) {
            $table->dropForeign(['id_posicion']);
            $table->dropColumn([
                'precio_mn', 'precio_me', 'id_posicion', 'fecha_fabricacion',
                'balanceada', 'profinicial', 'explotacion_anterior',
                'kms_promedio', 'fecha_plan_retiro', 'fecha_plan_aviso',
            ]);
        });
    }
};
