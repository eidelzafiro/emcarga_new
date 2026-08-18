<?php

namespace App\Policies;

use App\Models\Firma;
use App\Models\User;

/**
 * Policy del módulo firmas.
 */
class FirmaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'firmas';
}
