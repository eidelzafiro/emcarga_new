<?php

namespace App\Policies;

use App\Models\Penalizacion;
use App\Models\User;

/**
 * Policy del módulo penalizaciones.
 */
class PenalizacionPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'penalizaciones';
}
