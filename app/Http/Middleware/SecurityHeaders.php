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
 * Nota: el Content-Security-Policy se añade sin nonce porque el build
 * Inertia/Vite inyecta el script de Ziggy (@routes) de forma inline; por ello
 * script-src/style-src permiten 'unsafe-inline'. El resto del policy es
 * restrictivo (frame-ancestors/base-uri/form-action al origen, object-src none,
 * connect-src limitado). Un CSP con nonce requeriría parchear el Blade de Ziggy.
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
            'camera=(), microphone=(), geolocation=(), browsing-topics=()'
        );

        // ⚠️ HSTS y 'upgrade-insecure-requests' SOLO si la petición llega
        // realmente por HTTPS (no por APP_ENV: el staging local corre con
        // APP_ENV=production pero HTTP plano, y forzar https rompería el SPA:
        // ERR_SSL_PROTOCOL_ERROR porque nginx no escucha TLS).
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Content-Security-Policy (CSP).
        // No se usa nonce porque el build Inertia/Vite inyecta el script de
        // Ziggy (@routes) de forma inline; por eso script-src/style-src permiten
        // 'unsafe-inline'. El resto del policy es estricto y sí aporta protección
        // real (anti-clickjacking, anti-inyección de base/form, sin plugins).
        $response->headers->set('Content-Security-Policy', $this->cspPolicy($request));

        return $response;
    }

    /**
     * Construye la directiva Content-Security-Policy.
     *
     * 'self' + 'unsafe-inline' para scripts/estilos (compatibilidad con Ziggy y
     * PrimeVue). El remaining es restrictivo: prohíbe framing externo, fija
     * base-uri y form-action al origen, bloquea object/embed, y limita conexiones
     * al origen (+ websockets para Reverb).
     *
     * 'upgrade-insecure-requests' se emite únicamente cuando la petición llega
     * por HTTPS; en HTTP plano rompería el SPA (el navegador "actualizaría"
     * login/logout a https inexistente).
     */
    private function cspPolicy(Request $request): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "connect-src 'self' ws: wss:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];

        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
