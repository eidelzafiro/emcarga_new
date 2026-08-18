<?php

namespace App\Policies;

use App\Models\Taller;
use App\Models\User;

/**
 * Policy del módulo talleres (catálogo de talleres).
 */
class TallerPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'talleres';
}
