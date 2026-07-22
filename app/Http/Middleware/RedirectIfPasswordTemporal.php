<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfPasswordTemporal
{
    /**
     * Fuerza el cambio de la contraseña temporal antes de usar la
     * aplicación (equivalente a la bandera 'cpass' del sistema legacy).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->password_temporal && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.edit')
                ->with('warning', 'Debe cambiar su contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
