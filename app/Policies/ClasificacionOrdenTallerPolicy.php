<?php

namespace App\Policies;

use App\Models\ClasificacionOrdenTaller;
use App\Models\User;

/**
 * Policy del módulo clasificaciones-ordenes-taller.
 */
class ClasificacionOrdenTallerPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'clasificaciones-ordenes-taller';
}
