<?php

namespace App\Policies;

use App\Models\ConfiguracioneModelo;
use App\Models\User;

/**
 * Policy del módulo configuraciones-modelo.
 */
class ConfiguracioneModeloPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'configuraciones-modelo';
}
