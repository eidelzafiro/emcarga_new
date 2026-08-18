<?php

namespace App\Policies;

use App\Models\TipoCargasReporte;
use App\Models\User;

/**
 * Policy del módulo tipos-cargas-reporte.
 */
class TipoCargasReportePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-cargas-reporte';
}
