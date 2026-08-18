<?php

namespace App\Policies;

use App\Models\OrdenesTaller;
use App\Models\User;

/**
 * Policy del módulo taller.
 */
class OrdenesTallerPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'taller';
}
