<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite cancelar solicitudes de servicio sin cartas de porte asociadas:
     * añade la fecha de cancelación y el usuario que cancela.
     */
    public function up(): void
    {
        Schema::table('solicitudes_servicio', function (Blueprint $table) {
            $table->dateTime('fecha_cancelacion')->nullable()->after('fecha_ejecutada');
            $table->unsignedBigInteger('id_user_cancelacion')->nullable()->after('fecha_cancelacion');
            $table->string('motivo_cancelacion')->nullable()->after('id_user_cancelacion');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_servicio', function (Blueprint $table) {
            $table->dropColumn(['fecha_cancelacion', 'id_user_cancelacion', 'motivo_cancelacion']);
        });
    }
};
