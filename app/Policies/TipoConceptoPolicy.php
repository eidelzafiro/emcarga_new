<?php

namespace App\Policies;

use App\Models\TipoConcepto;
use App\Models\User;

/**
 * Policy del módulo tipos-conceptos.
 */
class TipoConceptoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-conceptos';
}
