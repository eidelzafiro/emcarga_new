<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve la entidad activa del token API (stateless).
 *
 * La ability `entidad:{id}` del token Sanctum define el ámbito. Si el token no
 * la trae, se usa `user.id_entidad`. El valor queda en el request para que los
 * controladores apliquen el scoping con `Entidad::idsPermitidos()`.
 */
class ResolverEntidadApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $entidadId = null;
        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        if ($token && ! empty($token->abilities)) {
            foreach ($token->abilities as $ability) {
                if (str_starts_with((string) $ability, 'entidad:')) {
                    $entidadId = (int) substr($ability, 8);
                    break;
                }
            }
        }

        $request->attributes->set('api_entidad_id', $entidadId ?: (int) $user->id_entidad);

        return $next($request);
    }
}
