<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea `neumaticos_movimientos` con el legacy `tec_neumaticosmov` para el ETL:
 * - `fecha_montaje` pasa a nullable (algún movimiento legacy tiene fecha 0000-00-00).
 * - `posicion` era varchar(50) → se mantiene varchar (guarda el nombre/valor legacy).
 * - Se añade `id_entidad` (scoping por entidad, patrón del proyecto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('neumaticos_movimientos', function (Blueprint $table) {
            $table->date('fecha_montaje')->nullable()->change();
            $table->date('fecha_retiro')->nullable()->change();
            $table->foreignId('id_entidad')->nullable()->after('id_destino')->constrained('entidades');
            $table->index('id_neumatico');
        });
    }

    public function down(): void
    {
        Schema::table('neumaticos_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_entidad');
        });
    }
};
