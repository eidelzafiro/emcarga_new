<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * E-1 (2026-08-22): crea `movimientos_rrhh` con paridad 1:1 al legacy.
     *
     * El legacy tiene DOS tablas con solapamiento de ids (rh_movimientos
     * id 1-1506 y rh_hmovimientos id 1-413, mismo rango 1-413). Se migran
     * AMBAS preservando el id legacy en `id_legacy` y marcando el origen
     * en `origen` ('mov' | 'hmov'). La PK nueva (`id`) es autoincremental
     * y desambigua el solapamiento (índice único compuesto
     * [origen, id_legacy]).
     *
     * Se enlazan las FKs colgantes:
     *  - turnos.idmovimientos (legacy rh_turnos.idmovimientos, origen 'mov')
     *    → turnos.id_movimiento_rrhh
     *  - salarios_administrativos.id_movimiento (legacy rh_saladmin.idmovimientos,
     *    origen 'mov') → salarios_administrativos.id_movimiento_rrhh
     *
     * Las FKs hacia bolsa/tractivos/plantilla/users usan nullOnDelete y se
     * resuelven en el ETL (si el padre no existe en la BD nueva → NULL + aviso),
     * de modo que no queden FKs rotas.
     */
    public function up(): void
    {
        Schema::create('movimientos_rrhh', function (Blueprint $table) {
            $table->id();
            $table->string('origen', 20);
            $table->unsignedBigInteger('id_legacy');
            $table->unsignedBigInteger('id_bolsa')->nullable();
            $table->unsignedBigInteger('nronomina')->nullable();
            $table->unsignedBigInteger('id_tractivo')->nullable();
            $table->unsignedBigInteger('id_plantilla')->nullable();
            $table->date('fbaja')->nullable();
            $table->integer('cubreplaza')->default(0);
            $table->string('tipomov', 100)->nullable();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->timestamps();

            $table->unique(['origen', 'id_legacy']);
            $table->foreign('id_bolsa')->references('id')->on('bolsa')->nullOnDelete();
            $table->foreign('id_tractivo')->references('id')->on('tractivos')->nullOnDelete();
            $table->foreign('id_plantilla')->references('id')->on('plantilla')->nullOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('turnos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_movimiento_rrhh')->nullable()->after('idmovimientos');
            $table->foreign('id_movimiento_rrhh')->references('id')->on('movimientos_rrhh')->nullOnDelete();
        });

        Schema::table('salarios_administrativos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_movimiento_rrhh')->nullable()->after('id_movimiento');
            $table->foreign('id_movimiento_rrhh')->references('id')->on('movimientos_rrhh')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropForeign(['id_movimiento_rrhh']);
            $table->dropColumn('id_movimiento_rrhh');
        });

        Schema::table('salarios_administrativos', function (Blueprint $table) {
            $table->dropForeign(['id_movimiento_rrhh']);
            $table->dropColumn('id_movimiento_rrhh');
        });

        Schema::dropIfExists('movimientos_rrhh');
    }
};
