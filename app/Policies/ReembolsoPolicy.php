<?php

namespace App\Policies;

use App\Models\Reembolso;

class ReembolsoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'reembolsos';
}
