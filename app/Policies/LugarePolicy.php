<?php

namespace App\Policies;

use App\Models\Lugare;
use App\Models\User;

/**
 * Policy del módulo lugares.
 */
class LugarePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'lugares';
}
