<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the the migrations.
     */
    public function up(): void
    {
        // Solicitudes de transporte
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->foreignId('id_lugar_origen')->constrained('lugares');
            $table->foreignId('id_lugar_destino')->constrained('lugares');
            $table->date('fecha_solicitud');
            $table->date('fecha_requerida');
            $table->decimal('toneladas_solicitadas', 10, 2);
            $table->string('tipo_carga', 100)->nullable();
            $table->text('descripcion_carga')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('id_cliente');
            $table->index('estado');
            $table->index('fecha_solicitud');
        });

        // Hojas de ruta
        Schema::create('hojas_ruta', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_solicitud')->constrained('solicitudes');
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->date('fecha_salida');
            $table->date('fecha_llegada_estimada')->nullable();
            $table->date('fecha_llegada_real')->nullable();
            $table->string('estado', 50)->default('en_transito');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('id_solicitud');
            $table->index('id_tractivo');
            $table->index('estado');
            $table->index('fecha_salida');
        });

        // Cartas de porte (documento comercial principal)
        Schema::create('cartas_porte', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_hoja_ruta')->constrained('hojas_ruta');
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->foreignId('id_lugar_origen')->constrained('lugares');
            $table->foreignId('id_lugar_destino')->constrained('lugares');
            $table->date('fecha_emision');
            $table->date('fecha_recepcion')->nullable();
            $table->decimal('toneladas', 10, 2);
            $table->decimal('tarifa_km', 10, 2)->nullable();
            $table->decimal('total_flete', 12, 2)->nullable();
            $table->string('estado', 50)->default('emitida');
            $table->text('notas')->nullable();
            $table->boolean('re Facturacion')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('id_hoja_ruta');
            $table->index('id_cliente');
            $table->index('estado');
            $table->index('fecha_emision');
        });

        // Planes de transporte
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 50)->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('id_cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes');

        Schema::dropIfExists('cartas_porte');
        Schema::dropIfExists('hojas_ruta');
        Schema::dropIfExists('solicitudes');
    }
};
