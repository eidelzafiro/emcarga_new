<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 3 de unificación (2026-08-23, decisión del usuario): las tablas
 * `tipos_*` SIMPLES (solo nombre/código + extras triviales) se eliminan y
 * su gestión queda exclusivamente en `catalogo_items`.
 *
 * Se EXCLUYEN deliberadamente (ficha técnica / parámetros de negocio /
 * leídas en caliente por EtlService u otros servicios):
 *   tipos_tractivos, tipos_cargas_reporte, tipos_arrastres,
 *   tipos_agregados, tipos_combustibles, tipos_mantenimiento,
 *   tipos_incidencias, tipos_penalizaciones, tipos_modelo,
 *   tipos_operaciones
 *
 * Proceso idempotente por tabla:
 *   1. Re-sincroniza hacia catalogo_items (upsert tipo+origen_id; los
 *      extras del JSON viajan en el propio catálogo).
 *   2. Descubre DINÁMICAMENTE las FKs entrantes (information_schema),
 *      las suelta, remapea valor legacy → catalogo_items.id y anula
 *      huérfanos reales (patrón dual id/origen_id).
 *   3. Crea FKs nuevas fk_*_catalogo (si no existen).
 *   4. Menús del módulo redirigidos a catalogo.index?tipo=...
 */
return new class extends Migration
{
    private const TABLAS = [
        'tipos_aceites', 'tipos_causas', 'tipos_clasificacion_laboral',
        'tipos_color_piel', 'tipos_conceptos', 'tipos_contratos',
        'tipos_deducciones', 'tipos_estado_civil', 'tipos_estados',
        'tipos_gastos', 'tipos_grupo_horario', 'tipos_indicadores',
        'tipos_integracion_politica', 'tipos_lubricantes',
        'tipos_nivel_educacion', 'tipos_pagos_adicionales', 'tipos_roturas',
        'tipos_sexo', 'tipos_sistemas', 'tipos_sistemas_pago',
        'tipos_suspension', 'tipos_tasas', 'tipos_ubicacion_defensa',
        'tipos_vehiculos', 'tipos_servicios',
    ];

    public function up(): void
    {
        $base = ['id', 'codigo', 'nombre', 'activo', 'deleted_at', 'created_at', 'updated_at'];

        // ── 1. Sincronizar hacia catalogo_items ────────────────────────
        foreach (self::TABLAS as $tipo) {
            if (! Schema::hasTable($tipo)) {
                continue;
            }

            $extras = array_diff(Schema::getColumnListing($tipo), $base);
            foreach (DB::table($tipo)->get() as $fila) {
                $extra = [];
                foreach ($extras as $col) {
                    if ($fila->{$col} !== null && $fila->{$col} !== '') {
                        $extra[$col] = $fila->{$col};
                    }
                }

                DB::table('catalogo_items')->updateOrInsert(
                    ['tipo' => $tipo, 'origen_id' => $fila->id],
                    [
                        'codigo' => $fila->codigo ?? null,
                        'nombre' => $fila->nombre ?? '',
                        'activo' => (bool) ($fila->activo ?? true),
                        'extra' => $extra === [] ? null : json_encode($extra, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]
                );
            }

            // Registrar el tipo por si faltara (no pisa existentes)
            $titulo = mb_convert_case(str_replace('_', ' ', $tipo), MB_CASE_TITLE, 'UTF-8');
            DB::table('catalogo_tipos')->updateOrInsert(
                ['tipo' => $tipo],
                [
                    'titulo' => DB::table('catalogo_tipos')->where('tipo', $tipo)->value('titulo') ?? $titulo,
                    'agrupacion' => DB::table('catalogo_tipos')->where('tipo', $tipo)->value('agrupacion') ?? 'Técnica',
                    'activo' => true,
                    'tabla_legacy' => $tipo,
                    'fields' => \App\Support\CatalogoSchema::defaultFields($tipo) !== []
                        ? json_encode(\App\Support\CatalogoSchema::defaultFields($tipo), JSON_UNESCAPED_UNICODE)
                        : DB::table('catalogo_tipos')->where('tipo', $tipo)->value('fields'),
                    'updated_at' => now(),
                ]
            );
        }

        // ── 2. Descubrir y soltar FKs entrantes ────────────────────────
        $placeholders = implode(',', array_fill(0, count(self::TABLAS), '?'));
        $pares = DB::select("
            SELECT kcu.TABLE_NAME tabla, kcu.COLUMN_NAME columna,
                   kcu.CONSTRAINT_NAME restriccion, kcu.REFERENCED_TABLE_NAME tipo
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.REFERENCED_TABLE_NAME IN ($placeholders)
              AND kcu.REFERENCED_TABLE_SCHEMA = DATABASE()
        ", self::TABLAS);

        foreach ($pares as $par) {
            Schema::table($par->tabla, fn ($t) => $t->dropForeign($par->restriccion));
        }

        // ── 3. Remapear valores legacy -> catalogo_items.id ────────────
        foreach ($pares as $par) {
            $tabla = $par->tabla;
            $columna = $par->columna;
            $tipo = $par->tipo;

            // anulable (necesario para huérfanos)
            $info = DB::selectOne(
                "SELECT IS_NULLABLE, COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$tabla, $columna]
            );
            if ($info->IS_NULLABLE === 'NO') {
                DB::statement("ALTER TABLE {$tabla} MODIFY {$columna} {$info->COLUMN_TYPE} NULL");
            }

            DB::table($tabla)->where($columna, 0)->update([$columna => null]);

            DB::statement("
                UPDATE {$tabla} t
                JOIN catalogo_items ci ON ci.tipo = ? AND ci.origen_id = t.{$columna}
                SET t.{$columna} = ci.id
            ", [$tipo]);

            DB::statement("
                UPDATE {$tabla}
                SET {$columna} = NULL
                WHERE {$columna} IS NOT NULL
                  AND {$columna} NOT IN (SELECT id FROM catalogo_items WHERE tipo = ?)
                  AND {$columna} NOT IN (SELECT origen_id FROM catalogo_items WHERE tipo = ?)
            ", [$tipo, $tipo]);
        }

        // ── 4. FK nuevas contra catalogo_items ─────────────────────────
        foreach ($pares as $par) {
            $nombre = "fk_{$par->tabla}_{$par->columna}_catalogo";
            $existe = DB::selectOne(
                "SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
                [$par->tabla, $nombre]
            )->c;
            if (! $existe) {
                DB::statement(
                    "ALTER TABLE {$par->tabla} ADD CONSTRAINT {$nombre}
                     FOREIGN KEY ({$par->columna}) REFERENCES catalogo_items(id)"
                );
            }
        }

        // ── 5. Menús propios -> catálogo unificado ─────────────────────
        foreach (self::TABLAS as $tipo) {
            $rutaVieja = str_replace('_', '-', $tipo).'.index';
            DB::table('menu_items')->where('route', $rutaVieja)->update([
                'route' => "catalogo.index?tipo={$tipo}",
                'permission' => 'catalogo.ver',
                'updated_at' => now(),
            ]);
        }

        // ── 6. Drop ────────────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach (self::TABLAS as $tabla) {
                Schema::dropIfExists($tabla);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function down(): void
    {
        // No reversible: la fuente de verdad es ahora catalogo_items.
    }
};
