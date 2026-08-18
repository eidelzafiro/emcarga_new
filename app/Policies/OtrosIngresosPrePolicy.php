<?php

namespace App\Policies;

use App\Models\OtrosIngresosPre;
use App\Models\User;

/**
 * Policy del módulo otros-ingresos-pre.
 */
class OtrosIngresosPrePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'otros-ingresos-pre';
}
