<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->dateTime('fecha_cancelacion')->nullable()->after('cancelada');
            $table->unsignedBigInteger('id_user_cancelacion')->nullable()->after('fecha_cancelacion');
            $table->index('id_user_cancelacion', 'cartas_porte_id_user_cancelacion_index');
        });
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->dropIndex('cartas_porte_id_user_cancelacion_index');
            $table->dropColumn(['fecha_cancelacion', 'id_user_cancelacion']);
        });
    }
};
