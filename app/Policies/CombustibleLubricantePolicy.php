<?php

namespace App\Policies;

use App\Models\CombustibleLubricante;
use App\Models\User;

/**
 * Policy del módulo combustibles-lubricantes.
 */
class CombustibleLubricantePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'combustibles-lubricantes';
}
