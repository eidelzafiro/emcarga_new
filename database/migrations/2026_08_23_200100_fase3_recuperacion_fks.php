<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RECUPERACIÓN de la fase 3 (2026-08-23).
 *
 * Síntoma: al eliminar las tablas padres con FOREIGN_KEY_CHECKS=0, MariaDB
 * conservó en las tablas hijas FKs "fantasma" que ya NO aparecen en
 * information_schema pero SÍ se aplican al escribir (error 1452 citando la
 * tabla padre eliminada). Eso bloqueó el remapeo de varias columnas.
 *
 * Esta migración recorre el inventario estático completo de pares
 * (tabla_hija, columna, catálogo_origen) y por cada uno:
 *   1. Suelta cualquier FK residual que no sea fk_*_catalogo (try/catch,
 *      tolera las que ya no existen).
 *   2. Remapea valor legacy (origen_id) -> catalogo_items.id.
 *   3. Anula huérfanos reales (patrón dual id/origen_id).
 *   4. Crea la FK nueva contra catalogo_items(id) si falta.
 */
return new class extends Migration
{
    /** [tabla_hija, columna, tipo_catálogo] — inventario completo pre-fase3 */
    private const PARES = [
        ['gastos_orden', 'id_tipo_agregado', 'tipos_agregados'],
        ['lineas_otro_agregado', 'id_tipo_agregado', 'tipos_agregados'],
        ['detalle_prefacturas', 'id_tipo_carga', 'tipos_cargas'],
        ['giros', 'id_tipo_carga', 'tipos_cargas'],
        ['solicitudes_servicio', 'id_tipo_carga2', 'tipos_cargas'],
        ['solicitudes_servicio', 'id_tipo_carga', 'tipos_cargas'],
        ['tarifas', 'id_tipo_carga', 'tipos_cargas'],
        ['tasas', 'id_tipo_carga', 'tipos_cargas'],
        ['combustibles_lubricantes', 'id_causa', 'tipos_causas'],
        ['consumo_lubricantes', 'id_causa', 'tipos_causas'],
        ['neumaticos_roturas', 'id_tipo_causa', 'tipos_causas'],
        ['cargos', 'id_clasificacion_laboral', 'tipos_clasificacion_laboral'],
        ['salarios', 'id_color_piel', 'tipos_color_piel'],
        ['cierre_tarjetas', 'id_tipo_combustibles', 'tipos_combustibles'],
        ['combustible_cargas', 'id_tipo_combustibles', 'tipos_combustibles'],
        ['htarjetas', 'id_tipo_combustibles', 'tipos_combustibles'],
        ['tipos_tractivos', 'id_tipo_combustible', 'tipos_combustibles'],
        ['otros_gastos', 'id_tipo_concepto', 'tipos_conceptos'],
        ['tipos_incidencias', 'id_tipo_deducciones', 'tipos_deducciones'],
        ['tipos_arrastres', 'id_tipo_equipo', 'tipos_equipos'],
        ['tipos_gastos_x', 'id_tipo_gasto', 'tipos_gastos'], // placeholder nunca existe
        ['cargos', 'id_grupo_horario', 'tipos_grupo_horario'],
        ['incidencias', 'id_tipo_incidencia', 'tipos_incidencias'],
        ['indicadores_planes', 'id_tipo_indicador', 'tipos_indicadores'],
        ['salarios', 'id_integracion_politica', 'tipos_integracion_politica'],
        ['consumo_lubricantes', 'id_tipo_aceite', 'tipos_lubricantes'],
        ['combustibles_lubricantes', 'id_tipo_lubricante', 'tipos_lubricantes'],
        ['lineas_diferencial', 'id_lubricante', 'tipos_lubricantes'],
        ['lineas_lubricante', 'id_tipo_lubricante', 'tipos_lubricantes'],
        ['otros_agregados', 'id_lubricante', 'tipos_lubricantes'],
        ['tipos_tractivos', 'id_lubricante_cubo', 'tipos_lubricantes'],
        ['tipos_tractivos', 'id_lubricante_motor', 'tipos_lubricantes'],
        ['lineas_mantenimiento', 'id_tipo_mantenimiento', 'tipos_mantenimiento'],
        ['planes_mantenimiento', 'id_tipo_mantenimiento', 'tipos_mantenimiento'],
        ['ordenes_taller', 'id_tipo_mantenimiento', 'tipos_mantenimiento'],
        ['lineas_neumatico', 'id_tipo_neumatico', 'tipos_neumaticos'],
        ['cargos', 'id_nivel_educacion', 'tipos_nivel_educacion'],
        ['salarios', 'id_nivel_educacion', 'tipos_nivel_educacion'],
        ['ordenes_operaciones', 'id_tipo_operacion', 'tipos_operaciones'],
        ['pagos_adicionales_cargo', 'id_tipo_pago_adicional', 'tipos_pagos_adicionales'],
        ['tipos_penalizaciones', 'tipo_pago_adicional_id', 'tipos_pagos_adicionales'],
        ['penalizaciones', 'id_tipo_penalizacion', 'tipos_penalizaciones'],
        ['sub_tipos_roturas', 'id_tipo_rotura', 'tipos_roturas'],
        ['tractivos', 'id_tipo_servicio', 'tipos_servicios'],
        ['salarios', 'id_sexo', 'tipos_sexo'],
        ['salarios', 'id_tipo_sistema_pago', 'tipos_sistemas_pago'],
        ['tipos_arrastres', 'id_tipo_suspension', 'tipos_suspension'],
    ];

    public function up(): void
    {
        foreach (self::PARES as [$tabla, $columna, $tipo]) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            // 1. Soltar FKs residuales (fantasmas incluidos)
            foreach ($this->fksResiduales($tabla, $columna) as $nombre) {
                try {
                    DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY {$nombre}");
                } catch (\Throwable) {
                    // ya no existe
                }
            }

            // 2. Remapear legacy -> catalogo_items.id
            DB::statement("
                UPDATE {$tabla} t
                JOIN catalogo_items ci ON ci.tipo = ? AND ci.origen_id = t.{$columna}
                SET t.{$columna} = ci.id
            ", [$tipo]);

            // 3. Huérfanos reales -> NULL
            DB::statement("
                UPDATE {$tabla}
                SET {$columna} = NULL
                WHERE {$columna} IS NOT NULL
                  AND {$columna} NOT IN (SELECT id FROM catalogo_items WHERE tipo = ?)
                  AND {$columna} NOT IN (SELECT origen_id FROM catalogo_items WHERE tipo = ?)
            ", [$tipo, $tipo]);

            // 4. FK nueva si falta
            $nombreNuevo = "fk_{$tabla}_{$columna}_catalogo";
            if (! $this->existeFk($tabla, $nombreNuevo)) {
                DB::statement(
                    "ALTER TABLE {$tabla} ADD CONSTRAINT {$nombreNuevo}
                     FOREIGN KEY ({$columna}) REFERENCES catalogo_items(id)"
                );
            }
        }
    }

    /**
     * Nombres de FKs sobre la columna indicada que NO sean las nuevas del
     * patrón fk_*_catalogo. Combina TABLE_CONSTRAINTS + SHOW CREATE como
     * red de seguridad para fantasmas invisibles en information_schema.
     *
     * @return string[]
     */
    private function fksResiduales(string $tabla, string $columna): array
    {
        $nombres = [];
        foreach (DB::select("
            SELECT tc.CONSTRAINT_NAME nombre
            FROM information_schema.TABLE_CONSTRAINTS tc
            JOIN information_schema.KEY_COLUMN_USAGE kcu
              ON kcu.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
             AND kcu.TABLE_NAME = tc.TABLE_NAME
             AND kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = DATABASE()
              AND tc.TABLE_NAME = ?
              AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND kcu.COLUMN_NAME = ?
        ", [$tabla, $columna]) as $fila) {
            $nombres[$fila->nombre] = true;
        }

        // Red de seguridad: parsear el CREATE TABLE atrapa fantasmas que
        // information_schema omite tras borrar el padre con FK_CHECKS=0.
        try {
            $create = DB::selectOne("SHOW CREATE TABLE {$tabla}")->{'Create Table'} ?? '';
            if (preg_match_all(
                '/CONSTRAINT\s+`([^`]+)`\s+FOREIGN\s+KEY\s*\(`([^`]+)`\)/i',
                $create, $m, PREG_SET_ORDER
            )) {
                foreach ($m as [$_, $nombre, $col]) {
                    if ($col === $columna && ! str_ends_with($nombre, '_catalogo')) {
                        $nombres[$nombre] = true;
                    }
                }
            }
        } catch (\Throwable) {
            //
        }

        return array_keys($nombres);
    }

    private function existeFk(string $tabla, string $nombre): bool
    {
        return (bool) DB::selectOne(
            "SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$tabla, $nombre]
        )->c;
    }

    public function down(): void
    {
        //
    }
};
