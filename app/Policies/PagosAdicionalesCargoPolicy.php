<?php

namespace App\Policies;

use App\Models\PagosAdicionalesCargo;
use App\Models\User;

/**
 * Policy del módulo pagos-adicionales-cargo.
 */
class PagosAdicionalesCargoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'pagos-adicionales-cargo';
}
