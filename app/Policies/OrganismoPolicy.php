<?php

namespace App\Policies;

use App\Models\Organismo;
use App\Models\User;

/**
 * Policy del módulo organismos.
 */
class OrganismoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'organismos';
}
