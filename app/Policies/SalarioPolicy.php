<?php

namespace App\Policies;

use App\Models\Salario;
use App\Models\User;

/**
 * Policy del módulo salarios.
 */
class SalarioPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'salarios';
}
