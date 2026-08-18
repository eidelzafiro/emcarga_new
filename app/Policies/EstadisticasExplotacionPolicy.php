<?php

namespace App\Policies;

use App\Models\EstadisticasExplotacion;
use App\Models\User;

/**
 * Policy del módulo estadisticas-explotacion.
 */
class EstadisticasExplotacionPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'estadisticas-explotacion';
}
