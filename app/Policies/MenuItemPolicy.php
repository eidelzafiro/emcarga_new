<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menus.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('menus.crear');
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menus.editar');
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menus.eliminar');
    }
}
