<?php

namespace App\Policies;

use App\Models\PizarraTractivo;
use App\Models\User;

/**
 * Policy del módulo pizarra-tractivos.
 */
class PizarraTractivoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'pizarra-tractivos';
}
