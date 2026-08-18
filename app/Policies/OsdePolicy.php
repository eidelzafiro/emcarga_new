<?php

namespace App\Policies;

use App\Models\Osde;
use App\Models\User;

/**
 * Policy del módulo osdes.
 */
class OsdePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'osdes';
}
