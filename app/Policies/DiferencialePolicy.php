<?php

namespace App\Policies;

use App\Models\Diferenciale;
use App\Models\User;

/**
 * Policy del módulo diferenciales.
 */
class DiferencialePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'diferenciales';
}
