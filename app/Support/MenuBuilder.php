<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\User;

class MenuBuilder
{
    /**
     * Construye el árbol de menú visible para el usuario, filtrando
     * por sus permisos. Un agrupador (ítem sin ruta) solo aparece
     * si tiene al menos un hijo visible.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function para(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return MenuItem::with('children')
            ->whereNull('parent_id')
            ->where('activo', true)
            ->orderBy('orden')
            ->get()
            ->map(fn (MenuItem $item) => self::filtrar($item, $user))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Filtra recursivamente un ítem y sus hijos según los permisos.
     */
    private static function filtrar(MenuItem $item, User $user): ?array
    {
        if (! $item->visiblePara($user)) {
            return null;
        }

        $hijos = $item->children
            ->where('activo', true)
            ->map(fn (MenuItem $hijo) => self::filtrar($hijo, $user))
            ->filter()
            ->values()
            ->all();

        // Un agrupador sin hijos visibles no se muestra
        if (is_null($item->route) && empty($hijos)) {
            return null;
        }

        // Menú plano: si el agrupador tiene exactamente 1 hijo, lo
        // promovemos al nivel superior (sin wrapper).
        if (is_null($item->route) && count($hijos) === 1) {
            return $hijos[0];
        }

        return [
            'label' => $item->label,
            'icon' => $item->icon,
            'route' => $item->route,
            'url' => $item->route ? route($item->route) : null,
            'children' => $hijos,
        ];
    }
}
