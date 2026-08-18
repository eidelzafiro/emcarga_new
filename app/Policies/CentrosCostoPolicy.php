<?php

namespace App\Policies;

use App\Models\CentrosCosto;
use App\Models\User;

/**
 * Policy del módulo centros-costos.
 */
class CentrosCostoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'centros-costos';
}
