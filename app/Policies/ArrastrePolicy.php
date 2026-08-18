<?php

namespace App\Policies;

use App\Models\User;

/**
 * Policy del módulo arrastres.
 *
 * Los arrastres se modelan como tractivos (grupo 8), por lo que esta policy
 * se aplica explícitamente en ArrastresController con permisos `arrastres.*`.
 */
class ArrastrePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'arrastres';
}