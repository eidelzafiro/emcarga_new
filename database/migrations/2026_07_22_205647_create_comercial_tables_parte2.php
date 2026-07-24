<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogos comerciales
        Schema::create('monedas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->nullable()->unique();
            $table->string('nombre', 255);
            $table->string('simbolo', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_cargas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_indicadores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->string('unidad', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Solicitudes de servicio
        Schema::create('solicitudes_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->foreignId('id_lugar_origen')->nullable()->constrained('lugares');
            $table->foreignId('id_lugar_destino')->nullable()->constrained('lugares');
            $table->foreignId('id_producto')->nullable()->constrained('productos');
            $table->foreignId('id_producto2')->nullable()->constrained('productos');
            $table->foreignId('id_tipo_carga')->nullable()->constrained('tipos_cargas');
            $table->foreignId('id_tipo_carga2')->nullable()->constrained('tipos_cargas');
            $table->foreignId('id_moneda')->nullable()->constrained('monedas');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->date('fecha_solicitud');
            $table->date('fecha_planificada')->nullable();
            $table->date('fecha_ejecutada')->nullable();
            $table->decimal('valor_mt', 12, 2)->nullable();
            $table->decimal('valor_total', 12, 2)->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha_solicitud');
        });

        // Carta porte / giro
        Schema::create('giros', function (Blueprint $table) {
            $table->id();
            $table->string('numero_carta_porte', 50)->unique();
            $table->foreignId('id_solicitud')->nullable()->constrained('solicitudes_servicio');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->foreignId('id_lugar_origen')->nullable()->constrained('lugares');
            $table->foreignId('id_lugar_destino')->nullable()->constrained('lugares');
            $table->foreignId('id_producto')->nullable()->constrained('productos');
            $table->foreignId('id_tipo_carga')->nullable()->constrained('tipos_cargas');
            $table->foreignId('id_moneda')->nullable()->constrained('monedas');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->date('fecha_parte');
            $table->decimal('ingreso_mt', 12, 2)->nullable();
            $table->decimal('flete_mt', 12, 2)->nullable();
            $table->string('estado', 50)->default('activo');
            $table->timestamps();

            $table->index('estado');
        });

        // Ajustes
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_giro')->constrained('giros')->cascadeOnDelete();
            $table->string('concepto', 255);
            $table->decimal('monto', 12, 2);
            $table->string('tipo', 50)->comment('descuento, recargo');
            $table->timestamps();
        });

        // Otros ingresos
        Schema::create('otros_ingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_giro')->constrained('giros')->cascadeOnDelete();
            $table->string('concepto', 255);
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->timestamps();
        });

        // Indicadores / Planes
        Schema::create('indicadores_planes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_indicador')->constrained('tipos_indicadores');
            $table->integer('periodo')->comment('año');
            $table->longText('valores_mensuales')->nullable();
            $table->decimal('plan_periodo', 12, 2)->nullable();
            $table->decimal('ajuste_periodo', 12, 2)->nullable();
            $table->decimal('real_periodo_anterior', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicadores_planes');
        Schema::dropIfExists('otros_ingresos');
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('giros');
        Schema::dropIfExists('solicitudes');
        Schema::dropIfExists('tipos_indicadores');
        Schema::dropIfExists('tipos_servicios');
        Schema::dropIfExists('tipos_cargas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('monedas');
    }
};
