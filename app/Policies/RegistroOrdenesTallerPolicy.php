<?php

namespace App\Policies;

use App\Models\RegistroOrdenesTaller;
use App\Models\User;

/**
 * Policy del módulo registro-ordenes-taller.
 */
class RegistroOrdenesTallerPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'registro-ordenes-taller';
}
