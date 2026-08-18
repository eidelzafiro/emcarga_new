<?php

namespace App\Policies;

use App\Models\HistorialTractivo;
use App\Models\User;

/**
 * Policy del módulo historial-tractivos.
 */
class HistorialTractivoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'historial-tractivos';
}
