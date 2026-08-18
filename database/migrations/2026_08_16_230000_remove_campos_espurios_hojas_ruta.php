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
            // Idempotente: en el schema dump estas columnas pueden estar o no
            // presentes según el estado en que se generó. Se verifica existencia
            // antes de dropear para que la migración corra en BD real y en testing.
            $columns = Schema::getColumnListing('hojas_ruta');
            $foreignKeys = collect(Schema::getForeignKeys('hojas_ruta'))->pluck('columns')->flatten()->all();

            if (in_array('id_cliente', $columns, true)) {
                if (in_array('id_cliente', $foreignKeys, true)) {
                    $table->dropForeign(['id_cliente']);
                }
                $table->dropColumn('id_cliente');
            }
            if (in_array('fecha_salida', $columns, true)) {
                $table->dropColumn('fecha_salida');
            }
            foreach (['fecha_llegada_estimada', 'fecha_llegada_real', 'observaciones'] as $col) {
                if (in_array($col, $columns, true)) {
                    $table->dropColumn($col);
                }
            }
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