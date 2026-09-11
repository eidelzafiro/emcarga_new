<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Nave;
use App\Models\Taller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Role;

class MenuBuilder
{
    private static ?bool $tallerExiste = null;
    private static ?bool $navesExisten = null;
    private static ?array $tablasVacias = null;

    public static function para(?User $user, ?string $perfil = null, ?int $entidadActivaId = null): array
    {
        if (! $user) {
            return [];
        }

        self::$tallerExiste = self::$tallerExiste ?? Taller::where('activo', true)
            ->when($entidadActivaId, fn ($q) => $q->where('id_entidad', $entidadActivaId))
            ->exists();

        self::$navesExisten = self::$navesExisten ?? Nave::where('activo', true)
            ->when($entidadActivaId, fn ($q) => $q->where('id_entidad', $entidadActivaId))
            ->exists();

        self::$tablasVacias = self::$tablasVacias ?? self::detectarTablasVacias();

        $perfilRole = $perfil ? Role::findByName($perfil) : null;

        return MenuItem::with('children.children')
            ->whereNull('parent_id')
            ->where('activo', true)
            ->orderBy('orden')
            ->get()
            ->map(fn (MenuItem $item) => self::filtrar($item, $user, $perfilRole))
            ->filter()
            ->values()
            ->all();
    }

    private static function filtrar(MenuItem $item, User $user, ?Role $perfilRole): ?array
    {
        if (! self::visibleParaPerfil($item, $user, $perfilRole)) {
            return null;
        }

        $hijos = $item->children
            ->where('activo', true)
            ->map(fn (MenuItem $hijo) => self::filtrar($hijo, $user, $perfilRole))
            ->filter()
            ->values()
            ->all();

        // Los grupos marcados como `siempre_visible` (p. ej. Reportes) se
        // muestran aunque el perfil no tenga permiso sobre ningún hijo.
        if (is_null($item->route) && empty($hijos) && ! $item->siempre_visible) {
            return null;
        }

        $disabled = false;
        // Soporta parámetros en la ruta del ítem: "catalogo.index?tipo=tipos_modelo".
        [$routeName, $query] = array_pad(explode('?', (string) $item->route, 2), 2, null);
        parse_str($query ?? '', $params);

        if ($routeName === 'naves.index' && ! self::$tallerExiste) {
            $disabled = true;
        } elseif ($routeName === 'vallas.index' && (! self::$tallerExiste || ! self::$navesExisten)) {
            $disabled = true;
        }

        // Deshabilitar módulos cuya tabla está vacía (sin datos que mostrar).
        $tablaVacia = self::$tablasVacias[$routeName] ?? false;
        if ($tablaVacia) {
            $disabled = true;
        }

        if (is_null($item->route) && count($hijos) === 1 && ! $item->siempre_visible) {
            return $hijos[0];
        }

        // Ruta no registrada (p. ej. módulo aún no implementado): se muestra
        // deshabilitado en lugar de crashear con RouteNotFoundException.
        $rutaExiste = $routeName === '' || Route::has($routeName);

        return [
            'label' => $item->label,
            'icon' => $item->icon,
            'route' => $item->route,
            'url' => $routeName !== '' && $rutaExiste ? route($routeName, $params) : null,
            'disabled' => $disabled || ! $rutaExiste,
            'nueva_ventana' => self::abreEnNuevaVentana($routeName),
            'children' => $hijos,
        ];
    }

    /**
     * Determina si un ítem de menú debe abrirse en una ventana nueva del
     * navegador en lugar de navegar con la SPA Inertia. Aplica a las rutas
     * de reportes que devuelven un binario (PDF/Excel) como descarga.
     */
    private static function abreEnNuevaVentana(string $routeName): bool
    {
        return str_starts_with($routeName, 'reportes.')
            && ! in_array($routeName, ['reportes.catalogo', 'reportes.generar', 'reportes.modelo1.filtros', 'reportes.resumen', 'reportes.salarios'], true);
    }

    /**
     * Detecta tablas vacías para módulos que no deben mostrarse sin datos.
     */
    private static function detectarTablasVacias(): array
    {
        $tablas = [
            'talleres.index' => 'talleres',
            'pagos.index' => 'pagos',
            'reembolsos.index' => 'reembolsos',
            'conciliaciones.index' => 'conciliaciones',
            'amortizacion-taller.index' => 'amortizacion_taller',
            'estadisticas-explotacion.index' => 'estadisticas_explotacion',
        ];

        $vacias = [];
        foreach ($tablas as $ruta => $tabla) {
            try {
                $vacias[$ruta] = DB::table($tabla)->count() === 0;
            } catch (\Exception) {
                // Tabla no existe → tratar como vacía.
                $vacias[$ruta] = true;
            }
        }

        return $vacias;
    }

    private static function visibleParaPerfil(MenuItem $item, User $user, ?Role $perfilRole): bool
    {
        if (! $perfilRole) {
            return $item->visiblePara($user);
        }

        if (is_null($item->permission)) {
            return true;
        }

        // Permiso no catalogado (menú obsoleto): se oculta en vez de explotar.
        try {
            return $perfilRole->hasPermissionTo($item->permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
