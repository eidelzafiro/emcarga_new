<?php

namespace App\Policies;

use App\Models\Distancia;
use App\Models\User;

/**
 * Policy del módulo distancias.
 */
class DistanciaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'distancias';
}
