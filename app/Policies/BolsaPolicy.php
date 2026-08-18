<?php

namespace App\Policies;

use App\Models\Bolsa;
use App\Models\User;

/**
 * Policy del módulo bolsa.
 */
class BolsaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'bolsa';
}
