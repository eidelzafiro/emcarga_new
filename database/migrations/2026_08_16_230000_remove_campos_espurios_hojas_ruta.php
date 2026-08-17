<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina los campos espurios de `hojas_ruta` heredados del esquema
     * genérico original (2024_01_01_000050) que no corresponden al legacy
     * `com_hojaruta`: id_cliente, fecha_llegada_estimada, fecha_llegada_real,
     * observaciones y fecha_salida (redundante con fecha_emision).
     */
    public function up(): void
    {
        Schema::table('hojas_ruta', function (Blueprint $table) {
            $table->dropForeign(['id_cliente']);
            $table->dropIndex('hojas_ruta_fecha_salida_index');
            $table->dropColumn([
                'id_cliente',
                'fecha_llegada_estimada',
                'fecha_llegada_real',
                'observaciones',
                'fecha_salida',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('hojas_ruta', function (Blueprint $table) {
            $table->foreignId('id_cliente')->nullable()->after('id_tractivo')->constrained('clientes')->nullOnDelete();
            $table->date('fecha_salida')->nullable()->after('fecha_emision');
            $table->date('fecha_llegada_estimada')->nullable()->after('fecha_salida');
            $table->date('fecha_llegada_real')->nullable()->after('fecha_llegada_estimada');
            $table->text('observaciones')->nullable()->after('estado');
        });
    }
};