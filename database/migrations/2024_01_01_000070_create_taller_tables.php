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
        // Tipos de mantenimiento
        Schema::create('tipos_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Subsistemas del vehículo
        Schema::create('subsistemas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tipos de operaciones de taller
        Schema::create('tipos_operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Órdenes de taller
        Schema::create('ordenes_taller', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->foreignId('id_tipo_mantenimiento')->constrained('tipos_mantenimiento');
            $table->date('fecha_ingreso');
            $table->date('fecha_salida_estimada')->nullable();
            $table->date('fecha_salida_real')->nullable();
            $table->decimal('kilometraje', 12, 2)->nullable();
            $table->string('estado', 50)->default('abierta');
            $table->text('diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('id_tractivo');
            $table->index('estado');
            $table->index('fecha_ingreso');
        });

        // Operaciones de órdenes de taller
        Schema::create('ordenes_operaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller');
            $table->foreignId('id_tipo_operacion')->constrained('tipos_operaciones');
            $table->foreignId('id_subsistema')->constrained('subsistemas')->nullable();
            $table->text('descripcion');
            $table->decimal('costo_mano_obra', 10, 2)->default(0);
            $table->decimal('costo_repuestos', 10, 2)->default(0);
            $table->decimal('costo_total', 10, 2)->default(0);
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();

            $table->index('id_orden_taller');
            $table->index('id_tipo_operacion');
        });

        // Piezas y repuestos
        Schema::create('piezas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('descripcion', 500)->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->integer('stock_actual')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
        });

        // Gastos de taller
        Schema::create('gastos_taller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller');
            $table->string('concepto', 255);
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('id_orden_taller');
            $table->index('fecha');
        });

        // Lubricantes
        Schema::create('lubricantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('tipo', 100)->nullable();
            $table->string('viscosidad', 50)->nullable();
            $table->decimal('costo_litro', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Control de lubricantes por vehículo
        Schema::create('control_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->foreignId('id_lubricante')->constrained('lubricantes');
            $table->date('fecha_cambio');
            $table->decimal('cantidad_litros', 8, 2);
            $table->decimal('kilometraje', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_tractivo');
            $table->index('fecha_cambio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_lubricantes');
        Schema::dropIfExists('lubricantes');
        Schema::dropIfExists('gastos_taller');
        Schema::dropIfExists('piezas');
        Schema::dropIfExists('ordenes_operaciones');
        Schema::dropIfExists('ordenes_taller');
        Schema::dropIfExists('tipos_operaciones');
        Schema::dropIfExists('subsistemas');
        Schema::dropIfExists('tipos_mantenimiento');
    }
};
