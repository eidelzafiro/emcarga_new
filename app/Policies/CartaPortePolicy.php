<?php

namespace App\Policies;

use App\Models\CartaPorte;
use App\Models\User;

/**
 * Policy del módulo carta-porte.
 */
class CartaPortePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'carta-porte';
}
