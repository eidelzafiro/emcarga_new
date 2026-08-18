<?php

namespace App\Policies;

use App\Models\Modelo;
use App\Models\User;

/**
 * Policy del módulo modelos.
 */
class ModeloPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'modelos';
}
