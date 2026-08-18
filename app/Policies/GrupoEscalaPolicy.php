<?php

namespace App\Policies;

use App\Models\GrupoEscala;
use App\Models\User;

/**
 * Policy del módulo grupos-escala.
 */
class GrupoEscalaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'grupos-escala';
}
