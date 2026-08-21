<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad HTTP aplicadas globalmente a todas las respuestas web.
 *
 * Conjunto de bajo riesgo que no interfere con el SPA Inertia/Vite:
 *   - X-Content-Type-Options: nosniff        (evita MIME sniffing)
 *   - X-Frame-Options: SAMEORIGIN            (anti-clickjacking; SAMEORIGIN por si se embeben PDFs)
 *   - Referrer-Policy: strict-origin-when-cross-origin
 *   - Permissions-Policy: deshabilita APIs navegador no usadas
 *   - Strict-Transport-Security (HSTS)       (solo en producción sobre HTTPS)
 *
 * Nota (deuda conocida): no se aplica Content-Security-Policy (CSP) todavía porque
 * el build Vite/Inertia inyecta scripts y requiere integración de nonce (cspNonce)
 * para no romper el SPA. Ver PLAN.md → S-1.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );
        $response->headers->set(
            'Permissions-Policy',
            "camera=(), microphone=(), geolocation=(), browsing-topics=()"
        );

        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
