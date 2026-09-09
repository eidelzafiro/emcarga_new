<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pobla los campos de nómina de `cargos` desde el legacy `rh_cargos`.
 *
 * El ETL de cargos (config/etl.php) históricamente solo migró nombre, codigo e
 * id_entidad, dejando tarifa/cla y demás en NULL. Estos campos son críticos
 * para el cálculo de salarios (escala, CLA, nocturnidad) en los reportes de
 * nómina (Modelo 1, prenómina, salario choferes).
 *
 * Idempotente: actualiza por codigo (= idcargos legacy).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('legacy')->table('rh_cargos')
            ->orderBy('idcargos')
            ->chunk(1000, function ($filas) {
                foreach ($filas as $fila) {
                    DB::table('cargos')
                        ->where('codigo', (string) $fila->idcargos)
                        ->update([
                            'tarifa' => (float) $fila->tarifa,
                            'cla' => (float) $fila->cla,
                            'pago_adicional' => (float) $fila->padicional,
                            'noct1' => (float) $fila->noct1,
                            'noct2' => (float) $fila->noct2,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No se revierte — los datos correctos son los del legacy.
    }
};
