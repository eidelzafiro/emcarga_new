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
        // Facturas
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuestos', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('moneda', 3)->default('CUP');
            $table->string('estado', 50)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('id_cliente');
            $table->index('estado');
            $table->index('fecha_emision');
        });

        // Detalle de facturas
        Schema::create('facturas_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_factura')->constrained('facturas');
            $table->foreignId('id_carta_porte')->constrained('cartas_porte')->nullable();
            $table->string('descripcion', 255);
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->index('id_factura');
        });

        // Pre-facturas
        Schema::create('prefacturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->date('fecha_emision');
            $table->decimal('total', 12, 2);
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('id_cliente');
        });

        // Tarjetas de combustible
        Schema::create('tarjetas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->string('descripcion', 255);
            $table->foreignId('id_cliente')->constrained('clientes')->nullable();
            $table->decimal('saldo_actual', 12, 2)->default(0);
            $table->decimal('limite_credito', 12, 2)->nullable();
            $table->string('estado', 50)->default('activa');
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('estado');
        });

        // Movimientos de tarjetas
        Schema::create('movimientos_tarjetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjeta')->constrained('tarjetas');
            $table->string('tipo_movimiento', 50); // carga, descarga, transferencia
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo_anterior', 12, 2);
            $table->decimal('saldo_posterior', 12, 2);
            $table->date('fecha_movimiento');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('id_tarjeta');
            $table->index('fecha_movimiento');
        });

        // Asientos contables
        Schema::create('contabilidad', function (Blueprint $table) {
            $table->id();
            $table->string('numero_asiento', 50)->unique();
            $table->date('fecha_asiento');
            $table->string('tipo_concepto', 100);
            $table->string('descripcion', 500);
            $table->decimal('debe', 12, 2)->default(0);
            $table->decimal('haber', 12, 2)->default(0);
            $table->string('estado', 50)->default('borrador');
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_asiento');
            $table->index('fecha_asiento');
            $table->index('tipo_concepto');
        });

        // Detalle de asientos contables
        Schema::create('contabilidad_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asiento')->constrained('contabilidad');
            $table->string('cuenta_contable', 50);
            $table->string('sub_cuenta', 50)->nullable();
            $table->string('descripcion', 500);
            $table->decimal('debe', 12, 2)->default(0);
            $table->decimal('haber', 12, 2)->default(0);
            $table->timestamps();

            $table->index('id_asiento');
            $table->index('cuenta_contable');
        });

        // Dietas de choferes
        Schema::create('dietas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bolsa')->constrained('bolsa');
            $table->foreignId('id_hoja_ruta')->constrained('hojas_ruta')->nullable();
            $table->date('fecha');
            $table->decimal('monto', 10, 2);
            $table->string('tipo_dieta', 50); // normal, especial, etc.
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_bolsa');
            $table->index('fecha');
        });

        // Reembolsos
        Schema::create('reembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bolsa')->constrained('bolsa');
            $table->date('fecha');
            $table->decimal('monto', 10, 2);
            $table->text('concepto');
            $table->text('documentos')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_bolsa');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reembolsos');
        Schema::dropIfExists('dietas');
        Schema::dropIfExists('contabilidad_detalle');
        Schema::dropIfExists('contabilidad');
        Schema::dropIfExists('movimientos_tarjetas');
        Schema::dropIfExists('tarjetas');
        Schema::dropIfExists('prefacturas');
        Schema::dropIfExists('facturas_detalle');
        Schema::dropIfExists('facturas');
    }
};
