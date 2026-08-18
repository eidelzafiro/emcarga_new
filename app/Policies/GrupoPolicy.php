<?php

namespace App\Policies;

use App\Models\Grupo;
use App\Models\User;

/**
 * Policy del módulo grupos.
 */
class GrupoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'grupos';
}
