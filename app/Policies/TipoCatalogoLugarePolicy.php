<?php

namespace App\Policies;

use App\Models\TipoCatalogoLugare;
use App\Models\User;

/**
 * Policy del módulo tipos-catalogo-lugares.
 */
class TipoCatalogoLugarePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-catalogo-lugares';
}
