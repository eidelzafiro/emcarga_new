<?php

namespace App\Policies;

use App\Models\HojasRuta;
use App\Models\User;

/**
 * Policy del módulo hojas-ruta.
 */
class HojasRutaPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'hojas-ruta';
}
