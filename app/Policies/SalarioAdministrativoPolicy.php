<?php

namespace App\Policies;

use App\Models\SalarioAdministrativo;
use App\Models\User;

/**
 * Policy del módulo salarios-administrativos.
 */
class SalarioAdministrativoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'salarios-administrativos';
}
