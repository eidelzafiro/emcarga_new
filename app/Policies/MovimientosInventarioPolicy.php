<?php

namespace App\Policies;

use App\Models\MovimientosInventario;
use App\Models\User;

/**
 * Policy del módulo movimientos-inventario.
 */
class MovimientosInventarioPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'movimientos-inventario';
}
