<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * La autorización se delega en los permisos de spatie/laravel-permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('usuarios.crear');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('usuarios.editar');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('usuarios.eliminar');
    }

    public function desbloquear(User $user, User $model): bool
    {
        return $user->can('usuarios.desbloquear');
    }

    public function restablecerPassword(User $user, User $model): bool
    {
        return $user->can('usuarios.restablecer');
    }
}
