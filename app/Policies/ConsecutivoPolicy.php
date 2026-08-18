<?php

namespace App\Policies;

use App\Models\Consecutivo;
use App\Models\User;

/**
 * Policy del módulo consecutivos.
 */
class ConsecutivoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'consecutivos';
}
