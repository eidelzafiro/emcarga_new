<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseña las tablas operativas de Órdenes de Taller con paridad del legacy CI3:
 *
 *   tec_otoperaciones      → ordenes_operaciones  (hasta 3 operarios + tiempos)
 *   tec_otgasto            → gastos_orden         (piezas/recursos de almacén)
 *   tec_movimientostaller  → movimientos_taller   (ubicación nave/valla + tiempos)
 *
 * Todas estaban vacías (0 filas), por lo que se dropean y recrean.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Órdenes de taller → operaciones (hasta 3 operarios)
        Schema::dropIfExists('ordenes_operaciones');
        Schema::create('ordenes_operaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller');
            $table->foreignId('id_tipo_operacion')->nullable()->constrained('tipos_operaciones');
            $table->foreignId('id_operario')->nullable()->constrained('bolsa');
            $table->foreignId('id_operario2')->nullable()->constrained('bolsa');
            $table->foreignId('id_operario3')->nullable()->constrained('bolsa');
            $table->date('fecha_inicio')->nullable();
            $table->string('hora_inicio', 40)->nullable();
            $table->date('fecha_final')->nullable();
            $table->string('hora_final', 40)->nullable();
            $table->decimal('tiempo', 15, 2)->default(0);
            $table->foreignId('id_nave')->nullable()->constrained('naves');
            $table->foreignId('id_valla')->nullable()->constrained('vallas');
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->timestamps();

            $table->index('id_orden_taller');
        });

        // Piezas/recursos de almacén por OT (tec_otgasto)
        Schema::dropIfExists('gastos_orden');
        Schema::create('gastos_orden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller');
            $table->decimal('importe_me', 10, 2)->default(0);
            $table->string('vale', 10)->nullable();
            $table->foreignId('id_tipo_agregado')->nullable()->constrained('tipos_agregados');
            $table->string('nombre', 255)->nullable();
            $table->decimal('cantidad', 8, 2)->default(0);
            $table->string('codigo_pieza', 10)->nullable();
            $table->string('motivo', 255)->nullable();
            $table->foreignId('id_motor')->nullable()->constrained('motores');
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->timestamps();

            $table->index('id_orden_taller');
        });

        // Movimientos en taller (nave/valla + tiempos)
        Schema::dropIfExists('movimientos_taller');
        Schema::create('movimientos_taller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller');
            $table->foreignId('id_nave')->nullable()->constrained('naves');
            $table->foreignId('id_valla')->nullable()->constrained('vallas');
            $table->date('fecha_inicio')->nullable();
            $table->string('hora_inicio', 10)->nullable();
            $table->date('fecha_final')->nullable();
            $table->string('hora_final', 10)->nullable();
            $table->decimal('tiempo', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->timestamps();

            $table->index('id_orden_taller');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('movimientos_taller');
        Schema::dropIfExists('gastos_orden');
        Schema::dropIfExists('ordenes_operaciones');
        Schema::enableForeignKeyConstraints();
    }
};
