<?php

namespace App\Policies;

use App\Models\TipoContrato;
use App\Models\User;

/**
 * Policy del módulo tipos-contratos.
 */
class TipoContratoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-contratos';
}
