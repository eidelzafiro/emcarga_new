<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_factura')->nullable()->constrained('facturas');
            $table->date('fecha_conciliacion');
            $table->decimal('monto', 12, 2);
            $table->string('tipo', 50)->comment('bancaria, interna, cliente');
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha_conciliacion');
            $table->index('estado');
        });

        Schema::create('tipos_conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('otros_gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bolsa')->nullable()->constrained('bolsa');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_tipo_concepto')->nullable()->constrained('tipos_conceptos');
            $table->date('fecha');
            $table->string('concepto', 255);
            $table->decimal('monto_mn', 12, 2)->default(0);
            $table->decimal('monto_mlc', 12, 2)->default(0);
            $table->text('descripcion')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha');
            $table->index('estado');
        });

        Schema::create('combustible_cargas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_tarjeta')->nullable()->constrained('tarjetas');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_bolsa')->nullable()->constrained('bolsa');
            $table->date('fecha_carga');
            $table->decimal('cantidad_litros', 10, 2);
            $table->decimal('precio_litro', 10, 4);
            $table->decimal('total', 12, 2);
            $table->string('tipo_combustible', 50)->nullable();
            $table->string('lugar', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('registrada');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha_carga');
            $table->index('estado');
        });

        Schema::create('combustible_descargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carga')->nullable()->constrained('combustible_cargas');
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->date('fecha_descarga');
            $table->decimal('cantidad_litros', 10, 2);
            $table->decimal('kilometraje', 10, 2)->nullable();
            $table->string('tipo_combustible', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('registrada');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha_descarga');
            $table->index('estado');
        });

        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('categoria', 100)->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->decimal('cantidad_actual', 12, 2)->default(0);
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->decimal('costo_total', 12, 2)->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('categoria');
        });

        Schema::create('vales', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_bolsa')->nullable()->constrained('bolsa');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->date('fecha_emision');
            $table->string('tipo', 50)->comment('almacen, combustible, repuesto');
            $table->text('concepto')->nullable();
            $table->string('estado', 50)->default('emitido');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha_emision');
            $table->index('estado');
        });

        Schema::create('detalles_vale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_vale')->constrained('vales');
            $table->foreignId('id_inventario')->nullable()->constrained('inventario');
            $table->string('descripcion', 255);
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad', 50)->nullable();
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->timestamps();

            $table->index('id_vale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_vale');
        Schema::dropIfExists('vales');
        Schema::dropIfExists('inventario');
        Schema::dropIfExists('combustible_descargas');
        Schema::dropIfExists('combustible_cargas');
        Schema::dropIfExists('otros_gastos');
        Schema::dropIfExists('tipos_conceptos');
        Schema::dropIfExists('conciliaciones');
    }
};
