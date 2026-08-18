<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Nave;
use App\Models\Taller;
use App\Models\User;
use Spatie\Permission\Models\Role;

class MenuBuilder
{
    public static function para(?User $user, ?string $perfil = null, ?int $entidadActivaId = null): array
    {
        if (! $user) {
            return [];
        }

        $tallerExiste = Taller::where('activo', true)
            ->when($entidadActivaId, fn ($q) => $q->where('id_entidad', $entidadActivaId))
            ->exists();

        $navesExisten = Nave::where('activo', true)
            ->when($entidadActivaId, fn ($q) => $q->where('id_entidad', $entidadActivaId))
            ->exists();

        $perfilRole = $perfil ? Role::findByName($perfil) : null;

        return MenuItem::with('children')
            ->whereNull('parent_id')
            ->where('activo', true)
            ->orderBy('orden')
            ->get()
            ->map(fn (MenuItem $item) => self::filtrar($item, $user, $perfilRole, $tallerExiste, $navesExisten))
            ->filter()
            ->values()
            ->all();
    }

    private static function filtrar(MenuItem $item, User $user, ?Role $perfilRole, bool $tallerExiste, bool $navesExisten): ?array
    {
        if (! self::visibleParaPerfil($item, $user, $perfilRole)) {
            return null;
        }

        $hijos = $item->children
            ->where('activo', true)
            ->map(fn (MenuItem $hijo) => self::filtrar($hijo, $user, $perfilRole, $tallerExiste, $navesExisten))
            ->filter()
            ->values()
            ->all();

        if (is_null($item->route) && empty($hijos)) {
            return null;
        }

        $disabled = false;
        // Soporta parámetros en la ruta del ítem: "catalogo.index?tipo=tipos_modelo".
        [$routeName, $query] = array_pad(explode('?', (string) $item->route, 2), 2, null);
        parse_str($query ?? '', $params);

        if ($routeName === 'naves.index' && ! $tallerExiste) {
            $disabled = true;
        } elseif ($routeName === 'vallas.index' && (! $tallerExiste || ! $navesExisten)) {
            $disabled = true;
        }

        if (is_null($item->route) && count($hijos) === 1) {
            return $hijos[0];
        }

        return [
            'label' => $item->label,
            'icon' => $item->icon,
            'route' => $item->route,
            'url' => $routeName !== '' ? route($routeName, $params) : null,
            'disabled' => $disabled,
            'children' => $hijos,
        ];
    }

    private static function visibleParaPerfil(MenuItem $item, User $user, ?Role $perfilRole): bool
    {
        if (! $perfilRole) {
            return $item->visiblePara($user);
        }

        if (is_null($item->permission)) {
            return true;
        }

        return $perfilRole->hasPermissionTo($item->permission);
    }
}
