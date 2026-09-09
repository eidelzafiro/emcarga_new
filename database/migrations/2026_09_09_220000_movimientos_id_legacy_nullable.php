<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los movimientos creados desde Zafiro (altas/traslados/bajas nuevos) no
 * tienen id legacy: la columna pasa a nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_rrhh', function (Blueprint $table) {
            $table->unsignedInteger('id_legacy')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No se puede restaurar NOT NULL sin valores (los nuevos tendrían null).
        Schema::table('movimientos_rrhh', function (Blueprint $table) {
            $table->unsignedInteger('id_legacy')->nullable(false)->default(0)->change();
        });
    }
};
