<?php

namespace App\Policies;

use App\Models\Motore;
use App\Models\User;

/**
 * Policy del módulo motores.
 */
class MotorePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'motores';
}
