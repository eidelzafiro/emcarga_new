<?php

namespace App\Policies;

use App\Models\ElementosGasto;
use App\Models\User;

/**
 * Policy del módulo elementos-gasto.
 */
class ElementosGastoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'elementos-gasto';
}
