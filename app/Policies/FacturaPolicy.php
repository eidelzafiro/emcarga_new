<?php

namespace App\Policies;

use App\Models\Factura;
use App\Models\User;

/**
 * Policy del módulo facturas.
 */
class FacturaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'facturas';
}
