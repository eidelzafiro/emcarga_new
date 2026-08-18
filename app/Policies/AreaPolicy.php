<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

/**
 * Policy del módulo areas.
 */
class AreaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'areas';
}
