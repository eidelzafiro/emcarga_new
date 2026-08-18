<?php

namespace App\Policies;

use App\Models\CombustibleDescarga;
use App\Models\User;

/**
 * Policy del módulo combustible-descargas.
 */
class CombustibleDescargaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'combustible-descargas';
}
