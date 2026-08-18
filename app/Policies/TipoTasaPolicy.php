<?php

namespace App\Policies;

use App\Models\TipoTasa;
use App\Models\User;

/**
 * Policy del módulo tipos-tasas.
 */
class TipoTasaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-tasas';
}
