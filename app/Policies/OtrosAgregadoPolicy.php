<?php

namespace App\Policies;

use App\Models\OtrosAgregado;
use App\Models\User;

/**
 * Policy del módulo otros-agregados.
 */
class OtrosAgregadoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'otros-agregados';
}
