<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía `baterias` y `baterias_movimientos` con paridad del legacy CI3:
 *
 *   tec_baterias    → baterias            (voltaje, amperaje, precios MN/MLC)
 *   tec_bateriasmov → baterias_movimientos (id_entidad, id_destino)
 *
 * La tabla `baterias` ya tiene 91 filas (ETL previo) y `baterias_movimientos`
 * 295, por lo que se añaden columnas con ALTER (no se dropean).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baterias', function (Blueprint $table) {
            $table->integer('voltaje')->nullable()->after('modelo');
            $table->integer('amperaje')->nullable()->after('voltaje');
            $table->decimal('precio_mn', 10, 2)->nullable()->after('amperaje');
            $table->decimal('precio_me', 10, 2)->nullable()->after('precio_mn');
            $table->foreignId('id_motivo_baja')->nullable()->after('precio_me')->constrained('motivos_baja_bateria');
            $table->foreignId('id_destino')->nullable()->after('id_motivo_baja')->constrained('destinos_agregados');
            $table->date('fecha_movimiento')->nullable()->after('fecha_instalacion');
        });

        Schema::table('baterias_movimientos', function (Blueprint $table) {
            $table->foreignId('id_entidad')->nullable()->after('id_destino')->constrained('entidades');
            $table->index('id_bateria');
        });
    }

    public function down(): void
    {
        Schema::table('baterias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_destino');
            $table->dropConstrainedForeignId('id_motivo_baja');
            $table->dropColumn('fecha_movimiento');
            $table->dropColumn('precio_me');
            $table->dropColumn('precio_mn');
            $table->dropColumn('amperaje');
            $table->dropColumn('voltaje');
        });
        Schema::table('baterias_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_entidad');
        });
    }
};
