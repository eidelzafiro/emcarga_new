<?php

namespace App\Policies;

use App\Models\TipoMedioProteccion;
use App\Models\User;

/**
 * Policy del módulo tipos-medios-proteccion.
 */
class TipoMedioProteccionPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-medios-proteccion';
}
