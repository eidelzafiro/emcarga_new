<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

/**
 * Policy del módulo cajas.
 */
class CajaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'cajas';
}
