<?php

namespace App\Policies;

use App\Models\Dieta;
use App\Models\User;

/**
 * Policy del módulo dietas.
 */
class DietaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'dietas';
}
