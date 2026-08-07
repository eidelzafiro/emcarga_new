<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tarjetas de combustible (legacy cont_tarjetas): agrega los campos
 * originales y hace id_cliente nullable (legacy no relaciona la tarjeta
 * con clientes sino con empleados/tractivos). Decisión del usuario 2026-07-31.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarjetas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cliente')->nullable()->change();

            $table->date('fcompra')->nullable()->after('saldo_actual');
            $table->date('fvence')->nullable()->after('fcompra');
            $table->decimal('saldoinicialmon', 10, 2)->nullable()->after('fvence');
            $table->decimal('saldoiniciallts', 10, 2)->nullable()->after('saldoinicialmon');
            $table->decimal('saldoactuallts', 10, 2)->nullable()->after('saldoiniciallts');
            $table->decimal('saldotransferenciamon', 10, 2)->nullable()->after('saldoactuallts');
            $table->decimal('saldotransferencialts', 10, 2)->nullable()->after('saldotransferenciamon');
            $table->unsignedBigInteger('idmonedas')->nullable()->after('saldotransferencialts');
            $table->unsignedBigInteger('idtipocombustibles')->nullable()->after('idmonedas');
            $table->unsignedBigInteger('idempleado')->nullable()->after('idtipocombustibles');
            $table->unsignedBigInteger('idtractivos')->nullable()->after('idempleado');
            $table->unsignedBigInteger('idchofer')->nullable()->after('idtractivos');
            $table->integer('cancelado')->nullable()->after('idchofer');
            $table->integer('inactiva')->nullable()->after('cancelado');
            $table->date('fmovimiento')->nullable()->after('inactiva');
            $table->date('fcancelado')->nullable()->after('fmovimiento');
            $table->date('fcierre')->nullable()->after('fcancelado');
            $table->unsignedBigInteger('id_entidad')->nullable()->after('fcierre');
        });
    }

    public function down(): void
    {
        Schema::table('tarjetas', function (Blueprint $table) {
            $table->dropColumn([
                'fcompra', 'fvence', 'saldoinicialmon', 'saldoiniciallts',
                'saldoactuallts', 'saldotransferenciamon', 'saldotransferencialts',
                'idmonedas', 'idtipocombustibles', 'idempleado', 'idtractivos',
                'idchofer', 'cancelado', 'inactiva', 'fmovimiento', 'fcancelado',
                'fcierre', 'id_entidad',
            ]);
        });
    }
};
