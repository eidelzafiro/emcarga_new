<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogos de taller
        Schema::create('naves', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->text('ubicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('vallas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->foreignId('id_nave')->constrained('naves')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('equipos_garaje', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_gastos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->string('tipo', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('conceptos_costos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->foreignId('id_tipo_gasto')->nullable()->constrained('tipos_gastos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Líneas del plan de mantenimiento (por kilometraje)
        Schema::create('lineas_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_mantenimiento')->constrained('tipos_mantenimiento')->cascadeOnDelete();
            $table->integer('kilometraje');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // Movimientos de órdenes de taller (cambio de nave/valla)
        Schema::create('movimientos_taller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller')->cascadeOnDelete();
            $table->foreignId('id_nave')->nullable()->constrained('naves');
            $table->foreignId('id_valla')->nullable()->constrained('vallas');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_final')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_final')->nullable();
            $table->integer('tiempo_minutos')->nullable();
            $table->timestamps();
        });

        // Gastos de orden de taller (recursos/almacén)
        Schema::create('gastos_orden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller')->cascadeOnDelete();
            $table->string('nombre', 255);
            $table->decimal('cantidad', 10, 2);
            $table->string('codigo_pieza', 100)->nullable();
            $table->string('vale', 50)->nullable();
            $table->text('motivo')->nullable();
            $table->foreignId('id_motor')->nullable()->constrained('motores');
            $table->timestamps();
        });

        // Control de lubricantes por vehículo (extensión)
        Schema::table('control_lubricantes', function (Blueprint $table) {
            $table->foreignId('id_orden_taller')->nullable()->constrained('ordenes_taller');
            $table->string('confeccionado_por', 100)->nullable();
        });

        // Costos de taller
        Schema::create('costos_taller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->constrained('tractivos')->cascadeOnDelete();
            $table->decimal('horas_taller', 10, 2)->default(0);
            $table->date('fecha');
            $table->timestamps();
        });

        // Consumo de piezas/repuestos
        Schema::create('consumo_piezas', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_concepto')->nullable()->constrained('conceptos_costos');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('importe_mn', 12, 2)->nullable();
            $table->decimal('importe_me', 12, 2)->nullable();
            $table->date('fecha');
            $table->timestamps();
        });

        // Amortización/depreciación
        Schema::create('amortizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->constrained('tractivos')->cascadeOnDelete();
            $table->decimal('amortizacion_mn', 12, 2);
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amortizaciones');
        Schema::dropIfExists('consumo_piezas');
        Schema::dropIfExists('costos_taller');

        Schema::table('control_lubricantes', function (Blueprint $table) {
            $table->dropColumn(['id_orden_taller', 'confeccionado_por']);
        });

        Schema::dropIfExists('gastos_orden');
        Schema::dropIfExists('movimientos_taller');
        Schema::dropIfExists('lineas_mantenimiento');
        Schema::dropIfExists('conceptos_costos');
        Schema::dropIfExists('tipos_gastos');
        Schema::dropIfExists('equipos_garaje');
        Schema::dropIfExists('vallas');
        Schema::dropIfExists('naves');
    }
};
