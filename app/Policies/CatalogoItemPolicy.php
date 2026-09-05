<?php

namespace App\Policies;

use App\Models\CatalogoItem;
use App\Models\User;
use App\Support\PermissionResolver;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

/**
 * Policy del módulo catalogo.
 *
 * Verifica permisos específicos por tipo de catálogo:
 *   1. catalogo.{tipo}.{accion} (ej: catalogo.marcas.ver)
 *   2. catalogo.{accion} (fallback genérico, ej: catalogo.ver)
 */
class CatalogoItemPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'catalogo';

    /**
     * Obtiene el tipo de catálogo del request actual.
     */
    private function obtenerTipoCatalogo(): ?string
    {
        $request = app(Request::class);
        return $request->route('tipo');
    }

    /**
     * Devuelve el primer permiso candidato que exista en BD.
     * Mismo patrón que EnsureModulePermission::resolverPermiso().
     */
    private function resolverPermiso(string $accion): string
    {
        $tipo = $this->obtenerTipoCatalogo();
        $permisosExistentes = app(PermissionRegistrar::class)->getPermissions();

        $candidatos = [];

        if ($tipo) {
            $candidatos[] = "catalogo.{$tipo}.{$accion}";
        }

        $candidatos[] = "{$this->permissionPrefix}.{$accion}";

        foreach ($candidatos as $candidato) {
            if ($permisosExistentes->contains('name', $candidato)) {
                return $candidato;
            }
        }

        return "{$this->permissionPrefix}.{$accion}";
    }

    public function viewAny(User $user): bool
    {
        return PermissionResolver::puede($user, $this->resolverPermiso('ver'));
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionResolver::puede($user, $this->resolverPermiso('crear'));
    }

    public function update(User $user, Model $model): bool
    {
        return PermissionResolver::puede($user, $this->resolverPermiso('editar'));
    }

    public function delete(User $user, Model $model): bool
    {
        return PermissionResolver::puede($user, $this->resolverPermiso('eliminar'));
    }
}
