<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Entidad;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Scoping por entidad para los controladores de la API móvil.
 *
 * La entidad activa proviene de la ability `entidad:{id}` del token (resuelta
 * por App\Http\Middleware\Api\ResolverEntidadApi). La matriz ve sus filiales;
 * una filial solo lo suyo.
 */
trait ScopesEntidadApi
{
    protected function entidadActivaId(Request $request): int
    {
        return (int) $request->attributes->get('api_entidad_id');
    }

    /** @return array<int,int> */
    protected function entidadesPermitidas(Request $request): array
    {
        $id = $this->entidadActivaId($request);

        return $id ? Entidad::idsPermitidos($id) : [];
    }

    protected function autorizarEntidad(Request $request, ?int $idEntidad): void
    {
        $permitidas = array_map('intval', $this->entidadesPermitidas($request));

        abort_unless($idEntidad !== null && in_array((int) $idEntidad, $permitidas, true), 403);
    }

    /**
     * Mes de operaciones del token (primer día del mes), resuelto por
     * App\Http\Middleware\Api\ResolverFechaOperacionesApi.
     */
    protected function fechaOperaciones(Request $request): Carbon
    {
        $fecha = $request->attributes->get('api_fecha_operaciones');

        return $fecha ? Carbon::parse($fecha)->startOfMonth() : now()->startOfMonth();
    }

    /**
     * Restringe una consulta sobre cartas de porte a las entidades permitidas.
     * La entidad de una CP se resuelve por hoja de ruta, tractivo o solicitud.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int,int>  $ids
     */
    protected function whereCartaEnEntidades($query, array $ids): void
    {
        $query->where(function ($w) use ($ids) {
            $w->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $ids))
                ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $ids))
                ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $ids));
        });
    }
}
