<?php

namespace App\Policies;

use App\Models\Cargo;
use App\Models\User;

/**
 * Policy del módulo cargos.
 */
class CargoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'cargos';
}
