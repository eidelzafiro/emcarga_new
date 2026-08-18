<?php

namespace App\Policies;

use App\Models\Vacacione;
use App\Models\User;

/**
 * Policy del módulo vacaciones.
 */
class VacacionePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'vacaciones';
}
