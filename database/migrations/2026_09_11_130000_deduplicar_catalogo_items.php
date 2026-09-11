<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deduplica catalogo_items por (tipo, nombre): conserva la fila de menor id,
 * reasigna las referencias de negocio (todas las columnas con FK a
 * catalogo_items) y elimina las filas sobrantes.
 *
 * Excluye tipos con duplicación legítima:
 *  - tipos_modelo / tipos_tasas: una fila por entidad / por rango de tonelaje.
 *  - tipos_operaciones: se consolida en su propia migración junto con la
 *    eliminación de la tabla propia.
 *
 * Idempotente: al no quedar duplicados, una segunda corrida no hace nada.
 */
return new class extends Migration
{
    private array $excluidos = ['tipos_modelo', 'tipos_tasas', 'tipos_operaciones'];

    public function up(): void
    {
        $fks = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('REFERENCED_TABLE_NAME', 'catalogo_items')
            ->get(['TABLE_NAME', 'COLUMN_NAME']);

        $grupos = DB::table('catalogo_items')
            ->select('tipo', DB::raw('TRIM(nombre) as nombre'))
            ->whereNull('deleted_at')
            ->whereNotIn('tipo', $this->excluidos)
            ->groupBy('tipo', DB::raw('TRIM(nombre)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($grupos as $grupo) {
            $ids = DB::table('catalogo_items')
                ->where('tipo', $grupo->tipo)
                ->whereRaw('TRIM(nombre) = ?', [$grupo->nombre])
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $superviviente = array_shift($ids);

            foreach ($ids as $sobrante) {
                foreach ($fks as $fk) {
                    DB::table($fk->TABLE_NAME)
                        ->where($fk->COLUMN_NAME, $sobrante)
                        ->update([$fk->COLUMN_NAME => $superviviente]);
                }

                DB::table('catalogo_items')->where('id', $sobrante)->delete();
            }
        }
    }

    public function down(): void
    {
        // La deduplicación es destructiva; no se revierte.
    }
};
