<?php

namespace App\Services;

use App\Models\CatalogoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fusión de ítems de catálogo (tipos de equipo, marcas y modelos).
 *
 * Al fusionar un ítem "origen" dentro de un "destino", todas las referencias
 * (tipo_vehiculos y tablas de negocio que apuntan a esa marca/modelo) se
 * re-apuntan al destino y el origen se elimina, de modo que las fichas de
 * vehículo se actualizan automáticamente.
 */
class FusionCatalogosService
{
    /** Tablas que referencian marcas/modelos (id_marca / id_modelo). */
    private const TABLAS_MARCA_MODELO = ['tipo_vehiculos', 'baterias', 'neumaticos', 'otros_agregados', 'tarjetero'];

    public function tipos(): array
    {
        return [
            ['value' => 'tipos_equipos', 'label' => 'Tipos de Equipos'],
            ['value' => 'marcas', 'label' => 'Marcas'],
            ['value' => 'modelos', 'label' => 'Modelos'],
            ['value' => 'tipos_vehiculos', 'label' => 'Tipos de Vehículos'],
        ];
    }

    /**
     * Lista los ítems de un tipo para los selectores del formulario.
     */
    public function opciones(string $tipo): array
    {
        if ($tipo === 'tipos_equipos') {
            return DB::table('tipos_equipos')
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->nombre])
                ->all();
        }

        if ($tipo === 'tipos_vehiculos') {
            return DB::table('tipo_vehiculos as tv')
                ->leftJoin('catalogo_items as m', 'm.id', '=', 'tv.id_marca')
                ->leftJoin('catalogo_items as mo', 'mo.id', '=', 'tv.id_modelo')
                ->select('tv.id', 'm.nombre as marca', 'mo.nombre as modelo', 'tv.clase')
                ->orderBy('m.nombre')
                ->orderBy('mo.nombre')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'nombre' => trim(($r->marca ?? 'Sin marca').' '.($r->modelo ?? '')).($r->clase ? " ({$r->clase})" : ''),
                ])
                ->all();
        }

        return CatalogoItem::where('tipo', $tipo)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->nombre])
            ->all();
    }

    /**
     * Fusiona el ítem origen dentro del destino. Devuelve el conteo de
     * referencias re-apuntadas.
     */
    public function fusionar(string $tipo, int $origenId, int $destinoId): array
    {
        if ($origenId === $destinoId) {
            throw new \InvalidArgumentException('El origen y el destino deben ser distintos.');
        }

        return match ($tipo) {
            'tipos_equipos' => $this->fusionarTiposEquipos($origenId, $destinoId),
            'marcas' => $this->fusionarCatalogo('marcas', 'id_marca', $origenId, $destinoId),
            'modelos' => $this->fusionarCatalogo('modelos', 'id_modelo', $origenId, $destinoId),
            'tipos_vehiculos' => $this->fusionarTiposVehiculos($origenId, $destinoId),
            default => throw new \InvalidArgumentException("Tipo de catálogo desconocido: {$tipo}"),
        };
    }

    private function fusionarTiposVehiculos(int $origenId, int $destinoId): array
    {
        $origen = DB::table('tipo_vehiculos')->find($origenId);
        $destino = DB::table('tipo_vehiculos')->find($destinoId);

        if (! $origen || ! $destino) {
            throw new \InvalidArgumentException('El tipo de vehículo origen o destino no existe.');
        }

        $referencias = 0;
        $referencias += DB::table('tractivos')
            ->where('id_tipo_vehiculo', $origenId)
            ->update(['id_tipo_vehiculo' => $destinoId]);

        if (Schema::hasColumn('arrastres', 'id_tipo_vehiculo')) {
            $referencias += DB::table('arrastres')
                ->where('id_tipo_vehiculo', $origenId)
                ->update(['id_tipo_vehiculo' => $destinoId]);
        }

        DB::table('tipo_vehiculos')->where('id', $origenId)->delete();

        return ['referencias' => $referencias];
    }

    private function fusionarTiposEquipos(int $origenId, int $destinoId): array
    {
        $origen = DB::table('tipos_equipos')->find($origenId);
        $destino = DB::table('tipos_equipos')->find($destinoId);

        if (! $origen || ! $destino) {
            throw new \InvalidArgumentException('El tipo de equipo origen o destino no existe.');
        }

        $referencias = DB::table('tipo_vehiculos')
            ->where('id_tipo_equipo', $origenId)
            ->update(['id_tipo_equipo' => $destinoId]);

        DB::table('tipos_equipos')->where('id', $origenId)->delete();

        return ['referencias' => $referencias];
    }

    private function fusionarCatalogo(string $tipo, string $columna, int $origenId, int $destinoId): array
    {
        $origen = CatalogoItem::where('tipo', $tipo)->find($origenId);
        $destino = CatalogoItem::where('tipo', $tipo)->find($destinoId);

        if (! $origen || ! $destino) {
            throw new \InvalidArgumentException('El ítem origen o destino no existe.');
        }

        $referencias = 0;
        foreach (self::TABLAS_MARCA_MODELO as $tabla) {
            if (Schema::hasColumn($tabla, $columna)) {
                $referencias += DB::table($tabla)
                    ->where($columna, $origenId)
                    ->update([$columna => $destinoId]);
            }
        }

        $origen->delete();

        return ['referencias' => $referencias];
    }
}
