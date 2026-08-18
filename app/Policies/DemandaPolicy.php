<?php

namespace App\Policies;

use App\Models\Demanda;
use App\Models\User;

/**
 * Policy del módulo demandas.
 */
class DemandaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'demandas';
}
