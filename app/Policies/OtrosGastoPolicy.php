<?php

namespace App\Policies;

use App\Models\OtrosGasto;
use App\Models\User;

/**
 * Policy del módulo otros-gastos.
 */
class OtrosGastoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'otros-gastos';
}
