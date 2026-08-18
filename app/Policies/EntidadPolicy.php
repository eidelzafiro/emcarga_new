<?php

namespace App\Policies;

use App\Models\Entidad;
use App\Models\User;

/**
 * Policy del módulo entidades.
 */
class EntidadPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'entidades';
}
