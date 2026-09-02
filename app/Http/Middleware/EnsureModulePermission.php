<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\PermissionResolver;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforcement de permisos en servidor por nombre de ruta.
 *
 * Infiere el permiso requerido a partir del nombre de la ruta:
 *
 *   tractivos.index    → tractivos.ver
 *   tractivos.create   → tractivos.crear
 *   tractivos.store    → tractivos.crear
 *   tractivos.edit     → tractivos.editar
 *   tractivos.update   → tractivos.editar
 *   tractivos.destroy  → tractivos.eliminar
 *   facturas.cancelar  → facturas.cancelar (permiso literal)
 *   dashboard          → dashboard.ver
 *
 * Si ningún permiso candidato existe en la tabla `permissions`, la ruta
 * queda accesible (fail-open): permite rutas de plataforma sin permiso
 * catalogado (perfil, notificaciones, APIs internas de apoyo).
 */
class EnsureModulePermission
{
    /**
     * Módulos cuyo nombre de ruta no coincide con el prefijo del permiso.
     */
    private const ALIAS_MODULO = [
        'aforos' => 'facturas',
        'menu-items' => 'menus',
        'tipos-lubricantes' => 'lubricantes',
    ];

    private const MAPA_ACCIONES = [
        'index' => 'ver',
        'show' => 'ver',
        'create' => 'crear',
        'store' => 'crear',
        'edit' => 'editar',
        'update' => 'editar',
        'destroy' => 'eliminar',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $nombreRuta = $request->route()?->getName();

        if (! $user || ! $nombreRuta) {
            return $next($request);
        }

        [$modulo, $accion] = array_pad(explode('.', $nombreRuta, 2), 2, null);
        $modulo = self::ALIAS_MODULO[$modulo] ?? $modulo;

        $permiso = $this->resolverPermiso($modulo, $accion, $request);

        if ($permiso === null || PermissionResolver::puede($user, $permiso)) {
            return $next($request);
        }

        \Log::warning('EnsureModulePermission BLOCKED', [
            'user' => $user->id,
            'route' => $nombreRuta,
            'modulo' => $modulo,
            'accion' => $accion,
            'permiso' => $permiso,
            'hasSuperadmin' => $user->hasRole('SUPERADMIN'),
        ]);

        abort(403);
    }

    /**
     * Devuelve el primer permiso candidato que exista en BD, o null si
     * la ruta no tiene permiso catalogado (fail-open).
     */
    private function resolverPermiso(string $modulo, ?string $accion, Request $request): ?string
    {
        $candidatos = [];

        if ($accion === null) {
            // Ruta simple tipo "dashboard" → dashboard.ver
            $candidatos[] = "{$modulo}.ver";
        } elseif (isset(self::MAPA_ACCIONES[$accion])) {
            $accionPermiso = self::MAPA_ACCIONES[$accion];

            // Catálogo unificado: permiso específico por tipo (catalogo.{tipo}.{accion})
            // antes del genérico catalogo.{accion}. Permite conceder "marcas" sin
            // exponer el resto del catálogo.
            if ($modulo === 'catalogo' && ($tipo = $request->route('tipo'))) {
                $candidatos[] = "catalogo.{$tipo}.{$accionPermiso}";
            }

            $candidatos[] = "{$modulo}.{$accionPermiso}";
        } else {
            // Acción personalizada: primero el permiso literal; como
            // fallback, el permiso según el verbo HTTP de la petición.
            $candidatos[] = "{$modulo}.{$accion}";
            $candidatos[] = $request->isMethod('GET') ? "{$modulo}.ver" : "{$modulo}.editar";
        }

        $permisosExistentes = app(PermissionRegistrar::class)->getPermissions();

        foreach ($candidatos as $candidato) {
            if ($permisosExistentes->contains('name', $candidato)) {
                return $candidato;
            }
        }

        return null;
    }
}
