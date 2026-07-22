<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de clientes
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('razon_social', 255)->nullable();
            $table->string('nit', 50)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('telefono', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('contacto', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('nombre');
        });

        // Tabla de lugares (origen/destino)
        Schema::create('lugares', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('provincia', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('provincia');
        });

        // Tabla de distancias entre lugares
        Schema::create('distancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_lugar_origen')->constrained('lugares');
            $table->foreignId('id_lugar_destino')->constrained('lugares');
            $table->decimal('distancia_km', 10, 2);
            $table->decimal('tiempo_horas', 8, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['id_lugar_origen', 'id_lugar_destino']);
        });

        // Tabla de acuerdos tarifarios
        Schema::create('acuerdos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('tarifa_base', 12, 2);
            $table->string('moneda', 3)->default('CUP');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_cliente');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acuerdos');
        Schema::dropIfExists('distancias');
        Schema::dropIfExists('lugares');
        Schema::dropIfExists('clientes');
    }
};
