<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * P2.2 del plan general: valida la integridad referencial post-ETL.
 *
 * Enumera todas las FKs de la BD de trabajo (emcarga_new) y, para cada una,
 * cuenta las filas huérfanas (referencian un registro inexistente). Reporta
 * un resumen y, con --detalle, los valores huerfanos encontrados.
 *
 * No modifica datos: es diagnóstico. Las referencias rotas reales requieren
 * decisión de negocio (re-mapeo o NULL) salvo el caso id=0 legacy (benigno).
 */
class VerificarIntegridad extends Command
{
    protected $signature = 'zafiro:verificar-integridad {--detalle : Mostrar los valores huérfanos por FK}';

    protected $description = 'Valida la integridad referencial (FOREIGN KEYs) de la BD y reporta filas huérfanas.';

    public function handle(): int
    {
        $db = config('database.connections.mysql.database');

        $fks = DB::select("
            SELECT kcu.TABLE_NAME AS tabla, kcu.COLUMN_NAME AS col,
                   kcu.REFERENCED_TABLE_NAME AS ref_tabla, kcu.REFERENCED_COLUMN_NAME AS ref_col
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.TABLE_CONSTRAINTS tc
              ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ? AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
            ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
        ", [$db]);

        $this->info("BD: {$db} — FKs encontradas: ".count($fks));

        $problemas = 0;
        $revisadas = 0;

        foreach ($fks as $fk) {
            $tabla = $fk->tabla;
            $col = $fk->col;
            $rt = $fk->ref_tabla;
            $rc = $fk->ref_col;

            try {
                $n = DB::table("{$tabla} as c")
                    ->leftJoin("{$rt} as p", "c.{$col}", '=', "p.{$rc}")
                    ->whereNotNull("c.{$col}")
                    ->whereNull("p.{$rc}")
                    ->count();
            } catch (\Throwable $e) {
                continue;
            }

            $revisadas++;
            if ($n > 0) {
                $problemas++;
                $this->warn("  ORFANOS: {$tabla}.{$col} -> {$rt}.{$rc} = {$n} fila(s)");

                if ($this->option('detalle')) {
                    $vals = DB::table($tabla)
                        ->whereNotNull($col)
                        ->whereNotIn($col, fn ($q) => $q->select($rc)->from($rt))
                        ->select($col, DB::raw('COUNT(*) as n'))
                        ->groupBy($col)
                        ->orderBy($col)
                        ->get();
                    foreach ($vals as $v) {
                        $this->line("      valor {$v->$col} => {$v->n} fila(s)");
                    }
                }
            }
        }

        $this->newLine();
        $this->info("FKs revisadas: {$revisadas} | con hijos huérfanos: {$problemas}");

        return $problemas === 0 ? self::SUCCESS : self::FAILURE;
    }
}
