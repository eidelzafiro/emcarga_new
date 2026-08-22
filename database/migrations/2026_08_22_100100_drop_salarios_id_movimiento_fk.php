<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * E-1: la columna `salarios_administrativos.id_movimiento` trae una FK
     * muerta hacia la tabla scaffolded `movimientos` (otro concepto, no usada).
     * Se suelta para poder poblarla con el id legacy de rh_saladmin.idmovimientos
     * y enlazarla vía `id_movimiento_rrhh` (→ movimientos_rrhh). La tabla
     * `movimientos` queda sin referencias y se ignora.
     */
    public function up(): void
    {
        Schema::table('salarios_administrativos', function (Blueprint $table) {
            $table->dropForeign(['id_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::table('salarios_administrativos', function (Blueprint $table) {
            $table->foreign('id_movimiento')->references('id')->on('movimientos')->nullOnDelete();
        });
    }
};
