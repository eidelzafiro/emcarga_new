<?php

namespace App\Policies;

use App\Models\Valla;
use App\Models\User;

/**
 * Policy del módulo vallas.
 */
class VallaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'vallas';
}
