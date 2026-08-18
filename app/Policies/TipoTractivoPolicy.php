<?php

namespace App\Policies;

use App\Models\TipoTractivo;
use App\Models\User;

/**
 * Policy del módulo tipos-tractivos.
 */
class TipoTractivoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-tractivos';
}
