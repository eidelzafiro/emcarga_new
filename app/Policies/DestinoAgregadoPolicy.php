<?php

namespace App\Policies;

use App\Models\DestinoAgregado;
use App\Models\User;

/**
 * Policy del módulo destinos-agregados.
 */
class DestinoAgregadoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'destinos-agregados';
}
