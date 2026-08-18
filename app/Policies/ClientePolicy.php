<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

/**
 * Policy del módulo clientes.
 */
class ClientePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'clientes';
}
