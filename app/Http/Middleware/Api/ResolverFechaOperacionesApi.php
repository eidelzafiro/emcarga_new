<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el mes de operaciones del token API (stateless).
 *
 * La ability `fecha:{Y-m}` del token Sanctum define el mes/año en curso. Si el
 * token no la trae, se usa el mes actual. El valor queda en el request para que
 * los controladores anclen sus consultas (aforos, combustible, taller, etc.).
 */
class ResolverFechaOperacionesApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $fecha = null;
        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        if ($token && ! empty($token->abilities)) {
            foreach ($token->abilities as $ability) {
                if (str_starts_with((string) $ability, 'fecha:')) {
                    $fecha = substr($ability, 6);
                    break;
                }
            }
        }

        // Normaliza a primer día del mes (Y-m-d). Si la ability es inválida o
        // ausente, cae al mes actual.
        try {
            $carbon = $fecha ? Carbon::createFromFormat('Y-m', $fecha)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            $carbon = now()->startOfMonth();
        }

        $request->attributes->set('api_fecha_operaciones', $carbon->toDateString());

        return $next($request);
    }
}
