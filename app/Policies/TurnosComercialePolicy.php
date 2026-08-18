<?php

namespace App\Policies;

use App\Models\TurnosComerciale;
use App\Models\User;

/**
 * Policy del módulo turnos-comerciales.
 */
class TurnosComercialePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'turnos-comerciales';
}
