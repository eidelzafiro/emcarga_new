<?php

namespace App\Policies;

use App\Models\Vale;
use App\Models\User;

/**
 * Policy del módulo vales.
 */
class ValePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'vales';
}
