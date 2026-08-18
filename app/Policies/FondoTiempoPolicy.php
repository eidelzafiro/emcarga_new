<?php

namespace App\Policies;

use App\Models\FondoTiempo;
use App\Models\User;

/**
 * Policy del módulo fondos-tiempo.
 */
class FondoTiempoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'fondos-tiempo';
}
