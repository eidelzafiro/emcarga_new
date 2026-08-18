<?php

namespace App\Policies;

use App\Models\CombustibleCarga;
use App\Models\User;

/**
 * Policy del módulo combustible-cargas.
 */
class CombustibleCargaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'combustible-cargas';
}
