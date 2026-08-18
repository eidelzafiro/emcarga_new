<?php

namespace App\Policies;

use App\Models\DescuentosEmpleado;
use App\Models\User;

/**
 * Policy del módulo descuentos-empleados.
 */
class DescuentosEmpleadoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'descuentos-empleados';
}
