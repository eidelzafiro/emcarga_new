<?php

namespace App\Policies;

use App\Models\Devolucione;
use App\Models\User;

/**
 * Policy del módulo devoluciones.
 */
class DevolucionePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'devoluciones';
}
