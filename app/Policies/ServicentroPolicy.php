<?php

namespace App\Policies;

use App\Models\Servicentro;
use App\Models\User;

/**
 * Policy del módulo servicentros.
 */
class ServicentroPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'servicentros';
}
