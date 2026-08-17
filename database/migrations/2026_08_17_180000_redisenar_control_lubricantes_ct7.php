<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseña `control_lubricantes` con paridad del legacy CI3 `tec_controllubricante`
 * (módulo Taller → CT-7 Control de Lubricantes). La tabla estaba vacía (0 filas),
 * por lo que se dropea y recrea con el esquema definitivo.
 *
 * El registro es por sistema del vehículo (motor, transmisión, dirección, hidráulico,
 * frenos, agua, grasas rollete/copillas), con un tipo de lubricante por sistema
 * (FK a `lubricantes`) y un tipo de operación (RELLENO/MTTO/O.CAUSAS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('control_lubricantes');
        Schema::create('control_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_unidad')->nullable()->constrained('entidades');
            $table->date('fecha_cambio')->nullable();
            $table->string('tipo_operacion', 50)->default('RELLENO'); // RELLENO/MTTO/O.CAUSAS
            $table->decimal('litros_motor', 10, 2)->default(0);
            $table->decimal('litros_transmision', 10, 2)->default(0);
            $table->decimal('litros_direccion', 10, 2)->default(0);
            $table->decimal('litros_hidraulico', 10, 2)->default(0);
            $table->decimal('liquido_freno', 10, 2)->default(0);
            $table->decimal('agua_refrigerada', 10, 2)->default(0);
            $table->decimal('grasa_rollete', 10, 2)->default(0);
            $table->decimal('grasa_copillas', 10, 2)->default(0);
            $table->foreignId('id_lub_motor')->nullable()->constrained('lubricantes');
            $table->foreignId('id_lub_transmision')->nullable()->constrained('lubricantes');
            $table->foreignId('id_lub_hidraulico')->nullable()->constrained('lubricantes');
            $table->foreignId('id_lub_direccion')->nullable()->constrained('lubricantes');
            $table->foreignId('id_grasa_rollete')->nullable()->constrained('lubricantes');
            $table->foreignId('id_grasa_copillas')->nullable()->constrained('lubricantes');
            $table->foreignId('id_liquido_freno')->nullable()->constrained('lubricantes');
            $table->foreignId('id_agua')->nullable()->constrained('lubricantes');
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->timestamps();

            $table->index(['fecha_cambio', 'id_tractivo']);
            $table->index('tipo_operacion');
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('control_lubricantes');
        Schema::create('control_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_lubricante')->nullable()->constrained('lubricantes');
            $table->date('fecha_cambio')->nullable();
            $table->decimal('cantidad_litros', 10, 2)->default(0);
            $table->decimal('kilometraje', 10, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('id_orden_taller')->nullable()->constrained('ordenes_taller');
            $table->string('confeccionado_por')->nullable();
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }
};
