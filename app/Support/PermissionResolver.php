<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Resolución unificada de permisos, compartida por el middleware
 * `EnsureModulePermission` y por las policies de módulo.
 *
 * Respeta el perfil activo de sesión (`perfil_activo`): si el usuario eligió
 * un perfil, se evalúa el permiso contra ese rol; si no, contra todos sus roles.
 */
class PermissionResolver
{
    /**
     * ¿El usuario (o su perfil activo) tiene el permiso?
     */
    public static function puede(User $user, string $permiso): bool
    {
        $perfil = session('perfil_activo');

        if (! $perfil) {
            return $user->can($permiso);
        }

        $role = Role::findByName($perfil);

        return $role->hasPermissionTo($permiso);
    }
}