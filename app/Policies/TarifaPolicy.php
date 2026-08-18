<?php

namespace App\Policies;

use App\Models\Tarifa;
use App\Models\User;

/**
 * Policy del módulo tarifas.
 */
class TarifaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tarifas';
}
