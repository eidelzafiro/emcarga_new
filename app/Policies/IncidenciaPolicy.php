<?php

namespace App\Policies;

use App\Models\Incidencia;
use App\Models\User;

/**
 * Policy del módulo incidencias.
 */
class IncidenciaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'incidencias';
}
