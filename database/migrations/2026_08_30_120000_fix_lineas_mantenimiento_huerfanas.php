<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reparación de datos y FKs (2026-08-30): las líneas de mantenimiento quedaron
 * huérfanas apuntando a ids 351-356 (catalogo_items, producto de la unificación
 * de la FASE 3), mientras que el resto del código (OrdenTallerService, el modelo
 * TiposMantenimiento y tipo_vehiculos.id_tipo_mantenimiento) usa la tabla propia
 * tipos_mantenimiento con ids legacy (1,3,6,7,8,9).
 *
 * La FASE 3 unificó erróneamente "tipos_mantenimiento" en catalogo_items para
 * las tablas hijas, dejando FKs contra catalogo_items que contradicen el resto
 * del sistema. Esta migración:
 *   1. Remapea lineas_mantenimiento 351-356 → 1,3,6,7,8,9.
 *   2. Corrige las FKs de lineas_mantenimiento / ordenes_taller /
 *      planes_mantenimiento para que referencien tipos_mantenimiento(id).
 *
 * El remapeo se infiere por origen_id (catalogo_items.origen_id == legacy id):
 *   351→1 (S/DEFINIR), 352→3, 353→6, 354→7, 355→8, 356→9.
 *
 * Idempotente.
 */
return new class extends Migration
{
    private const REMAP = [
        351 => 1,
        352 => 3,
        353 => 6,
        354 => 7,
        355 => 8,
        356 => 9,
    ];

    public function up(): void
    {
        foreach (self::REMAP as $origen => $destino) {
            $hayHuerfanas = DB::table('lineas_mantenimiento')
                ->where('id_tipo_mantenimiento', $origen)
                ->exists();
            $destinoExiste = DB::table('tipos_mantenimiento')->where('id', $destino)->exists();

            if (! $hayHuerfanas || ! $destinoExiste) {
                continue;
            }

            // Evita colisiones: si el destino ya tiene una línea en el mismo
            // kilometraje, se borra para que prevalezca la legacy.
            // (MariaDB no permite subquery sobre la misma tabla del DELETE,
            // por eso se materializan los kilometrajes primero.)
            $kms = DB::table('lineas_mantenimiento')
                ->where('id_tipo_mantenimiento', $origen)
                ->pluck('kilometraje');

            if ($kms->isNotEmpty()) {
                DB::table('lineas_mantenimiento')
                    ->where('id_tipo_mantenimiento', $destino)
                    ->whereIn('kilometraje', $kms->all())
                    ->delete();
            }

            DB::table('lineas_mantenimiento')
                ->where('id_tipo_mantenimiento', $origen)
                ->update(['id_tipo_mantenimiento' => $destino, 'updated_at' => now()]);
        }

        $this->reapuntarFk('lineas_mantenimiento', 'id_tipo_mantenimiento');
        $this->reapuntarFk('ordenes_taller', 'id_tipo_mantenimiento');
        $this->reapuntarFk('planes_mantenimiento', 'id_tipo_mantenimiento');
    }

    /**
     * Suelta cualquier FK sobre la columna y la recrea contra tipos_mantenimiento.
     */
    private function reapuntarFk(string $tabla, string $columna): void
    {
        $fks = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tabla)
            ->where('COLUMN_NAME', $columna)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($fks as $nombre) {
            try {
                DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY {$nombre}");
            } catch (\Throwable) {
                // ya no existe
            }
        }

        DB::statement(
            "ALTER TABLE {$tabla} ADD CONSTRAINT fk_{$tabla}_{$columna}_mtto
             FOREIGN KEY ({$columna}) REFERENCES tipos_mantenimiento(id)"
        );
    }

    public function down(): void
    {
        foreach (self::REMAP as $origen => $destino) {
            DB::table('lineas_mantenimiento')
                ->where('id_tipo_mantenimiento', $destino)
                ->update(['id_tipo_mantenimiento' => $origen, 'updated_at' => now()]);
        }

        foreach (['lineas_mantenimiento', 'ordenes_taller', 'planes_mantenimiento'] as $tabla) {
            try {
                DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY fk_{$tabla}_id_tipo_mantenimiento_mtto");
            } catch (\Throwable) {
                //
            }
            DB::statement(
                "ALTER TABLE {$tabla} ADD CONSTRAINT fk_{$tabla}_id_tipo_mantenimiento_catalogo
                 FOREIGN KEY (id_tipo_mantenimiento) REFERENCES catalogo_items(id)"
            );
        }
    }
};
