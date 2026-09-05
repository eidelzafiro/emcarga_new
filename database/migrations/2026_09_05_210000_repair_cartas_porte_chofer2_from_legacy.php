<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repara id_chofer2 en cartas_porte usando los valores reales del legacy.
 *
 * El backfill anterior en 2026_09_05_200000 copiaba id_chofer2 desde
 * hojas_ruta, pero el legacy almacena el chofer de la CP en com_girado.idchofer2.
 * Una HR puede tener chofer2=1028 pero la CP puede tener idchofer2=0.
 *
 * Resultado: ~1391 CPs tienen id_chofer2 incorrecto (con segundo chofer cuando
 * no lo tienen en legacy). Esto causaba que el salario se dividiera incorrectamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Para cada CP que tiene id_chofer2, verificar si el legacy tiene idchofer2=0
        // y si es así, poner null (sin segundo chofer).
        DB::connection('legacy')->table('com_girado')
            ->whereYear('femision', '>=', 2024)
            ->orderBy('idcartaporte')
            ->chunk(1000, function ($filas) {
                $updates = [];
                foreach ($filas as $fila) {
                    $legacyChofer2 = (int) $fila->idchofer2;
                    if ($legacyChofer2 === 0) {
                        $updates[] = [
                            'id' => $fila->idcartaporte,
                            'id_chofer2_legacy' => null,
                        ];
                    }
                }
                if (!empty($updates)) {
                    foreach ($updates as $u) {
                        DB::table('cartas_porte')
                            ->where('id', $u['id'])
                            ->update(['id_chofer2' => $u['id_chofer2_legacy']]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No se revierte — los datos correctos son los del legacy.
    }
};
