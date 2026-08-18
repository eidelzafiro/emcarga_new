<?php

namespace App\Policies;

use App\Models\Prefactura;
use App\Models\User;

/**
 * Policy del módulo prefacturas.
 */
class PrefacturaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'prefacturas';
}
