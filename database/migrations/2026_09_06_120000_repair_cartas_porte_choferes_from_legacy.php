<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-puebla cartas_porte.id_chofer / id_chofer2 desde el legacy com_girado.
 *
 * Al añadir id_chofer/id_chofer2 en 2026_09_05_200000, el backfill del ETL solo
 * cubrió las CP migradas en corridas posteriores; las CP ya existentes quedaron
 * con id_chofer2=NULL aunque el legacy tuviera un chofer2 válido (doble chofer).
 * Esto rompía la división de ingresos por doble chofer en la prenómina.
 *
 * Idempotente: re-puebla ambos campos (id_chofer y id_chofer2) desde com_girado.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('legacy')->table('com_girado')
            ->orderBy('idcartaporte')
            ->chunk(1000, function ($filas) {
                foreach ($filas as $fila) {
                    $idChofer = (int) $fila->idchofer;
                    $idChofer2 = (int) $fila->idchofer2;

                    DB::table('cartas_porte')
                        ->where('id', (int) $fila->idcartaporte)
                        ->update([
                            'id_chofer' => $idChofer > 0 ? $idChofer : null,
                            'id_chofer2' => $idChofer2 > 0 ? $idChofer2 : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No se revierte — los datos correctos son los del legacy.
    }
};
