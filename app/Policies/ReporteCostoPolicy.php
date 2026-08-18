<?php

namespace App\Policies;

use App\Models\ReporteCosto;
use App\Models\User;

/**
 * Policy del módulo reportes-costos.
 */
class ReporteCostoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'reportes-costos';
}
