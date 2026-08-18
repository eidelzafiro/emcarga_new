<?php

namespace App\Policies;

use App\Models\Naviera;
use App\Models\User;

/**
 * Policy del módulo navieras.
 */
class NavieraPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'navieras';
}
