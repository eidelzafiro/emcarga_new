<?php

namespace App\Policies;

use App\Models\Empleado;
use App\Models\User;

/**
 * Policy del módulo empleados.
 */
class EmpleadoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'empleados';
}
