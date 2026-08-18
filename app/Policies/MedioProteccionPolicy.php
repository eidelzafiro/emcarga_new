<?php

namespace App\Policies;

use App\Models\MedioProteccion;
use App\Models\User;

/**
 * Policy del módulo medios-proteccion.
 */
class MedioProteccionPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'medios-proteccion';
}
