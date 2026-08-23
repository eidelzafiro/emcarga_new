<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ELIMINACIÓN de codificadores obsoletos (2026-08-23, decisión del usuario).
 *
 * Estos 13 codificadores del legacy ya no se usan y se retiran por completo
 * (modelos, controladores, rutas, policies, menú, permisos y tablas):
 *
 *   causas_gps, causas_multas, tipos_documentos, tipos_ramas,
 *   tipos_sistemas_cuc, tipos_subcta_unidad, centros_costos, buques,
 *   navieras, clientes_mm, elementos_gasto,
 *   tipos_medios_proteccion, tipos_catalogo_lugares
 *
 * ⚠️ REGLA DE MIGRACIÓN: NO volver a traer estas tablas desde el legacy
 * EMCARGA ni recrear vínculos contra ellas. El ETL debe saltarlas y el
 * dump canónico las deja obsoletas (esta migración corre después del dump
 * en installs frescos, así que terminan eliminadas igualmente).
 *
 * Columnas dependientes que se eliminan junto a sus FKs (todas vacías o
 * sin uso según auditoría previa):
 *   - medios_proteccion.id_tipo_medio_proteccion (25 filas del módulo vivo
 *     conservan nombre/duración; el tipo era opcional)
 *   - pagos.id_tipo_documento, devoluciones.id_cliente_mm,
 *     importes_gps.id_causa_gps, importes_multas.id_causa_multa
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Menús del sistema (autorizado por EIDEL: los módulos mueren)
        DB::table('menu_items')->whereRaw("route REGEXP ?", [
            'buques|navieras|causas-gps|causas-multas|tipos-ramas|tipos-sistemas-cuc|tipos-subcta|clientes-mm|tipos-documentos|centros-costos|elementos-gasto|tipos-medios-proteccion|tipos-catalogo-lugares',
        ])->delete();

        // 2. Catálogo unificado: tipos e ítems residuales
        $tiposCatalogo = ['tipos_medios_proteccion', 'tipos_catalogo_lugares'];
        DB::table('catalogo_items')->whereIn('tipo', $tiposCatalogo)->delete();
        DB::table('catalogo_tipos')->whereIn('tipo', $tiposCatalogo)->delete();

        // 3. Columnas con FK hacia las tablas eliminadas
        $columnasConFk = [
            'medios_proteccion' => 'id_tipo_medio_proteccion',
            'pagos' => 'id_tipo_documento',
            'devoluciones' => 'id_cliente_mm',
            'importes_gps' => 'id_causa_gps',
            'importes_multas' => 'id_causa_multa',
        ];
        foreach ($columnasConFk as $tabla => $columna) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }
            foreach (Schema::getForeignKeys($tabla) as $fk) {
                if (in_array($columna, $fk['columns'])) {
                    Schema::table($tabla, fn ($t) => $t->dropForeign($fk['name']));
                }
            }
            Schema::table($tabla, fn ($t) => $t->dropColumn($columna));
        }

        // 4. Las 13 tablas
        foreach ([
            'causas_gps', 'causas_multas', 'tipos_documentos', 'tipos_ramas',
            'tipos_sistemas_cuc', 'tipos_subcta_unidad', 'centros_costos',
            'buques', 'navieras', 'clientes_mm', 'elementos_gasto',
            'tipos_medios_proteccion', 'tipos_catalogo_lugares',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }

    public function down(): void
    {
        // No se restaura: son tablas obsoletas del legacy sin datos.
    }
};
