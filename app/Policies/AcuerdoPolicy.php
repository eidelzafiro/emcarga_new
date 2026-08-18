<?php

namespace App\Policies;

use App\Models\Acuerdo;
use App\Models\User;

/**
 * Policy del módulo acuerdos.
 */
class AcuerdoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'acuerdos';
}
