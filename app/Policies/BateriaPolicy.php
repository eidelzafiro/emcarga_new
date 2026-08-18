<?php

namespace App\Policies;

use App\Models\Bateria;
use App\Models\User;

/**
 * Policy del módulo baterias.
 */
class BateriaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'baterias';
}
