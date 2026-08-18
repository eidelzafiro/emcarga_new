<?php

namespace App\Policies;

use App\Models\SolicitudesServicio;
use App\Models\User;

/**
 * Policy del módulo solicitudes.
 */
class SolicitudesServicioPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'solicitudes';
}
