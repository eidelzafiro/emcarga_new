<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mover todo lo de "arrastres" a la tabla `tractivos` y eliminar la tabla
 * física `arrastres` (capa legacy que ya no es fuente de datos).
 *
 * Contexto decidido con usuario 2026-08-07:
 * - El legacy NO tiene tabla de arrastres, solo tractivos. Un tractivo es
 *   ARRASTRE si id_grupo=8 (grupo ARRASTRES).
 * - La tabla `arrastres` contenía datos repetidos de esos tractivos (grupo 8)
 *   más 9 arrastres de baja que el ETL de tractivos omitía (fbaja != null).
 * - Regla: incorporar esos 9 de baja a `tractivos` (grupo 8, estado
 *   "propuesta_baja") y re-apuntar las FKs (`arrastre_tractivo`,
 *   `hojas_ruta`) que referenciaban `arrastres(id)` → `tractivos(id)`.
 *
 * Los 9 ids huérfanos (716,746,1242,1243,1247,1250,1251,1281,1296) no son
 * referenciados por ninguna FK. Todos los id_arrastre referenciados existían
 * ya en `tractivos`, así que el re-apuntado no rompe integridad.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->incorporarArrastresDeBaja();
        $this->resolverFk('arrastre_tractivo', 'id_arrastre', 'arrastre_tractivo_id_arrastre_foreign');
        $this->resolverFk('hojas_ruta', 'id_arrastre', 'hojas_ruta_id_arrastre_foreign');
        $this->eliminarTabla();
    }

    public function down(): void
    {
        // No se restaura la tabla ni las FK viejas por diseño: `arrastres`
        // era una capa intermedia innecesaria. Restaurar requiere el backup.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('arrastre_tractivo')->delete();
        DB::table('hojas_ruta')->update(['id_arrastre' => null]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Inserta en `tractivos` (grupo 8) los arrastres de baja que hoy solo
     * quedan en `arrastres`. Idempotente por id.
     */
    private function incorporarArrastresDeBaja(): void
    {
        $ids = [716, 746, 1242, 1243, 1247, 1250, 1251, 1281, 1296];

        $legacy = DB::connection('legacy');
        $filas = $legacy->table('tec_tractivos')->whereIn('idtractivos', $ids)->get();

        // Ficha del tipo arrastre (marca/modelo desde marcas/modelos nuevos).
        $tipos = DB::table('tipos_arrastres')->get(['id', 'id_marca', 'id_modelo', 'fabricacion']);
        $marcas = DB::table('marcas')->get()->keyBy('id');
        $modelos = DB::table('modelos')->get()->keyBy('id');

        foreach ($filas as $fila) {
            $id = (int) $fila->idtractivos;
            if (DB::table('tractivos')->where('id', $id)->exists()) {
                continue;
            }

            $tipo = $tipos->firstWhere('id', (int) $fila->idtipotractivos);
            $marca = $tipo && $tipo->id_marca ? $marcas->get($tipo->id_marca)?->nombre : null;
            $modelo = $tipo && $tipo->id_modelo ? $modelos->get($tipo->id_modelo)?->nombre : null;

            $codigo = trim((string) ($fila->codtractivo ?? '')) ?: null;
            $placa = trim((string) ($fila->chapa ?? '')) ?: null;

            DB::table('tractivos')->updateOrInsert(
                ['id' => $id],
                [
                    'id_entidad' => (int) $fila->idunidad ?: null,
                    'id_grupo' => 8,
                    'codigo' => $codigo,
                    'descripcion' => $codigo,
                    'placa' => $placa,
                    'id_tipo_vehiculo' => $tipo ? (int) $fila->idtipotractivos : null,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'anno' => $tipo && preg_match('/^\d{4}$/', (string) $tipo->fabricacion) ? (int) $tipo->fabricacion : null,
                    'capacidad_toneladas' => $fila->capacidad,
                    'lot' => trim((string) ($fila->lot ?? '')) ?: null,
                    'circulacion' => trim((string) ($fila->circulacion ?? '')) ?: null,
                    'estado' => 'propuesta_baja',
                    'fecha_alta' => $this->fecha($fila->falta),
                    'fecha_baja' => $this->fecha($fila->fbaja),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Si la tabla tiene la FK apuntando a `arrastres`, la elimina y la
     * recrea apuntando a `tractivos`. Tolerante a que ya apunte a `tractivos`.
     */
    private function resolverFk(string $tabla, string $columna, string $fkNombre): void
    {
        $fk = DB::selectOne(
            'SELECT T2.REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE T2
             WHERE T2.TABLE_SCHEMA = DATABASE() AND T2.TABLE_NAME = ? AND T2.CONSTRAINT_NAME = ?',
            [$tabla, $fkNombre]
        );

        if (! $fk || $fk->REFERENCED_TABLE_NAME === 'tractivos') {
            return;
        }

        DB::statement("ALTER TABLE `{$tabla}` DROP FOREIGN KEY `{$fkNombre}`");
        DB::statement("ALTER TABLE `{$tabla}` ADD CONSTRAINT `{$fkNombre}` FOREIGN KEY (`{$columna}`) REFERENCES `tractivos` (`id`) ON DELETE CASCADE");
    }

    private function eliminarTabla(): void
    {
        if (Schema::hasTable('arrastres')) {
            Schema::dropIfExists('arrastres');
        }
    }

    private function fecha($v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = (string) $v;

        return str_starts_with($s, '0000-00-00') ? null : $s;
    }
};