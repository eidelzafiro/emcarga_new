<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // Tarjetero (maestro de productos/inventario)
        // ============================================================
        Schema::create('tarjetero', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('tipo_linea', 50)->comment('bateria, lubricante, diferencial, neumatico, otro');
            $table->foreignId('id_marca')->nullable()->constrained('marcas');
            $table->foreignId('id_modelo')->nullable()->constrained('modelos');
            $table->foreignId('id_pais')->nullable()->constrained('paises');
            $table->decimal('existencia', 12, 2)->default(0);
            $table->decimal('precio_mn', 12, 2)->nullable();
            $table->decimal('precio_me', 12, 2)->nullable();
            $table->decimal('valor_mn', 12, 2)->nullable();
            $table->decimal('valor_me', 12, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ============================================================
        // Líneas de producto por tipo
        // ============================================================
        Schema::create('lineas_bateria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjetero')->constrained('tarjetero')->cascadeOnDelete();
            $table->decimal('amperaje', 8, 2)->nullable();
            $table->decimal('voltaje', 8, 2)->nullable();
            $table->decimal('largo', 8, 2)->nullable();
            $table->decimal('ancho', 8, 2)->nullable();
            $table->decimal('alto', 8, 2)->nullable();
            $table->string('durabilidad', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('lineas_diferencial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjetero')->constrained('tarjetero')->cascadeOnDelete();
            $table->foreignId('id_lubricante')->nullable()->constrained('tipos_lubricantes');
            $table->string('durabilidad', 100)->nullable();
            $table->decimal('ancho', 8, 2)->nullable();
            $table->string('relacion', 50)->nullable();
            $table->decimal('litros', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('lineas_lubricante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjetero')->constrained('tarjetero')->cascadeOnDelete();
            $table->foreignId('id_tipo_lubricante')->nullable()->constrained('tipos_lubricantes');
            $table->timestamps();
        });

        Schema::create('lineas_neumatico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjetero')->constrained('tarjetero')->cascadeOnDelete();
            $table->foreignId('id_tipo_neumatico')->nullable()->constrained('tipos_neumaticos');
            $table->foreignId('id_medida_neumatico')->nullable()->constrained('medidas_neumaticos');
            $table->integer('capas')->nullable();
            $table->decimal('presion', 8, 2)->nullable();
            $table->string('carga', 50)->nullable();
            $table->string('velocidad', 50)->nullable();
            $table->string('durabilidad', 100)->nullable();
            $table->boolean('regrabable')->default(false);
            $table->boolean('camara')->default(false);
            $table->timestamps();
        });

        Schema::create('lineas_otro_agregado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjetero')->constrained('tarjetero')->cascadeOnDelete();
            $table->foreignId('id_tipo_agregado')->nullable()->constrained('tipos_agregados');
            $table->string('durabilidad', 100)->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Movimientos de inventario (entradas/recepciones)
        // ============================================================
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();
            $table->foreignId('id_almacen')->nullable()->constrained('tarjetero');
            $table->foreignId('id_suministrador')->nullable()->constrained('clientes');
            $table->date('fecha_movimiento');
            $table->string('factura', 100)->nullable();
            $table->date('fecha_factura')->nullable();
            $table->decimal('importe_mn', 12, 2)->nullable();
            $table->decimal('importe_me', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_movimiento')->constrained('movimientos_inventario')->cascadeOnDelete();
            $table->foreignId('id_tarjetero')->constrained('tarjetero');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_mn', 12, 2)->nullable();
            $table->decimal('precio_me', 12, 2)->nullable();
            $table->decimal('valor_mn', 12, 2)->nullable();
            $table->decimal('valor_me', 12, 2)->nullable();
            $table->integer('renglon')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Detalle de vales de salida de inventario
        // ============================================================
        Schema::create('detalle_vales_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_vale')->constrained('vales')->cascadeOnDelete();
            $table->foreignId('id_tarjetero')->constrained('tarjetero');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_mn', 12, 2)->nullable();
            $table->decimal('precio_me', 12, 2)->nullable();
            $table->decimal('valor_mn', 12, 2)->nullable();
            $table->decimal('valor_me', 12, 2)->nullable();
            $table->integer('renglon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_vales_inventario');
        Schema::dropIfExists('detalle_movimientos_inventario');
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('lineas_otro_agregado');
        Schema::dropIfExists('lineas_neumatico');
        Schema::dropIfExists('lineas_lubricante');
        Schema::dropIfExists('lineas_diferencial');
        Schema::dropIfExists('lineas_bateria');
        Schema::dropIfExists('tarjetero');
    }
};
