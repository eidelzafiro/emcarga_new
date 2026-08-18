<?php

namespace App\Policies;

use App\Models\Alerta;
use App\Models\User;

/**
 * Policy del módulo alertas.
 */
class AlertaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'alertas';
}
