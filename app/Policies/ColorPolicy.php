<?php

namespace App\Policies;

use App\Models\Color;
use App\Models\User;

/**
 * Policy del módulo colores.
 */
class ColorPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'colores';
}
