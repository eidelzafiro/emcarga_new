<?php

namespace App\Policies;

use App\Models\TiposMantenimiento;

/**
 * Policy del módulo de tipos de mantenimiento.
 */
class TiposMantenimientoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-mantenimiento';
}
