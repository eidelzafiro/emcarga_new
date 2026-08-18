<?php

namespace App\Policies;

use App\Models\Contenedore;
use App\Models\User;

/**
 * Policy del módulo contenedores.
 */
class ContenedorePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'contenedores';
}
