<?php

namespace App\Support;

use App\Models\CatalogoItem;
use Illuminate\Support\Facades\Cache;

/**
 * Acceso centralizado a los catálogos unificados (catalogo_items).
 *
 * Desde 2026-08-23 los codificadores simples del legacy (marcas, modelos,
 * paises, organismos, colores, grupos, etc.) viven SOLO en catalogo_items;
 * sus tablas dedicadas fueron eliminadas. Las tablas de negocio referencian
 * directamente catalogo_items.id y este helper resuelve:
 *
 *   - Dropdowns para vistas:  Catalogos::opciones('marcas')
 *   - Ids de negocio fijos:   Catalogos::grupoArrastresId()  (origen_id=8,
 *     regla legacy "un tractivo es arrastre si su grupo es ARRASTRES")
 */
class Catalogos
{
    /** Duración del cache de opciones/ids (segundos). */
    private const TTL = 3600;

    /**
     * Opciones para selects Inertia: [{id, nombre, codigo}, ...] ordenado
     * por nombre, solo ítems activos. `id` es catalogo_items.id (el valor
     * que guardan las tablas de negocio).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function opciones(string $tipo, bool $conExtra = false): array
    {
        return Cache::remember("catalogos.opciones.{$tipo}.{$conExtra}", self::TTL,
            function () use ($tipo, $conExtra) {
                $items = CatalogoItem::query()
                    ->where('tipo', $tipo)
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'codigo', 'nombre', 'extra']);

                return $items->map(function ($item) use ($conExtra) {
                    $fila = $item->only(['id', 'codigo', 'nombre']);
                    if ($conExtra && is_array($item->extra)) {
                        $fila = array_merge($fila, $item->extra);
                    }

                    return $fila;
                })->values()->toArray();
            });
    }

    /**
     * Id de catálogo (catalogo_items.id) a partir del origen_id legacy.
     */
    public static function idDe(string $tipo, int $origenId): ?int
    {
        return Cache::remember("catalogos.id.{$tipo}.{$origenId}", self::TTL,
            fn () => CatalogoItem::where('tipo', $tipo)->where('origen_id', $origenId)->value('id'));
    }

    /**
     * Id del grupo ARRASTRES (origen_id 8 del legacy). Usado por la regla
     * "tractivo es arrastre si su grupo es ARRASTRES".
     */
    public static function grupoArrastresId(): ?int
    {
        return Cache::rememberForever('catalogos.grupo_arrastres_id',
            fn () => CatalogoItem::where('tipo', 'grupos')->where('origen_id', 8)->value('id'));
    }
}
