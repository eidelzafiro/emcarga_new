<?php

namespace App\Http\Controllers\Traits;

use App\Models\Entidad;

/**
 * Scoping y autorización por entidad (contexto de trabajo).
 *
 * La entidad activa del usuario (sesión `entidad_activa_id`) ve su propia
 * entidad más sus subordinadas en la jerarquía (la matriz ve todas sus
 * filiales; una filial solo lo suyo).
 */
trait EntidadScoping
{
    /**
     * Ids de entidad permitidos para la entidad activa (ella + subordinadas).
     */
    protected function entidadesPermitidas(): array
    {
        $entidadId = (int) session('entidad_activa_id');
        if (! $entidadId) {
            return [];
        }

        return collect(Entidad::subEntidadesIds($entidadId))
            ->push($entidadId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Aborta con 403 si la entidad dada no está dentro de las permitidas.
     */
    protected function autorizarEntidad(?int $idEntidad, string $mensaje = 'No tiene permiso para acceder a este registro.'): void
    {
        $ids = $this->entidadesPermitidas();
        if (empty($ids) || $idEntidad === null) {
            return;
        }

        if (! in_array((int) $idEntidad, $ids, true)) {
            abort(403, $mensaje);
        }
    }
}
