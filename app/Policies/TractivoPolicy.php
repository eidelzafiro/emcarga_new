<?php

namespace App\Policies;

use App\Models\Tractivo;
use App\Models\User;

/**
 * Policy del módulo tractivos.
 */
class TractivoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tractivos';
}
