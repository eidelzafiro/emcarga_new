<?php

namespace App\Policies;

use App\Models\CategoriaCargo;
use App\Models\User;

/**
 * Policy del módulo categorias-cargo.
 */
class CategoriaCargoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'categorias-cargo';
}
