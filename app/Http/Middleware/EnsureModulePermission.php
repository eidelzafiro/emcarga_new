<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        if ($permiso === null || $this->usuarioPuede($user, $permiso, $request)) {
            return $next($request);
        }

        abort(403);
    }

    /**
     * Verifica si el usuario (o su perfil activo en sesión) tiene el permiso.
     */
    private function usuarioPuede($user, string $permiso, Request $request): bool
    {
        $perfil = $request->session()->get('perfil_activo');

        if (! $perfil) {
            return $user->can($permiso);
        }

        $role = \Spatie\Permission\Models\Role::findByName($perfil);

        return $role->hasPermissionTo($permiso);
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
            $candidatos[] = "{$modulo}.".self::MAPA_ACCIONES[$accion];
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
