<?php

namespace App\Http\Middleware;

use App\Models\Entidad;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenciaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->id_entidad) {
            $entidad = Entidad::find($user->id_entidad);
            if ($entidad && ! $entidad->licencia_activa) {
                session()->flash('licencia_alerta', 'Licencia desactivada manualmente.');
                session()->flash('licencia_tipo', 'warn');
            } elseif ($entidad && $entidad->licencia_vencimiento && $entidad->licencia_vencimiento->isPast()) {
                session()->flash('licencia_alerta', 'Licencia vencida desde '.$entidad->licencia_vencimiento->format('d/m/Y').'.');
                session()->flash('licencia_tipo', 'error');
            }
        }

        return $next($request);
    }
}
