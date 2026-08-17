<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos indirectos mensuales por entidad (réplica `cont_contabilidad` legacy).
 * Solo columnas MN: el legacy guardaba los indirectos de taller y administración
 * por mes en una sola fila con idunidad → id_entidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indirectos_mensuales', function (Blueprint $table) {
            $table->id();
            $table->date('fcontabilidad');
            $table->decimal('dietas', 10, 2)->default(0);
            $table->decimal('chapa', 10, 2)->default(0);
            $table->decimal('combustiblemn', 10, 2)->default(0);
            $table->decimal('lubricantemn', 10, 2)->default(0);
            $table->decimal('piezasmn', 10, 2)->default(0);
            $table->decimal('amortizacionmn', 10, 2)->default(0);
            $table->decimal('salario', 10, 2)->default(0);
            $table->decimal('vacaciones', 10, 2)->default(0);
            $table->decimal('impuesto1', 10, 2)->default(0);
            $table->decimal('impuesto2', 10, 2)->default(0);
            $table->decimal('ogastosmn', 10, 2)->default(0);
            $table->decimal('indirectotallermn', 10, 2)->default(0);
            $table->decimal('indirectoadminmn', 10, 2)->default(0);
            $table->decimal('ingresosmn', 10, 2)->default(0);
            $table->decimal('toneladas', 10, 2)->default(0);
            $table->decimal('trafico', 10, 2)->default(0);
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->timestamps();

            $table->unique(['fcontabilidad', 'id_entidad']);
            $table->index('fcontabilidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indirectos_mensuales');
    }
};
