<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rediseña `turnos` para replicar el legacy `rh_turnos` (turnos
     * transaccionales de nómina), en lugar del catálogo de turnos de trabajo
     * que se había creado. Decisión del usuario (2026-08-18): se necesita como
     * el legacy.
     *
     * Estructura resultante: id, inicio, final, idmovimientos, tiempo,
     * noct1, noct2, doblaje. `idmovimientos` queda como referencia sin FK
     * porque la tabla `movimientos` aún no se migra (pendiente).
     */
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropUnique('turnos_codigo_unique');
            $table->dropColumn([
                'codigo',
                'nombre',
                'hora_entrada',
                'hora_salida',
                'dias_descanso',
                'activo',
                'deleted_at',
            ]);
        });

        // Vaciar posibles filas del catálogo previo (en la BD real está vacía).
        DB::table('turnos')->delete();

        Schema::table('turnos', function (Blueprint $table) {
            $table->date('inicio')->nullable();
            $table->date('final')->nullable();
            $table->unsignedBigInteger('idmovimientos')->nullable();
            $table->decimal('tiempo', 6, 2)->default(0);
            $table->decimal('noct1', 6, 2)->default(0);
            $table->decimal('noct2', 6, 2)->default(0);
            $table->decimal('doblaje', 6, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['inicio', 'final', 'idmovimientos', 'tiempo', 'noct1', 'noct2', 'doblaje']);
        });

        Schema::table('turnos', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre');
            $table->time('hora_entrada');
            $table->time('hora_salida');
            $table->integer('dias_descanso')->nullable();
            $table->boolean('activo')->default(true);
            $table->softDeletes();
        });
    }
};
