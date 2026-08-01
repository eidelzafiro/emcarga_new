<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El flag penalizacuc de tipos_incidencias ya no se usa (en config
 * modelo los valores salen no activos). Se elimina columna y mapeos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_incidencias', function (Blueprint $table) {
            $table->dropColumn('penalizacuc');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_incidencias', function (Blueprint $table) {
            $table->boolean('penalizacuc')->default(false);
        });
    }
};
