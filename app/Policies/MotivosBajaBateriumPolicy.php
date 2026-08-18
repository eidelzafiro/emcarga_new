<?php

namespace App\Policies;

use App\Models\MotivosBajaBaterium;
use App\Models\User;

/**
 * Policy del módulo motivos-baja-bateria.
 */
class MotivosBajaBateriumPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'motivos-baja-bateria';
}
