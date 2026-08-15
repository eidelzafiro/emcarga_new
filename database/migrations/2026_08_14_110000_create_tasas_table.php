<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de tasas de salario por rango (réplica de `rh_tipotasas` legacy).
     * Cada fila define el coeficiente de salario para un tipo de carga dentro de
     * un rango de distancia (dist1-dist2) y de capacidad (cap1-cap2), opcionalmente
     * con un segundo coeficiente `tasa2` (cuando hay chofer 2).
     */
    public function up(): void
    {
        Schema::create('tasas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 250);
            $table->decimal('tasa', 12, 6)->default(0);
            $table->decimal('tasa2', 12, 6)->default(0);
            $table->foreignId('id_tipo_carga')->nullable()->constrained('tipos_cargas');
            $table->integer('distancia_1')->default(0);
            $table->integer('distancia_2')->default(0);
            $table->integer('capacidad_1')->default(0);
            $table->integer('capacidad_2')->default(0);
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->timestamps();

            $table->index(['id_tipo_carga', 'id_entidad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas');
    }
};
