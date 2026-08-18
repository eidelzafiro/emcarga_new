<?php

namespace App\Policies;

use App\Models\MotivosEntradaTaller;
use App\Models\User;

/**
 * Policy del módulo motivos-entrada-taller.
 */
class MotivosEntradaTallerPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'motivos-entrada-taller';
}
