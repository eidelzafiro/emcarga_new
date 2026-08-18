<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PermissionResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy base para módulos CRUD.
 *
 * Cada módulo define el prefijo de su permiso (ej. `tractivos`) y la base
 * mapea las acciones estándar de Laravel a los permisos Spatie:
 *   viewAny/view → {prefijo}.ver
 *   create       → {prefijo}.crear
 *   update       → {prefijo}.editar
 *   delete       → {prefijo}.eliminar
 *
 * La comprobación usa `PermissionResolver`, la misma lógica que el middleware
 * `EnsureModulePermission` (respeta el perfil activo de sesión).
 */
abstract class ModulePolicy
{
    /**
     * Prefijo del permiso (ej. `tractivos`, `facturas`).
     */
    protected string $permissionPrefix;

    public function viewAny(User $user): bool
    {
        return PermissionResolver::puede($user, "{$this->permissionPrefix}.ver");
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionResolver::puede($user, "{$this->permissionPrefix}.crear");
    }

    public function update(User $user, Model $model): bool
    {
        return PermissionResolver::puede($user, "{$this->permissionPrefix}.editar");
    }

    public function delete(User $user, Model $model): bool
    {
        return PermissionResolver::puede($user, "{$this->permissionPrefix}.eliminar");
    }
}