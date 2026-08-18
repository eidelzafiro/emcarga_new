<?php

namespace App\Policies;

use App\Models\Embalaje;
use App\Models\User;

/**
 * Policy del módulo embalajes.
 */
class EmbalajePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'embalajes';
}
