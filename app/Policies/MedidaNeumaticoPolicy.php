<?php

namespace App\Policies;

use App\Models\MedidaNeumatico;
use App\Models\User;

/**
 * Policy del módulo medidas-neumaticos.
 */
class MedidaNeumaticoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'medidas-neumaticos';
}
