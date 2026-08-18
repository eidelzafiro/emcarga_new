<?php

namespace App\Policies;

use App\Models\TipoMedioCargo;
use App\Models\User;

/**
 * Policy del módulo tipos-medios-cargo.
 */
class TipoMedioCargoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-medios-cargo';
}
