<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * La gestión de perfiles usa dos permisos: perfiles.ver para
     * consultar y perfiles.editar para cualquier modificación.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('perfiles.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('perfiles.editar');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('perfiles.editar');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('perfiles.editar');
    }
}
