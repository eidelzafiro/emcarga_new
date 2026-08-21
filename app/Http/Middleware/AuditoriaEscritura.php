<?php

namespace App\Http\Middleware;

use App\Models\Bitacora;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auditoría de escritura para todos los módulos (S-2 del PLAN.md).
 *
 * Registra en `bitacora` toda petición de escritura (POST/PUT/PATCH/DELETE)
 * con ruta nombrada, excepto las de autenticación/sesión y la ruta de salud.
 * Esto cubre los ~86 controladores CRUD sin necesidad de tocar cada uno,
 * complementando las auditorías detalladas ya existentes en auth/usuarios/perfis/menu.
 *
 * Campos sensibles (password, token) se excluyen del detalle.
 */
class AuditoriaEscritura
{
    /** Rutas que nunca deben auditarse (auth, sesión, salud, internas). */
    protected array $ignorar = [
        'login',
        'login.*',
        'logout',
        'password.*',
        'up',
        'password.temporal*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->debeAuditar($request)) {
            return $response;
        }

        $this->registrar($request, $response);

        return $response;
    }

    private function debeAuditar(Request $request): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $ruta = $request->route()?->getName();
        if ($ruta === null) {
            return false;
        }

        foreach ($this->ignorar as $patron) {
            if (Str::is($patron, $ruta)) {
                return false;
            }
        }

        return true;
    }

    private function registrar(Request $request, Response $response): void
    {
        $ruta = $request->route()?->getName() ?? $request->path();
        $metodo = strtolower($request->method());
        $accion = $metodo.'.'.$ruta;

        $datos = collect($request->except([
            'password', 'password_confirmation', '_token', 'current_password',
        ]))->map(fn ($v) => is_array($v) ? json_encode($v) : $v)->toArray();

        Bitacora::registrar(
            accion: $accion,
            detalles: 'IP: '.$request->ip()
                .' | Estado: '.$response->getStatusCode()
                .' | Datos: '.json_encode($datos, JSON_UNESCAPED_UNICODE),
        );
    }
}
