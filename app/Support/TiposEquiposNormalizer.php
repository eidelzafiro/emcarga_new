<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Normalización de TIPOS DE EQUIPOS tras la migración desde legacy.
 *
 * REGLA DE NEGOCIO (2026-08-23, decisión del usuario):
 *
 * El legacy EMCARGA trae `tec_tipoequipos` con nombres redundantes o en
 * plural que representan lo mismo. Al migrar se consolidan en un catálogo
 * canónico según este mapa (clave = nombre FINAL conservado; valores =
 * nombres absorbidos que desaparecen):
 *
 *   CUÑA TRACTORA  <- CUÑAS TRACTORAS
 *   OMNIBUS        <- (duplicado exacto de OMNIBUS: se conserva un solo registro)
 *   CISTERNA       <- CAMION CISTERNA AGUA
 *   AUTO           <- AUTO LIGERO, AUTO ESPECIAL
 *   GRUA           <- CAMION GRUA
 *   VOLTEO         <- CAMION VOLTEO, S/R VOLTEO
 *
 * Proceso por grupo:
 *   1. El sobreviviente es la fila ya llamada como el nombre final; si no
 *      existe ninguna, se toma la de menor id entre los miembros y se le
 *      renombra al nombre final.
 *   2. Las filas absorbidas se re-direccionan en las tablas dependientes
 *      (tipos_arrastres.id_tipo_equipo) hacia el id del sobreviviente y se
 *      eliminan de tipos_equipos.
 *   3. Se refleja en el catálogo unificado: el ítem del sobreviviente se
 *      renombra (si aplica) y los ítems de los absorbidos se eliminan.
 *   4. La imagen asignada al sobreviviente se conserva intacta.
 *
 * Este proceso es IDEMPOTENTE: puede ejecutarse tantas veces como sea
 * necesario (se invoca automáticamente al final de `zafiro:migrar-catalogos`
 * para el tipo tipos_equipos).
 */
class TiposEquiposNormalizer
{
    /**
     * Mapa de consolidación: nombre final => nombres que absorbe.
     * Un grupo sin absorbidos solo elimina duplicados exactos de su propio nombre.
     */
    private const GRUPOS = [
        'CUÑA TRACTORA' => ['CUÑAS TRACTORAS'],
        'OMNIBUS' => [],
        'CISTERNA' => ['CAMION CISTERNA AGUA'],
        'AUTO' => ['AUTO LIGERO', 'AUTO ESPECIAL'],
        'GRUA' => ['CAMION GRUA'],
        'VOLTEO' => ['CAMION VOLTEO', 'S/R VOLTEO'],
    ];

    /**
     * Ejecuta la normalización y devuelve un reporte legible por consola.
     *
     * @return string[] líneas del reporte
     */
    public static function normalizar(): array
    {
        $reporte = [];

        foreach (self::GRUPOS as $final => $absorbidos) {
            $nombresGrupo = array_merge([$final], $absorbidos);

            $filas = DB::table('tipos_equipos')
                ->whereIn('nombre', $nombresGrupo)
                ->orderBy('id')
                ->get();

            if ($filas->isEmpty()) {
                continue;
            }

            // Sobreviviente: fila con el nombre final; si no existe, la más antigua.
            $sobreviviente = $filas->firstWhere('nombre', $final) ?? $filas->first();

            if ($sobreviviente->nombre !== $final) {
                DB::table('tipos_equipos')
                    ->where('id', $sobreviviente->id)
                    ->update(['nombre' => $final, 'updated_at' => now()]);
                $reporte[] = "Renombrado {$sobreviviente->nombre} (id {$sobreviviente->id}) -> {$final}";
            }

            // Absorción del resto del grupo (incluye duplicados exactos de $final).
            foreach ($filas as $fila) {
                if ((int) $fila->id === (int) $sobreviviente->id) {
                    continue;
                }

                $movidas = DB::table('tipos_arrastres')
                    ->where('id_tipo_equipo', $fila->id)
                    ->update(['id_tipo_equipo' => $sobreviviente->id]);

                DB::table('tipos_equipos')->where('id', $fila->id)->delete();

                $reporte[] = "Absorbido {$fila->nombre} (id {$fila->id}) en {$final}"
                    .($movidas > 0 ? " [{$movidas} fichas re-asignadas]" : '');
            }

            // NOTA: tipos_equipos ya NO vive en el catálogo unificado (desde
            // 2026-08-24 tiene controlador/rutas/vistas propias). El espejo en
            // catalogo_items se eliminó para evitar re-sincronizarlo.
        }

        return $reporte;
    }
}
