<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade los campos de costos al catálogo de tipos de combustible
 * (réplica `tec_tipocombustibles` legacy: preciomn, elementomn, factor,
 * existfincmn, indice) y la provincia a los servicentros
 * (`cont_servicentros` legacy tenía idprovincia → `provincias`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_combustibles', function (Blueprint $table) {
            $table->decimal('preciomn', 10, 2)->default(0);
            $table->string('elementomn', 20)->nullable();
            $table->decimal('factor', 10, 3)->default(0);
            $table->decimal('existfincmn', 10, 2)->default(0);
            $table->decimal('indice', 5, 2)->default(0);
        });

        Schema::table('servicentros', function (Blueprint $table) {
            $table->foreignId('id_provincia')->nullable()->after('codigo')->constrained('provincias');
        });
    }

    public function down(): void
    {
        Schema::table('servicentros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_provincia');
        });

        Schema::table('tipos_combustibles', function (Blueprint $table) {
            $table->dropColumn(['preciomn', 'elementomn', 'factor', 'existfincmn', 'indice']);
        });
    }
};
