<?php

namespace App\Policies;

use App\Models\CatalogoItem;
use App\Models\User;

/**
 * Policy del módulo catalogo.
 */
class CatalogoItemPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'catalogo';
}
