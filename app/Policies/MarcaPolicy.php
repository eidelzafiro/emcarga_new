<?php

namespace App\Policies;

use App\Models\Marca;
use App\Models\User;

/**
 * Policy del módulo marcas.
 */
class MarcaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'marcas';
}
