<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla cds_entidades: coeficiente CDS (Sistema de Pago por Resultados EMCARGA)
 * por entidad + mes + año. Lo introduce manualmente el cliente cada mes
 * (el legacy lo guardaba en rh_tiposistemaspago.cds, un solo valor global).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cds_entidades')) {
            return;
        }

        Schema::create('cds_entidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->unsignedTinyInteger('mes');          // 1-12
            $table->unsignedSmallInteger('ano');         // año de operaciones
            $table->decimal('cds', 12, 6)->default(0);   // coeficiente
            $table->unsignedInteger('id_user')->nullable();
            $table->timestamps();

            $table->unique(['id_entidad', 'mes', 'ano'], 'uq_cds_entidad_mes_ano');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cds_entidades');
    }
};
