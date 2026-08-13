<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el folio original del legacy (com_rfactura.factura), que NO es
     * único (secuencia por unidad), para trazabilidad tras re-numerar en el
     * ETL. La columna `numero` (bigint UNIQUE) lleva el folio nuevo.
     */
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('numero_legacy', 20)->nullable()->after('numero');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('numero_legacy');
        });
    }
};
