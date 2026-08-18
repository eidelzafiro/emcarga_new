<?php

namespace App\Policies;

use App\Models\FirmaAutorizada;
use App\Models\User;

/**
 * Policy del módulo firmas-autorizadas.
 */
class FirmaAutorizadaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'firmas-autorizadas';
}
