<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolida `tipos_operaciones` en el catálogo unificado:
 *  1. Remapea ordenes_operaciones.id_tipo_operacion (id de la tabla propia)
 *     al id de catalogo_items (tipo='tipos_operaciones', origen_id = id propio).
 *  2. Deduplica catalogo_items.tipos_operaciones por nombre, reasignando refs.
 *  3. Elimina la tabla propia `tipos_operaciones`.
 *  4. Añade FK de ordenes_operaciones.id_tipo_operacion a catalogo_items.
 *
 * Idempotente: si la tabla propia ya no existe, no hace nada en los pasos 1-3.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tablaPropiaExiste = Schema::hasTable('tipos_operaciones');

        if ($tablaPropiaExiste) {
            // 1. Remapear referencias de negocio al catálogo.
            DB::statement("
                UPDATE ordenes_operaciones oo
                JOIN catalogo_items ci
                  ON ci.tipo = 'tipos_operaciones' AND ci.origen_id = oo.id_tipo_operacion
                SET oo.id_tipo_operacion = ci.id
                WHERE oo.id_tipo_operacion IS NOT NULL
            ");

            // 2. Deduplicar el catálogo de operaciones.
            $this->deduplicarOperaciones();

            // 3. Eliminar la tabla propia.
            DB::statement('DROP TABLE IF EXISTS tipos_operaciones');
        }

        // 4. Blindar la relación con FK.
        $this->agregarFk();
    }

    public function down(): void
    {
        // Consolidación destructiva; no se revierte.
    }

    private function deduplicarOperaciones(): void
    {
        $fks = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('REFERENCED_TABLE_NAME', 'catalogo_items')
            ->get(['TABLE_NAME', 'COLUMN_NAME']);

        $nombres = DB::table('catalogo_items')
            ->select(DB::raw('TRIM(nombre) as nombre'))
            ->where('tipo', 'tipos_operaciones')
            ->whereNull('deleted_at')
            ->groupBy(DB::raw('TRIM(nombre)'))
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nombre');

        foreach ($nombres as $nombre) {
            $ids = DB::table('catalogo_items')
                ->where('tipo', 'tipos_operaciones')
                ->whereRaw('TRIM(nombre) = ?', [$nombre])
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

    private function agregarFk(): void
    {
        $nombre = 'fk_ordenes_operaciones_id_tipo_operacion_catalogo';

        $existe = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'ordenes_operaciones')
            ->where('CONSTRAINT_NAME', $nombre)
            ->exists();

        if ($existe) {
            return;
        }

        DB::statement(
            "ALTER TABLE ordenes_operaciones ADD CONSTRAINT {$nombre}
             FOREIGN KEY (id_tipo_operacion) REFERENCES catalogo_items(id) ON DELETE SET NULL"
        );
    }
};
