<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establece el contexto de trabajo del usuario autenticado:
 * - entidad_activa_id: entidad sobre la que opera (filtro de datos)
 * - fecha_operaciones: fecha de operaciones (filtros de módulos)
 *
 * Ambos valores viven en sesión y se inicializan desde el usuario
 * (su entidad principal y su fecha de operaciones, o hoy por defecto).
 */
class EstablecerContextoTrabajo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $this->asegurarEntidadActiva($request, $user);
            $this->asegurarFechaOperaciones($request, $user);
        }

        return $next($request);
    }

    /**
     * Si la sesión no tiene entidad activa (o el usuario perdió acceso
     * a ella), se asigna la entidad principal o la primera permitida.
     */
    private function asegurarEntidadActiva(Request $request, $user): void
    {
        $activaId = (int) $request->session()->get('entidad_activa_id');

        if ($activaId && $user->tieneAccesoAEntidad($activaId)) {
            return;
        }

        $porDefecto = $user->id_entidad && $user->tieneAccesoAEntidad($user->id_entidad)
            ? $user->id_entidad
            : $user->entidadesAcceso()->first()?->id;

        $request->session()->put('entidad_activa_id', $porDefecto);
    }

    /**
     * La fecha de operaciones se toma de la sesión; si no existe,
     * se usa la última guardada del usuario o la fecha de hoy.
     */
    private function asegurarFechaOperaciones(Request $request, $user): void
    {
        if ($request->session()->has('fecha_operaciones')) {
            return;
        }

        $fecha = $user->fecha_operaciones?->toDateString() ?? now()->toDateString();

        $request->session()->put('fecha_operaciones', $fecha);
    }
}
