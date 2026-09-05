<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-añade id_chofer / id_chofer2 directamente en cartas_porte.
 *
 * Motivo: el cálculo de salario del chofer necesita determinar si la carta
 * tiene doble chofer para dividir el ingreso y usar tasa2. Antes se derivaba
 * de hojas_ruta, pero el usuario decidió que los choferes deben vivir en la
 * carta de porte porque una misma HR puede generar múltiples CP con diferentes
 * combinaciones de choferes.
 *
 * Se poblaron desde hojas_ruta vía ETL (comando zafiro:etl o actualización
 * manual). Las FKs apuntan a bolsa.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->unsignedBigInteger('id_chofer')->nullable()->after('id_solicitud');
            $table->unsignedBigInteger('id_chofer2')->nullable()->after('id_chofer');

            $table->index('id_chofer', 'cartas_porte_id_chofer_index');
        });

        // NOTA: el backfill se hace en el ETL (EtlService::migrarCartasPorte)
        // leyendo com_girado.idchofer / idchofer2, NO desde hojas_ruta.
        // Una HR puede tener chofer2 pero la CP puede no tenerlo (legacy idchofer2=0).
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->dropIndex('cartas_porte_id_chofer_index');
            $table->dropColumn(['id_chofer', 'id_chofer2']);
        });
    }
};
