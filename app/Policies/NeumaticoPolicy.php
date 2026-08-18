<?php

namespace App\Policies;

use App\Models\Neumatico;
use App\Models\User;

/**
 * Policy del módulo neumaticos.
 */
class NeumaticoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'neumaticos';
}
