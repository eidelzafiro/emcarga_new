<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UNIFICACIÓN de codificadores simples en catalogo_items (2026-08-23,
 * decisión del usuario). Las tablas dedicadas se ELIMINAN y las relaciones
 * de negocio pasan a apuntar directamente a catalogo_items.id:
 *
 *   marcas, modelos, paises, organismos, colores, grupos,
 *   categorias_cargo, clasificaciones_ordenes_taller, destinos_agregados,
 *   posiciones_neumaticos, medidas_neumaticos, motivos_baja_bateria,
 *   motivos_entrada_taller, embalajes
 *
 * También se eliminan (sin migrar): perfiles_rh, turnos_comerciales,
 * medios_proteccion y tipos_medios_cargo (esta última solo relacionaba
 * cargos con medios de protección; módulo retirado).
 *
 * Proceso idempotente:
 *   1. Sincroniza los 14 catálogos hacia catalogo_items (tipo+origen_id).
 *   2. Registra los tipos en catalogo_tipos con su config de campos.
 *   3. Re-apunta cada columna de negocio: valor legacy → catalogo_items.id.
 *      (auditoría previa: 0 huérfanos salvo osdes.id_organismo=13, que se
 *      anulan porque la columna es anulable).
 *   4. Cambia las FKs: sueltas las viejas y crea las nuevas contra
 *      catalogo_items(id).
 *   5. Menús propios redirigidos a catalogo.index?tipo=... ; permisos
 *      módulo.* eliminados (los roles ya tienen catalogo.ver).
 *   6. Drop de las 18 tablas.
 */
return new class extends Migration
{
    /** tipo => [titulo, tabla, extras => [col => label], fields JSON] */
    private const CATALOGOS = [
        'marcas' => ['Marcas', 'marcas', [],
            '{}'],
        'modelos' => ['Modelos', 'modelos', ['tipo' => 'Tipo'],
            '{"tipo":{"label":"Tipo","type":"text"}}'],
        'paises' => ['Países', 'paises', [], '{}'],
        'organismos' => ['Organismos', 'organismos', ['abreviatura' => 'Abreviatura'],
            '{"abreviatura":{"label":"Abreviatura","type":"text"}}'],
        'colores' => ['Colores', 'colores', [], '{}'],
        'grupos' => ['Grupos', 'grupos', [], '{}'],
        'categorias_cargo' => ['Categorías Cargo', 'categorias_cargo',
            ['abreviatura' => 'Abreviatura'], '{"abreviatura":{"label":"Abreviatura","type":"text"}}'],
        'clasificaciones_ordenes_taller' => ['Clasif. Órdenes Taller', 'clasificaciones_ordenes_taller', [], '{}'],
        'destinos_agregados' => ['Destinos Agregados', 'destinos_agregados', [], '{}'],
        'posiciones_neumaticos' => ['Posiciones Neumáticos', 'posiciones_neumaticos',
            ['descripcion' => 'Descripción'], '{"descripcion":{"label":"Descripción","type":"textarea"}}'],
        'medidas_neumaticos' => ['Medidas Neumáticos', 'medidas_neumaticos', ['medida' => 'Medida'],
            '{"medida":{"label":"Medida","type":"text"}}'],
        'motivos_baja_bateria' => ['Motivos Baja Batería', 'motivos_baja_bateria', [], '{}'],
        'motivos_entrada_taller' => ['Motivos Entrada Taller', 'motivos_entrada_taller', [], '{}'],
        'embalajes' => ['Embalajes', 'embalajes', [], '{}'],
    ];

    /** [tabla_negocio, columna, tipo_catálogo] — auditoría previa sin huérfanos */
    private const REMAPES = [
        ['otros_agregados', 'id_marca', 'marcas'],
        ['tarjetero', 'id_marca', 'marcas'],
        ['tipos_arrastres', 'id_marca', 'marcas'],
        ['tipos_tractivos', 'id_marca', 'marcas'],
        ['otros_agregados', 'id_modelo', 'modelos'],
        ['tarjetero', 'id_modelo', 'modelos'],
        ['tipos_arrastres', 'id_modelo', 'modelos'],
        ['tipos_tractivos', 'id_modelo', 'modelos'],
        ['cajas', 'id_pais', 'paises'],
        ['otros_agregados', 'id_pais', 'paises'],
        ['tarjetero', 'id_pais', 'paises'],
        ['tipos_arrastres', 'id_pais', 'paises'],
        ['tipos_tractivos', 'id_pais', 'paises'],
        ['osdes', 'id_organismo', 'organismos'],
        ['tractivos', 'id_color_primario', 'colores'],
        ['tractivos', 'id_color_secundario', 'colores'],
        ['historial_tractivos', 'id_grupo', 'grupos'],
        ['hojas_ruta', 'id_grupo', 'grupos'],
        ['tractivos', 'id_grupo', 'grupos'],
        ['cargos', 'id_categoria_cargo', 'categorias_cargo'],
        ['salarios', 'id_categoria_cargo', 'categorias_cargo'],
        ['ordenes_taller', 'id_clasificacion', 'clasificaciones_ordenes_taller'],
        ['baterias', 'id_destino', 'destinos_agregados'],
        ['baterias_movimientos', 'id_destino', 'destinos_agregados'],
        ['neumaticos_movimientos', 'id_destino', 'destinos_agregados'],
        ['neumaticos', 'id_posicion', 'posiciones_neumaticos'],
        ['lineas_neumatico', 'id_medida_neumatico', 'medidas_neumaticos'],
        ['tipos_arrastres', 'id_medida_del', 'medidas_neumaticos'],
        ['tipos_arrastres', 'id_medida_tra', 'medidas_neumaticos'],
        ['tipos_arrastres', 'id_medida_res', 'medidas_neumaticos'],
        ['tipos_tractivos', 'id_medida_del', 'medidas_neumaticos'],
        ['tipos_tractivos', 'id_medida_tra', 'medidas_neumaticos'],
        ['tipos_tractivos', 'id_medida_res', 'medidas_neumaticos'],
        ['baterias', 'id_motivo_baja', 'motivos_baja_bateria'],
        ['ordenes_taller', 'id_motivo_entrada', 'motivos_entrada_taller'],
        ['demandas', 'id_embalaje', 'embalajes'],
    ];

    public function up(): void
    {
        // ── 1. Sincronizar datos hacia catalogo_items ──────────────────
        foreach (self::CATALOGOS as $tipo => [$titulo, $tabla, $extras, $fields]) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }

            $columnas = array_merge(['id', 'codigo', 'nombre', 'activo'], array_keys($extras));
            foreach (DB::table($tabla)->get() as $fila) {
                $extra = [];
                foreach ($extras as $col => $label) {
                    $valor = $fila->{$col} ?? null;
                    if ($valor !== null && $valor !== '') {
                        $extra[$col] = $valor;
                    }
                }

                DB::table('catalogo_items')->updateOrInsert(
                    ['tipo' => $tipo, 'origen_id' => $fila->id],
                    [
                        'codigo' => $fila->codigo ?? null,
                        'nombre' => $fila->nombre ?? '',
                        'activo' => (bool) ($fila->activo ?? true),
                        'extra' => $extra === [] ? null : json_encode($extra, JSON_UNESCAPED_UNICODE),
                        'deleted_at' => null,
                        'created_at' => $fila->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // ── 2. Registrar tipos en catalogo_tipos ───────────────────────
        $orden = 100;
        foreach (self::CATALOGOS as $tipo => [$titulo, $tabla, $extras, $fields]) {
            DB::table('catalogo_tipos')->updateOrInsert(
                ['tipo' => $tipo],
                [
                    'titulo' => $titulo,
                    'agrupacion' => 'Técnica',
                    'activo' => true,
                    'orden' => $orden++,
                    'tabla_legacy' => $tabla,
                    'fields' => $fields,
                    'updated_at' => now(),
                    'created_at' => DB::table('catalogo_tipos')->where('tipo', $tipo)->value('created_at') ?? now(),
                ]
            );
        }

        // ── 3a. Soltar las FKs viejas (impiden el remapeo) ─────────────
        $agrupadas = [];
        foreach (self::REMAPES as [$tabla, $columna, $tipo]) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, $columna)) {
                $agrupadas[$tabla][] = [$columna, $tipo];
            }
        }

        foreach ($agrupadas as $tabla => $pares) {
            $fksActuales = [];
            foreach (Schema::getForeignKeys($tabla) as $fk) {
                // No tocar las FK nuevas de esta misma migración (idempotencia)
                if (str_starts_with($fk['name'], 'fk_') && str_ends_with($fk['name'], '_catalogo')) {
                    continue;
                }
                foreach ($fk['columns'] as $col) {
                    $fksActuales[$col] = $fk['name'];
                }
            }
            foreach ($pares as [$columna, $tipo]) {
                if (isset($fksActuales[$columna])) {
                    Schema::table($tabla, fn ($t) => $t->dropForeign($fksActuales[$columna]));
                }
            }
        }

        // ── 3b. Re-apuntar columnas de negocio a catalogo_items.id ─────

        foreach (self::REMAPES as [$tabla, $columna, $tipo]) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            // Basura legacy (0): NULL para no romper la FK nueva
            DB::table($tabla)->where($columna, 0)->update([$columna => null]);

            // Remapeo: valor legacy (origen_id) -> catalogo_items.id
            DB::statement("
                UPDATE {$tabla} t
                JOIN catalogo_items ci ON ci.tipo = ? AND ci.origen_id = t.{$columna}
                SET t.{$columna} = ci.id
            ", [$tipo]);

            // Huérfanos REALES: valores que no corresponden ni al id nuevo
            // (ya remapeados) ni a ningún origen_id legacy (ej: 13 osdes sin
            // organismo). Se anulan; la columna es anulable.
            // ⚠️ NUNCA usar LEFT JOIN por origen_id aquí: tras el remapeo los
            // valores son ids nuevos y ese patrón los anularía en masa.
            DB::statement("
                UPDATE {$tabla}
                SET {$columna} = NULL
                WHERE {$columna} IS NOT NULL
                  AND {$columna} NOT IN (SELECT id FROM catalogo_items WHERE tipo = ?)
                  AND {$columna} NOT IN (SELECT origen_id FROM catalogo_items WHERE tipo = ?)
            ", [$tipo, $tipo]);
        }

        // demandas.id_embalaje no era anulable (tabla vacía): hacerla nullable
        DB::statement('ALTER TABLE demandas MODIFY id_embalaje BIGINT UNSIGNED NULL');

        // ── 4. Crear las FK nuevas contra catalogo_items(id) ───────────
        foreach ($agrupadas as $tabla => $pares) {
            $fksActuales = [];
            foreach (Schema::getForeignKeys($tabla) as $fk) {
                foreach ($fk['columns'] as $col) {
                    $fksActuales[$col] = $fk['name'];
                }
            }

            $nuevas = [];
            foreach ($pares as [$columna, $tipo]) {
                $nombre = "fk_{$tabla}_{$columna}_catalogo";
                $existe = DB::selectOne(
                    "SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
                    [$tabla, $nombre]
                )->c;
                if (! $existe) {
                    $nuevas[] = "ADD CONSTRAINT {$nombre} FOREIGN KEY ({$columna}) REFERENCES catalogo_items(id)";
                }
            }
            if ($nuevas !== []) {
                DB::statement("ALTER TABLE {$tabla} ".implode(', ', $nuevas));
            }
        }

        // ── 5. Menús: redirigir al catálogo unificado / eliminar ───────
        $redirecciones = [
            'marcas.index' => 'marcas', 'modelos.index' => 'modelos', 'grupos.index' => 'grupos',
            'destinos-agregados.index' => 'destinos_agregados',
            'medidas-neumaticos.index' => 'medidas_neumaticos',
            'posiciones-neumaticos.index' => 'posiciones_neumaticos',
            'embalajes.index' => 'embalajes', 'organismos.index' => 'organismos',
            'categorias-cargo.index' => 'categorias_cargo',
            'motivos-entrada-taller.index' => 'motivos_entrada_taller',
            'clasificaciones-ordenes-taller.index' => 'clasificaciones_ordenes_taller',
            'motivos-baja-bateria.index' => 'motivos_baja_bateria',
            'paises.index' => 'paises',
        ];
        foreach ($redirecciones as $rutaVieja => $tipo) {
            DB::table('menu_items')->where('route', $rutaVieja)->update([
                'route' => "catalogo.index?tipo={$tipo}",
                'permission' => 'catalogo.ver',
                'updated_at' => now(),
            ]);
        }
        // Módulos que mueren sin sustituto
        DB::table('menu_items')->whereRaw("route REGEXP ?", [
            'medios-proteccion|perfiles-rh|turnos-comerciales|tipos-medios-cargo',
        ])->delete();

        // Permisos huérfanos (los roles ya cuentan con catalogo.ver)
        $modulos = ['marcas', 'modelos', 'paises', 'organismos', 'colores', 'grupos',
            'categorias-cargo', 'clasificaciones-ordenes-taller', 'destinos-agregados',
            'posiciones-neumaticos', 'medidas-neumaticos', 'motivos-baja-bateria',
            'motivos-entrada-taller', 'embalajes', 'perfiles-rh', 'turnos-comerciales',
            'medios-proteccion'];
        $ids = DB::table('permissions')->where(function ($q) use ($modulos) {
            foreach ($modulos as $m) {
                $q->orWhere('name', 'like', "{$m}.%");
            }
        })->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        // ── 6. Drop de tablas ──────────────────────────────────────────
        // FK_CHECKS=0: tablas como perfiles_rh tienen FKs desde tablas vacías
        // (alertas.id_perfil) que bloquearían el drop sin necesidad.
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ([...array_column(self::CATALOGOS, 1), 'perfiles_rh', 'turnos_comerciales',
                'medios_proteccion', 'tipos_medios_cargo'] as $tabla) {
                Schema::dropIfExists($tabla);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function down(): void
    {
        // No reversible: los catálogos viven ahora en catalogo_items.
    }
};
