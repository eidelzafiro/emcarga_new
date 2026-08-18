<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\User;

/**
 * Policy del módulo pagos.
 */
class PagoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'pagos';
}
