<?php

namespace App\Policies;

use App\Models\Inventario;
use App\Models\User;

/**
 * Policy del módulo inventario.
 */
class InventarioPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'inventario';
}
