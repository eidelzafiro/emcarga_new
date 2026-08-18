<?php

namespace App\Policies;

use App\Models\Choferes;
use App\Models\User;

/**
 * Policy del módulo choferes.
 */
class ChoferesPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'choferes';
}
