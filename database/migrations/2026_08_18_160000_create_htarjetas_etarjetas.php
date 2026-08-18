<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Históricos de tarjetas de combustible (réplica del legacy):
     * - htarjetas: histórico diario por tarjeta (cont_htarjetas).
     * - etarjetas: entregas/estado por tarjeta (cont_etarjetas, vacía en legacy).
     */
    public function up(): void
    {
        Schema::create('htarjetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjeta')->constrained('tarjetas')->cascadeOnDelete();
            $table->date('ftrabajo')->nullable();
            $table->string('codtm', 15)->nullable();
            $table->decimal('saldoinicialmon', 10, 2)->default(0);
            $table->decimal('saldoiniciallts', 10, 2)->default(0);
            $table->foreignId('id_monedas')->nullable()->constrained('monedas')->nullOnDelete();
            $table->foreignId('id_tipo_combustibles')->nullable()->constrained('tipos_combustibles')->nullOnDelete();
            $table->decimal('preciomn', 4, 2)->default(0);
            $table->decimal('saldocargadomon', 10, 2)->default(0);
            $table->decimal('saldocargadolts', 10, 2)->default(0);
            $table->decimal('saldodescargadomon', 10, 2)->default(0);
            $table->decimal('saldodescargadolts', 10, 2)->default(0);
            $table->decimal('saldotransferenciamon', 10, 2)->default(0);
            $table->decimal('saldotransferencialts', 10, 2)->default(0);
            $table->decimal('saldoactualmon', 10, 2)->default(0);
            $table->decimal('saldoactuallts', 10, 2)->default(0);
            $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('etarjetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjeta')->constrained('tarjetas')->cascadeOnDelete();
            $table->date('fmovimiento')->nullable();
            $table->unsignedBigInteger('id_entrega')->nullable();
            $table->unsignedBigInteger('id_recibe')->nullable();
            $table->decimal('saldomon', 6, 2)->default(0);
            $table->decimal('saldolts', 6, 2)->default(0);
            $table->unsignedBigInteger('id_comprobante')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etarjetas');
        Schema::dropIfExists('htarjetas');
    }
};
