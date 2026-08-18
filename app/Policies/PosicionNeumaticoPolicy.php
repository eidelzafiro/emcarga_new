<?php

namespace App\Policies;

use App\Models\PosicionNeumatico;
use App\Models\User;

/**
 * Policy del módulo posiciones-neumaticos.
 */
class PosicionNeumaticoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'posiciones-neumaticos';
}
