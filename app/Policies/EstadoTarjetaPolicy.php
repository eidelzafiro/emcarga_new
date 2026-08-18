<?php

namespace App\Policies;

use App\Models\EstadoTarjeta;
use App\Models\User;

/**
 * Policy del módulo estados-tarjetas.
 */
class EstadoTarjetaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'estados-tarjetas';
}
