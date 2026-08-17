<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cierres mensuales de tarjetas de combustible (réplica `cont_htarjetas` legacy).
 * Una fila por (id_tarjeta, ftrabajo) con los saldos iniciales, cargados,
 * descargados, transferidos y actuales, en moneda y litros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierre_tarjetas', function (Blueprint $table) {
            $table->id();
            $table->date('ftrabajo');
            $table->foreignId('id_tarjeta')->constrained('tarjetas');
            $table->string('codtm', 15)->default('');
            $table->decimal('saldoinicialmon', 10, 2)->default(0);
            $table->decimal('saldoiniciallts', 10, 2)->default(0);
            $table->foreignId('id_monedas')->nullable()->constrained('monedas');
            $table->foreignId('id_tipo_combustibles')->nullable()->constrained('tipos_combustibles');
            $table->decimal('preciomn', 10, 2)->default(0);
            $table->decimal('saldocargadomon', 10, 2)->default(0);
            $table->decimal('saldocargadolts', 10, 2)->default(0);
            $table->decimal('saldodescargadomon', 10, 2)->default(0);
            $table->decimal('saldodescargadolts', 10, 2)->default(0);
            $table->decimal('saldotransferenciamon', 10, 2)->default(0);
            $table->decimal('saldotransferencialts', 10, 2)->default(0);
            $table->decimal('saldoactualmon', 10, 2)->default(0);
            $table->decimal('saldoactuallts', 10, 2)->default(0);
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->timestamps();

            $table->unique(['id_tarjeta', 'ftrabajo']);
            $table->index('ftrabajo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierre_tarjetas');
    }
};
