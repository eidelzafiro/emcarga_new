<?php

namespace App\Policies;

use App\Models\Tarjeta;
use App\Models\User;

/**
 * Policy del módulo tarjetas.
 */
class TarjetaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tarjetas';
}
