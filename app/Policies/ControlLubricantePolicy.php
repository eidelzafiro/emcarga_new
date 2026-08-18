<?php

namespace App\Policies;

use App\Models\ControlLubricante;
use App\Models\User;

/**
 * Policy del módulo control-lubricante.
 */
class ControlLubricantePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'control-lubricante';
}
