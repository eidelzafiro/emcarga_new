<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_ingresos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('siglas', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('numero')->index();
            $table->date('fecha_emision');
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->bigInteger('id_unidad')->nullable()->index();
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->decimal('flete_mt', 12, 2)->default(0);
            $table->decimal('flete_mlc', 12, 2)->default(0);
            $table->decimal('flete_demora', 12, 2)->default(0);
            $table->decimal('otros_mt', 12, 2)->default(0);
            $table->decimal('ingreso_mt', 12, 2)->default(0);
            $table->boolean('cancelada')->default(false);
            $table->boolean('refacturada')->default(false);
            $table->boolean('oventas')->default(false);
            $table->foreignId('id_tipo_ingreso')->nullable()->constrained('tipo_ingresos');
            $table->text('notas')->nullable();
            $table->date('fecha_firma')->nullable();
            $table->date('fecha_cobro_mn')->nullable();
            $table->date('fecha_cobro_mlc')->nullable();
            $table->date('fecha_conciliacion')->nullable();
            $table->string('factura_cliente', 100)->nullable();
            $table->string('doc_pago_mn', 100)->nullable();
            $table->string('estado', 50)->default('emitida');
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha_emision');
        });

        Schema::create('prefacturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->date('fecha');
            $table->decimal('flete_mt', 12, 2)->default(0);
            $table->decimal('flete_mlc', 12, 2)->default(0);
            $table->decimal('flete_demora', 12, 2)->default(0);
            $table->decimal('otros_mt', 12, 2)->default(0);
            $table->decimal('ingreso_mt', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha');
        });

        Schema::create('aforos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carta_porte')->constrained('giros');
            $table->foreignId('id_factura')->nullable()->constrained('facturas');
            $table->foreignId('id_prefactura')->nullable()->constrained('prefacturas');
            $table->date('fecha_parte');
            $table->decimal('flete_mt', 12, 2)->default(0);
            $table->decimal('flete_mlc', 12, 2)->default(0);
            $table->decimal('flete_demora', 12, 2)->default(0);
            $table->decimal('otros_mt', 12, 2)->default(0);
            $table->decimal('ingreso_mt', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->boolean('refactura')->default(false);
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique('id_carta_porte');
            $table->index('id_factura');
            $table->index('fecha_parte');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aforos');
        Schema::dropIfExists('prefacturas');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('tipo_ingresos');
    }
};
