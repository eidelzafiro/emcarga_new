<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Entidad;
use Illuminate\Http\Request;

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
}
