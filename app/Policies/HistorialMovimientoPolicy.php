<?php

namespace App\Policies;

use App\Models\HistorialMovimiento;
use App\Models\User;

/**
 * Policy del módulo historial-movimientos.
 */
class HistorialMovimientoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'historial-movimientos';
}
