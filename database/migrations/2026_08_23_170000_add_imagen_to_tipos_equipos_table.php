<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Imágenes ilustrativas para los tipos de equipos (2026-08-23).
 *  - imagen: ruta relativa en el disco público (ej. "tipos_equipos/camion.jpg").
 *  - imagen_fuente: URL de origen (Wikimedia Commons) para trazabilidad/licencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_equipos', function (Blueprint $table) {
            $table->string('imagen', 255)->nullable()->after('activo');
            $table->string('imagen_fuente', 500)->nullable()->after('imagen');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_equipos', function (Blueprint $table) {
            $table->dropColumn(['imagen', 'imagen_fuente']);
        });
    }
};
