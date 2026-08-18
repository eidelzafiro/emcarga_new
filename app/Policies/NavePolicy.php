<?php

namespace App\Policies;

use App\Models\Nave;
use App\Models\User;

/**
 * Policy del módulo naves.
 */
class NavePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'naves';
}
