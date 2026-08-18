<?php

namespace App\Policies;

use App\Models\Conciliacione;
use App\Models\User;

/**
 * Policy del módulo conciliaciones.
 */
class ConciliacionePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'conciliaciones';
}
