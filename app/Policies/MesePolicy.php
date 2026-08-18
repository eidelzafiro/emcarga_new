<?php

namespace App\Policies;

use App\Models\Mese;
use App\Models\User;

/**
 * Policy del módulo meses.
 */
class MesePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'meses';
}
