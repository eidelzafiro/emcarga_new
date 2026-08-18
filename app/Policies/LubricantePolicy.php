<?php

namespace App\Policies;

use App\Models\Lubricante;
use App\Models\User;

/**
 * Policy del módulo lubricantes.
 */
class LubricantePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'lubricantes';
}
